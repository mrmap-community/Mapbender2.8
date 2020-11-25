
DROP TABLE IF EXISTS wfs_search_table_tmp;
select * into wfs_search_table_tmp from search_wfs_view;


DROP TABLE IF EXISTS wfs_search_table;
--TODO
ALTER TABLE wfs_search_table_tmp RENAME TO  wfs_search_table;

-- Index: gist_wst_the_geom

-- DROP INDEX gist_wst_featuretype_the_geom;

CREATE INDEX gist_wst_featuretype_the_geom
  ON wfs_search_table
  USING gist
  (the_geom);

-- Index: idx_wst_featuretype_department

-- DROP INDEX idx_wst_featuretype_department;

CREATE INDEX idx_wst_featuretype_department
  ON wfs_search_table
  USING btree
  (department);

-- Index: idx_wst_featuretype_id

-- DROP INDEX idx_wst_featuretype_id;

CREATE INDEX idx_wst_featuretype_id
  ON wfs_search_table
  USING btree
  (featuretype_id);

-- Index: idx_wst_featuretype_searchtext

-- DROP INDEX idx_wst_featuretype_searchtext;

CREATE INDEX idx_wst_featuretype_searchtext
  ON wfs_search_table
  USING btree
  (searchtext);

-- Index: idx_wst_wfs_timestamp

-- DROP INDEX idx_wst_wfs_timestamp;

CREATE INDEX idx_wst_wfs_timestamp
  ON wfs_search_table
  USING btree
  (wfs_timestamp);
--vacuum analyze;
--VACUUM ANALYZE wfs_search_table;

GRANT ALL ON TABLE wfs_search_table TO mapbenderdbuser;
ALTER TABLE wfs_search_table OWNER TO mapbenderdbuser;


