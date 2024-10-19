<?php

namespace MediaWiki\Extension\GlobalBlocking\Services;

use MediaWiki\Extension\CentralAuth\User\CentralAuthUser;
use MediaWiki\Permissions\Authority;
use MediaWiki\Registration\ExtensionRegistry;
use MediaWiki\User\UserFactory;

/**
 * A service that determines whether the given authority can view the given user.
 *
 * @since 1.43
 */
class GlobalBlockingUserVisibilityLookup {

	private UserFactory $userFactory;

	public function __construct( UserFactory $userFactory ) {
		$this->userFactory = $userFactory;
	}

	/**
	 * Returns whether the current authority can see the given user. This checks the local visibility of the user
	 * and if the CentralAuth extension is loaded checks the visibility of the CentralAuthUser.
	 *
	 * @param string $username The username to check
	 * @param Authority $authority The authority to check against
	 * @return bool
	 */
	public function checkAuthorityCanSeeUser( string $username, Authority $authority ): bool {
		$user = $this->userFactory->newFromName( $username );
		if ( !$user ) {
			// If the user is not valid, then it cannot be hidden so return true.
			return true;
		}

		// Assume that the authority has the rights to see the user by default.
		$canViewTarget = true;

		// If the user exists locally, then we can check if the user is hidden locally.
		if ( $user->isRegistered() ) {
			$canViewTarget = !( $user->isHidden() && !$authority->isAllowed( 'hideuser' ) );
		}

		// If CentralAuth is loaded, then we can check if the central user is hidden.
		// This is necessary if the user does not exist on this wiki but their global account is hidden.
		if ( $canViewTarget && ExtensionRegistry::getInstance()->isLoaded( 'CentralAuth' ) ) {
			$centralUser = CentralAuthUser::getInstance( $user );
			$canViewTarget = !( $centralUser->isHidden() && !$authority->isAllowed( 'centralauth-suppress' ) );
		}

		return $canViewTarget;
	}
}
