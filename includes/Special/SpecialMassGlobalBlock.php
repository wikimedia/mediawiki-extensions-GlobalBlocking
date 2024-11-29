<?php

namespace MediaWiki\Extension\GlobalBlocking\Special;

use MediaWiki\Block\BlockUserFactory;
use MediaWiki\CommentStore\CommentStore;
use MediaWiki\Extension\GlobalBlocking\Services\GlobalBlockingConnectionProvider;
use MediaWiki\Extension\GlobalBlocking\Services\GlobalBlockingExpirySelectorBuilder;
use MediaWiki\Extension\GlobalBlocking\Services\GlobalBlockingGlobalBlockDetailsRenderer;
use MediaWiki\Extension\GlobalBlocking\Services\GlobalBlockingLinkBuilder;
use MediaWiki\Extension\GlobalBlocking\Services\GlobalBlockLookup;
use MediaWiki\Extension\GlobalBlocking\Services\GlobalBlockManager;
use MediaWiki\Html\Html;
use MediaWiki\HTMLForm\HTMLForm;
use MediaWiki\Linker\Linker;
use MediaWiki\Message\Message;
use MediaWiki\Permissions\PermissionManager;
use MediaWiki\SpecialPage\SpecialPage;
use MediaWiki\User\UserIdentityLookup;
use UserBlockedError;
use Wikimedia\IPUtils;
use Wikimedia\ScopedCallback;

class SpecialMassGlobalBlock extends SpecialPage {

	private array $targetsForLookup;
	private array $targets;

	private BlockUserFactory $blockUserFactory;
	private PermissionManager $permissionManager;
	private UserIdentityLookup $userIdentityLookup;
	private GlobalBlockManager $globalBlockManager;
	private GlobalBlockLookup $globalBlockLookup;
	private GlobalBlockingLinkBuilder $globalBlockingLinkBuilder;
	private GlobalBlockingGlobalBlockDetailsRenderer $globalBlockDetailsRenderer;
	private GlobalBlockingConnectionProvider $globalBlockingConnectionProvider;
	private GlobalBlockingExpirySelectorBuilder $globalBlockingExpirySelectorBuilder;

	public function __construct(
		BlockUserFactory $blockUserFactory,
		PermissionManager $permissionManager,
		UserIdentityLookup $userIdentityLookup,
		GlobalBlockManager $globalBlockManager,
		GlobalBlockLookup $globalBlockLookup,
		GlobalBlockingLinkBuilder $globalBlockingLinkBuilder,
		GlobalBlockingGlobalBlockDetailsRenderer $globalBlockDetailsRenderer,
		GlobalBlockingConnectionProvider $globalBlockingConnectionProvider,
		GlobalBlockingExpirySelectorBuilder $globalBlockingExpirySelectorBuilder
	) {
		parent::__construct( 'MassGlobalBlock', 'globalblock' );
		$this->blockUserFactory = $blockUserFactory;
		$this->permissionManager = $permissionManager;
		$this->userIdentityLookup = $userIdentityLookup;
		$this->globalBlockManager = $globalBlockManager;
		$this->globalBlockLookup = $globalBlockLookup;
		$this->globalBlockingLinkBuilder = $globalBlockingLinkBuilder;
		$this->globalBlockDetailsRenderer = $globalBlockDetailsRenderer;
		$this->globalBlockingConnectionProvider = $globalBlockingConnectionProvider;
		$this->globalBlockingExpirySelectorBuilder = $globalBlockingExpirySelectorBuilder;
	}

	/**
	 * @inheritDoc
	 * @codeCoverageIgnore Merely declarative
	 */
	public function doesWrites(): bool {
		return true;
	}

