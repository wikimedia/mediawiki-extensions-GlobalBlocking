<?php

namespace MediaWiki\Extension\GlobalBlocking\Services;

use InvalidArgumentException;
use Language;
use Liuggio\StatsdClient\Factory\StatsdDataFactoryInterface;
use MediaWiki\Config\ServiceOptions;
use MediaWiki\Extension\GlobalBlocking\GlobalBlock;
use MediaWiki\Extension\GlobalBlocking\Hook\GlobalBlockingHookRunner;
use MediaWiki\HookContainer\HookContainer;
use MediaWiki\User\CentralId\CentralIdLookup;
use MediaWiki\User\User;
use MediaWiki\User\UserIdentity;
use MediaWiki\WikiMap\WikiMap;
use Message;
use RequestContext;
use stdClass;
use Wikimedia\IPUtils;
use Wikimedia\Rdbms\IExpression;
use Wikimedia\Rdbms\IResultWrapper;
use Wikimedia\Rdbms\LikeValue;
use Wikimedia\Rdbms\OrExpressionGroup;

/**
 * Allows looking up global blocks in the globalblocks table.
 *
 * @since 1.42
 */
class GlobalBlockLookup {

	public const CONSTRUCTOR_OPTIONS = [
		'GlobalBlockingAllowedRanges',
		'GlobalBlockingBlockXFF',
		'GlobalBlockingCIDRLimit',
		'GlobalBlockingAllowGlobalAccountBlocks',
	];

	private const TYPE_USER = 1;
	private const TYPE_IP = 2;
	private const TYPE_RANGE = 3;

	/** @var int Flag to ignore blocks on IP addresses which are marked as anon-only. */
	public const SKIP_SOFT_IP_BLOCKS = 1;
	/** @var int Flag to ignore all blocks on IP addresses. */
	public const SKIP_IP_BLOCKS = 2;
	/** @var int Flag to skip checking if the blocks that affect a target are locally disabled. */
	public const SKIP_LOCAL_DISABLE_CHECK = 4;
	/** @var int Flag to skip the excluding of IP blocks in the GlobalBlockingAllowedRanges config. */
	public const SKIP_ALLOWED_RANGES_CHECK = 8;

	private ServiceOptions $options;
	private GlobalBlockingConnectionProvider $globalBlockingConnectionProvider;
	private StatsdDataFactoryInterface $statsdFactory;
	private GlobalBlockingHookRunner $hookRunner;
	private CentralIdLookup $centralIdLookup;
	private Language $contentLanguage;
	private GlobalBlockReasonFormatter $globalBlockReasonFormatter;
	private GlobalBlockLocalStatusLookup $globalBlockLocalStatusLookup;
	private GlobalBlockingLinkBuilder $globalBlockingLinkBuilder;

	private array $getUserBlockDetailsCache = [];

	public function __construct(
		ServiceOptions $options,
		GlobalBlockingConnectionProvider $globalBlockingConnectionProvider,
		StatsdDataFactoryInterface $statsdFactory,
		HookContainer $hookContainer,
		CentralIdLookup $centralIdLookup,
		Language $contentLanguage,
		GlobalBlockReasonFormatter $globalBlockReasonFormatter,
		GlobalBlockLocalStatusLookup $globalBlockLocalStatusLookup,
		GlobalBlockingLinkBuilder $globalBlockingLinkBuilder
	) {
		$options->assertRequiredOptions( self::CONSTRUCTOR_OPTIONS );
		$this->options = $options;
		$this->globalBlockingConnectionProvider = $globalBlockingConnectionProvider;
		$this->statsdFactory = $statsdFactory;
		$this->hookRunner = new GlobalBlockingHookRunner( $hookContainer );
		$this->centralIdLookup = $centralIdLookup;
		$this->contentLanguage = $contentLanguage;
		$this->globalBlockReasonFormatter = $globalBlockReasonFormatter;
		$this->globalBlockLocalStatusLookup = $globalBlockLocalStatusLookup;
		$this->globalBlockingLinkBuilder = $globalBlockingLinkBuilder;
	}

