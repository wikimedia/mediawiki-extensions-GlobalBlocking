<?php

namespace MediaWiki\Extension\GlobalBlocking\Services;

use Language;
use Liuggio\StatsdClient\Factory\StatsdDataFactoryInterface;
use MediaWiki\Config\ServiceOptions;
use MediaWiki\Extension\GlobalBlocking\GlobalBlock;
use MediaWiki\Extension\GlobalBlocking\GlobalBlocking;
use MediaWiki\Extension\GlobalBlocking\Hook\GlobalBlockingHookRunner;
use MediaWiki\HookContainer\HookContainer;
use MediaWiki\User\CentralId\CentralIdLookup;
use MediaWiki\User\User;
use MediaWiki\User\UserIdentity;
use MediaWiki\WikiMap\WikiMap;
use Message;
use RequestContext;
use stdClass;
use UnexpectedValueException;
use Wikimedia\IPUtils;
use Wikimedia\Rdbms\AndExpressionGroup;
use Wikimedia\Rdbms\Expression;
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
	];

	private const TYPE_IP = 2;
	private const TYPE_RANGE = 3;

	private ServiceOptions $options;
	private GlobalBlockingConnectionProvider $globalBlockingConnectionProvider;
	private StatsdDataFactoryInterface $statsdFactory;
	private GlobalBlockingHookRunner $hookRunner;
	private CentralIdLookup $centralIdLookup;
	private Language $contentLanguage;
	private GlobalBlockReasonFormatter $globalBlockReasonFormatter;
	private GlobalBlockLocalStatusLookup $globalBlockLocalStatusLookup;

	private array $getUserBlockDetailsCache = [];

	public function __construct(
		ServiceOptions $options,
		GlobalBlockingConnectionProvider $globalBlockingConnectionProvider,
		StatsdDataFactoryInterface $statsdFactory,
		HookContainer $hookContainer,
		CentralIdLookup $centralIdLookup,
		Language $contentLanguage,
		GlobalBlockReasonFormatter $globalBlockReasonFormatter,
		GlobalBlockLocalStatusLookup $globalBlockLocalStatusLookup
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

		if ( !empty( $details['error'] ) ) {
			$row = $details['block'];
			return new GlobalBlock(
				$row,
				$details['error'],
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
	 * @return array ['block' => DB row or null, 'error' => empty or message objects]
	 * @phan-return array{block:stdClass|null,error:Message[]}
	 */
	private function getUserBlockDetails( User $user, ?string $ip ) {
		// Check first if the instance cache has the result.
		$cachedResult = $this->getUserBlockDetailsCacheResult( $user );
		if ( $cachedResult !== null ) {
			return $cachedResult;
		}

		$this->statsdFactory->increment( 'global_blocking.get_user_block' );

		if ( $user->isAllowed( 'ipblock-exempt' ) || $user->isAllowed( 'globalblock-exempt' ) ) {
			// User is exempt from IP blocks.
			return $this->addToUserBlockDetailsCache( [ 'block' => null, 'error' => [] ], $user );
		}

		// We have callers from different code paths which may leave $ip as null when providing an
		// IP address as the $user where the IP address is not the session user. In this case, populate
		// the $ip argument with the IP provided in $user to get all the blocks that apply to the IP.
		$context = RequestContext::getMain();
		$isSessionUser = $user->equals( $context->getUser() );
		if ( $ip === null && !$isSessionUser && IPUtils::isIPAddress( $user->getName() ) ) {
			// Populate the IP for checking blocks against non-session users.
			$ip = $user->getName();
		}

		if ( $ip !== null ) {
			$ranges = $this->options->get( 'GlobalBlockingAllowedRanges' );
			foreach ( $ranges as $range ) {
				if ( IPUtils::isInRange( $ip, $range ) ) {
					return $this->addToUserBlockDetailsCache( [ 'block' => null, 'error' => [] ], $user );
				}
			}
		}

		$this->statsdFactory->increment( 'global_blocking.get_user_block_db_query' );

		$lang = $context->getLanguage();
		$block = $this->getGlobalBlockingBlock( $ip, !$user->isNamed() );
		if ( $block ) {
			// Check for local whitelisting
			if ( $this->globalBlockLocalStatusLookup->getLocalWhitelistInfo( $block->gb_id ) ) {
				// Block has been whitelisted.
				return $this->addToUserBlockDetailsCache( [ 'block' => null, 'error' => [] ], $user );
			}

			$blockTimestamp = $lang->timeanddate( wfTimestamp( TS_MW, $block->gb_timestamp ), true );
			$blockExpiry = $lang->formatExpiry( $block->gb_expiry );
			$display_wiki = WikiMap::getWikiName( $block->gb_by_wiki );
			$blockingUser = GlobalBlocking::maybeLinkUserpage(
				$block->gb_by_wiki,
				$this->centralIdLookup->nameFromCentralId( $block->gb_by_central_id ) ?? ''
			);

			// Allow site customization of blocked message.
			if ( IPUtils::isValid( $block->gb_address ) ) {
				$errorMsg = 'globalblocking-ipblocked';
				$this->hookRunner->onGlobalBlockingBlockedIpMsg( $errorMsg );
			} elseif ( IPUtils::isValidRange( $block->gb_address ) ) {
				$errorMsg = 'globalblocking-ipblocked-range';
				$this->hookRunner->onGlobalBlockingBlockedIpRangeMsg( $errorMsg );
			} else {
				throw new UnexpectedValueException(
					"This should not happen. IP globally blocked is not valid and is not a valid range?"
				);
			}

			return $this->addToUserBlockDetailsCache( [
				'block' => $block,
				'error' => [
					wfMessage(
						$errorMsg,
						$blockingUser,
						$display_wiki,
						$this->globalBlockReasonFormatter
							->format( $block->gb_reason, $this->contentLanguage->getCode() ),
						$blockTimestamp,
						$blockExpiry,
						$ip,
						$block->gb_address
					)
				],
			], $user );
		}

		$request = $context->getRequest();
		// Checking non-session users are not applicable to the XFF block.
		if ( $this->options->get( 'GlobalBlockingBlockXFF' ) && $isSessionUser ) {
			$xffIps = $request->getHeader( 'X-Forwarded-For' );
			if ( $xffIps ) {
				$xffIps = array_map( 'trim', explode( ',', $xffIps ) );
				$blocks = $this->checkIpsForBlock( $xffIps, !$user->isNamed() );
				if ( count( $blocks ) > 0 ) {
					$appliedBlock = $this->getAppliedBlock( $xffIps, $blocks );
					if ( $appliedBlock !== null ) {
						list( $blockIP, $block ) = $appliedBlock;
						$blockTimestamp = $lang->timeanddate(
							wfTimestamp( TS_MW, $block->gb_timestamp ),
							true
						);
						$blockExpiry = $lang->formatExpiry( $block->gb_expiry );
						$display_wiki = WikiMap::getWikiName( $block->gb_by_wiki );
						$blockingUser = GlobalBlocking::maybeLinkUserpage(
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
		}
	}

	/**
	 * Choose the most specific block from some combination of user, IP and IP range
	 * blocks. Decreasing order of specificity: IP > narrower IP range > wider IP
	 * range. A range that encompasses one IP address is ranked equally to a singe IP.
	 *
	 * Note that DatabaseBlock::chooseBlocks chooses blocks in a different way.
	 *
	 * This is based on DatabaseBlock::chooseMostSpecificBlock
	 *
	 * @param IResultWrapper $blocks These should not include autoblocks or ID blocks
	 * @return stdClass|null The block with the most specific target
	 */
	private function chooseMostSpecificBlock( $blocks ) {
		if ( $blocks->numRows() === 1 ) {
			return $blocks->current();
		}

		# This result could contain a block on the user, a block on the IP, and a russian-doll
		# set of rangeblocks.  We want to choose the most specific one, so keep a leader board.
		$bestBlock = null;

		# Lower will be better
		$bestBlockScore = 100;
		foreach ( $blocks as $block ) {
			$target = $block->gb_address;
			$type = $this->getTargetType( $target );
			if ( $type == self::TYPE_RANGE ) {
				# This is the number of bits that are allowed to vary in the block, give
				# or take some floating point errors
				$max = IPUtils::isIPv6( $target ) ? 128 : 32;
				[ $network, $bits ] = IPUtils::parseCIDR( $target );
				$size = $max - $bits;

				# Rank a range block covering a single IP equally with a single-IP block
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
	 * Get the row from the `globalblocks` table that applies to the given IP address.
	 *
	 * This does not check for the block being locally disabled or whether the current
	 * session user is exempt from the block. As such it should not be used to determine
	 * if a block should be applied to a user. Use ::getUserBlock for that.
	 *
	 * @param string|null $ip The IP address to be checked
	 * @param bool $anon If false, exclude blocks that are anon-only
	 * @return stdClass|null The most specific row from the `globalblocks` table, or null if no row was found
	 */
	public function getGlobalBlockingBlock( ?string $ip, bool $anon ) {
		if ( $ip === null ) {
			return null;
		}

		$queryBuilder = $this->globalBlockingConnectionProvider
			->getReplicaGlobalBlockingDatabase()
			->newSelectQueryBuilder()
			->select( self::selectFields() )
			->from( 'globalblocks' )
			->caller( __METHOD__ );

		$queryBuilder->where( $this->getRangeCondition( $ip ) );

		if ( !$anon ) {
			$queryBuilder->andWhere( [ 'gb_anon_only' => 0 ] );
		}

		// Get the most specific block for the global blocks that apply to the IP
		return $this->chooseMostSpecificBlock( $queryBuilder->fetchResultSet() );
	}

	/**
	 * Get the SQL WHERE conditions that allow looking up all blocks from the
	 * `globalblocks` table that apply to the given IP address or range.
	 *
	 * @param string $ip The IP address or range
	 * @return Expression[] multiple SQL WHERE conditions
	 */
	public function getRangeCondition( string $ip ): array {
		$dbr = $this->globalBlockingConnectionProvider->getReplicaGlobalBlockingDatabase();

		list( $start, $end ) = IPUtils::parseRange( $ip );

		// Don't bother checking blocks out of this /16.
		// @todo Make the range limit configurable
		$ipPattern = substr( $start, 0, 4 );

		return [
			$dbr->expr( 'gb_range_start', IExpression::LIKE, new LikeValue( $ipPattern, $dbr->anyString() ) ),
			$dbr->expr( 'gb_range_start', '<=', $start ),
			$dbr->expr( 'gb_range_end', '>=', $end ),
			// @todo expiry shouldn't be in this function
			$dbr->expr( 'gb_expiry', '>', $dbr->timestamp() ),
		];
	}

	/**
	 * Find all rows from the `globalblocks` table that target at least one of
	 * the given IP addresses.
	 *
	 * This method filters out blocks that are locally disabled, but does not
	 * check whether the given session user can be exempt from the block.
	 *
	 * @param string[] $ips The array of IP addresses to be checked
	 * @param bool $anon Get anon blocks only
	 * @return stdClass[] Array of applicable blocks as rows from the `globalblocks` table
	 */
	private function checkIpsForBlock( array $ips, bool $anon ): array {
		$dbr = $this->globalBlockingConnectionProvider->getReplicaGlobalBlockingDatabase();
		$queryBuilder = $dbr->newSelectQueryBuilder()
			->select( self::selectFields() )
			->from( 'globalblocks' );

		$conds = [];
		foreach ( $ips as $ip ) {
			if ( IPUtils::isValid( $ip ) ) {
				$conds[] = new AndExpressionGroup( ...self::getRangeCondition( $ip ) );
			}
		}

		if ( !$conds ) {
			// No valid IPs provided so don't even make the query. Bug 59705
			return [];
		}
		$queryBuilder->where( new OrExpressionGroup( ...$conds ) );

		if ( !$anon ) {
			$queryBuilder->where( [ 'gb_anon_only' => 0 ] );
		}

		$blocks = [];
		$results = $queryBuilder
			->caller( __METHOD__ )
			->fetchResultSet();

		foreach ( $results as $block ) {
			if ( !$this->globalBlockLocalStatusLookup->getLocalWhitelistInfo( $block->gb_id ) ) {
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
		$block = array_shift( $blocks );
		foreach ( $ips as $ip ) {
			$ipHex = IPUtils::toHex( $ip );
			if ( $block->gb_range_start <= $ipHex && $block->gb_range_end >= $ipHex ) {
				return [ $ip, $block ];
			}
		}

		return null;
	}

	/**
	 * Given a specific target, find the ID for the global block that applies to it.
	 * If no global block targets this IP address specifically, then this method
	 * returns 0.
	 *
	 * @param string $ip The specific target. This means that blocks on IP ranges that include this IP are
	 *   not included. Use ::getGlobalBlockingBlock to include these blocks.
	 * @param int $dbtype Either DB_REPLICA or DB_PRIMARY.
	 * @return int
	 */
	public function getGlobalBlockId( string $ip, int $dbtype = DB_REPLICA ): int {
		if ( $dbtype === DB_PRIMARY ) {
			$db = $this->globalBlockingConnectionProvider->getPrimaryGlobalBlockingDatabase();
		} else {
			$db = $this->globalBlockingConnectionProvider->getReplicaGlobalBlockingDatabase();
		}

		return (int)$db->newSelectQueryBuilder()
			->select( 'gb_id' )
			->from( 'globalblocks' )
			->where( [ 'gb_address' => $ip ] )
			->caller( __METHOD__ )
			->fetchField();
	}

	/**
	 * @return string[] The fields needed to construct a GlobalBlock object
	 */
	public static function selectFields(): array {
		return [
			'gb_id', 'gb_address', 'gb_by', 'gb_by_central_id', 'gb_by_wiki', 'gb_reason',
			'gb_timestamp', 'gb_anon_only', 'gb_expiry', 'gb_range_start', 'gb_range_end'
		];
	}
}
