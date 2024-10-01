<?php

namespace MediaWiki\Extension\GlobalBlocking;

use MediaWiki\Extension\GlobalBlocking\Maintenance\PopulateCentralId;
use MediaWiki\Installer\DatabaseUpdater;
use MediaWiki\Installer\Hook\LoadExtensionSchemaUpdatesHook;

class GlobalBlockingSchemaHooks implements LoadExtensionSchemaUpdatesHook {

	/**
	 * This is static since LoadExtensionSchemaUpdates does not allow service dependencies
	 * @codeCoverageIgnore Tested by updating or installing MediaWiki.
	 * @param DatabaseUpdater $updater
	 */
	public function onLoadExtensionSchemaUpdates( $updater ) {
		$base = __DIR__ . '/..';
		$type = $updater->getDB()->getType();

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
		if ( $updater->fieldExists( 'globalblocks', 'gb_by' ) ) {
			$updater->addExtensionUpdateOnVirtualDomain( [
				'virtual-globalblocking', 'modifyField', 'globalblocks', 'gb_anon_only',
				"$base/sql/$type/patch-globalblocks-gb_anon_only.sql", true,
			] );
		}

		// 1.39
		if ( $updater->fieldExists( 'globalblocks', 'gb_by' ) ) {
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
		if ( $updater->fieldExists( 'globalblocks', 'gb_by' ) ) {
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
	}
}