	/** @inheritDoc */
	public function execute( $subPage ) {
		$this->checkReadOnly();
		parent::execute( $subPage );

		// Don't allow sitewide blocked users to use the form, to be consistent with Special:GlobalBlock.
		$block = $this->getUser()->getBlock();
		if ( $block && $block->isSitewide() ) {
			throw new UserBlockedError( $block );
		}

		$this->addHelpLink( 'Extension:GlobalBlocking' );
		$out = $this->getOutput();
		$out->addModules( 'ext.globalBlocking' );
		$out->addModuleStyles( 'ext.globalBlocking.styles' );
		$out->setSubtitle( $this->globalBlockingLinkBuilder->buildSubtitleLinks( $this ) );

		$request = $this->getRequest();

		// Parse the list of targets provided and store them for later use in ::createTable.
		$targetsAsString = $request->getText( 'wpTargets' );
		if ( $targetsAsString ) {
			// We want to remove empty strings and duplicates, to avoid globally blocking the same user twice.
			$targets = explode( "\n", $targetsAsString );
			$targets = array_map( 'trim', $targets );
			$this->targetsForLookup = array_unique( array_filter( $targets ) );
		} else {
			$this->targetsForLookup = [];
		}

		$this->targets = $request->getArray( 'wpActionTarget', [] );

		$blockFormAlsoShown = false;
		if ( $request->wasPosted() ) {
			$method = $request->getRawVal( 'wpMethod', '' );
			if ( $method === 'search' && count( $this->targetsForLookup ) ) {
				$this->showBlockForm();
				$blockFormAlsoShown = true;
			} elseif ( $method === 'block' ) {
				$this->performGlobalBlockChanges();
				$this->showBlockForm();
				$blockFormAlsoShown = true;
			}
		}

		$this->showSearchForm( $blockFormAlsoShown );
	}

	/**
	 * Adds to the output the form that allows a user to search for targets to globally block or unblock.
	 *
	 * @return void
	 */
	private function showSearchForm( bool $blockFormAlsoShown ) {
		// The submit button is not the primary button if the block form is also shown.
		$submitFlags = [ 'progressive' ];
		if ( !$blockFormAlsoShown ) {
			$submitFlags[] = 'primary';
		}

		$fields = [
			'Targets' => [
				'type' => 'textarea',
				'dir' => 'ltr',
				'rows' => 20,
				'id' => 'mw-globalblock-addresslist',
				'required' => true,
				'autofocus' => true,
				'placeholder' => $this->msg( 'globalblocking-mass-block-query-placeholder' )->text(),
				'default' => $this->getRequest()->getText( 'wpTargets' ),
			],
			'Method' => [
				'type' => 'hidden',
				'default' => 'search',
			],
			'Submit' => [
				'type' => 'submit',
				'buttonlabel-message' => 'globalblocking-mass-block-query-submit',
				'id' => 'mw-globalblocking-mass-block-query-submit',
				'name' => 'mw-globalblocking-mass-block-query-submit',
				'flags' => $submitFlags,
			],
		];

		HTMLForm::factory( 'ooui', $fields, $this->getContext() )
			->setWrapperLegendMsg( 'globalblocking-mass-block-query-legend' )
			->setId( 'mw-globalblocking-mass-block-query' )
			->suppressDefaultSubmit()
			->setFormIdentifier( 'mw-globalblocking-mass-block-query' )
			->prepareForm()
			->displayForm( false );
	}

