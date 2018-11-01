CREATE TABLE /*_*/globalblocks (
	gb_id int NOT NULL PRIMARY KEY AUTO_INCREMENT,
	gb_address varchar(255) NOT NULL,
	gb_by varchar(255) NOT NULL,
	gb_by_wiki varbinary(255) NOT NULL,
	gb_reason varbinary(767) NOT NULL,
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
