-- This file is automatically generated using maintenance/generateSchemaSql.php.
-- Source: extensions/GlobalBlocking/sql/tables-globalblocks.json
-- Do not modify this file directly.
-- See https://www.mediawiki.org/wiki/Manual:Schema_changes
CREATE TABLE /*_*/globalblocks (
  gb_id INT UNSIGNED AUTO_INCREMENT NOT NULL,
  gb_address VARCHAR(255) NOT NULL,
  gb_by VARCHAR(255) NOT NULL,
  gb_by_central_id INT UNSIGNED DEFAULT NULL,
  gb_by_wiki VARBINARY(255) NOT NULL,
  gb_reason VARBINARY(767) NOT NULL,
  gb_timestamp VARCHAR(14) NOT NULL,
  gb_anon_only INT DEFAULT 0 NOT NULL,
  gb_expiry VARBINARY(14) DEFAULT '' NOT NULL,
  gb_range_start VARBINARY(35) NOT NULL,
  gb_range_end VARBINARY(35) NOT NULL,
  UNIQUE INDEX gb_address (gb_address, gb_anon_only),
  INDEX gb_range (gb_range_start, gb_range_end),
  INDEX gb_timestamp (gb_timestamp),
  INDEX gb_expiry (gb_expiry),
  PRIMARY KEY(gb_id)
) /*$wgDBTableOptions*/;
