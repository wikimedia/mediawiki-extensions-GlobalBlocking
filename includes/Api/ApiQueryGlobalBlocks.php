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

use ApiBase;
use ApiQuery;
use ApiQueryBase;
use ApiResult;
use CentralIdLookup;
use MediaWiki\Extension\GlobalBlocking\GlobalBlocking;
use Wikimedia\IPUtils;
use Wikimedia\ParamValidator\ParamValidator;
use Wikimedia\ParamValidator\TypeDef\IntegerDef;
use Wikimedia\Rdbms\IDatabase;

/**
 * Query module to enumerate all global blocks.
 *
 * @ingroup API
 * @ingroup Extensions
 */
class ApiQueryGlobalBlocks extends ApiQueryBase {

	/**
	 * @var IDatabase
	 */
	private $globalBlockingDb;

	/**
	 * @var CentralIdLookup
	 */
	private $lookup;

	/**
	 * @param ApiQuery $query
	 * @param string $moduleName
	 * @param CentralIdLookup $lookup
	 */
	public function __construct(
		ApiQuery $query,
		$moduleName,
		CentralIdLookup $lookup
	) {
		parent::__construct( $query, $moduleName, 'bg' );
		$this->lookup = $lookup;
	}

	public function execute() {
		$params = $this->extractRequestParams();
		$this->requireMaxOneParameter( $params, 'addresses', 'ip' );

		$prop = array_flip( $params['prop'] );
		$fld_id = isset( $prop['id'] );
		$fld_address = isset( $prop['address'] );
		$fld_by = isset( $prop['by'] );
		$fld_timestamp = isset( $prop['timestamp'] );
		$fld_expiry = isset( $prop['expiry'] );
		$fld_reason = isset( $prop['reason'] );
		$fld_range = isset( $prop['range'] );

		$result = $this->getResult();
		$data = [];

		$this->addTables( 'globalblocks' );
		if ( $fld_id ) {
			$this->addFields( 'gb_id' );
		}
		if ( $fld_address ) {
			$this->addFields( [ 'gb_address', 'gb_anon_only' ] );
		}
		if ( $fld_by ) {
			$this->addFields( [ 'gb_by_central_id', 'gb_by_wiki' ] );
		}

		$this->addFields( 'gb_timestamp' );

		if ( $fld_expiry ) {
			$this->addFields( 'gb_expiry' );
		}
		if ( $fld_reason ) {
			$this->addFields( 'gb_reason' );
		}
		if ( $fld_range ) {
			$this->addFields( [ 'gb_range_start', 'gb_range_end' ] );
		}

		$dbr = $this->getDB();
		$this->addOption( 'LIMIT', $params['limit'] + 1 );
		$this->addWhereRange( 'gb_timestamp', $params['dir'], $params['start'], $params['end'] );
		$this->addWhere( 'gb_expiry > ' . $dbr->addQuotes( $dbr->timestamp() ) );
		if ( isset( $params['ids'] ) ) {
			$this->addWhereFld( 'gb_id', $params['ids'] );
		}
		if ( isset( $params['addresses'] ) ) {
			$addresses = [];
			foreach ( (array)$params['addresses'] as $address ) {
				if ( !IPUtils::isIPAddress( $address ) ) {
					$this->dieWithError(
						[ 'globalblocking-apierror-badip', wfEscapeWikiText( $address ) ],
						'param_addresses'
					);
				}
				$addresses[] = $address;
			}
			$this->addWhereFld( 'gb_address', $addresses );
		}
		if ( isset( $params['ip'] ) ) {
			if ( IPUtils::isIPv4( $params['ip'] ) ) {
				$type = 'IPv4';
				$cidrLimit = 16; // @todo Make this configurable
				$prefixLen = 0;
			} elseif ( IPUtils::isIPv6( $params['ip'] ) ) {
				$type = 'IPv6';
				$cidrLimit = 16; // @todo Make this configurable
				$prefixLen = 3; // IPUtils::toHex output is prefixed with "v6-"
			} else {
				$this->dieWithError( 'apierror-badip', 'param_ip' );
			}

			# Check range validity, if it's a CIDR
			[ $ip, $range ] = IPUtils::parseCIDR( $params['ip'] );
			if ( $ip !== false && $range !== false && $range < $cidrLimit ) {
				$this->dieWithError( [ 'apierror-cidrtoobroad', $type, $cidrLimit ] );
			}

			# Let IPUtils::parseRange handle calculating $upper, instead of duplicating the logic here.
			[ $lower, $upper ] = IPUtils::parseRange( $params['ip'] );

			# Extract the common prefix to any rangeblock affecting this IP/CIDR
			$prefix = substr( $lower, 0, $prefixLen + $cidrLimit / 4 );

			# Fairly hard to make a malicious SQL statement out of hex characters,
			# but it is good practice to add quotes
			$lower = $dbr->addQuotes( $lower );
			$upper = $dbr->addQuotes( $upper );

			$this->addWhere( [
				'gb_range_start' . $dbr->buildLike( $prefix, $dbr->anyString() ),
				'gb_range_start <= ' . $lower,
				'gb_range_end >= ' . $upper,
			] );
		}

		$res = $this->select( __METHOD__ );

		$count = 0;
		foreach ( $res as $row ) {
			if ( ++$count > $params['limit'] ) {
				// We've had enough
				$this->setContinueEnumParameter( 'start', wfTimestamp( TS_ISO_8601, $row->gb_timestamp ) );
				break;
			}
			$block = [];
			if ( $fld_id ) {
				$block['id'] = $row->gb_id;
			}
			if ( $fld_address ) {
				$block['address'] = $row->gb_address;
				if ( $row->gb_anon_only ) {
					$block['anononly'] = '';
				}
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
			if ( $fld_range ) {
				$block['rangestart'] = IPUtils::hexToQuad( $row->gb_range_start );
				$block['rangeend'] = IPUtils::hexToQuad( $row->gb_range_end );
			}
			$data[] = $block;
		}
		$result->setIndexedTagName( $data, 'block' );
		$result->addValue( 'query', $this->getModuleName(), $data );
	}

	public function getCacheMode( $params ) {
		return 'public';
	}

	public function getAllowedParams() {
		return [
			'start' => [
				ParamValidator::PARAM_TYPE => 'timestamp'
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
				ParamValidator::PARAM_ISMULTI => true
			],
			'addresses' => [
				ParamValidator::PARAM_ISMULTI => true
			],
			'ip' => null,
			'limit' => [
				ParamValidator::PARAM_DEFAULT => 10,
				ParamValidator::PARAM_TYPE => 'limit',
				IntegerDef::PARAM_MIN => 1,
				IntegerDef::PARAM_MAX => ApiBase::LIMIT_BIG1,
				IntegerDef::PARAM_MAX2 => ApiBase::LIMIT_BIG2
			],
			'prop' => [
				ParamValidator::PARAM_DEFAULT => 'id|address|by|timestamp|expiry|reason',
				ParamValidator::PARAM_TYPE => [
					'id',
					'address',
					'by',
					'timestamp',
					'expiry',
					'reason',
					'range',
				],
				ParamValidator::PARAM_ISMULTI => true
			]
		];
	}

	protected function getDB() {
		if ( $this->globalBlockingDb === null ) {
			$this->globalBlockingDb = GlobalBlocking::getReplicaGlobalBlockingDatabase();
		}
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
