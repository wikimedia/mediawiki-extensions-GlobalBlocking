<?php

namespace MediaWiki\Extension\GlobalBlocking\Special;

use LogEventsList;
use MediaWiki\Block\BlockUserFactory;
use MediaWiki\Block\BlockUtils;
use MediaWiki\CommentStore\CommentStore;
use MediaWiki\Extension\GlobalBlocking\Services\GlobalBlockingConnectionProvider;
use MediaWiki\Extension\GlobalBlocking\Services\GlobalBlockingLinkBuilder;
use MediaWiki\Extension\GlobalBlocking\Services\GlobalBlockManager;
use MediaWiki\Html\Html;
use MediaWiki\HTMLForm\HTMLForm;
use MediaWiki\Message\Message;
use MediaWiki\SpecialPage\FormSpecialPage;
use MediaWiki\Status\Status;
use MediaWiki\Title\Title;
use MediaWiki\User\CentralId\CentralIdLookup;
use MediaWiki\User\UserIdentity;
use MediaWiki\User\UserNameUtils;
use Wikimedia\IPUtils;

class SpecialGlobalBlock extends FormSpecialPage {
	/**
	 * @see SpecialGlobalBlock::setParameter()
	 * @var string|null
	 */
	protected ?string $target;

	/**
	 * @var bool Whether there is an existing block on the target
	 */
	private bool $modifyForm = false;

	private BlockUserFactory $blockUserFactory;
	private BlockUtils $blockUtils;
	private GlobalBlockingConnectionProvider $globalBlockingConnectionProvider;
	private GlobalBlockManager $globalBlockManager;
	private GlobalBlockingLinkBuilder $globalBlockingLinkBuilder;
	private CentralIdLookup $centralIdLookup;
	private UserNameUtils $userNameUtils;

	/**
	 * @param BlockUserFactory $blockUserFactory
	 * @param BlockUtils $blockUtils
	 * @param GlobalBlockingConnectionProvider $globalBlockingConnectionProvider
	 * @param GlobalBlockManager $globalBlockManager
	 * @param GlobalBlockingLinkBuilder $globalBlockingLinkBuilder
	 * @param CentralIdLookup $centralIdLookup
	 * @param UserNameUtils $userNameUtils
	 */
	public function __construct(
		BlockUserFactory $blockUserFactory,
		BlockUtils $blockUtils,
		GlobalBlockingConnectionProvider $globalBlockingConnectionProvider,
		GlobalBlockManager $globalBlockManager,
		GlobalBlockingLinkBuilder $globalBlockingLinkBuilder,
		CentralIdLookup $centralIdLookup,
		UserNameUtils $userNameUtils
	) {
		parent::__construct( 'GlobalBlock', 'globalblock' );
		$this->blockUserFactory = $blockUserFactory;
		$this->blockUtils = $blockUtils;
		$this->globalBlockingConnectionProvider = $globalBlockingConnectionProvider;
		$this->globalBlockManager = $globalBlockManager;
		$this->globalBlockingLinkBuilder = $globalBlockingLinkBuilder;
		$this->centralIdLookup = $centralIdLookup;
		$this->userNameUtils = $userNameUtils;
	}

	public function doesWrites() {
		return true;
	}

	public function execute( $par ) {
		parent::execute( $par );
		$this->addHelpLink( 'Extension:GlobalBlocking' );
		$out = $this->getOutput();
		$out->addModules( 'ext.globalBlocking' );
		$out->setSubtitle( $this->globalBlockingLinkBuilder->buildSubtitleLinks( $this ) );
	}