	/**
	 * Given a target and the IP address being used to make the request, get an existing
	 * GlobalBlock object that applies to the target or IP address being used. If no
	 * block exists, then this method returns null.
	 *
	 * @param User $user Filter for GlobalBlock objects that target this user or IP address
	 * @param string|null $ip The IP address being used by the user, used to apply global blocks
	 *     on IPs or IP ranges that are not anon-only. Specifying null when $user is an IP address
	 *     and is not the session user will cause the value to be autogenerated.
	 * @return GlobalBlock|null The GlobalBlock that applies to the given user or IP, or null if no block applies.
	 */
	public function getUserBlock( User $user, ?string $ip ) {
		$details = $this->getUserBlockDetails( $user, $ip );

		if ( $details['block'] ) {
			$row = $details['block'];
			return new GlobalBlock(
				$row,
				[
					'address' => $row->gb_address,
					'reason' => $row->gb_reason,
					'timestamp' => $row->gb_timestamp,
					'anonOnly' => $row->gb_anon_only,
					'expiry' => $row->gb_expiry,
					'xff' => $details['xff'] ?? false,
				]
			);
		}

		return null;
	}

	/**
	 * Given a target and the IP address being used to make the request, get the human
	 * readable error message(s) describing the GlobalBlock that applies to the user. If
	 * no GlobalBlock exists, then this returns an empty array.
	 *
	 * @param User $user Filter for GlobalBlock objects that target this user or IP address
	 * @param string|null $ip The IP address being used by the user, used to apply global blocks
	 *    on IPs or IP ranges that are not anon-only. Specifying null when $user is an IP address
	 *    and is not the session user will cause the value to be autogenerated.
	 * @return Message[] empty or message objects
	 * @deprecated Since 1.42. Use ::getUserBlock instead to get the block and then use BlockErrorFormatter to get
	 *    a human readable error message.
	 */
	public function getUserBlockErrors( User $user, ?string $ip ): array {
		$details = $this->getUserBlockDetails( $user, $ip );
		return $details['error'];
	}

	/**
	 * Add the $result to the instance cache under the username of the given $user.
	 *
	 * @param array $result
	 * @param UserIdentity $user
	 * @return array The value of $result
	 */
	private function addToUserBlockDetailsCache( array $result, UserIdentity $user ) {
		$this->getUserBlockDetailsCache[$user->getName()] = $result;
		return $result;
	}

	/**
	 * Get the cached result of ::getUserBlockDetails for the given user.
	 *
	 * @param UserIdentity $userIdentity
	 * @return array|null Array if the result is cached, null if no cache exists
	 */
	protected function getUserBlockDetailsCacheResult( UserIdentity $userIdentity ): ?array {
		return $this->getUserBlockDetailsCache[$userIdentity->getName()] ?? null;
	}

