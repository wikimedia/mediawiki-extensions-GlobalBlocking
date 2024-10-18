<?php

namespace MediaWiki\Extension\GlobalBlocking\Special;

use InvalidArgumentException;
use MediaWiki\CommentFormatter\CommentFormatter;
use MediaWiki\Context\IContextSource;
use MediaWiki\Extension\GlobalBlocking\Services\GlobalBlockingConnectionProvider;
use MediaWiki\Extension\GlobalBlocking\Services\GlobalBlockingGlobalBlockDetailsRenderer;
use MediaWiki\Extension\GlobalBlocking\Services\GlobalBlockingLinkBuilder;
use MediaWiki\Extension\GlobalBlocking\Services\GlobalBlockLookup;
use MediaWiki\Html\Html;
use MediaWiki\Linker\LinkRenderer;
use MediaWiki\Pager\IndexPager;
use MediaWiki\Pager\TablePager;
use MediaWiki\SpecialPage\SpecialPage;

class GlobalBlockListPager extends TablePager {
	private array $queryConds;

	private CommentFormatter $commentFormatter;
	private GlobalBlockingLinkBuilder $globalBlockingLinkBuilder;
	private GlobalBlockingGlobalBlockDetailsRenderer $globalBlockDetailsRenderer;

	public function __construct(
		IContextSource $context,
		array $conds,
		LinkRenderer $linkRenderer,
		CommentFormatter $commentFormatter,
		GlobalBlockingLinkBuilder $globalBlockingLinkBuilder,
		GlobalBlockingConnectionProvider $globalBlockingConnectionProvider,
		GlobalBlockingGlobalBlockDetailsRenderer $globalBlockDetailsRenderer
	) {
		// Set database before parent constructor so that the DB that has the globalblocks table is used
		// over the local database which may not be the same database.
		$this->mDb = $globalBlockingConnectionProvider->getReplicaGlobalBlockingDatabase();
		parent::__construct( $context, $linkRenderer );
		$this->queryConds = $conds;
		$this->commentFormatter = $commentFormatter;
		$this->globalBlockingLinkBuilder = $globalBlockingLinkBuilder;
		$this->globalBlockDetailsRenderer = $globalBlockDetailsRenderer;

		$this->getOutput()->addModuleStyles( [ 'mediawiki.interface.helpers.styles', 'ext.globalBlocking.styles' ] );
		$this->mDefaultDirection = IndexPager::DIR_DESCENDING;
	}

	protected function getFieldNames() {
		return [
			'gb_timestamp' => $this->msg( 'globalblocking-list-table-heading-timestamp' )->text(),
			'target' => $this->msg( 'globalblocking-list-table-heading-target' )->text(),
			'gb_expiry' => $this->msg( 'globalblocking-list-table-heading-expiry' )->text(),
			'by' => $this->msg( 'globalblocking-list-table-heading-by' )->text(),
			'params' => $this->msg( 'globalblocking-list-table-heading-params' )->text(),
			'gb_reason' => $this->msg( 'globalblocking-list-table-heading-reason' )->text(),
		];
	}

	/**
	 * @param string $name
	 * @param string|null $value
	 * @return string
	 */
	public function formatValue( $name, $value ) {
		$row = $this->mCurrentRow;

		switch ( $name ) {
			case 'gb_timestamp':
				// Link the timestamp to the block ID. This allows users without permissions to change blocks
				// to be able to generate a link to a specific block.
				return $this->getLinkRenderer()->makeKnownLink(
					SpecialPage::getTitleFor( 'GlobalBlockList' ),
					$this->getLanguage()->userTimeAndDate( $value, $this->getUser() ),
					[],
					[ 'target' => "#{$row->gb_id}" ],
				);
			case 'target':
				return $this->globalBlockDetailsRenderer->formatTargetForDisplay( $row, $this->getContext() );
			case 'gb_expiry':
				$targetForUrl = $row->gb_address;
				if ( $row->gb_autoblock_parent_id ) {
					$targetForUrl = '#' . $row->gb_id;
				}
				$actionLinks = Html::rawElement(
					'span',
					[ 'class' => 'mw-globalblocking-globalblocklist-actions' ],
					$this->globalBlockingLinkBuilder->getActionLinks(
						$this->getAuthority(), $targetForUrl, $this->getContext()
					)
				);
				return $this->msg( 'globalblocking-list-table-cell-expiry' )
					->expiryParams( $value )
					->rawParams( $actionLinks )
					->parse();
			case 'by':
				return $this->globalBlockDetailsRenderer->getPerformerForDisplay( $row, $this->getContext() );
			case 'gb_reason':
				return $this->commentFormatter->format( $value );
			case 'params':
				$options = $this->globalBlockDetailsRenderer->getBlockOptionsForDisplay( $row, $this->getContext() );

				// Wrap the options in <li> HTML tags to make the options into a list.
				$options = array_map( static function ( $prop ) {
					return Html::rawElement( 'li', [], $prop );
				}, $options );

				return Html::rawElement( 'ul', [], implode( '', $options ) );
			default:
				throw new InvalidArgumentException( "Unable to format $name" );
		}
	}

	public function getQueryInfo() {
		return [
			'tables' => 'globalblocks',
			'fields' => GlobalBlockLookup::selectFields(),
			'conds' => $this->queryConds,
		];
	}

	public function getIndexField() {
		return 'gb_timestamp';
	}

	protected function getTableClass() {
		return parent::getTableClass() . ' mw-globalblocking-globalblocklist';
	}

	public function getDefaultSort() {
		return '';
	}

	protected function isFieldSortable( $name ) {
		return false;
	}
}
