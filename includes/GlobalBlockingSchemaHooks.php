<?php

namespace MediaWiki\Extension\GlobalBlocking;

use DatabaseUpdater;
use MediaWiki\Extension\GlobalBlocking\Maintenance\PopulateCentralId;
use MediaWiki\Installer\Hook\LoadExtensionSchemaUpdatesHook;

class GlobalBlockingSchemaHooks implements LoadExtensionSchemaUpdatesHook {

	/**
	 * This is static since LoadExtensionSchemaUpdates does not allow service dependencies
	 * @param DatabaseUpdater $updater
	 */
	public function onLoadExtensionSchemaUpdates( $updater ) {
		$base = __DIR__ . '/..';
		$type = $updater->getDB()->getType();

		$updater->addExtensionTable(
			'globalblocks',
			"$base/sql/$type/tables-generated-globalblocks.sql"
		);

		$updater->addExtensionTable(
			'global_block_whitelist',
			"$base/sql/$type/tables-generated-global_block_whitelist.sql"
		);

		if ( $type === 'mysql' ) {
			// 1.34
			$updater->modifyExtensionField(
				'globalblocks',
				'gb_reason',
				"$base/sql/$type/patch-globalblocks-reason-length.sql"
			);
			$updater->modifyExtensionField(
				'global_block_whitelist',
				'gbw_reason',
				"$base/sql/$type/patch-global_block_whitelist-reason-length.sql"
			);
			$updater->modifyExtensionField(
				'global_block_whitelist',
				'gbw_by_text',
				"$base/sql/$type/patch-global_block_whitelist-use-varbinary.sql"
			);
		}

		// 1.38
		$updater->addExtensionField(
			'globalblocks',
			'gb_by_central_id',
			"$base/sql/$type/patch-add-gb_by_central_id.sql"
		);
		$updater->addPostDatabaseUpdateMaintenance( PopulateCentralId::class );
		$updater->modifyExtensionField(
			'globalblocks',
			'gb_anon_only',
			"$base/sql/$type/patch-globalblocks-gb_anon_only.sql"
		);

		// 1.39
		$updater->modifyExtensionField(
			'globalblocks',
			'gb_expiry',
			"$base/sql/$type/patch-globalblocks-timestamps.sql"
		);
		if ( $type === 'postgres' ) {
			$updater->modifyExtensionField(
				'global_block_whitelist',
				'gbw_expiry',
				"$base/sql/$type/patch-global_block_whitelist-timestamps.sql"
			);
		}
	}
}
