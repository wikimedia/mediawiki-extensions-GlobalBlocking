<?php

namespace MediaWiki\Extension\GlobalBlocking\Services;

use InvalidArgumentException;
use Liuggio\StatsdClient\Factory\StatsdDataFactoryInterface;
use MediaWiki\Config\ServiceOptions;
use MediaWiki\Context\RequestContext;
use MediaWiki\Extension\GlobalBlocking\GlobalBlock;
use MediaWiki\User\CentralId\CentralIdLookup;
use MediaWiki\User\TempUser\TempUserConfig;
use MediaWiki\User\User;
use MediaWiki\User\UserFactory;
use MediaWiki\User\UserIdentity;
use stdClass;
use Wikimedia\IPUtils;
use Wikimedia\Rdbms\IExpression;
use Wikimedia\Rdbms\IResultWrapper;
use Wikimedia\Rdbms\LikeValue;

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
	private CentralIdLookup $centralIdLookup;
	private GlobalBlockLocalStatusLookup $globalBlockLocalStatusLookup;
	private TempUserConfig $tempUserConfig;
	private UserFactory $userFactory;

	private array $getUserBlockDetailsCache = [];

	public function __construct(
		ServiceOptions $options,
		GlobalBlockingConnectionProvider $globalBlockingConnectionProvider,
		StatsdDataFactoryInterface $statsdFactory,
		CentralIdLookup $centralIdLookup,
		GlobalBlockLocalStatusLookup $globalBlockLocalStatusLookup,
		TempUserConfig $tempUserConfig,
		UserFactory $userFactory
	) {
		$options->assertRequiredOptions( self::CONSTRUCTOR_OPTIONS );
		$this->options = $options;
		$this->globalBlockingConnectionProvider = $globalBlockingConnectionProvider;
		$this->statsdFactory = $statsdFactory;
		$this->centralIdLookup = $centralIdLookup;
		$this->globalBlockLocalStatusLookup = $globalBlockLocalStatusLookup;
		$this->tempUserConfig = $tempUserConfig;
		$this->userFactory = $userFactory;
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
	public function getUserBlock( User $user, ?string $ip ): ?GlobalBlock {
		$details = $this->getUserBlockDetails( $user, $ip );

		if ( $details['block'] ) {
			$row = $details['block'];
			return new GlobalBlock(
				[
					'id' => $row->gb_id,
					'isAutoblock' => boolval( $row->gb_autoblock_parent_id ),
					'enableAutoblock' => $row->gb_enable_autoblock,
					'byCentralId' => $row->gb_by_central_id,
					'byWiki' => $row->gb_by_wiki,
					'address' => $row->gb_address,
					'reason' => $row->gb_reason,
					'timestamp' => $row->gb_timestamp,
					'anonOnly' => $row->gb_anon_only,
					'expiry' => $row->gb_expiry,
					'createAccount' => $row->gb_create_account,
					'xff' => $details['xff'],
				]
			);
		}

		return null;
	}

	/**
	 * Add the $result to the instance cache under the username of the given $user.
	 *
	 * @param array $result
	 * @param UserIdentity $user
	 * @return array The value of $result
	 */
	private function addToUserBlockDetailsCache( array $result, UserIdentity $user ): array {
		$this->getUserBlockDetailsCache[$user->getName()] = $result;
		return $result;
	}

	/**
	 * Get the cached result of ::getUserBlockDetails for the given user.
	 *
	 * @param UserIdentity $userIdentity
	 * @return array|null Array if the result is cached, null if there is no cached result
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
	 * @return array An array with the key 'block' for the DB row of the block that applies. May include a
	 *   xff key if the block was applied due to the X-Forwarded-For header value.
	 * @phan-return array{block:stdClass|null,xff:bool}
	 */
	private function getUserBlockDetails( User $user, ?string $ip ): array {
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
		if ( $user->isRegistered() ) {
			$centralId = $this->centralIdLookup->centralIdFromLocalUser( $user, CentralIdLookup::AUDIENCE_RAW );
		}

		$this->statsdFactory->increment( 'global_blocking.get_user_block_db_query' );

		$block = $this->getGlobalBlockingBlock( $ip, $centralId, $flags );
		if ( $block ) {
			return $this->addToUserBlockDetailsCache( [ 'block' => $block, 'xff' => false ], $user );
		}

		// We should only check XFF blocks if we are checking blocks for the session user. The exception to this is
		// that we should also check XFF blocks when the name is the temporary account placeholder, as this is used
		// when a logged out user is making edit on a wiki with temporary accounts enabled (T353564).
		if (
			$this->options->get( 'GlobalBlockingBlockXFF' ) &&
			(
				$isSessionUser ||
				( $this->tempUserConfig->isEnabled() && $this->userFactory->newTempPlaceholder()->equals( $user ) )
			)
		) {
			$xffIps = $context->getRequest()->getHeader( 'X-Forwarded-For' );
			if ( $xffIps ) {
				$xffIps = array_map( 'trim', explode( ',', $xffIps ) );
				// Always skip the allowed ranges check when checking the XFF IPs as the value of this header
				// is easy to spoof.
				$xffFlags = $flags | self::SKIP_ALLOWED_RANGES_CHECK;
				$block = $this->getAppliedBlock( $xffIps, $this->checkIpsForBlock( $xffIps, $xffFlags ) );
				if ( $block !== null ) {
					return $this->addToUserBlockDetailsCache( [ 'block' => $block, 'xff' => true ], $user );
				}
			}
		}

		return $this->addToUserBlockDetailsCache( [ 'block' => null, 'xff' => false ], $user );
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

			// Always prioritise blocks that deny account creation, and then order the blocks using a score generated
			// based on the target type and for ranges how wide the range is.
			if (
				$bestBlock === null ||
				$score < $bestBlockScore ||
				( !$bestBlock->gb_create_account && $block->gb_create_account )
			) {
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
		wfDeprecated( __METHOD__, '1.42' );
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

		if ( $ip !== null ) {
			$sanitisedIp = IPUtils::sanitizeIP( $ip );
			if ( !IPUtils::isIPAddress( $ip ) || !$sanitisedIp ) {
				// The provided IP is invalid, so throw.
				throw new InvalidArgumentException(
					"Invalid IP address or range provided to GlobalBlockLookup::getGlobalBlockLookupConditions."
				);
			}
			// Use the sanitised version of the IP address, incase an IPv4 address is provided that has leading 0s.
			// If leading 0s are present, then IPUtils::parseRange will fail to parse the range properly.
			$ip = $sanitisedIp;
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
			->where( $dbr->orExpr( $conds ) )
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
	 * This is determined by choosing the first block that applies, giving priority to blocks
	 * that disable account creation.
	 *
	 * @param string[] $ips The array of IP addresses to be checked
	 * @param stdClass[] $blocks The array returned by ::checkIpsForBlock
	 * @return stdClass|null The block that is chosen for the end user.
	 *   If no block applies, then this method returns null.
	 */
	private function getAppliedBlock( array $ips, array $blocks ): ?stdClass {
		$currentBlock = null;
		foreach ( $blocks as $block ) {
			foreach ( $ips as $ip ) {
				$ipHex = IPUtils::toHex( $ip );
				if ( !( $block->gb_range_start <= $ipHex && $block->gb_range_end >= $ipHex ) ) {
					continue;
				}

				if (
					$currentBlock === null ||
					( !$currentBlock->gb_create_account && $block->gb_create_account )
				) {
					$currentBlock = $block;
				}
			}
		}

		return $currentBlock;
	}

	/**
	 * Given a specific target, find the ID for the global block that applies to it.
	 * If no global block exists for this target, then this method returns 0.
	 *
	 * @param string $target The specific target which can be a username, IP address, range, or global block ID that
	 *   may or may not exist. The target being specific means that if you provide a single IP which is covered by a
	 *   range block, the range block will not be returned. Use ::getGlobalBlockingBlock to include these blocks.
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
			->from( 'globalblocks' )
			->where( $db->expr( 'gb_expiry', '>', $db->timestamp() ) );

		$globalBlockId = self::isAGlobalBlockId( $target );
		if ( $globalBlockId ) {
			$queryBuilder->where( [ 'gb_id' => $globalBlockId ] );
		} elseif ( IPUtils::isIPAddress( $target ) ) {
			$queryBuilder->where( [ 'gb_address' => $target ] );
		} else {
			$centralId = $this->centralIdLookup->centralIdFromName( $target, CentralIdLookup::AUDIENCE_RAW );
			if ( !$centralId ) {
				// If we are looking up a block by a central ID of a user, then the user must have a central ID
				// for a block to apply to them.
				return 0;
			}
			$queryBuilder->where( [ 'gb_target_central_id' => $centralId ] );
		}

		return (int)$queryBuilder
			->caller( __METHOD__ )
			->fetchField();
	}

	/**
	 * Determines if a given string is in the format of a global block ID.
	 *
	 * This method does not validate that the global block ID actually exists. Use
	 * {@link GlobalBlockLookup::getGlobalBlockId} for that.
	 *
	 * @param string $target The string to check
	 * @return int|false False if the string is not in the format of a global block ID, or the ID of the global
	 *   block if it is in the format of a global block ID.
	 */
	public static function isAGlobalBlockId( string $target ) {
		$isTargetABlockId = preg_match( '/^#\d+$/', $target );
		if ( $isTargetABlockId ) {
			return intval( substr( $target, 1 ) );
		}
		return false;
	}

	/**
	 * @return string[] The fields needed to construct a GlobalBlock object
	 */
	public static function selectFields(): array {
		return [
			'gb_id', 'gb_address', 'gb_target_central_id', 'gb_by_central_id', 'gb_by_wiki', 'gb_reason',
			'gb_timestamp', 'gb_anon_only', 'gb_expiry', 'gb_range_start', 'gb_range_end', 'gb_create_account',
			'gb_enable_autoblock', 'gb_autoblock_parent_id',
		];
	}
}