	/**
	 * Adds to the output the form that allows a user to perform the mass global blocks or unblocks.
	 *
	 * @return void
	 */
	private function showBlockForm() {
		$getExpiry = $this->globalBlockingExpirySelectorBuilder->buildExpirySelector( $this->getContext() );
		$fields = [
			'Action' => [
				'type' => 'radio',
				'default' => 'block',
				'options-messages' => [
					'globalblocking-mass-block-block' => 'block',
					'globalblocking-mass-block-unblock' => 'unblock',
				]
			],
			'Expiry' => [
				'type' => count( $getExpiry ) ? 'selectorother' : 'text',
				'label-message' => 'globalblocking-block-expiry',
				'id' => 'mw-globalblocking-mass-block-expiry-selector',
				'required' => false,
				'options' => $getExpiry,
				'other' => $this->msg( 'globalblocking-block-expiry-selector-other' )->text(),
				'hide-if' => [ '!==', 'Action', 'block' ],
			],
			'Reason' => [
				'type' => 'selectandother',
				'maxlength' => CommentStore::COMMENT_CHARACTER_LIMIT,
				'label-message' => 'globalblocking-block-reason',
				'id' => 'mw-globalblocking-mass-block-reason',
				'options-message' => 'globalblocking-block-reason-dropdown',
			],
			'AnonOnly' => [
				'type' => 'check',
				'label-message' => 'globalblocking-ipbanononly',
				'id' => 'mw-globalblocking-mass-block-anon-only',
				'hide-if' => [ '!==', 'Action', 'block' ],
			],
			'CreateAccount' => [
				'type' => 'check',
				'id' => 'mw-globalblocking-mass-block-disable-account-creation',
				'label-message' => 'globalblocking-block-disable-account-creation',
				'default' => true,
				'hide-if' => [ '!==', 'Action', 'block' ],
			],
			'AutoBlock' => [
				'type' => 'check',
				'label-message' => [
					'globalblocking-block-enable-autoblock',
					Message::numParam( $this->getConfig()->get( 'GlobalBlockingMaximumIPsToRetroactivelyAutoblock' ) ),
					Message::durationParam( $this->getConfig()->get( 'GlobalBlockingAutoblockExpiry' ) ),
				],
				'id' => 'mw-globalblocking-mass-block-enable-autoblock',
				'default' => true,
				'hide-if' => [ '!==', 'Action', 'block' ],
			],
		];
		if ( $this->getUser()->isAllowed( 'block' ) ) {
			$fields += [
				'AlsoLocal' => [
					'type' => 'check',
					'label-message' => 'globalblocking-also-local',
					'id' => 'mw-globalblocking-mass-block-local',
					'hide-if' => [ '!==', 'Action', 'block' ],
				],
				'AlsoLocalTalk' => [
					'type' => 'check',
					'label-message' => 'globalblocking-also-local-talk',
					'id' => 'mw-globalblocking-mass-block-local-talk',
					'hide-if' => [ 'OR', [ '!==', 'Action', 'block' ], [ '!==', 'AlsoLocal', '1' ] ],
				],
				'AlsoLocalEmail' => [
					'type' => 'check',
					'label-message' => 'globalblocking-also-local-email',
					'id' => 'mw-globalblocking-mass-block-local-email',
					'hide-if' => [ 'OR', [ '!==', 'Action', 'block' ], [ '!==', 'AlsoLocal', '1' ] ],
				],
				'AlsoLocalSoft' => [
					'type' => 'check',
					'label-message' => 'globalblocking-also-local-soft',
					'id' => 'mw-globalblocking-mass-block-local-soft',
					'hide-if' => [ 'OR', [ '!==', 'Action', 'block' ], [ '!==', 'AlsoLocal', '1' ] ],
					'default' => true,
				],
				'AlsoLocalAccountCreation' => [
					'type' => 'check',
					'label-message' => 'globalblocking-also-local-disable-account-creation',
					'id' => 'mw-globalblocking-mass-block-local-disable-account-creation',
					'hide-if' => [ '!==', 'AlsoLocal', '1' ],
					'default' => true,
				]
			];
		}
		$fields += [
			'MarkBot' => [
				'type' => 'check',
				'label-message' => 'globalblocking-mass-block-bot',
				'id' => 'mw-globalblocking-mass-block-bot',
			],
			'Method' => [
				'type' => 'hidden',
				'default' => 'block',
			],
			'Targets' => [
				'type' => 'hidden',
				'default' => implode( "\n", $this->targetsForLookup ),
			],
		];

		$form = HTMLForm::factory( 'ooui', $fields, $this->getContext() );
		$form->setMethod()
			->setWrapperLegendMsg( 'globalblocking-mass-block-legend' )
			->setId( 'mw-globalblocking-mass-block-form' )
			->setSubmitID( 'mw-globalblocking-mass-block-submit' )
			->setSubmitName( 'mw-globalblocking-mass-block-submit' )
			->setSubmitDestructive()
			->setFormIdentifier( 'mw-globalblocking-mass-block' )
			->addHeaderHtml( $this->createTable() )
			->prepareForm()
			->displayForm( false );
	}

