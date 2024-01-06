<?php

namespace MediaWiki\Extension\GlobalBlocking\Api;

use ApiBase;
use ApiMain;
use ApiResult;
use MediaWiki\Block\BlockUserFactory;
use MediaWiki\Extension\GlobalBlocking\GlobalBlocking;
use MediaWiki\Status\Status;
use StatusValue;
use Wikimedia\ParamValidator\ParamValidator;

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

			$status = GlobalBlocking::block(
				$this->getParameter( 'target' ),
				$this->getParameter( 'reason' ),
				$this->getParameter( 'expiry' ),
				$this->getUser(),
				$options
			);

			if ( $this->getParameter( 'alsolocal' ) && $status->isOK() ) {
				$this->blockUserFactory->newBlockUser(
					$this->getParameter( 'target' ),
					$this->getUser(),
					$this->getParameter( 'expiry' ),
					$this->getParameter( 'reason' ),
					[
						'isCreateAccountBlocked' => true,
						'isEmailBlocked' => $this->getParameter( 'localblocksemail' ),
						'isUserTalkEditBlocked' => $this->getParameter( 'localblockstalk' ),
						'isHardBlock' => !$this->getParameter( 'localanononly' ),
						'isAutoblocking' => true,
					]
				)->placeBlock( $this->getParameter( 'modify' ) );
				$result->addValue( 'globalblock', 'blockedlocally', true );
			}

			if ( !$status->isOK() ) {
				$this->addLegacyErrorsFromStatus( $status, $result );
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
			$status = GlobalBlocking::unblock(
				$this->getParameter( 'target' ),
				$this->getParameter( 'reason' ),
				$this->getUser()
			);

			if ( !$status->isOK() ) {
				$this->addLegacyErrorsFromStatus( $status, $result );
			} else {
				$result->addValue( 'globalblock', 'user', $this->getParameter( 'target' ) );
				$result->addValue( 'globalblock', 'unblocked', '' );
			}

		}
	}

	/**
	 * @param StatusValue $status
	 * @param ApiResult $result
	 * @return void
	 */
	private function addLegacyErrorsFromStatus( StatusValue $status, ApiResult $result ) {
		// Convert a StatusValue to the legacy format used by the API.
		// TODO deprecate and replace with ApiErrorFormatter::addMessagesFromStatus()
		$legacyErrors = [];
		$errors = Status::wrap( $status )->getErrorsArray();
		foreach ( $errors as $error ) {
			$legacyErrors[] = [
				'code' => $error[0],
				'message' => str_replace(
					"\n",
					" ",
					$this->msg( ...$error )->text()
				)
			];
		}
		$result->setIndexedTagName( $legacyErrors, 'error' );
		$result->addValue( 'error', 'globalblock', $legacyErrors );
	}

	public function getAllowedParams() {
		return [
			'target' => [
				ParamValidator::PARAM_TYPE => 'string',
				ParamValidator::PARAM_REQUIRED => true
			],
			'expiry' => [
				ParamValidator::PARAM_TYPE => 'expiry'
			],
			'unblock' => [
				ParamValidator::PARAM_TYPE => 'boolean'
			],
			'reason' => [
				ParamValidator::PARAM_TYPE => 'string',
				ParamValidator::PARAM_REQUIRED => true
			],
			'anononly' => [
				ParamValidator::PARAM_TYPE => 'boolean'
			],
			'modify' => [
				ParamValidator::PARAM_TYPE => 'boolean'
			],
			'alsolocal' => [
				ParamValidator::PARAM_TYPE => 'boolean'
			],
			'localblockstalk' => [
				ParamValidator::PARAM_TYPE => 'boolean'
			],
			'localblocksemail' => [
				ParamValidator::PARAM_TYPE => 'boolean'
			],
			'localanononly' => [
				ParamValidator::PARAM_TYPE => 'boolean'
			],
			'token' => [
				ParamValidator::PARAM_TYPE => 'string',
				ParamValidator::PARAM_REQUIRED => true
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