	/**
	 * Given a target and the IP address being used to make the request, get the
	 * most specific block that applies along with a human readable error message
	 * associated with the block. If no block exists, this returns an array with
	 * no block and an empty array of error messages.
	 *
	 * @param User $user See ::getUserBlock. Note this may not be the session user.
	 * @param string|null $ip See ::getUserBlock.
	 * @return array ['block' => DB row or null, 'error' => empty or message objects]. The
	 *    error message key is deprecated since 1.42.
	 * @phan-return array{block:stdClass|null,error:Message[]}
	 */
	private function getUserBlockDetails( User $user, ?string $ip ) {
		// Check first if the instance cache has the result.
		$cachedResult = $this->getUserBlockDetailsCacheResult( $user );
		if ( $cachedResult !== null ) {
			return $cachedResult;
		}

		$this->statsdFactory->increment( 'global_blocking.get_user_block' );

		// We have callers from different code paths which may leave $ip as null when providing an
		// IP address as the $user where the IP address is not the session user. In this case, populate
		// the $ip argument with the IP provided in $user to get all the blocks that apply to the IP.
		$context = RequestContext::getMain();
		$isSessionUser = $user->equals( $context->getUser() );
		if ( $ip === null && !$isSessionUser && IPUtils::isIPAddress( $user->getName() ) ) {
			// Populate the IP for checking blocks against non-session users.
			$ip = $user->getName();
		}

		$flags = 0;
		if ( $user->isAllowedAny( 'ipblock-exempt', 'globalblock-exempt' ) ) {
			// User is exempt from IP blocks.
			$flags |= self::SKIP_IP_BLOCKS;
		}
		if ( $user->isNamed() ) {
			// User is a named account, so skip anon-only (soft) IP blocks.
			$flags |= self::SKIP_SOFT_IP_BLOCKS;
		}

		$centralId = 0;
		if ( $this->options->get( 'GlobalBlockingAllowGlobalAccountBlocks' ) && $user->isRegistered() ) {
			$centralId = $this->centralIdLookup->centralIdFromLocalUser( $user, CentralIdLookup::AUDIENCE_RAW );
		}

		$this->statsdFactory->increment( 'global_blocking.get_user_block_db_query' );

		$lang = $context->getLanguage();
		$block = $this->getGlobalBlockingBlock( $ip, $centralId, $flags );
		if ( $block ) {
			// The following code in this if block, except the returning of $block, is deprecated since 1.42 (T358776).
			$blockTimestamp = $lang->timeanddate( wfTimestamp( TS_MW, $block->gb_timestamp ), true );
			$blockExpiry = $lang->formatExpiry( $block->gb_expiry );
			$display_wiki = WikiMap::getWikiName( $block->gb_by_wiki );
			$blockingUser = $this->globalBlockingLinkBuilder->maybeLinkUserpage(
				$block->gb_by_wiki,
				$this->centralIdLookup->nameFromCentralId( $block->gb_by_central_id ) ?? ''
			);

			// The following Hooks are deprecated and the message it generates is not used anywhere.
			// The hooks will be removed in the future through T358776.
			// Allow site customization of blocked message.
			if ( IPUtils::isValid( $block->gb_address ) ) {
				$errorMsg = 'globalblocking-ipblocked';
				$this->hookRunner->onGlobalBlockingBlockedIpMsg( $errorMsg );
			} elseif ( IPUtils::isValidRange( $block->gb_address ) ) {
				$errorMsg = 'globalblocking-ipblocked-range';
				$this->hookRunner->onGlobalBlockingBlockedIpRangeMsg( $errorMsg );
			} else {
				$errorMsg = false;
			}
			$blockDetails = [
				'block' => $block,
				'error' => [],
			];
			if ( $errorMsg ) {
				$blockDetails['error'][] = wfMessage(
					$errorMsg,
					$blockingUser,
					$display_wiki,
					$this->globalBlockReasonFormatter->format( $block->gb_reason, $this->contentLanguage->getCode() ),
					$blockTimestamp,
					$blockExpiry,
					$ip,
					$block->gb_address
				);
			}

			return $this->addToUserBlockDetailsCache( $blockDetails, $user );
		}

		$request = $context->getRequest();
		// Checking non-session users are not applicable to the XFF block.
		if ( $this->options->get( 'GlobalBlockingBlockXFF' ) && $isSessionUser ) {
			$xffIps = $request->getHeader( 'X-Forwarded-For' );
			if ( $xffIps ) {
				$xffIps = array_map( 'trim', explode( ',', $xffIps ) );
				// Always skip the allowed ranges check when checking the XFF IPs as the value of this header
				// is easy to spoof.
				$xffFlags = $flags | self::SKIP_ALLOWED_RANGES_CHECK;
				$appliedBlock = $this->getAppliedBlock(
					$xffIps, $this->checkIpsForBlock( $xffIps, $xffFlags )
				);
				if ( $appliedBlock !== null ) {
					// The following code in this if block, except the returning of $block, is deprecated since 1.42.
					[ $blockIP, $block ] = $appliedBlock;
					$blockTimestamp = $lang->timeanddate(
						wfTimestamp( TS_MW, $block->gb_timestamp ),
						true
					);
					$blockExpiry = $lang->formatExpiry( $block->gb_expiry );
					$display_wiki = WikiMap::getWikiName( $block->gb_by_wiki );
					$blockingUser = $this->globalBlockingLinkBuilder->maybeLinkUserpage(
						$block->gb_by_wiki,
						$this->centralIdLookup->nameFromCentralId( $block->gb_by_central_id ) ?? ''
					);
					// Allow site customization of blocked message.
					$blockedIpXffMsg = 'globalblocking-ipblocked-xff';
					$this->hookRunner->onGlobalBlockingBlockedIpXffMsg( $blockedIpXffMsg );
					return $this->addToUserBlockDetailsCache( [
						'block' => $block,
						'error' => [
							wfMessage(
								$blockedIpXffMsg,
								$blockingUser,
								$display_wiki,
								$block->gb_reason,
								$blockTimestamp,
								$blockExpiry,
								$blockIP
							)
						],
						'xff' => true,
					], $user );
				}
			}
		}

		return $this->addToUserBlockDetailsCache( [ 'block' => null, 'error' => [] ], $user );
	}

