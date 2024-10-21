<?php

/**
 * Created on Nov 1, 2008
 *
 * GlobalBlocking extension
 *
 * Copyright (C) 2008 Roan Kattouw <Firstname>.<Lastname>@home.nl
 *
 * This program is free software; you can redistribute it and/or modify
 * it under the terms of the GNU General Public License as published by
 * the Free Software Foundation; either version 2 of the License, or
 * (at your option) any later version.
 *
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE. See the
 * GNU General Public License for more details.
 *
 * You should have received a copy of the GNU General Public License along
 * with this program; if not, write to the Free Software Foundation, Inc.,
 * 51 Franklin Street, Fifth Floor, Boston, MA 02110-1301, USA.
 * http://www.gnu.org/copyleft/gpl.html
 */

namespace MediaWiki\Extension\GlobalBlocking\Api;

use MediaWiki\Api\ApiBase;
use MediaWiki\Api\ApiQuery;
use MediaWiki\Api\ApiQueryBase;
use MediaWiki\Api\ApiResult;
use MediaWiki\Extension\GlobalBlocking\Services\GlobalBlockingConnectionProvider;
use MediaWiki\Extension\GlobalBlocking\Services\GlobalBlockLookup;
use MediaWiki\User\CentralId\CentralIdLookup;
use Wikimedia\IPUtils;
use Wikimedia\ParamValidator\ParamValidator;
use Wikimedia\ParamValidator\TypeDef\EnumDef;
use Wikimedia\ParamValidator\TypeDef\IntegerDef;
use Wikimedia\Rdbms\IReadableDatabase;

/**
 * Query module to enumerate all global blocks.
 *
 * @ingroup API
 * @ingroup Extensions
 */
class ApiQueryGlobalBlocks extends ApiQueryBase {
	private IReadableDatabase $globalBlockingDb;

	private CentralIdLookup $lookup;
	private GlobalBlockLookup $globalBlockLookup;

	/**
	 * @param ApiQuery $query
	 * @param string $moduleName
	 * @param CentralIdLookup $lookup
	 * @param GlobalBlockLookup $globalBlockLookup
	 * @param GlobalBlockingConnectionProvider $globalBlockingConnectionProvider
	 */
	public function __construct(
		ApiQuery $query,
		$moduleName,
		CentralIdLookup $lookup,
		GlobalBlockLookup $globalBlockLookup,
		GlobalBlockingConnectionProvider $globalBlockingConnectionProvider
	) {
		parent::__construct( $query, $moduleName, 'bg' );
		$this->lookup = $lookup;
		$this->globalBlockLookup = $globalBlockLookup;
		$this->globalBlockingDb = $globalBlockingConnectionProvider->getReplicaGlobalBlockingDatabase();
	}

