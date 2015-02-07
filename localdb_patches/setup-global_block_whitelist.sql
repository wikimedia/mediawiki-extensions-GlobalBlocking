-- Patch to create local table for whitelisted global blocks
-- Andrew Garrett, April 2008

CREATE TABLE /*_*/global_block_whitelist (
	gbw_id int(11) NOT NULL PRIMARY KEY, -- Key to gb_id in globalblocks database.
	gbw_address varbinary(255) NOT NULL,
	gbw_by	int(11) NOT NULL, -- Key to user_id
	gbw_by_text varchar(255) NOT NULL,
	gbw_reason varchar(255) NOT NULL,
	gbw_expiry binary(14) NOT NULL
) /*$wgDBTableOptions*/;

CREATE INDEX /*i*/gbw_by ON  /*_*/global_block_whitelist (gbw_by);