	/**
	 * Returns the ::TYPE_* constant for the given target.
	 *
	 * @param string $target
	 * @return int
	 */
	private function getTargetType( string $target ) {
		if ( IPUtils::isValid( $target ) ) {
			return self::TYPE_IP;
		} elseif ( IPUtils::isValidRange( $target ) ) {
			return self::TYPE_RANGE;
		} else {
			return self::TYPE_USER;
		}
	}

	/**
	 * Choose the most specific block from some combination of user, IP and IP range
	 * blocks. Decreasing order of specificity: IP > narrower IP range > wider IP
	 * range. A range that encompasses one IP address is ranked equally to a single IP.
	 *
	 * Note that DatabaseBlock::chooseBlocks chooses blocks in a different way.
	 *
	 * This is based on DatabaseBlock::chooseMostSpecificBlock
	 *
	 * @param IResultWrapper $blocks These should not include autoblocks or ID blocks
	 * @param int $flags The $flags provided. This method only checks for BLOCK_FLAG_SKIP_LOCAL_DISABLE_CHECK,
	 *   and callers are in charge of checking for other relevant flags.
	 * @return stdClass|null The block with the most specific target
	 */
	private function chooseMostSpecificBlock( IResultWrapper $blocks, int $flags ): ?stdClass {
		// This result could contain a block on the user, a block on the IP, and a russian-doll
		// set of rangeblocks.  We want to choose the most specific one, so keep a leader board.
		$bestBlock = null;

		// Lower will be better
		$bestBlockScore = 100;
		foreach ( $blocks as $block ) {
			// Check for local whitelisting, unless the flag is set to skip the check.
			if (
				!( $flags & self::SKIP_LOCAL_DISABLE_CHECK ) &&
				$this->globalBlockLocalStatusLookup->getLocalWhitelistInfo( $block->gb_id )
			) {
				continue;
			}
			$target = $block->gb_address;
			$type = $this->getTargetType( $target );
			if ( $type == self::TYPE_RANGE ) {
				// This is the number of bits that are allowed to vary in the block, give
				// or take some floating point errors
				$max = IPUtils::isIPv6( $target ) ? 128 : 32;
				[ $network, $bits ] = IPUtils::parseCIDR( $target );
				$size = $max - $bits;

				// Rank a range block covering a single IP equally with a single-IP block
				$score = self::TYPE_RANGE - 1 + ( $size / $max );
			} else {
				$score = $type;
			}

			if ( $score < $bestBlockScore ) {
				$bestBlockScore = $score;
				$bestBlock = $block;
			}
		}

		return $bestBlock;
	}

