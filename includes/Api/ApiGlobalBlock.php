<?php

namespace MediaWiki\Extension\GlobalBlocking\Api;

use MediaWiki\Api\ApiBase;
use MediaWiki\Api\ApiMain;
use MediaWiki\Api\ApiResult;
use MediaWiki\Block\BlockUserFactory;
use MediaWiki\Extension\GlobalBlocking\Services\GlobalBlockingConnectionProvider;
use MediaWiki\Extension\GlobalBlocking\Services\GlobalBlockLookup;
use MediaWiki\Extension\GlobalBlocking\Services\GlobalBlockManager;
use MediaWiki\ParamValidator\TypeDef\UserDef;
use MediaWiki\Status\Status;
use StatusValue;
use Wikimedia\ParamValidator\ParamValidator;

class ApiGlobalBlock extends ApiBase {
	private BlockUserFactory $blockUserFactory;
	private GlobalBlockManager $globalBlockManager;
	private GlobalBlockingConnectionProvider $globalBlockingConnectionProvider;

	/**
	 * @param ApiMain $mainModule
	 * @param string $moduleName
	 * @param BlockUserFactory $blockUserFactory
	 * @param GlobalBlockManager $globalBlockManager
	 * @param GlobalBlockingConnectionProvider $globalBlockingConnectionProvider
	 */
	public function __construct(
		ApiMain $mainModule,
		$moduleName,
		BlockUserFactory $blockUserFactory,
		GlobalBlockManager $globalBlockManager,
		GlobalBlockingConnectionProvider $globalBlockingConnectionProvider
	) {
		parent::__construct( $mainModule, $moduleName );

		$this->blockUserFactory = $blockUserFactory;
		$this->globalBlockManager = $globalBlockManager;
		$this->globalBlockingConnectionProvider = $globalBlockingConnectionProvider;
	}

	public function execute() {
		$this->checkUserRightsAny( 'globalblock' );

		// Validate that the API request was not made with incompatible parameters.
		$params = $this->extractRequestParams();
		$this->requireOnlyOneParameter( $params, 'id', 'target' );
		$this->requireOnlyOneParameter( $params, 'expiry', 'unblock' );
		$this->requireMaxOneParameter( $params, 'id', 'alsolocal' );

		$target = $this->getParameter( 'target' ) ?? '#' . $this->getParameter( 'id' );

		$result = $this->getResult();

		if ( $this->getParameter( 'expiry' ) ) {
			// Prevent modification of global autoblocks, as these are managed by the software.
			$globalBlockId = GlobalBlockLookup::isAGlobalBlockId( $target );
			if ( $globalBlockId ) {
				$globalBlockingDbr = $this->globalBlockingConnectionProvider->getReplicaGlobalBlockingDatabase();
				$isGlobalBlockAnAutoblock = $globalBlockingDbr->newSelectQueryBuilder()
					->select( 'gb_autoblock_parent_id' )
					->from( 'globalblocks' )
					->where( [ 'gb_id' => $globalBlockId ] )
					->caller( __METHOD__ )
					->fetchField();

				if ( $isGlobalBlockAnAutoblock ) {
					$this->dieWithError(
						'globalblocking-apierror-cannot-modify-global-autoblock',
						'cannot-modify-global-autoblock'
					);
				}
			}

			$options = [];

			if ( $this->getParameter( 'anononly' ) ) {
				$options[] = 'anon-only';
			}

			if ( $this->getParameter( 'allow-account-creation' ) ) {
				$options[] = 'allow-account-creation';
			}

			if ( $this->getParameter( 'enable-autoblock' ) ) {
				$options[] = 'enable-autoblock';
			}

			if ( $this->getParameter( 'modify' ) ) {
				$options[] = 'modify';
			}

			$status = $this->globalBlockManager->block(
				$target,
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
						'isCreateAccountBlocked' => !$this->getParameter( 'local-allow-account-creation' ),
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
				$result->addValue( 'globalblock', 'user', $target );
				$result->addValue( 'globalblock', 'blocked', '' );
				if ( $this->getParameter( 'anononly' ) ) {
					$result->addValue( 'globalblock', 'anononly', '' );
				}
				if ( $this->getParameter( 'allow-account-creation' ) ) {
					$result->addValue( 'globalblock', 'allow-account-creation', '' );
				}
				if ( $this->getParameter( 'enable-autoblock' ) ) {
					$result->addValue( 'globalblock', 'enable-autoblock', '' );
				}
				$expiry = ApiResult::formatExpiry( $this->getParameter( 'expiry' ), 'infinite' );
				$result->addValue( 'globalblock', 'expiry', $expiry );
			}
		} elseif ( $this->getParameter( 'unblock' ) ) {
			$status = $this->globalBlockManager->unblock(
				$target,
				$this->getParameter( 'reason' ),
				$this->getUser()
			);

			if ( !$status->isOK() ) {
				$this->addLegacyErrorsFromStatus( $status, $result );
			} else {
				$result->addValue( 'globalblock', 'user', $target );
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
			'id' => [
				ParamValidator::PARAM_TYPE => 'integer',
			],
			'target' => [
				ParamValidator::PARAM_TYPE => 'user',
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
			'allow-account-creation' => [
				ParamValidator::PARAM_TYPE => 'boolean',
			],
			'enable-autoblock' => [
				ParamValidator::PARAM_TYPE => 'boolean',
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
			'local-allow-account-creation' => [
				ParamValidator::PARAM_TYPE => 'boolean',
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
