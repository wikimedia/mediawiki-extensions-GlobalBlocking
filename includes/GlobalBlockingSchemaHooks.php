<?php

namespace MediaWiki\Extension\GlobalBlocking;

use MediaWiki\Extension\GlobalBlocking\Maintenance\PopulateCentralId;
use MediaWiki\Extension\GlobalBlocking\Maintenance\UpdateAutoBlockParentIdColumn;
use MediaWiki\Installer\DatabaseUpdater;
use MediaWiki\Installer\Hook\LoadExtensionSchemaUpdatesHook;
use MediaWiki\MediaWikiServices;
use Wikimedia\Rdbms\IMaintainableDatabase;

class GlobalBlockingSchemaHooks implements LoadExtensionSchemaUpdatesHook {

	/**
	 * This is static since LoadExtensionSchemaUpdates does not allow service dependencies
	 * @codeCoverageIgnore Tested by updating or installing MediaWiki.
	 * @param DatabaseUpdater $updater
	 */
	public function onLoadExtensionSchemaUpdates( $updater ) {
		$base = __DIR__ . '/..';
		$type = $updater->getDB()->getType();
		$lbFactory = MediaWikiServices::getInstance()->getDBLoadBalancerFactory();
		/** @var IMaintainableDatabase $virtualGlobalBlockingDb */
		$virtualGlobalBlockingDb = $lbFactory->getPrimaryDatabase( 'virtual-globalblocking' );
		'@phan-var IMaintainableDatabase $virtualGlobalBlockingDb';

		$updater->addExtensionUpdateOnVirtualDomain( [
			'virtual-globalblocking', 'addTable', 'globalblocks',
			"$base/sql/$type/tables-generated-globalblocks.sql", true,
		] );

		$updater->addExtensionTable(
			'global_block_whitelist',
			"$base/sql/$type/tables-generated-global_block_whitelist.sql"
		);

		// 1.38
		$updater->addExtensionUpdateOnVirtualDomain( [
			'virtual-globalblocking', 'addField', 'globalblocks', 'gb_by_central_id',
			"$base/sql/$type/patch-add-gb_by_central_id.sql", true,
		] );
		$updater->addExtensionUpdateOnVirtualDomain( [
			'virtual-globalblocking',
			'runMaintenance',
			PopulateCentralId::class
		] );
		if ( $virtualGlobalBlockingDb->fieldExists( 'globalblocks', 'gb_by', __METHOD__ ) ) {
			$updater->addExtensionUpdateOnVirtualDomain( [
				'virtual-globalblocking', 'modifyField', 'globalblocks', 'gb_anon_only',
				"$base/sql/$type/patch-globalblocks-gb_anon_only.sql", true,
			] );
		}

		// 1.39
		if ( $virtualGlobalBlockingDb->fieldExists( 'globalblocks', 'gb_by', __METHOD__ ) ) {
			$updater->addExtensionUpdateOnVirtualDomain( [
				'virtual-globalblocking', 'modifyField', 'globalblocks', 'gb_expiry',
				"$base/sql/$type/patch-globalblocks-timestamps.sql", true,
			] );
		}
		if ( $type === 'postgres' ) {
			$updater->modifyExtensionField(
				'global_block_whitelist',
				'gbw_expiry',
				"$base/sql/$type/patch-global_block_whitelist-timestamps.sql"
			);
		}

		// 1.42
		$updater->addExtensionUpdateOnVirtualDomain( [
			'virtual-globalblocking', 'addField', 'globalblocks', 'gb_target_central_id',
			"$base/sql/$type/patch-add-gb_target_central_id.sql", true,
		] );
		$updater->addExtensionField(
			'global_block_whitelist',
			'gbw_target_central_id',
			"$base/sql/$type/patch-add-gbw_target_central_id.sql"
		);
		if ( $virtualGlobalBlockingDb->fieldExists( 'globalblocks', 'gb_by', __METHOD__ ) ) {
			$updater->addExtensionUpdateOnVirtualDomain( [
				'virtual-globalblocking', 'modifyField', 'globalblocks', 'gb_by',
				"$base/sql/$type/patch-modify-gb_by-default.sql", true,
			] );
		}

		// 1.43
		$updater->addExtensionUpdateOnVirtualDomain( [
			'virtual-globalblocking', 'dropField', 'globalblocks', 'gb_by',
			"$base/sql/$type/patch-globalblocks-drop-gb_by.sql", true,
		] );
		$updater->addExtensionUpdateOnVirtualDomain( [
			'virtual-globalblocking', 'addField', 'globalblocks', 'gb_create_account',
			"$base/sql/$type/patch-globalblocks-add-gb_create_account.sql", true,
		] );
		$updater->addExtensionUpdateOnVirtualDomain( [
			'virtual-globalblocking', 'addField', 'globalblocks', 'gb_enable_autoblock',
			"$base/sql/$type/patch-globalblocks-add-gb_enable_autoblock.sql", true,
		] );
		$updater->addExtensionUpdateOnVirtualDomain( [
			'virtual-globalblocking', 'addField', 'globalblocks', 'gb_autoblock_parent_id',
			"$base/sql/$type/patch-globalblocks-add-gb_autoblock_parent_id.sql", true,
		] );
		$updater->modifyExtensionField(
			'global_block_whitelist',
			'gbw_address',
			"$base/sql/$type/patch-global_block_whitelist-default-gbw_address.sql"
		);
		$updater->addExtensionUpdateOnVirtualDomain( [
			'virtual-globalblocking', 'renameIndex', 'globalblocks', 'gb_address', 'gb_address_autoblock_parent_id',
			false, "$base/sql/$type/patch-globalblocks-modify-gb_address-index.sql", true,
		] );
		// We can skip running the update script if the column is not nullable, as it means that it has been
		// ran before or does not need to be run because there is no data to update. We also need to skip the schema
		// change on gb_autoblock_parent_id to make it not NULLable if the field already is NOT NULL, because
		// modifyField does not handle this correctly.
		$autoBlockParentIdFieldInfo = $virtualGlobalBlockingDb->fieldInfo( 'globalblocks', 'gb_autoblock_parent_id' );
		if ( $autoBlockParentIdFieldInfo && $autoBlockParentIdFieldInfo->isNullable() ) {
			$updater->addExtensionUpdateOnVirtualDomain( [
				'virtual-globalblocking',
				'runMaintenance',
				UpdateAutoBlockParentIdColumn::class
			] );
			$updater->addExtensionUpdateOnVirtualDomain( [
				'virtual-globalblocking', 'modifyField', 'globalblocks', 'gb_autoblock_parent_id',
				"$base/sql/$type/patch-globalblocks-modify-gb_autoblock_parent_id-default.sql", true,
			] );
		}
	}
}