	/**
	 * Get the most specific row from the `globalblocks` table that applies to the given IP address
	 * or the central user.
	 *
	 * This does not check if the user is exempt from IP blocks. As such it should not be used to determine
	 * if a block should be applied to a user. Use ::getUserBlock for that.
	 *
	 * @param string|null $ip The IP address used by the user. If null, then no IP blocks will be checked.
	 * @param int $centralId The central ID of the user. 0 if the user is anonymous. Setting this as
	 *   a boolean is soft deprecated and will be treated as 0.
	 * @param int $flags Flags to control the behavior of the block lookup
	 * @return stdClass|null The most specific row from the `globalblocks` table, or null if no row was found
	 */
	public function getGlobalBlockingBlock( ?string $ip, int $centralId, int $flags = 0 ): ?stdClass {
		$conds = $this->getGlobalBlockLookupConditions( $ip, $centralId, $flags );
		if ( $conds === null ) {
			// No conditions, so don't perform the query and assume the user is not targeted by any block
			return null;
		}

		$blocks = $this->globalBlockingConnectionProvider
			->getReplicaGlobalBlockingDatabase()
			->newSelectQueryBuilder()
			->select( self::selectFields() )
			->from( 'globalblocks' )
			->where( $conds )
			->caller( __METHOD__ )
			->fetchResultSet();

		// Get the most specific block for the global blocks that apply to the user.
		return $this->chooseMostSpecificBlock( $blocks, $flags );
	}

	/**
	 * Get the SQL WHERE conditions that allow looking up all blocks from the
	 * `globalblocks` table that apply to the given IP address or range.
	 *
	 * @param string $ip The IP address or range
	 * @deprecated Since 1.42. Use ::getGlobalBlockLookupConditions.
	 * @return IExpression
	 */
	public function getRangeCondition( string $ip ): IExpression {
		// This method does not return null if an IP is provided and the allowed ranges check is skipped.
		// @phan-suppress-next-line PhanTypeMismatchReturnNullable
		return $this->getGlobalBlockLookupConditions( $ip, 0, self::SKIP_ALLOWED_RANGES_CHECK );
	}

