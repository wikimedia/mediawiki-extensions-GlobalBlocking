-- This file is automatically generated using maintenance/generateSchemaChangeSql.php.
-- Source: extensions/GlobalBlocking/sql/abstractSchemaChanges/patch-globalblocks-add-gb_autoblock_parent_id.json
-- Do not modify this file directly.
-- See https://www.mediawiki.org/wiki/Manual:Schema_changes
ALTER TABLE /*_*/globalblocks
  ADD gb_autoblock_parent_id INT UNSIGNED DEFAULT NULL;

CREATE INDEX gb_autoblock_parent_id ON /*_*/globalblocks (gb_autoblock_parent_id);