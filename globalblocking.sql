CREATE TABLE /*$wgDBprefix*/globalblocks (
	gb_id int NOT NULL PRIMARY KEY AUTO_INCREMENT,
	gb_address varchar(255) NOT NULL,
	gb_by varchar(255) NOT NULL,
	gb_by_wiki varbinary(255) NOT NULL,
	gb_reason TINYBLOB NOT NULL,
	gb_timestamp binary(14) NOT NULL,
	gb_anon_only bool NOT NULL default 0,
	gb_expiry varbinary(14) NOT NULL default '',
	gb_range_start varbinary(35) NOT NULL,
	gb_range_end varbinary(35) NOT NULL
) /*$wgDBTableOptions*/;

CREATE UNIQUE INDEX gb_address ON /*$wgDBprefix*/globalblocks (gb_address, gb_anon_only);
CREATE INDEX gb_range ON /*$wgDBprefix*/globalblocks (gb_range_start, gb_range_end);
CREATE INDEX gb_timestamp ON /*$wgDBprefix*/globalblocks (gb_timestamp);
CREATE INDEX gb_expiry ON /*$wgDBprefix*/globalblocks (gb_expiry);

CREATE TABLE /*$wgDBprefix*/global_block_whitelist (
	gbw_id int(11) NOT NULL PRIMARY KEY, -- Key to gb_id in globalblocks database.
	gbw_address varbinary(255) NOT NULL,
	gbw_by int(11) NOT NULL, -- Key to user_id
	gbw_by_text varchar(255) NOT NULL,
	gbw_reason varchar(255) NOT NULL,
	gbw_expiry binary(14) NOT NULL
) /*$wgDBTableOptions*/;
CREATE INDEX gbw_by ON /*$wgDBprefix*/global_block_whitelist (gbw_by);