	public function execute() {
		$params = $this->extractRequestParams();
		$this->requireMaxOneParameter( $params, 'addresses', 'targets', 'ip' );

		$prop = array_flip( $params['prop'] );
		$fld_id = isset( $prop['id'] );
		$fld_target = isset( $prop['target'] );
		$fld_by = isset( $prop['by'] );
		$fld_timestamp = isset( $prop['timestamp'] );
		$fld_expiry = isset( $prop['expiry'] );
		$fld_reason = isset( $prop['reason'] );
		$fld_range = isset( $prop['range'] );

		// Treat the deprecated 'address' prop as 'target', unless 'target' is also set
		$targetPropName = 'target';
		if ( isset( $prop['address'] ) && !isset( $prop['target'] ) ) {
			$fld_target = true;
			$targetPropName = 'address';
		}

		$this->addTables( 'globalblocks' );

		// Add the fields dependent on whether a given prop was requested
		$this->addFieldsIf( 'gb_id', $fld_id );
		$this->addFieldsIf(
			[ 'gb_address', 'gb_anon_only', 'gb_create_account', 'gb_autoblock_parent_id', 'gb_enable_autoblock' ],
			$fld_target
		);
		$this->addFieldsIf( [ 'gb_by_central_id', 'gb_by_wiki' ], $fld_by );
		$this->addFieldsIf( 'gb_expiry', $fld_expiry );
		$this->addFieldsIf( 'gb_reason', $fld_reason );
		$this->addFieldsIf( [ 'gb_range_start', 'gb_range_end', 'gb_autoblock_parent_id' ], $fld_range );

		// The timestamp is always needed in case we need to return a continue value.
		$this->addFields( 'gb_timestamp' );

		// Treat the deprecated 'addresses' parameter as 'targets', unless 'targets' is also set
		if ( isset( $params['addresses'] ) && !isset( $params['targets'] ) ) {
			$params['targets'] = $params['addresses'];
		}

		$dbr = $this->getDB();
		$this->addOption( 'LIMIT', $params['limit'] + 1 );
		$this->addWhereRange( 'gb_timestamp', $params['dir'], $params['start'], $params['end'] );
		if ( isset( $params['ids'] ) ) {
			$this->addWhereFld( 'gb_id', $params['ids'] );
		}
		if ( isset( $params['targets'] ) ) {
			$ipTargets = [];
			$centralIds = [];
			$nonExistingUsernameSeen = false;
			foreach ( (array)$params['targets'] as $target ) {
				if ( IPUtils::isIPAddress( $target ) ) {
					// If the target is an IP, add it to the list of IP targets.
					$ipTargets[] = $target;
				} else {
					// If the target is not an IP, then look up the central ID for the target and add it
					// to the list of central IDs.
					$centralId = $this->lookup->centralIdFromName( $target );
					if ( $centralId ) {
						$centralIds[] = $centralId;
					} else {
						$nonExistingUsernameSeen = true;
					}
				}
			}

			// Combine the list of central IDs and IP targets into a single WHERE clause such that all blocks affecting
			// any of the targets are selected.
			if ( count( $centralIds ) && count( $ipTargets ) ) {
				$this->addWhere(
					$this->getDB()
						->expr( 'gb_target_central_id', '=', $centralIds )
						->or( 'gb_address', '=', $ipTargets )
				);
			} elseif ( count( $centralIds ) ) {
				$this->addWhereFld( 'gb_target_central_id', $centralIds );
			} elseif ( count( $ipTargets ) ) {
				// Exclude autoblocks from the results, as we do not want to expose the IP address target of the
				// autoblock as that is hidden.
				$this->addWhere(
					$this->getDB()->expr( 'gb_address', '=', $ipTargets )
						->and( 'gb_autoblock_parent_id', '=', 0 )
				);
			} elseif ( $nonExistingUsernameSeen && ( !isset( $params['ids'] ) || !count( $params['ids'] ) ) ) {
				// If the targets parameter contained a non-existing username, no other valid targets
				// were provided, and the `ids` filter is not set, then return no global blocks.
				// Otherwise, all global blocks would be returned if a non-existing target was provided.
				$this->addWhere( '1=0' );
			}
		}
		if ( isset( $params['ip'] ) ) {
			$blockCIDRLimit = $this->getConfig()->get( 'GlobalBlockingCIDRLimit' );
			if ( IPUtils::isIPv4( $params['ip'] ) ) {
				$type = 'IPv4';
				$cidrLimit = $blockCIDRLimit['IPv4'];
			} elseif ( IPUtils::isIPv6( $params['ip'] ) ) {
				$type = 'IPv6';
				$cidrLimit = $blockCIDRLimit['IPv6'];
			} else {
				$this->dieWithError( 'apierror-badip', 'invalidip' );
			}

			// Check range validity, if it's a CIDR
			[ $ip, $range ] = IPUtils::parseCIDR( $params['ip'] );
			if ( $ip !== false && $range !== false && $range < $cidrLimit ) {
				$this->dieWithError( [ 'apierror-cidrtoobroad', $type, $cidrLimit ] );
			}

			// Attempt to get an IExpression of conditions for the IP, and die if none were returned.
			// ::getGlobalBlockLookupConditions does not return null unless 'ip' is an invalid IP. We have checked
			// above that the IP is valid, so we can safely suppress the Phan warning here.
			// @phan-suppress-next-line PhanTypeMismatchArgumentNullable
			$this->addWhere( $this->globalBlockLookup->getGlobalBlockLookupConditions(
				$params['ip'], 0,
				GlobalBlockLookup::SKIP_LOCAL_DISABLE_CHECK | GlobalBlockLookup::SKIP_ALLOWED_RANGES_CHECK |
				GlobalBlockLookup::SKIP_AUTOBLOCKS
			) );
		} else {
			// We need to exclude expired blocks to avoid them appearing in the list of active global blocks.
			// When the bgip parameter is set, we do not need to specify this as it is added by
			// ::getGlobalBlockLookupConditions (so this statement is skipped in this case).
			$this->addWhere( $dbr->expr( 'gb_expiry', '>', $dbr->timestamp() ) );
		}

		// Hide global autoblocks from the API response to not break code which relies on this API on WMF wikis.
		// Only used as a temporary feature flag, and will be removed once code which calls WMF wikis is properly
		// updated.
		if ( $this->getConfig()->get( 'GlobalBlockingHideAutoblocksInGlobalBlocksAPIResponse' ) ) {
			$this->addWhere( [ 'gb_autoblock_parent_id' => 0 ] );
		}

		$res = $this->select( __METHOD__ );

		$data = [];
		$count = 0;
		foreach ( $res as $row ) {
			if ( ++$count > $params['limit'] ) {
				// We've reached the limit requested by the user, which means that there is at least one more row to
				// be returned in a subsequent request. We set the continue parameter to the timestamp of the row
				// that was not displayed to allow the user to continue where they left off.
				$this->setContinueEnumParameter( 'start', wfTimestamp( TS_ISO_8601, $row->gb_timestamp ) );
				break;
			}

			// Construct the array of data about this database row to return to the user.
			$block = [
				ApiResult::META_TYPE => 'assoc'
			];
			if ( $fld_id ) {
				$block['id'] = $row->gb_id;
			}
			if ( $fld_target ) {
				// Hide the target if the block is an autoblock
				if ( !$row->gb_autoblock_parent_id ) {
					$block[$targetPropName] = $row->gb_address;
				}
				$block['anononly'] = (bool)$row->gb_anon_only;
				$block['account-creation-disabled'] = (bool)$row->gb_create_account;
				$block['autoblocking-enabled'] = (bool)$row->gb_enable_autoblock;
				$block['automatic'] = (bool)$row->gb_autoblock_parent_id;
			}
			if ( $fld_by ) {
				$block['by'] = $this->lookup->nameFromCentralId( $row->gb_by_central_id );
				$block['bywiki'] = $row->gb_by_wiki;
			}
			if ( $fld_timestamp ) {
				$block['timestamp'] = wfTimestamp( TS_ISO_8601, $row->gb_timestamp );
			}
			if ( $fld_expiry ) {
				$block['expiry'] = ApiResult::formatExpiry( $row->gb_expiry );
			}
			if ( $fld_reason ) {
				$block['reason'] = $row->gb_reason;
			}
			if ( $fld_range && $row->gb_range_start ) {
				// Hide the target if the block is an autoblock
				if ( !$row->gb_autoblock_parent_id ) {
					$block['rangestart'] = IPUtils::formatHex( $row->gb_range_start );
					$block['rangeend'] = IPUtils::formatHex( $row->gb_range_end );
				}
			}
			$data[] = $block;
		}

		$result = $this->getResult();
		$result->setIndexedTagName( $data, 'block' );
		$result->addValue( 'query', $this->getModuleName(), $data );
	}

