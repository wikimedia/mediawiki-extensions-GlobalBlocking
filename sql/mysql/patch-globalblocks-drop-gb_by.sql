-- This file is automatically generated using maintenance/generateSchemaChangeSql.php.
-- Source: extensions/GlobalBlocking/sql/abstractSchemaChanges/patch-globalblocks-drop-gb_by.json
-- Do not modify this file directly.
-- See https://www.mediawiki.org/wiki/Manual:Schema_changes
ALTER TABLE /*_*/globalblocks
  DROP gb_by;