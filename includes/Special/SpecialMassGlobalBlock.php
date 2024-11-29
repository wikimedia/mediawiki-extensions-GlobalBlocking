<?php

namespace MediaWiki\Extension\GlobalBlocking\Special;

use MediaWiki\Extension\GlobalBlocking\Services\GlobalBlockingConnectionProvider;
use MediaWiki\Extension\GlobalBlocking\Services\GlobalBlockingGlobalBlockDetailsRenderer;
use MediaWiki\Extension\GlobalBlocking\Services\GlobalBlockingLinkBuilder;
use MediaWiki\Extension\GlobalBlocking\Services\GlobalBlockLookup;
use MediaWiki\Extension\GlobalBlocking\Services\GlobalBlockManager;
use MediaWiki\Html\Html;
use MediaWiki\HTMLForm\HTMLForm;
use MediaWiki\Linker\Linker;
use MediaWiki\SpecialPage\SpecialPage;
use MediaWiki\User\UserIdentityLookup;
use UserBlockedError;

class SpecialMassGlobalBlock extends SpecialPage {

	private array $targetsForLookup;

	private UserIdentityLookup $userIdentityLookup;
	private GlobalBlockManager $globalBlockManager;
	private GlobalBlockLookup $globalBlockLookup;
	private GlobalBlockingLinkBuilder $globalBlockingLinkBuilder;
	private GlobalBlockingGlobalBlockDetailsRenderer $globalBlockDetailsRenderer;
	private GlobalBlockingConnectionProvider $globalBlockingConnectionProvider;

	public function __construct(
		UserIdentityLookup $userIdentityLookup,
		GlobalBlockManager $globalBlockManager,
		GlobalBlockLookup $globalBlockLookup,
		GlobalBlockingLinkBuilder $globalBlockingLinkBuilder,
		GlobalBlockingGlobalBlockDetailsRenderer $globalBlockDetailsRenderer,
		GlobalBlockingConnectionProvider $globalBlockingConnectionProvider
	) {
		parent::__construct( 'MassGlobalBlock', 'globalblock' );
		$this->userIdentityLookup = $userIdentityLookup;
		$this->globalBlockManager = $globalBlockManager;
		$this->globalBlockLookup = $globalBlockLookup;
		$this->globalBlockingLinkBuilder = $globalBlockingLinkBuilder;
		$this->globalBlockDetailsRenderer = $globalBlockDetailsRenderer;
		$this->globalBlockingConnectionProvider = $globalBlockingConnectionProvider;
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

		if ( $request->wasPosted() ) {
			$method = $request->getRawVal( 'wpMethod', '' );
			if ( $method === 'search' && count( $this->targetsForLookup ) ) {
				$this->showBlockForm();
			}
		}

		$this->showSearchForm();
	}

	/**
	 * Adds to the output the form that allows a user to search for targets to globally block or unblock.
	 *
	 * @return void
	 */
	private function showSearchForm() {
		$fields = [
			'Targets' => [
				'type' => 'textarea',
				'dir' => 'ltr',
				'rows' => 20,
				'id' => 'mw-globalblock-addresslist',
				'required' => true,
				'autofocus' => true,
				'placeholder' => $this->msg( 'globalblocking-mass-block-query-placeholder' )->text(),
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
			],
		];

		HTMLForm::factory( 'ooui', $fields, $this->getContext() )
			->setWrapperLegendMsg( 'globalblocking-mass-block-query-legend' )
			->setId( 'mw-globalblocking-mass-block-query' )
			->suppressDefaultSubmit()
			->prepareForm()
			->displayForm( false );
	}

	/**
	 * Adds to the output the form that allows a user to perform the mass global blocks or unblocks.
	 *
	 * @return void
	 */
	private function showBlockForm() {
		// The form will be expanded in a later patch. This was done to keep the patch sizes down.
		$fields = [];

		$form = HTMLForm::factory( 'ooui', $fields, $this->getContext() );
		$form->setMethod()
			->setWrapperLegendMsg( 'globalblocking-mass-block-legend' )
			->suppressDefaultSubmit()
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
					'td', [ 'colspan' => 5 ],
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

		$row = [
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

		return Html::rawElement( 'tr', [], implode( "\n", $row ) );
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
}