	/**
	 * Set subpage parameter or 'wpAddress' as $this->address
	 * @param string $par
	 */
	protected function setParameter( $par ) {
		if ( $par && !$this->getRequest()->wasPosted() ) {
			// GET request to Special:GlobalBlock/target where 'target' can be an IP, range, or username.
			$target = $par;
		} else {
			$target = trim( $this->getRequest()->getText( 'wpAddress' ) );
		}

		if ( IPUtils::isValidRange( $target ) ) {
			$this->target = IPUtils::sanitizeRange( $target );
		} elseif ( IPUtils::isIPAddress( $target ) ) {
			$this->target = IPUtils::sanitizeIP( $target );
		} else {
			$normalisedTarget = $this->userNameUtils->getCanonical( $target );
			if ( $normalisedTarget ) {
				$this->target = $normalisedTarget;
			} else {
				// Allow invalid targets to be set, so that the user can be shown an error message.
				$this->target = $target;
			}
		}

		[ $targetForSkin ] = $this->blockUtils->parseBlockTarget( $target );

		if ( $targetForSkin instanceof UserIdentity ) {
			$this->getSkin()->setRelevantUser( $targetForSkin );
		}
	}

	/**
	 * If there is an existing block for the target specified, change
	 * the form to a modify form and load that block's settings so that
	 * we can show it in the default form fields.
	 *
	 * @return array
	 */
	protected function loadExistingBlock(): array {
		$blockOptions = [];
		if ( $this->target ) {
			$dbr = $this->globalBlockingConnectionProvider->getReplicaGlobalBlockingDatabase();
			$queryBuilder = $dbr->newSelectQueryBuilder()
				->select( [ 'gb_anon_only', 'gb_reason', 'gb_expiry', 'gb_create_account', 'gb_enable_autoblock' ] )
				->from( 'globalblocks' );
			if ( IPUtils::isIPAddress( $this->target ) ) {
				// Exclude global autoblocks from the lookup to avoid exposing the IP address being autoblocked.
				$queryBuilder->where( [
					'gb_address' => $this->target, 'gb_autoblock_parent_id' => 0,
				] );
			} else {
				$centralId = $this->centralIdLookup->centralIdFromName( $this->target );
				if ( !$centralId ) {
					return [];
				}
				$queryBuilder->where( [ 'gb_target_central_id' => $centralId ] );
			}
			$block = $queryBuilder
				->andWhere( $dbr->expr( 'gb_expiry', '>', $dbr->timestamp() ) )
				->caller( __METHOD__ )
				->fetchRow();
			if ( $block ) {
				$this->modifyForm = true;

				$blockOptions['anononly'] = $block->gb_anon_only;
				$blockOptions['createAccount'] = $block->gb_create_account;
				$blockOptions['reason'] = $block->gb_reason;
				$blockOptions['enableAutoblock'] = $block->gb_enable_autoblock;
				$blockOptions['expiry'] = ( $block->gb_expiry === 'infinity' )
					? 'indefinite'
					: wfTimestamp( TS_ISO_8601, $block->gb_expiry );
			}
		}

		return $blockOptions;
	}