	/**
	 * Get the SQL WHERE conditions that allow looking up all blocks from the `globalblocks` table.
	 *
	 * @param ?string $ip The IP address or range. If null, then no IP blocks will be checked.
	 * @param int $centralId The central ID of the user. 0 if the user is anonymous and 0 will skip
	 *   checking user specific blocks.
	 * @param int $flags Flags which control what conditions are returned. Ignores the
	 *   ::BLOCK_FLAG_SKIP_LOCAL_DISABLE_CHECK flag and callers are expected to check if the block is
	 *   locally disabled if this is needed.
	 * @return IExpression|null The conditions to be used in a SQL query to look up global blocks, or null if no valid
	 *   conditions could be generated.
	 */
	public function getGlobalBlockLookupConditions( ?string $ip, int $centralId = 0, int $flags = 0 ): ?IExpression {
		$dbr = $this->globalBlockingConnectionProvider->getReplicaGlobalBlockingDatabase();
		$ipExpr = null;
		$userExpr = null;

		if ( $ip !== null && !IPUtils::isIPAddress( $ip ) ) {
			// The provided IP is invalid, so throw.
			throw new InvalidArgumentException(
				"Invalid IP address or range provided to GlobalBlockLookup::getGlobalBlockLookupConditions."
			);
		}

		if ( $ip !== null && !( $flags & self::SKIP_ALLOWED_RANGES_CHECK ) ) {
			$ranges = $this->options->get( 'GlobalBlockingAllowedRanges' );
			foreach ( $ranges as $range ) {
				if ( IPUtils::isInRange( $ip, $range ) ) {
					// IP is in a range that is exempt from IP blocks, so treat the user as having
					// global IP block exemption for this specific IP address
					$flags |= self::SKIP_IP_BLOCKS;
					break;
				}
			}
		}

		if ( $ip !== null && !( $flags & self::SKIP_IP_BLOCKS ) ) {
			// If we have been provided an IP address or range in $ip, then
			// add conditions to the query to lookup blocks that apply to the IP address / range.
			[ $start, $end ] = IPUtils::parseRange( $ip );
			$chunk = $this->getIpFragment( $start );
			$ipExpr = $dbr->expr( 'gb_range_start', IExpression::LIKE, new LikeValue( $chunk, $dbr->anyString() ) )
				->and( 'gb_range_start', '<=', $start )
				->and( 'gb_range_end', '>=', $end );

			if ( $flags & self::SKIP_SOFT_IP_BLOCKS ) {
				// If the flags say to skip soft IP blocks, then exclude blocks with gb_anon_only
				// set to 1 (which should only be soft blocks on IP addresses or ranges).
				$ipExpr = $ipExpr->and( 'gb_anon_only', '!=', 1 );
			}
		}

		if ( $centralId !== 0 ) {
			// If we have been provided a non-zero central ID, then also look for blocks that target the
			// given central ID.
			$userExpr = $dbr->expr( 'gb_target_central_id', '=', $centralId );
		}

		// Combine the IP conditions and user IExpressions
		if ( $userExpr !== null && $ipExpr !== null ) {
			// If we have conditions for both the IP and the user, then combine them with an OR
			// to allow selecting blocks that apply to either the IP or the user.
			$targetExpr = $userExpr->orExpr( $ipExpr );
		} elseif ( $userExpr !== null ) {
			// If we only have conditions for the user, then use that IExpression.
			$targetExpr = $userExpr;
		} elseif ( $ipExpr !== null ) {
			// If we only have conditions for the IP, then use that IExpression.
			$targetExpr = $ipExpr;
		} else {
			// No conditions, so don't perform the query otherwise we will select all blocks from the DB.
			// In this case, we can assume the user or their IP is not affected by any global block.
			return null;
		}
		// @todo expiry shouldn't be in this function
		return $dbr->expr( 'gb_expiry', '>', $dbr->timestamp() )
			->andExpr( $targetExpr );
	}

	/**
	 * Get the component of an IP address which is certain to be the same between an IP
	 * address and a range block containing that IP address.
	 *
	 * This mostly duplicates the logic in DatabaseStoreBlock::getIpFragment, but with the
	 * CIDR limit config being the GlobalBlocking extension specific one.
	 *
	 * @param string $hex Hexadecimal IP representation
	 * @return string
	 */
	private function getIpFragment( string $hex ): string {
		$blockCIDRLimit = $this->options->get( 'GlobalBlockingCIDRLimit' );
		if ( str_starts_with( $hex, 'v6-' ) ) {
			return 'v6-' . substr( substr( $hex, 3 ), 0, (int)floor( $blockCIDRLimit['IPv6'] / 4 ) );
		} else {
			return substr( $hex, 0, (int)floor( $blockCIDRLimit['IPv4'] / 4 ) );
		}
	}

	/**
	 * Find all rows from the `globalblocks` table that target at least one of
	 * the given IP addresses.
	 *
	 * This method filters out blocks that are locally disabled, but does not
	 * check whether the given session user can be exempt from the block.
	 *
	 * @param string[] $ips The array of IP addresses to be checked
	 * @param int $flags Flags which control what blocks are returned.
	 * @return stdClass[] Array of applicable blocks as rows from the `globalblocks` table
	 */
	private function checkIpsForBlock( array $ips, int $flags = 0 ): array {
		if ( $flags & self::SKIP_IP_BLOCKS ) {
			// If the flags say to skip IP blocks, then don't even make the query.
			return [];
		}

		$dbr = $this->globalBlockingConnectionProvider->getReplicaGlobalBlockingDatabase();
		$conds = [];
		foreach ( $ips as $ip ) {
			if ( IPUtils::isValid( $ip ) ) {
				$ipConds = $this->getGlobalBlockLookupConditions( $ip, 0, $flags );
				if ( $ipConds !== null ) {
					$conds[] = $ipConds;
				}
			}
		}

		if ( !$conds ) {
			// No valid IPs provided so don't even make the query. Bug 59705
			return [];
		}
		$results = $dbr->newSelectQueryBuilder()
			->select( self::selectFields() )
			->from( 'globalblocks' )
			->where( new OrExpressionGroup( ...$conds ) )
			->caller( __METHOD__ )
			->fetchResultSet();

		$blocks = [];
		foreach ( $results as $block ) {
			if (
				( $flags & self::SKIP_LOCAL_DISABLE_CHECK ) ||
				!$this->globalBlockLocalStatusLookup->getLocalWhitelistInfo( $block->gb_id )
			) {
				$blocks[] = $block;
			}
		}

		return $blocks;
	}

