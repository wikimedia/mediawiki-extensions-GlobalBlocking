CREATE TABLE /*_*/globalblocks (
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

CREATE UNIQUE INDEX /*i*/gb_address ON /*_*/globalblocks (gb_address, gb_anon_only);
CREATE INDEX /*i*/gb_range ON /*_*/globalblocks (gb_range_start, gb_range_end);
CREATE INDEX /*i*/gb_timestamp ON /*_*/globalblocks (gb_timestamp);
CREATE INDEX /*i*/gb_expiry ON /*_*/globalblocks (gb_expiry);

CREATE TABLE /*_*/global_block_whitelist (
	gbw_id int(11) NOT NULL PRIMARY KEY, -- Key to gb_id in globalblocks database.
	gbw_address varbinary(255) NOT NULL,
	gbw_by int(11) NOT NULL, -- Key to user_id
	gbw_by_text varchar(255) NOT NULL,
	gbw_reason varchar(255) NOT NULL,
	gbw_expiry binary(14) NOT NULL
) /*$wgDBTableOptions*/;

CREATE INDEX /*i*/gbw_by ON  /*_*/global_block_whitelist (gbw_by);