	/**
	 * @return array
	 */
	protected function getFormFields() {
		$getExpiry = self::buildExpirySelector();
		$targetIsAnAccount = $this->target && !IPUtils::isIPAddress( $this->target );
		$fields = [
			'Address' => [
				'type' => 'user',
				'ipallowed' => true,
				'iprange' => true,
				'label-message' => 'globalblocking-target',
				'id' => 'mw-globalblock-target',
				'required' => true,
				'autofocus' => true,
				'default' => $this->target,
			],
			'Expiry' => [
				'type' => count( $getExpiry ) ? 'selectorother' : 'text',
				'label-message' => 'globalblocking-block-expiry',
				'id' => 'mw-globalblocking-block-expiry-selector',
				'required' => true,
				'options' => $getExpiry,
				'other' => $this->msg( 'globalblocking-block-expiry-selector-other' )->text(),
			],
			'Reason' => [
				'type' => 'selectandother',
				'maxlength' => CommentStore::COMMENT_CHARACTER_LIMIT,
				'label-message' => 'globalblocking-block-reason',
				'id' => 'mw-globalblock-reason',
				'options-message' => 'globalblocking-block-reason-dropdown',
			],
			'AnonOnly' => [
				'type' => 'check',
				'label-message' => 'globalblocking-ipbanononly',
				'id' => 'mw-globalblock-anon-only',
			],
			'CreateAccount' => [
				'type' => 'check',
				'id' => 'mw-globalblock-disable-account-creation',
				'label-message' => 'globalblocking-block-disable-account-creation',
				'default' => true,
			],
			'AutoBlock' => [
				'type' => 'check',
				'label-message' => [
					'globalblocking-block-enable-autoblock',
					Message::numParam( $this->getConfig()->get( 'GlobalBlockingMaximumIPsToRetroactivelyAutoblock' ) ),
					Message::durationParam( $this->getConfig()->get( 'GlobalBlockingAutoblockExpiry' ) ),
				],
				'id' => 'mw-globalblock-enable-autoblock',
				'default' => true,
			],
			'Modify' => [
				'type' => 'hidden',
				'default' => '',
			],
			'Previous' => [
				'type' => 'hidden',
				'default' => $this->target,
			],
		];

		// Modify form defaults if there is an existing block
		$blockOptions = $this->loadExistingBlock();
		if ( $this->modifyForm ) {
			$fields['Expiry']['default'] = $blockOptions['expiry'];
			$fields['Reason']['default'] = $blockOptions['reason'];
			$fields['AnonOnly']['default'] = $blockOptions['anononly'];
			$fields['CreateAccount']['default'] = $blockOptions['createAccount'];
			$fields['AutoBlock']['default'] = $blockOptions['enableAutoblock'];
			if ( $this->getRequest()->getVal( 'Previous' ) !== $this->target ) {
				// Let the user know about it and re-submit to modify
				$fields['Modify']['default'] = 1;
			}
		}

		if ( $this->getUser()->isAllowed( 'block' ) ) {
			$fields['AlsoLocal'] = [
				'type' => 'check',
				'label-message' => 'globalblocking-also-local',
				'id' => 'mw-globalblock-local',
			];
			$fields['AlsoLocalTalk'] = [
				'type' => 'check',
				'label-message' => 'globalblocking-also-local-talk',
				'id' => 'mw-globalblock-local-talk',
				'hide-if' => [ '!==', 'AlsoLocal', '1' ],
			];
			$fields['AlsoLocalEmail'] = [
				'type' => 'check',
				'label-message' => 'globalblocking-also-local-email',
				'id' => 'mw-globalblock-local-email',
				'hide-if' => [ '!==', 'AlsoLocal', '1' ],
			];

			$fields['AlsoLocalSoft'] = [
				'type' => 'check',
				'label-message' => 'globalblocking-also-local-soft',
				'id' => 'mw-globalblock-local-soft',
				'hide-if' => [ '!==', 'AlsoLocal', '1' ],
				'default' => !$targetIsAnAccount,
			];

			$fields['AlsoLocalAccountCreation'] = [
				'type' => 'check',
				'label-message' => 'globalblocking-also-local-disable-account-creation',
				'id' => 'mw-globalblock-local-disable-account-creation',
				'hide-if' => [ '!==', 'AlsoLocal', '1' ],
				'default' => true,
			];
		}

		return $fields;
	}

	/**
	 * @param HTMLForm $form
	 */
	protected function alterForm( HTMLForm $form ) {
		$form->addPreHtml( $this->msg( 'globalblocking-block-intro' )->parseAsBlock() );

		if ( $this->modifyForm && !$this->getRequest()->wasPosted() ) {
			// For GET requests with target field prefilled, tell the user that it's already blocked
			// (For POST requests, this will be shown to the user as an actual error in HTMLForm)
			$msg = $this->msg( 'globalblocking-block-alreadyblocked', $this->target )->parseAsBlock();
			$form->addHeaderHtml( Html::rawElement( 'div', [ 'class' => 'error' ], $msg ) );
		}

		$form->setSubmitTextMsg(
			$this->modifyForm ? 'globalblocking-modify-submit' : 'globalblocking-block-submit'
		);
		$form->setSubmitDestructive();
		$form->setWrapperLegendMsg( 'globalblocking-block-legend' );
	}