	/**
	 * Creates the HTML of the table that shows information about the IPs, IP ranges, or accounts that the
	 * user may choose to globally block or unblock.
	 *
	 * @return string HTML element of the table
	 */
	private function createTable(): string {
		$out = $this->getOutput();
		$out->addModuleStyles( 'jquery.tablesorter.styles' );
		$out->addModules( 'jquery.tablesorter' );

		$tableHeadings = [
			Html::element( 'th', [ 'class' => 'unsortable' ] ),
			Html::element( 'th', [], $this->msg( 'globalblocking-list-table-heading-target' )->text() ),
			Html::element( 'th', [], $this->msg( 'globalblocking-mass-block-header-status' )->text() ),
			Html::element( 'th', [], $this->msg( 'globalblocking-list-table-heading-expiry' )->text() ),
			Html::element( 'th', [], $this->msg( 'globalblocking-list-table-heading-params' )->text() ),
		];
		$tableHeaderHtml = Html::rawElement(
			'thead', [],
			Html::rawElement( 'tr', [], implode( "\n", $tableHeadings ) )
		);

		$tableRows = [];
		foreach ( $this->targetsForLookup as $target ) {
			$tableRows[] = $this->createTableRow( $target );
		}
		$tableBodyHtml = Html::rawElement( 'tbody', [], implode( "\n", $tableRows ) );

		return Html::rawElement(
			'table',
			[ 'class' => 'wikitable sortable', 'id' => 'mw-globalblocking-mass-block-table' ],
			$tableHeaderHtml . $tableBodyHtml
		);
	}

	/**
	 * Creates each row of IPs, with the info on whether it's blocked,
	 * and the block info if relevant.
	 *
	 * @param string $target Target which has not yet been validated.
	 * @return string HTML element of the row
	 */
	private function createTableRow( string $target ): string {
		$targetValidationStatus = $this->globalBlockManager->validateGlobalBlockTarget( $target, $this->getUser() );
		if ( !$targetValidationStatus->isGood() ) {
			return Html::rawElement(
				'tr', [],
				Html::element(
					'td', [ 'colspan' => 5, 'data-mw-globalblocking-target' => $target ],
					$this->msg( $targetValidationStatus->getMessages()[0] )->text()
				)
			);
		}

		$targetData = $targetValidationStatus->getValue();

		$globalBlockId = GlobalBlockLookup::isAGlobalBlockId( $target );
		if ( !$globalBlockId ) {
			$globalBlockId = $this->globalBlockLookup->getGlobalBlockId( $targetData['targetForLookup'] );
		}

		$dbr = $this->globalBlockingConnectionProvider->getReplicaGlobalBlockingDatabase();
		$globalBlockRow = $dbr->newSelectQueryBuilder()
			->select( GlobalBlockLookup::selectFields() )
			->from( 'globalblocks' )
			->where( [ 'gb_id' => $globalBlockId ] )
			->caller( __METHOD__ )
			->fetchRow();

		// Make the checkbox checked if wpActionTargets has no items (which means on the first load
		// all users will be selected), or select the targets which are in wpActionTargets to persist
		// the checked state of the targets between attempts to submit.
		$shouldTargetCheckboxBeChecked = !count( $this->targets ) ||
			in_array( $targetData['targetForDisplay'], $this->targets );

		$checkboxAttribs = [];
		if ( $shouldTargetCheckboxBeChecked ) {
			$checkboxAttribs['checked'] = 'checked';
		}

		$row = [
			Html::rawElement(
				'td', [],
				Html::input( "wpActionTarget[]", $target, 'checkbox', $checkboxAttribs )
			),
			Html::rawElement( 'td', [], $this->buildTargetForDisplay( $targetData['targetForDisplay'] ) ),
		];

		if ( $globalBlockRow ) {
			$row[] = Html::rawElement(
				'td', [],
				$this->getLinkRenderer()->makeKnownLink(
					SpecialPage::getTitleFor( 'GlobalBlockList' ),
					$this->msg( 'globalblocking-mass-block-blocked' )->text(),
					[],
					[ 'target' => "#{$globalBlockRow->gb_id}" ],
				)
			);
			$row[] = Html::rawElement( 'td', [], $this->getLanguage()->formatExpiry( $globalBlockRow->gb_expiry ) );

			$options = $this->globalBlockDetailsRenderer->getBlockOptionsForDisplay(
				$globalBlockRow, $this->getContext()
			);
			$optionsAsText = '';
			if ( count( $options ) ) {
				$optionsAsText = $this->getLanguage()->commaList( $options );
			}
			$row[] = Html::rawElement( 'td', [], $optionsAsText );
		} else {
			$row[] = Html::element( 'td', [], $this->msg( 'globalblocking-mass-block-not-blocked' )->text() );
			$row[] = Html::element( 'td' );
			// @phan-suppress-next-line PhanPluginDuplicateAdjacentStatement
			$row[] = Html::element( 'td' );
		}

		return Html::rawElement(
			'tr',
			[ 'data-mw-globalblocking-target' => $targetData['targetForDisplay'] ],
			implode( "\n", $row )
		);
	}

