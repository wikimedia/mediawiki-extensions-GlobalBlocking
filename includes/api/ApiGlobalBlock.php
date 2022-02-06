<?php

use MediaWiki\Block\BlockUserFactory;

class ApiGlobalBlock extends ApiBase {
	/** @var BlockUserFactory */
	private $blockUserFactory;

	/**
	 * @param ApiMain $mainModule
	 * @param string $moduleName
	 * @param BlockUserFactory $blockUserFactory
	 */
	public function __construct(
		ApiMain $mainModule,
		$moduleName,
		BlockUserFactory $blockUserFactory
	) {
		parent::__construct( $mainModule, $moduleName );

		$this->blockUserFactory = $blockUserFactory;
	}

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
				$this->blockUserFactory->newBlockUser(
					$this->getParameter( 'target' ),
					$this->getUser(),
					$this->getParameter( 'expiry' ),
					$this->getParameter( 'reason' ),
					[
						'isCreateAccountBlocked' => true,
						'isEmailBlocked' => true,
						'isUserTalkEditBlocked' => $this->getParameter( 'localblockstalk' ),
						'isHardBlock' => !$this->getParameter( 'localanononly' ),
						'isAutoblocking' => true,
					]
				)->placeBlock( $this->getParameter( 'modify' ) );
				$result->addValue( 'globalblock', 'blockedlocally', true );
			}

			if ( count( $errors ) > 0 ) {
				foreach ( $errors as &$error ) {
					$error = [
						'code' => $error[0],
						'message' => str_replace(
							"\n",
							" ",
							$this->msg( ...$error )->text()
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
				$expiry = ApiResult::formatExpiry( $this->getParameter( 'expiry' ), 'infinite' );
				$result->addValue( 'globalblock', 'expiry', $expiry );
			}
		} elseif ( $this->getParameter( 'unblock' ) ) {
			$errors = GlobalBlocking::unblock(
				$this->getParameter( 'target' ),
				$this->getParameter( 'reason' ),
				$this->getUser()
			);

			if ( count( $errors ) > 0 ) {
				foreach ( $errors as &$error ) {
					$error = [
						'code' => $error[0],
						'message' => str_replace(
							"\n",
							" ",
							$this->msg( ...$error )->text()
						)
					];
				}
				$result->setIndexedTagName( $errors, 'error' );
				$result->addValue( 'error', 'globalblock', $errors );
			} else {
				$result->addValue( 'globalblock', 'user', $this->getParameter( 'target' ) );
				$result->addValue( 'globalblock', 'unblocked', '' );
			}

		}
	}

	public function getAllowedParams() {
		return [
			'target' => [
				ApiBase::PARAM_TYPE => 'string',
				ApiBase::PARAM_REQUIRED => true
			],
			'expiry' => [
				ApiBase::PARAM_TYPE => 'expiry'
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
			'localanononly' => [
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