	/**
	 * Show log of previous global blocks below the form
	 * @return string
	 */
	protected function postHtml() {
		$out = '';
		$title = Title::makeTitleSafe( NS_USER, $this->target );
		if ( $title ) {
			LogEventsList::showLogExtract(
				$out,
				'gblblock',
				$title->getPrefixedText(),
				'',
				[
					'lim' => 10,
					'msgKey' => 'globalblocking-showlog',
					'showIfEmpty' => false
				]
			);
		}
		return $out;
	}

	/** @inheritDoc */
	public function onSubmit( array $data ) {
		$options = [];
		$performer = $this->getUser();

		if ( $data['AnonOnly'] ) {
			$options[] = 'anon-only';
		}

		if ( !$data['CreateAccount'] ) {
			$options[] = 'allow-account-creation';
		}

		if ( $data['AutoBlock'] ) {
			$options[] = 'enable-autoblock';
		}

		if ( $this->modifyForm && $data['Modify']
			// Make sure that the block being modified is for the intended target
			// (i.e., not from a previous submission)
			&& $data['Previous'] === $data['Address']
		) {
			$options[] = 'modify';
		}

		// This handles validation too...
		$globalBlockStatus = $this->globalBlockManager->block(
			$this->target, // $this->target is sanitized; $data['Address'] isn't
			$data['Reason'][0],
			$data['Expiry'],
			$performer,
			$options
		);

		if ( !$globalBlockStatus->isOK() ) {
			// Show the error message(s) to the user if an error occurred.
			return Status::wrap( $globalBlockStatus );
		}

		// Add a local block if the user asked for that
		if ( $performer->isAllowed( 'block' ) && $data['AlsoLocal'] ) {
			$localBlockStatus = $this->blockUserFactory->newBlockUser(
				$this->target,
				$performer,
				$data['Expiry'],
				$data['Reason'][0],
				[
					'isCreateAccountBlocked' => $data['AlsoLocalAccountCreation'],
					'isEmailBlocked' => $data['AlsoLocalEmail'],
					'isUserTalkEditBlocked' => $data['AlsoLocalTalk'],
					'isHardBlock' => !$data['AlsoLocalSoft'],
					'isAutoblocking' => true,
				]
			)->placeBlock( $data['Modify'] );

			if ( !$localBlockStatus->isOK() ) {
				$this->getOutput()->addWikiMsg( 'globalblocking-local-failed' );
			}
		}

		return Status::newGood();
	}

	public function onSuccess() {
		$successMsg = $this->modifyForm ?
			'globalblocking-modify-success' : 'globalblocking-block-success';
		// The username must be escaped here, as it's user input and could contain wikitext.
		$this->getOutput()->addHTML(
			$this->msg( $successMsg )->plaintextParams( $this->target )->parseAsBlock()
		);

		$link = $this->getLinkRenderer()->makeKnownLink(
			$this->getPageTitle(),
			$this->msg( 'globalblocking-add-block' )->text()
		);
		$this->getOutput()->addHTML( $link );
	}

	/**
	 * Get an array of suggested block durations. Retrieved from
	 * 'globalblocking-expiry-options' and if it's disabled (default),
	 * retrieve it from SpecialBlock's 'ipboptions' message.
	 *
	 * @return array Expiry options, empty if messages are disabled.
	 * @see SpecialBlock::getSuggestedDurations()
	 */
	protected function buildExpirySelector() {
		$msg = $this->msg( 'globalblocking-expiry-options' )->inContentLanguage();
		if ( $msg->isDisabled() ) {
			$msg = $this->msg( 'ipboptions' )->inContentLanguage();
			if ( $msg->isDisabled() ) {
				// Do not assume that 'ipboptions' exists forever.
				$msg = false;
			}
		}

		$options = [];
		if ( $msg !== false ) {
			$msg = $msg->text();
			foreach ( explode( ',', $msg ) as $option ) {
				if ( strpos( $option, ':' ) === false ) {
					$option = "$option:$option";
				}

				[ $show, $value ] = explode( ':', $option );
				$options[$show] = $value;
			}
		}
		return $options;
	}

	protected function getGroupName() {
		return 'users';
	}

	protected function getDisplayFormat() {
		return 'ooui';
	}

	public function getDescription() {
		return $this->msg( 'globalblocking-block' );
	}
}