	/**
	 * Builds the HTML used in the 'Target' column for a given $target username.
	 *
	 * @param string $target Can be an IP, IP range, username, or global block ID.
	 * @return string HTML
	 */
	private function buildTargetForDisplay( string $target ): string {
		$globalBlockId = GlobalBlockLookup::isAGlobalBlockId( $target );
		if ( $globalBlockId ) {
			return $this->msg( 'globalblocking-global-block-id', $globalBlockId )->text();
		}

		$targetUserIdentity = $this->userIdentityLookup->getUserIdentityByName( $target );
		if ( $targetUserIdentity ) {
			$targetUserId = $targetUserIdentity->getId();
		} else {
			$targetUserId = 0;
		}
		return Linker::userLink( $targetUserId, $target );
	}

	/**
	 * Actually performs the mass global block or unblock on the selected targets.
	 *
	 * @return void
	 */
	private function performGlobalBlockChanges() {
		// Check that the CSRF token is as expected.
		if ( !$this->getContext()->getCsrfTokenSet()->matchTokenField() ) {
			$this->getOutput()->addHTML(
				Html::errorBox( $this->msg( 'globalblocking-mass-block-token-mismatch' )->parse() )
			);
			return;
		}

		// If there are no targets to block, then return as we have nothing to do here.
		if ( !count( $this->targets ) ) {
			return;
		}

		// If we are blocking or unblocking too many targets at once, then show an error.
		$request = $this->getRequest();
		$action = $request->getRawVal( 'wpAction', 'block' );

		$maxTargets = $this->getConfig()->get( 'GlobalBlockingMassGlobalBlockMaxTargets' );
		if ( $maxTargets < count( $this->targets ) ) {
			// Generates:
			// * globalblocking-mass-block-too-many-targets-to-block
			// * globalblocking-mass-block-too-many-targets-to-unblock
			$msgKey = 'globalblocking-mass-block-too-many-targets-';
			if ( $action === 'block' ) {
				$msgKey .= 'to-block';
			} else {
				$msgKey .= 'to-unblock';
			}
			$this->getOutput()->addHTML( Html::errorBox( $this->msg( $msgKey, $maxTargets )->parse() ) );
			return;
		}

		$performer = $this->getUser();

		$globalSuccess = [];
		$globalFailure = [];
		$localSuccess = [];
		$localFailure = [];

		// Make the user have the 'bot' right for the duration of this method if wpMarkBot is set.
		if ( $request->getCheck( 'wpMarkBot' ) ) {
			$temporaryUserRightsScope = $this->permissionManager->addTemporaryUserRights( $performer, 'bot' );
		}

		// Construct the reason to use for the global blocks, unblocks, and/or local blocks
		$reason = $request->getText( 'wpReason' );
		$reasonDetail = $request->getText( 'wpReason-other' );

		if ( $reason === 'other' ) {
			$reason = $reasonDetail;
		} elseif ( $reasonDetail ) {
			$reason .= $this->msg( 'colon-separator' )->inContentLanguage()->text() . $reasonDetail;
		}

		if ( $action === 'block' ) {
			foreach ( $this->targets as $target ) {
				// Validate that the target is valid for a global block. This also gets the type of target if
				// this succeeds.
				$targetDataStatus = $this->globalBlockManager->validateGlobalBlockTarget( $target, $performer );
				if ( !$targetDataStatus->isGood() ) {
					$globalFailure[] = $target;
					if ( $request->getCheck( 'wpAlsoLocal' ) ) {
						$localFailure[] = $target;
					}
					continue;
				}
				$targetData = $targetDataStatus->getValue();

				// Generate the global block options based on the form submission and also whether the option is
				// relevant for the given $target.
				$options = [ 'modify' ];
				if ( $request->getCheck( 'wpAnonOnly' ) && IPUtils::isIPAddress( $targetData['target'] ) ) {
					$options[] = 'anon-only';
				}

				if ( !$request->getCheck( 'wpCreateAccount' ) ) {
					$options[] = 'allow-account-creation';
				}

				if ( $request->getCheck( 'wpAutoBlock' ) && $targetData['targetCentralId'] !== 0 ) {
					$options[] = 'enable-autoblock';
				}

				$globalBlockStatus = $this->globalBlockManager->block(
					$target, $reason, $request->getVal( 'wpExpiry' ), $performer, $options
				);
				if ( $globalBlockStatus->isOK() ) {
					$globalSuccess[] = $target;
				} else {
					$globalFailure[] = $target;
				}

				if ( $request->getCheck( 'wpAlsoLocal' ) ) {
					$localBlockStatus = null;

					// Only perform the local block if the target is valid and the global block succeeded to avoid
					// half the intended actions being performed on the target.
					if ( $globalBlockStatus->isOK() ) {
						$localBlockStatus = $this->blockUserFactory->newBlockUser(
							$targetData['target'],
							$performer,
							$request->getVal( 'wpExpiry' ),
							$reason,
							[
								'isCreateAccountBlocked' => $request->getCheck( 'wpAlsoLocalAccountCreation' ),
								'isEmailBlocked' => $request->getCheck( 'wpAlsoLocalEmail' ),
								'isUserTalkEditBlocked' => $request->getCheck( 'wpAlsoLocalTalk' ),
								'isHardBlock' => !$request->getCheck( 'wpAlsoLocalSoft' ),
								'isAutoblocking' => true,
							]
						)->placeBlock( true );
					}

					if ( $localBlockStatus && $localBlockStatus->isOK() ) {
						$localSuccess[] = $target;
					} else {
						$localFailure[] = $target;
					}
				}
			}

			$this->showSuccessBox( $globalSuccess, 'globalblocking-mass-block-success-block' );
			$this->showSuccessBox( $localSuccess, 'globalblocking-mass-block-success-local' );
			$this->showErrorBox( $globalFailure, 'globalblocking-mass-block-failure-block' );
			$this->showErrorBox( $localFailure, 'globalblocking-mass-block-failure-local' );
		} elseif ( $action === 'unblock' ) {
			foreach ( $this->targets as $target ) {
				$globalUnblockStatus = $this->globalBlockManager->unblock( $target, $reason, $performer );

				if ( $globalUnblockStatus->isOK() ) {
					$globalSuccess[] = $target;
				} else {
					$globalFailure[] = $target;
				}
			}

			$this->showSuccessBox( $globalSuccess, 'globalblocking-mass-block-success-unblock' );
			$this->showErrorBox( $globalFailure, 'globalblocking-mass-block-failure-unblock' );
		}

		ScopedCallback::consume( $temporaryUserRightsScope );
	}