	/**
	 * Using the result of ::checkIpsForBlock and the IPs provided to that method,
	 * choose the block that will be shown to the end user.
	 *
	 * For the time being, this will be the first block that applies.
	 *
	 * @param string[] $ips The array of IP addresses to be checked
	 * @param \stdClass[] $blocks The array returned by ::checkIpsForBlock
	 * @return array|null An array where the first element is the IP address
	 *   that the block applies to, and the second element is the block itself.
	 *   If no block applies, then this method returns null.
	 * @phan-return array{string,stdClass}|null
	 */
	private function getAppliedBlock( array $ips, array $blocks ): ?array {
		foreach ( $blocks as $block ) {
			foreach ( $ips as $ip ) {
				$ipHex = IPUtils::toHex( $ip );
				if ( $block->gb_range_start <= $ipHex && $block->gb_range_end >= $ipHex ) {
					return [ $ip, $block ];
				}
			}
		}

		return null;
	}

	/**
	 * Given a specific target, find the ID for the global block that applies to it.
	 * If no global block targets this IP address specifically, then this method
	 * returns 0.
	 *
	 * @param string $target The specific target which can be a username, IP address or range. The target being
	 *   specific means that if you provide a single IP which is covered by a range block, the range block will
	 *   not be returned. Use ::getGlobalBlockingBlock to include these blocks.
	 * @param int $dbtype Either DB_REPLICA or DB_PRIMARY.
	 * @return int
	 */
	public function getGlobalBlockId( string $target, int $dbtype = DB_REPLICA ): int {
		if ( $dbtype === DB_PRIMARY ) {
			$db = $this->globalBlockingConnectionProvider->getPrimaryGlobalBlockingDatabase();
		} else {
			$db = $this->globalBlockingConnectionProvider->getReplicaGlobalBlockingDatabase();
		}

		$queryBuilder = $db->newSelectQueryBuilder()
			->select( 'gb_id' )
			->from( 'globalblocks' );

		if ( IPUtils::isIPAddress( $target ) ) {
			$queryBuilder->where( [ 'gb_address' => $target ] );
		} elseif ( $this->options->get( 'GlobalBlockingAllowGlobalAccountBlocks' ) ) {
			$centralId = $this->centralIdLookup->centralIdFromName( $target, CentralIdLookup::AUDIENCE_RAW );
			if ( !$centralId ) {
				// If we are looking up a block by a central ID of a user, then the user must have a central ID
				// for a block to apply to them.
				return 0;
			}
			$queryBuilder->where( [ 'gb_target_central_id' => $centralId ] );
		} else {
			return 0;
		}

		return (int)$queryBuilder
			->caller( __METHOD__ )
			->fetchField();
	}

	/**
	 * @return string[] The fields needed to construct a GlobalBlock object
	 */
	public static function selectFields(): array {
		return [
			'gb_id', 'gb_address', 'gb_target_central_id', 'gb_by', 'gb_by_central_id', 'gb_by_wiki', 'gb_reason',
			'gb_timestamp', 'gb_anon_only', 'gb_expiry', 'gb_range_start', 'gb_range_end'
		];
	}
}