	public function getCacheMode( $params ) {
		return 'public';
	}

	public function getAllowedParams() {
		return [
			'start' => [
				ParamValidator::PARAM_TYPE => 'timestamp',
			],
			'end' => [
				ParamValidator::PARAM_TYPE => 'timestamp',
			],
			'dir' => [
				ParamValidator::PARAM_TYPE => [
					'newer',
					'older'
				],
				ParamValidator::PARAM_DEFAULT => 'older',
				ApiBase::PARAM_HELP_MSG => 'api-help-param-direction',
			],
			'ids' => [
				ParamValidator::PARAM_TYPE => 'integer',
				ParamValidator::PARAM_ISMULTI => true,
			],
			'addresses' => [
				ParamValidator::PARAM_ISMULTI => true,
				ParamValidator::PARAM_DEPRECATED => true,
			],
			'targets' => [
				ParamValidator::PARAM_ISMULTI => true,
			],
			'ip' => null,
			'limit' => [
				ParamValidator::PARAM_DEFAULT => 10,
				ParamValidator::PARAM_TYPE => 'limit',
				IntegerDef::PARAM_MIN => 1,
				IntegerDef::PARAM_MAX => ApiBase::LIMIT_BIG1,
				IntegerDef::PARAM_MAX2 => ApiBase::LIMIT_BIG2,
			],
			'prop' => [
				ParamValidator::PARAM_DEFAULT => 'id|target|by|timestamp|expiry|reason',
				ParamValidator::PARAM_TYPE => [
					'id',
					'address',
					'target',
					'by',
					'timestamp',
					'expiry',
					'reason',
					'range',
				],
				ParamValidator::PARAM_ISMULTI => true,
				ApiBase::PARAM_HELP_MSG_PER_VALUE => [],
				EnumDef::PARAM_DEPRECATED_VALUES => [ 'address' => true ],
			]
		];
	}

	protected function getDB() {
		return $this->globalBlockingDb;
	}

	/**
	 * @see ApiBase::getExamplesMessages()
	 * @return array
	 */
	protected function getExamplesMessages() {
		return [
			'action=query&list=globalblocks'
				=> 'apihelp-query+globalblocks-example-1',
			'action=query&list=globalblocks&bgip=192.0.2.18'
				=> 'apihelp-query+globalblocks-example-2',
		];
	}
}
