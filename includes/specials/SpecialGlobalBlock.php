<?php

use MediaWiki\Block\BlockUserFactory;
use Wikimedia\IPUtils;

class SpecialGlobalBlock extends FormSpecialPage {
	/**
	 * @see SpecialGlobalBlock::setParameter()
	 * @var string|null
	 */
	protected $address;

	/**
	 * Whether there is an existing block on the target
	 * @var bool
	 */
	private $modifyForm = false;

	/** @var BlockUserFactory */
	private $blockUserFactory;

	/**
	 * @param BlockUserFactory $blockUserFactory
	 */
	public function __construct(
		BlockUserFactory $blockUserFactory
	) {
		parent::__construct( 'GlobalBlock', 'globalblock' );
		$this->blockUserFactory = $blockUserFactory;
	}

	public function doesWrites() {
		return true;
	}

	public function execute( $par ) {
		parent::execute( $par );
		$this->addHelpLink( 'Extension:GlobalBlocking' );
		$out = $this->getOutput();
		$out->setPageTitle( $this->msg( 'globalblocking-block' ) );
		$out->setSubtitle( GlobalBlocking::buildSubtitleLinks( $this ) );
	}

	/**
	 * Set subpage parameter or 'wpAddress' as $this->address
	 * @param string $par
	 */
	protected function setParameter( $par ) {
		if ( $par && !$this->getRequest()->wasPosted() ) {
			// GET request to Special:GlobalBlock/127.0.0.1
			$address = $par;
		} else {
			$address = trim( $this->getRequest()->getText( 'wpAddress' ) );
		}

		if ( IPUtils::isValidRange( $address ) ) {
			$this->address = IPUtils::sanitizeRange( $address );
		} else {
			// This catches invalid IPs too but we'll reject them at form submission.
			$this->address = IPUtils::sanitizeIP( $address );
		}
	}

	/**
	 * If there is an existing block for the target specified, change
	 * the form to a modify form and load that block's settings so that
	 * we can show it in the default form fields.
	 *
	 * @return array
	 */
	protected function loadExistingBlock() {
		$blockOptions = [];
		if ( $this->address ) {
			$dbr = GlobalBlocking::getGlobalBlockingDatabase( DB_REPLICA );
			$block = $dbr->selectRow( 'globalblocks',
					[ 'gb_anon_only', 'gb_reason', 'gb_expiry' ],
					[
						'gb_address' => $this->address,
						'gb_expiry >' . $dbr->addQuotes( $dbr->timestamp( wfTimestampNow() ) ),
					],
					__METHOD__
				);
			if ( $block ) {
				$this->modifyForm = true;

				$blockOptions['anononly'] = $block->gb_anon_only;
				$blockOptions['reason'] = $block->gb_reason;
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
		$fields = [
			'Address' => [
				'type' => 'text',
				'label-message' => 'globalblocking-ipaddress',
				'id' => 'mw-globalblock-address',
				'required' => true,
				'autofocus' => true,
				'default' => $this->address,
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
			'Modify' => [
				'type' => 'hidden',
				'default' => '',
			],
			'Previous' => [
				'type' => 'hidden',
				'default' => $this->address,
			],
		];

		// Modify form defaults if there is an existing block
		$blockOptions = $this->loadExistingBlock();
		if ( $this->modifyForm ) {
			$fields['Expiry']['default'] = $blockOptions['expiry'];
			$fields['Reason']['default'] = $blockOptions['reason'];
			$fields['AnonOnly']['default'] = $blockOptions['anononly'];
			if ( $this->getRequest()->getVal( 'Previous' ) !== $this->address ) {
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
		}

		return $fields;
	}

	/**
	 * @param HTMLForm $form
	 */
	protected function alterForm( HTMLForm $form ) {
		$form->addPreText( $this->msg( 'globalblocking-block-intro' )->parseAsBlock() );

		if ( $this->modifyForm && !$this->getRequest()->wasPosted() ) {
			// For GET requests with target field prefilled, tell the user that it's already blocked
			// (For POST requests, this will be shown to the user as an actual error in HTMLForm)
			$msg = $this->msg( 'globalblocking-block-alreadyblocked', $this->address )->parseAsBlock();
			$form->addHeaderText( Html::rawElement( 'div', [ 'class' => 'error' ], $msg ) );
		}

		$submitMsg = $this->modifyForm ? 'globalblocking-modify-submit' : 'globalblocking-block-submit';
		$form->setSubmitTextMsg( $submitMsg );
		$form->setSubmitDestructive();
		$form->setWrapperLegendMsg( 'globalblocking-block-legend' );
	}

	/**
	 * Show log of previous global blocks below the form
	 * @return string
	 */
	protected function postText() {
		$out = '';
		$title = Title::makeTitleSafe( NS_USER, $this->address );
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

	/**
	 * @param array $data
	 * @return true|array True for success, array on errors
	 */
	public function onSubmit( array $data ) {
		$options = [];
		$user = $this->getUser();

		if ( $data['AnonOnly'] ) {
			$options[] = 'anon-only';
		}

		if ( $this->modifyForm && $data['Modify']
			// Make sure that the block being modified is for the intended target
			// (i.e., not from a previous submission)
			&& $data['Previous'] === $data['Address']
		) {
			$options[] = 'modify';
		}

		// This handles validation too...
		$errors = GlobalBlocking::block(
			$this->address, // $this->address is sanitized; $data['Address'] isn't
			$data['Reason'][0],
			$data['Expiry'],
			$user,
			$options
		);

		if ( count( $errors ) ) {
			// Show the error message(s) to the user if an error occurred.
			return $errors;
		}

		// Add a local block if the user asked for that
		if ( $user->isAllowed( 'block' ) && $data['AlsoLocal'] ) {
			$status = $this->blockUserFactory->newBlockUser(
				$this->address,
				$user,
				$data['Expiry'],
				$data['Reason'][0],
				[
					'isCreateAccountBlocked' => true,
					'isEmailBlocked' => true,
					'isUserTalkEditBlocked' => $data['AlsoLocalTalk'],
					'isHardBlock' => !$data['AnonOnly'],
					'isAutoblocking' => true,
				]
			)->placeBlock( $data['Modify'] );

			if ( !$status->isOK() ) {
				$this->getOutput()->addWikiMsg( 'globalblocking-local-failed' );
			}
		}

		return true;
	}

	public function onSuccess() {
		$successMsg = $this->modifyForm ?
			'globalblocking-modify-success' : 'globalblocking-block-success';
		$this->getOutput()->addWikiMsg( $successMsg, $this->address );

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

				list( $show, $value ) = explode( ':', $option );
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
}