	/**
	 * Adds a success box to the output to indicate targets that were successfully globally blocked, locally blocked,
	 * or globally unblocked
	 *
	 * @param array $relevantTargets The array of targets (usernames, IPs, global block IDs) that were successfully
	 *   blocked/unblocked. If the array is empty, then no box is added.
	 * @param string $messageKey The message key to use for the success box
	 * @return void
	 */
	private function showSuccessBox( array $relevantTargets, string $messageKey ) {
		if ( count( $relevantTargets ) ) {
			$relevantTargetsAsString = implode( $this->msg( 'comma-separator' )->escaped(), $relevantTargets );
			$this->getOutput()->addHTML( Html::successBox(
				$this->msg( $messageKey, $relevantTargetsAsString )->parse()
			) );
		}
	}

	/**
	 * Adds a error box to the output to indicate targets that were failed to be globally blocked, locally blocked,
	 * or globally unblocked
	 *
	 * @param array $relevantTargets The array of targets (usernames, IPs, global block IDs) that were failed to
	 *   be blocked/unblocked. If the array is empty, then no box is added.
	 * @param string $messageKey The message key to use for the error box
	 * @return void
	 */
	private function showErrorBox( array $relevantTargets, string $messageKey ) {
		if ( count( $relevantTargets ) ) {
			$relevantTargetsAsString = implode( $this->msg( 'comma-separator' )->escaped(), $relevantTargets );
			$this->getOutput()->addHTML( Html::errorBox(
				$this->msg( $messageKey, $relevantTargetsAsString )->parse()
			) );
		}
	}
}
