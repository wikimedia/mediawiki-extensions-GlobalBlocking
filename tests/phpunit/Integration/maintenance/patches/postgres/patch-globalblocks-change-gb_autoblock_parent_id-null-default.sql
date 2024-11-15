ALTER TABLE /*_*/globalblocks ALTER COLUMN gb_autoblock_parent_id DROP NOT NULL;
ALTER TABLE /*_*/globalblocks ALTER COLUMN gb_autoblock_parent_id SET DEFAULT NULL;
