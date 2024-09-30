-- This file is automatically generated using maintenance/generateSchemaSql.php.
-- Source: extensions/GlobalBlocking/sql/tables-globalblocks.json
-- Do not modify this file directly.
-- See https://www.mediawiki.org/wiki/Manual:Schema_changes
CREATE TABLE /*_*/globalblocks (
  gb_id INTEGER PRIMARY KEY AUTOINCREMENT NOT NULL,
  gb_address VARCHAR(255) NOT NULL,
  gb_target_central_id INTEGER UNSIGNED DEFAULT 0 NOT NULL,
  gb_by_central_id INTEGER UNSIGNED NOT NULL,
  gb_by_wiki BLOB NOT NULL,
  gb_reason BLOB NOT NULL,
  gb_timestamp BLOB NOT NULL,
  gb_anon_only SMALLINT DEFAULT 0 NOT NULL,
  gb_create_account SMALLINT DEFAULT 1 NOT NULL,
  gb_enable_autoblock SMALLINT DEFAULT 0 NOT NULL,
  gb_autoblock_parent_id INTEGER UNSIGNED DEFAULT NULL,
  gb_expiry BLOB NOT NULL,
  gb_range_start BLOB NOT NULL,
  gb_range_end BLOB NOT NULL
);

CREATE UNIQUE INDEX gb_address_autoblock_parent_id ON /*_*/globalblocks (
  gb_address, gb_autoblock_parent_id
);

CREATE INDEX gb_target_central_id ON /*_*/globalblocks (gb_target_central_id);

CREATE INDEX gb_range ON /*_*/globalblocks (gb_range_start, gb_range_end);

CREATE INDEX gb_timestamp ON /*_*/globalblocks (gb_timestamp);

CREATE INDEX gb_expiry ON /*_*/globalblocks (gb_expiry);

CREATE INDEX gb_autoblock_parent_id ON /*_*/globalblocks (gb_autoblock_parent_id);
