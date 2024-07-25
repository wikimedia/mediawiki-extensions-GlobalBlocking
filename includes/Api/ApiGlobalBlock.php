<?php

namespace MediaWiki\Extension\GlobalBlocking\Api;

use ApiBase;
use ApiMain;
use ApiResult;
use MediaWiki\Block\BlockUserFactory;
use MediaWiki\Extension\GlobalBlocking\Services\GlobalBlockLookup;
use MediaWiki\Extension\GlobalBlocking\Services\GlobalBlockManager;
use MediaWiki\ParamValidator\TypeDef\UserDef;
use MediaWiki\Status\Status;
use MediaWiki\User\CentralId\CentralIdLookup;
use StatusValue;
use Wikimedia\IPUtils;
use Wikimedia\ParamValidator\ParamValidator;

class ApiGlobalBlock extends ApiBase {
	private BlockUserFactory $blockUserFactory;
	private GlobalBlockLookup $globalBlockLookup;
	private GlobalBlockManager $globalBlockManager;
	private CentralIdLookup $centralIdLookup;

	/**
	 * @param ApiMain $mainModule
	 * @param string $moduleName
	 * @param BlockUserFactory $blockUserFactory
	 * @param GlobalBlockLookup $globalBlockLookup
	 * @param GlobalBlockManager $globalBlockManager
	 * @param CentralIdLookup $centralIdLookup
	 */
	public function __construct(
		ApiMain $mainModule,
		$moduleName,
		BlockUserFactory $blockUserFactory,
		GlobalBlockLookup $globalBlockLookup,
		GlobalBlockManager $globalBlockManager,
		CentralIdLookup $centralIdLookup
	) {
		parent::__construct( $mainModule, $moduleName );

		$this->blockUserFactory = $blockUserFactory;
		$this->globalBlockLookup = $globalBlockLookup;
		$this->globalBlockManager = $globalBlockManager;
		$this->centralIdLookup = $centralIdLookup;
	}

	public function execute() {
		$this->checkUserRightsAny( 'globalblock' );

		$this->requireOnlyOneParameter( $this->extractRequestParams(), 'expiry', 'unblock' );
		$result = $this->getResult();
		$target = $this->getParameter( 'target' );

		if ( $this->getParameter( 'expiry' ) ) {
			$options = [];

			if ( $this->getParameter( 'anononly' ) ) {
				$options[] = 'anon-only';
			}

			$ip = null;
			$centralId = 0;
			if ( IPUtils::isIPAddress( $target ) ) {
				$ip = IPUtils::sanitizeIP( $target );
			} else {
				$centralId = $this->centralIdLookup->centralIdFromName( $target );
			}
			$existingBlock = $this->globalBlockLookup->getGlobalBlockingBlock(
				$ip, $centralId,
				GlobalBlockLookup::SKIP_ALLOWED_RANGES_CHECK | GlobalBlockLookup::SKIP_LOCAL_DISABLE_CHECK
			);
			if ( $existingBlock && $this->getParameter( 'modify' ) ) {
				$options[] = 'modify';
			}

			$status = $this->globalBlockManager->block(
				$this->getParameter( 'target' ),
				$this->getParameter( 'reason' ),
				$this->getParameter( 'expiry' ),
				$this->getUser(),
				$options
			);

			if ( $this->getParameter( 'alsolocal' ) && $status->isOK() ) {
				$localBlockStatus = $this->blockUserFactory->newBlockUser(
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
				if ( !$localBlockStatus->isOK() ) {
					$this->addLegacyErrorsFromStatus( $localBlockStatus, $result );
				} else {
					$result->addValue( 'globalblock', 'blockedlocally', true );
				}
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
			$status = $this->globalBlockManager->unblock(
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
				ParamValidator::PARAM_TYPE => 'user',
				ParamValidator::PARAM_REQUIRED => true,
				UserDef::PARAM_ALLOWED_USER_TYPES => [ 'ip', 'cidr', 'name', 'temp' ],
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
