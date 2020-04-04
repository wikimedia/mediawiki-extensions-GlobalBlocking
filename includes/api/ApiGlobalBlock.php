<?php
class ApiGlobalBlock extends ApiBase {
	public function execute() {
		$this->checkUserRightsAny( 'globalblock' );

		$this->requireOnlyOneParameter( $this->extractRequestParams(), 'expiry', 'unblock' );
		$result = $this->getResult();
		$block = GlobalBlocking::getGlobalBlockingBlock( $this->getParameter( 'target' ), true );

		if ( $this->getParameter( 'expiry' ) ) {
			$options = [];

			if ( $this->getParameter( 'anononly' ) ) {
				$options[] = 'anon-only';
			}

			if ( $block && $this->getParameter( 'modify' ) ) {
				$options[] = 'modify';
			}

			$errors = GlobalBlocking::block(
				$this->getParameter( 'target' ),
				$this->getParameter( 'reason' ),
				$this->getParameter( 'expiry' ),
				$this->getUser(),
				$options
			);

			if ( $this->getParameter( 'alsolocal' ) && count( $errors ) === 0 ) {
				SpecialBlock::processForm( [
					'Target' => $this->getParameter( 'target' ),
					'Reason' => [ $this->getParameter( 'reason' ) ],
					'Expiry' => $this->getParameter( 'expiry' ),
					'HardBlock' => !$this->getParameter( 'anononly' ),
					'CreateAccount' => true,
					'AutoBlock' => true,
					'DisableEmail' => true,
					'DisableUTEdit' => $this->getParameter( 'localblockstalk' ),
					'Reblock' => true,
					'Confirm' => true,
					'Watch' => false
				], $this->getContext() );
				$result->addValue( 'globalblock', 'blockedlocally', true );
			}

			if ( count( $errors ) ) {
				foreach ( $errors as &$error ) {
					$error = [
						'code' => $error[0],
						'message' => str_replace(
							"\n",
							" ",
							call_user_func_array( [ $this, 'msg' ], $error )->text()
						)
					];
				}
				$result->setIndexedTagName( $errors, 'error' );
				$result->addValue( 'error', 'globalblock', $errors );
			} else {
				$result->addValue( 'globalblock', 'user', $this->getParameter( 'target' ) );
				$result->addValue( 'globalblock', 'blocked', '' );
				if ( $this->getParameter( 'anononly' ) ) {
					$result->addValue( 'globalblock', 'anononly', '' );
				}
				$expiry = SpecialBlock::parseExpiryInput( $this->getParameter( 'expiry' ) );
				if ( $expiry == wfGetDB( DB_REPLICA )->getInfinity() ) {
					$displayExpiry = 'infinite';
				} else {
					$displayExpiry = wfTimestamp( TS_ISO_8601, $expiry );
				}
				$result->addValue( 'globalblock', 'expiry', $displayExpiry );
			}
		} elseif ( $this->getParameter( 'unblock' ) ) {
			GlobalBlocking::getGlobalBlockingDatabase( DB_MASTER )->delete(
				'globalblocks',
				[ 'gb_id' => $block->gb_id ],
				__METHOD__
			);

			$logPage = new LogPage( 'gblblock' );
			$logPage->addEntry(
				'gunblock',
				Title::makeTitleSafe( NS_USER, $block->gb_address ),
				$this->getParameter( 'reason' ),
				[],
				$this->getUser()
			);
			$result->addValue( 'globalblock', 'user', $this->getParameter( 'target' ) );
			$result->addValue( 'globalblock', 'unblocked', '' );
		}
	}

	public function getAllowedParams() {
		return [
			'target' => [
				ApiBase::PARAM_TYPE => 'string',
				ApiBase::PARAM_REQUIRED => true
			],
			'expiry' => [
				ApiBase::PARAM_TYPE => 'string'
			],
			'unblock' => [
				ApiBase::PARAM_TYPE => 'boolean'
			],
			'reason' => [
				ApiBase::PARAM_TYPE => 'string',
				ApiBase::PARAM_REQUIRED => true
			],
			'anononly' => [
				ApiBase::PARAM_TYPE => 'boolean'
			],
			'modify' => [
				ApiBase::PARAM_TYPE => 'boolean'
			],
			'alsolocal' => [
				ApiBase::PARAM_TYPE => 'boolean'
			],
			'localblockstalk' => [
				ApiBase::PARAM_TYPE => 'boolean'
			],
			'token' => [
				ApiBase::PARAM_TYPE => 'string',
				ApiBase::PARAM_REQUIRED => true
			]
		];
	}

	/**
	 * @see ApiBase::getExamplesMessages()
	 * @return array
	 */
	protected function getExamplesMessages() {
		return [
			'action=globalblock&target=192.0.2.1&expiry=indefinite&reason=Cross-wiki%20abuse&token=123ABC'
				=> 'apihelp-globalblock-example-1',
		];
	}

	public function mustBePosted() {
		return true;
	}

	public function isWriteMode() {
		return true;
	}

	public function needsToken() {
		return 'csrf';
	}
}
