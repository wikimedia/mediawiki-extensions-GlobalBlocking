-- This file is automatically generated using maintenance/generateSchemaChangeSql.php.
-- Source: extensions/GlobalBlocking/sql/abstractSchemaChanges/patch-globalblocks-modify-gb_address-index.json
-- Do not modify this file directly.
-- See https://www.mediawiki.org/wiki/Manual:Schema_changes
DROP INDEX gb_address;

CREATE UNIQUE INDEX gb_address_autoblock_parent_id ON globalblocks (
  gb_address, gb_autoblock_parent_id
);