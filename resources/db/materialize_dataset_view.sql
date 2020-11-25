DROP TABLE IF EXISTS dataset_search_table_tmp;
select * into dataset_search_table_tmp from search_dataset_view;


DROP TABLE IF EXISTS dataset_search_table;

ALTER TABLE dataset_search_table_tmp RENAME TO  dataset_search_table;

UPDATE dataset_search_table SET load_count=0 WHERE load_count is NULL;

-- Index: gist_wst_dataset_the_geom

-- DROP INDEX gist_wst_dataset_the_geom;

CREATE INDEX gist_wst_dataset_the_geom
  ON dataset_search_table
  USING gist
  (the_geom);

-- Index: idx_wst_dataset_searchtext

-- DROP INDEX idx_wst_dataset_searchtext;

CREATE INDEX idx_wst_dataset_searchtext
  ON dataset_search_table
  USING btree
  (searchtext);

-- Index: idx_wst_dataset_department

-- DROP INDEX idx_wst_dataset_department;

CREATE INDEX idx_wst_dataset_department
  ON dataset_search_table
  USING btree
  (department);
-- Index: idx_wst_dataset_md_topic_cats

-- DROP INDEX idx_wst_dataset_md_topic_cats;

CREATE INDEX idx_wst_dataset_md_topic_cats
  ON dataset_search_table
  USING btree
  (md_topic_cats);
-- Index: idx_wst_dataset_dataset_id

-- DROP INDEX idx_wst_dataset_metadata_id;

CREATE INDEX idx_wst_dataset_metadata_id
  ON dataset_search_table
  USING btree
  (metadata_id);

-- DROP INDEX idx_wst_dataset_metadata_id;

CREATE INDEX idx_wst_dataset_dataset_id
  ON dataset_search_table
  USING btree
  (dataset_id);
-- Index: idx_wst_dataset_md_inspire_cats

-- DROP INDEX idx_wst_dataset_md_inspire_cats;

CREATE INDEX idx_wst_dataset_md_inspire_cats
  ON dataset_search_table
  USING btree
  (md_inspire_cats);

-- Index: idx_wst_dataset_md_custom_cats

-- DROP INDEX idx_wst_dataset_md_custom_cats;

CREATE INDEX idx_wst_dataset_md_custom_cats
  ON dataset_search_table
  USING btree
  (md_custom_cats);

-- Index: idx_wst_dataset_timebegin

-- DROP INDEX idx_wst_dataset_timebegin;

CREATE INDEX idx_wst_dataset_timebegin
  ON dataset_search_table
  USING btree
  (timebegin);

-- Index: idx_wst_dataset_timeend

-- DROP INDEX idx_wst_dataset_timeend;

CREATE INDEX idx_wst_dataset_timeend
  ON dataset_search_table
  USING btree
  (timeend);

-- Index: idx_wst_dataset_department

-- DROP INDEX idx_wst_dataset_department;

CREATE INDEX idx_wst_dataset_timestamp
  ON dataset_search_table
  USING btree
  (dataset_timestamp);

GRANT ALL ON TABLE dataset_search_table TO mapbenderdbuser;
ALTER TABLE dataset_search_table OWNER TO mapbenderdbuser;
