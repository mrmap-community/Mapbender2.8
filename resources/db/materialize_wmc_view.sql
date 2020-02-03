DROP TABLE IF EXISTS wmc_search_table_tmp;
select * into wmc_search_table_tmp from search_wmc_view;


DROP TABLE IF EXISTS wmc_search_table;

ALTER TABLE wmc_search_table_tmp RENAME TO  wmc_search_table;

UPDATE wmc_search_table SET load_count=0 WHERE load_count is NULL;

-- Index: gist_wst_wmc_the_geom

-- DROP INDEX gist_wst_wmc_the_geom;

CREATE INDEX gist_wst_wmc_the_geom
  ON wmc_search_table
  USING gist
  (the_geom);

-- Index: idx_wst_wmc_searchtext

-- DROP INDEX idx_wst_wmc_searchtext;

CREATE INDEX idx_wst_wmc_searchtext
  ON wmc_search_table
  USING btree
  (searchtext);

-- Index: idx_wst_wmc_department

-- DROP INDEX idx_wst_wmc_department;

CREATE INDEX idx_wst_wmc_department
  ON wmc_search_table
  USING btree
  (department);
-- Index: idx_wst_wmc_md_topic_cats

-- DROP INDEX idx_wst_wmc_md_topic_cats;

CREATE INDEX idx_wst_wmc_md_topic_cats
  ON wmc_search_table
  USING btree
  (md_topic_cats);

-- DROP INDEX idx_wst_wmc_wmc_id;

CREATE INDEX idx_wst_wmc_wmc_id
  ON wmc_search_table
  USING btree
  (wmc_id);
-- Index: idx_wst_wmc_md_inspire_cats

-- DROP INDEX idx_wst_wmc_md_inspire_cats;

CREATE INDEX idx_wst_wmc_md_inspire_cats
  ON wmc_search_table
  USING btree
  (md_inspire_cats);

-- Index: idx_wst_wmc_md_custom_cats

-- DROP INDEX idx_wst_wmc_md_custom_cats;

CREATE INDEX idx_wst_wmc_md_custom_cats
  ON wmc_search_table
  USING btree
  (md_custom_cats);


-- Index: idx_wst_wmc_department

-- DROP INDEX idx_wst_wmc_department;

CREATE INDEX idx_wst_wmc_timestamp
  ON wmc_search_table
  USING btree
  (wmc_timestamp);

