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

/**
 * Query module to enumerate all available pages.
 *
 * @ingroup API
 * @ingroup Extensions
 */
class ApiQueryGlobalBlocks extends ApiQueryBase {

	public function __construct( $query, $moduleName ) {
		parent :: __construct( $query, $moduleName, 'bg' );
	}

	public function execute() {
		$params = $this->extractRequestParams();

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
			$this->addFields( [ 'gb_by', 'gb_by_wiki' ] );
		}
		if ( $fld_timestamp ) {
			$this->addFields( 'gb_timestamp' );
		}
		if ( $fld_expiry ) {
			$this->addFields( 'gb_expiry' );
		}
		if ( $fld_reason ) {
			$this->addFields( 'gb_reason' );
		}
		if ( $fld_range ) {
			$this->addFields( [ 'gb_range_start', 'gb_range_end' ] );
		}

		$this->addOption( 'LIMIT', $params['limit'] + 1 );
		$this->addWhereRange( 'gb_timestamp', $params['dir'], $params['start'], $params['end'] );
		if ( isset( $params['ids'] ) ) {
			$this->addWhereFld( 'gb_id', $params['ids'] );
		}
		if ( isset( $params['addresses'] ) ) {
			$this->addWhereFld( 'gb_address', $params['addresses'] );
		}
		if ( isset( $params['ip'] ) ) {
			list( $ip, $range ) = IP::parseCIDR( $params['ip'] );
			if ( $ip && $range ) {
				# We got a CIDR range
				if ( $range < 16 ) {
					$this->dieUsage( 'CIDR ranges broader than /16 are not accepted', 'cidrtoobroad' );
				}
				$lower = Wikimedia\base_convert( $ip, 10, 16, 8, false );
				$upper = Wikimedia\base_convert( $ip + pow( 2, 32 - $range ) - 1, 10, 16, 8, false );
			} else {
				$lower = $upper = IP::toHex( $params['ip'] );
			}
			$prefix = substr( $lower, 0, 4 );
			$this->addWhere( [
					'gb_range_start ' . $dbr->buildLike( $prefix, $dbr->anyString() ),
					'gb_range_start <= ' . $dbr->addQuotes( $lower ),
					'gb_range_end >= ' . $dbr->addQuotes( $upper )
				]
			);
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
				$block['by'] = $row->gb_by;
				$block['bywiki'] = $row->gb_by_wiki;
			}
			if ( $fld_timestamp ) {
				$block['timestamp'] = wfTimestamp( TS_ISO_8601, $row->gb_timestamp );
			}
			if ( $fld_expiry ) {
				$block['expiry'] = $this->getLanguage()->formatExpiry( $row->gb_expiry, TS_ISO_8601 );
			}
			if ( $fld_reason ) {
				$block['reason'] = $row->gb_reason;
			}
			if ( $fld_range ) {
				$block['rangestart'] = IP::hexToQuad( $row->gb_range_start );
				$block['rangeend'] = IP::hexToQuad( $row->gb_range_end );
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
				ApiBase :: PARAM_TYPE => 'timestamp'
			],
			'end' => [
				ApiBase :: PARAM_TYPE => 'timestamp',
			],
			'dir' => [
				ApiBase :: PARAM_TYPE => [
					'newer',
					'older'
				],
				ApiBase :: PARAM_DFLT => 'older',
				ApiBase::PARAM_HELP_MSG => 'api-help-param-direction',
			],
			'ids' => [
				ApiBase :: PARAM_TYPE => 'integer',
				ApiBase :: PARAM_ISMULTI => true
			],
			'addresses' => [
				ApiBase :: PARAM_ISMULTI => true
			],
			'ip' => null,
			'limit' => [
				ApiBase :: PARAM_DFLT => 10,
				ApiBase :: PARAM_TYPE => 'limit',
				ApiBase :: PARAM_MIN => 1,
				ApiBase :: PARAM_MAX => ApiBase :: LIMIT_BIG1,
				ApiBase :: PARAM_MAX2 => ApiBase :: LIMIT_BIG2
			],
			'prop' => [
				ApiBase :: PARAM_DFLT => 'id|address|by|timestamp|expiry|reason',
				ApiBase :: PARAM_TYPE => [
					'id',
					'address',
					'by',
					'timestamp',
					'expiry',
					'reason',
					'range',
				],
				ApiBase :: PARAM_ISMULTI => true
			]
		];
	}

	protected function getDB() {
		return GlobalBlocking::getGlobalBlockingDatabase( DB_SLAVE );
	}

	/**
	 * @see ApiBase::getExamplesMessages()
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
