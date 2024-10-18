<?php
/**
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
 *
 * @file
 */

namespace MediaWiki\Extension\GlobalBlocking;

use MediaWiki\Extension\GlobalBlocking\Services\GlobalBlockingBlockPurger;
use MediaWiki\Extension\GlobalBlocking\Services\GlobalBlockingConnectionProvider;
use MediaWiki\Extension\GlobalBlocking\Services\GlobalBlockingGlobalAutoblockExemptionListProvider;
use MediaWiki\Extension\GlobalBlocking\Services\GlobalBlockingGlobalBlockDetailsRenderer;
use MediaWiki\Extension\GlobalBlocking\Services\GlobalBlockingLinkBuilder;
use MediaWiki\Extension\GlobalBlocking\Services\GlobalBlockingUserVisibilityLookup;
use MediaWiki\Extension\GlobalBlocking\Services\GlobalBlockLocalStatusLookup;
use MediaWiki\Extension\GlobalBlocking\Services\GlobalBlockLocalStatusManager;
use MediaWiki\Extension\GlobalBlocking\Services\GlobalBlockLookup;
use MediaWiki\Extension\GlobalBlocking\Services\GlobalBlockManager;
use MediaWiki\Extension\GlobalBlocking\Services\GlobalBlockReasonFormatter;
use MediaWiki\MediaWikiServices;

/**
 * @author Taavi "Majavah" Väänänen <hi@taavi.wtf>
 */
class GlobalBlockingServices {

	/** @var MediaWikiServices */
	private $serviceContainer;

	/**
	 * @param MediaWikiServices $serviceContainer
	 */
	public function __construct( MediaWikiServices $serviceContainer ) {
		$this->serviceContainer = $serviceContainer;
	}

	/**
	 * Static version of the constructor, for nicer syntax.
	 * @param MediaWikiServices $serviceContainer
	 * @return static
	 */
	public static function wrap( MediaWikiServices $serviceContainer ): GlobalBlockingServices {
		return new static( $serviceContainer );
	}

	public function getReasonFormatter(): GlobalBlockReasonFormatter {
		return $this->serviceContainer->get( 'GlobalBlocking.GlobalBlockReasonFormatter' );
	}

	public function getGlobalBlockingConnectionProvider(): GlobalBlockingConnectionProvider {
		return $this->serviceContainer->get( 'GlobalBlocking.GlobalBlockingConnectionProvider' );
	}

	public function getGlobalBlockingBlockPurger(): GlobalBlockingBlockPurger {
		return $this->serviceContainer->get( 'GlobalBlocking.GlobalBlockingBlockPurger' );
	}

	public function getGlobalBlockLocalStatusLookup(): GlobalBlockLocalStatusLookup {
		return $this->serviceContainer->get( 'GlobalBlocking.GlobalBlockLocalStatusLookup' );
	}

	public function getGlobalBlockLocalStatusManager(): GlobalBlockLocalStatusManager {
		return $this->serviceContainer->get( 'GlobalBlocking.GlobalBlockLocalStatusManager' );
	}

	public function getGlobalBlockLookup(): GlobalBlockLookup {
		return $this->serviceContainer->get( 'GlobalBlocking.GlobalBlockLookup' );
	}

	public function getGlobalBlockManager(): GlobalBlockManager {
		return $this->serviceContainer->get( 'GlobalBlocking.GlobalBlockManager' );
	}

	public function getGlobalBlockingLinkBuilder(): GlobalBlockingLinkBuilder {
		return $this->serviceContainer->get( 'GlobalBlocking.GlobalBlockingLinkBuilder' );
	}

	public function getGlobalBlockingUserVisibilityLookup(): GlobalBlockingUserVisibilityLookup {
		return $this->serviceContainer->get( 'GlobalBlocking.GlobalBlockingUserVisibilityLookup' );
	}

	public function getGlobalAutoblockExemptionListProvider(): GlobalBlockingGlobalAutoblockExemptionListProvider {
		return $this->serviceContainer->get( 'GlobalBlocking.GlobalBlockingGlobalAutoblockExemptionListProvider' );
	}

	public function getGlobalBlockDetailsRenderer(): GlobalBlockingGlobalBlockDetailsRenderer {
		return $this->serviceContainer->get( 'GlobalBlocking.GlobalBlockingGlobalBlockDetailsRenderer' );
	}
}
