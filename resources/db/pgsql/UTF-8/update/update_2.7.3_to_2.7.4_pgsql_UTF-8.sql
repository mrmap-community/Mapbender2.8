-- new file for db changes to 2.7.4
ALTER TABLE wms ADD COLUMN wms_max_imagesize INTEGER DEFAULT 1000;
ALTER TABLE wfs ADD COLUMN wfs_max_features INTEGER DEFAULT 1000;

-- new element_var tableTools for element resultList
INSERT INTO gui_element_vars(fkey_gui_id, fkey_e_id, var_name, var_value, context, var_type)
SELECT gui_element.fkey_gui_id,
'resultList', 'tableTools', '[
      {
	      "sExtends": "xls",
	      "sButtonText": "Export to CSV",
	      "sFileName": "result.csv"
      }
]', 'set the initialization options for tableTools' ,'var' 
from gui_element
WHERE
gui_element.e_id = 'resultList' AND
gui_element.fkey_gui_id
NOT IN (SELECT fkey_gui_id FROM gui_element_vars WHERE fkey_e_id = 'resultList' AND var_name = 'tableTools');

--update jq_datatables element (new dataTables version + TableTools extension)
UPDATE gui_element set e_mb_mod = '../extensions/dataTables-1.7.5/media/js/jquery.dataTables.min.js,../extensions/dataTables-1.7.5/extras/TableTools-2.0.0/media/js/TableTools.min.js' WHERE e_id = 'jq_datatables';

--update css file of dataTables in body element
UPDATE gui_element_vars set var_value = '../extensions/dataTables-1.7.5/media/css/demo_table_jui.css' WHERE fkey_e_id = 'body' AND var_name = 'jq_datatables_css';

--extent table termsofuse to allow classification if some licence is open or not!
ALTER TABLE termsofuse ADD COLUMN isopen INTEGER DEFAULT 0;

CREATE SEQUENCE termsofuse_id_seq;
SELECT setval('termsofuse_id_seq', (SELECT max(termsofuse_id) FROM termsofuse));
ALTER TABLE termsofuse ALTER COLUMN termsofuse_id SET DEFAULT
nextval('termsofuse_id_seq'::regclass); 

--create some example entries for licences
-- CC-BY
--INSERT INTO termsofuse (name,symbollink,description,descriptionlink,isopen) VALUES ('cc-by', 'http://i.creativecommons.org/l/by/3.0/de/88x31.png','Creative Commons: Namensnennung 3.0 Deutschland','http://creativecommons.org/licenses/by/3.0/de/',1) ;
-- CC BY-NC 
---INSERT INTO termsofuse (name,symbollink,description,descriptionlink,isopen) VALUES ('cc-by-nc', 'http://i.creativecommons.org/l/by-nc/3.0/de/88x31.png','Creative Commons: Namensnennung - Keine kommerzielle Nutzung 3.0 Deutschland','http://creativecommons.org/licenses/by-nc/3.0/de/',0);
-- Datenlizenz Deutschland – Namensnennung – Version 1.0
--INSERT INTO termsofuse (name,symbollink,description,descriptionlink,isopen) VALUES ('dl-de-by-1.0', 'http://i.creativecommons.org/l/by/3.0/de/88x31.png','Datenlizenz Deutschland – Namensnennung – Version 1.0','http://www.daten-deutschland.de/bibliothek/Datenlizenz_Deutschland/dl-de-by-1.0',1) ;
-- Datenlizenz Deutschland – Namensnennung – nicht-kommerziell – Version 1.0
--INSERT INTO termsofuse (name,symbollink,description,descriptionlink,isopen) VALUES ('dl-de-by-nc-1.0', 'http://i.creativecommons.org/l/by-nc/3.0/de/88x31.png','Datenlizenz Deutschland – Namensnennung – nicht-kommerziell – Version 1.0','http://www.daten-deutschland.de/bibliothek/Datenlizenz_Deutschland/dl-de-by-nc-1.0',0);
UPDATE termsofuse SET name = 'cc-by-nc-nd' WHERE name = 'CC by-nc-nd';

-- Function: f_collect_layer_keywords(integer, integer)

-- DROP FUNCTION f_collect_layer_keywords(integer, integer);

CREATE OR REPLACE FUNCTION f_collect_layer_keywords(integer)
  RETURNS text AS
$BODY$DECLARE
    p_layer_id ALIAS FOR $1;
    
    r_keywords RECORD;
    l_result TEXT;
BEGIN
    l_result := '';
    FOR r_keywords IN SELECT DISTINCT keyword FROM
        (SELECT keyword FROM layer_keyword L JOIN keyword K ON (K.keyword_id = L.fkey_keyword_id AND L.fkey_layer_id = p_layer_id)
        ) AS __keywords__ LOOP
        l_result := l_result || ',' || COALESCE(r_keywords.keyword, '');
    END LOOP;
    l_result := trim(leading ',' from l_result);
    RETURN l_result;
END;
$BODY$
  LANGUAGE plpgsql VOLATILE STRICT
  COST 100;
ALTER FUNCTION f_collect_layer_keywords(integer)
  OWNER TO postgres;

-- legend_url in e_content for printPDF template print
UPDATE gui_element SET e_content = '<div id="printPDF_working_bg"></div><div id="printPDF_working"><img src="../img/indicator_wheel.gif" style="padding:10px 0 0 10px">Generating PDF</div><div id="printPDF_input"><form id="printPDF_form" action="../print/printFactory.php"><div id="printPDF_selector"></div><div class="print_option"><input type="hidden" id="opacity" name="opacity" value=""/><input type="hidden" id="map_url" name="map_url" value=""/><input type="hidden" id="overview_url" name="overview_url" value=""/><input type="hidden" id="legend_url" name="legend_url" value=""/><input type="hidden" id="map_scale" name="map_scale" value=""/><input type="hidden" name="measured_x_values" /><input type="hidden" name="measured_y_values" /><br /></div><div class="print_option" id="printPDF_formsubmit"><input id="submit" type="submit" value="Print"><br /></div></form><div id="printPDF_result"></div></div>' WHERE e_id = 'printPDF' and fkey_gui_id = 'template_print';

-- add new element vars for resizemapSize to gui2
INSERT INTO gui_element_vars(fkey_gui_id, fkey_e_id, var_name, var_value, context, var_type) VALUES('gui2', 'resizeMapsize', 'max_height', '700', 'define a max mapframe width (units pixel) f.e. 700 or false' ,'var');
INSERT INTO gui_element_vars(fkey_gui_id, fkey_e_id, var_name, var_value, context, var_type) VALUES('gui2', 'resizeMapsize', 'max_width', '700', 'define a max mapframe width (units pixel) f.e. 700 or false' ,'var');

-- Column: mb_group_admin_code

-- ALTER TABLE mb_group DROP COLUMN mb_group_admin_code;
-- e.g. NUTS 1 (federal state), NUTS 2, NUTS 3, LAU 1, LAU 2 - for european use case

ALTER TABLE mb_group ADD COLUMN mb_group_admin_code character varying(255);
ALTER TABLE mb_group ALTER COLUMN mb_group_admin_code SET DEFAULT ''::character varying;


--tables for categories and keywords of metadata sets - relational mapping



-- Table: mb_metadata_inspire_category

-- DROP TABLE mb_metadata_inspire_category;

CREATE TABLE mb_metadata_inspire_category
(
  fkey_metadata_id integer NOT NULL,
  fkey_inspire_category_id integer NOT NULL,
  CONSTRAINT mb_metadata_inspire_category_fkey_inspire_category_id_fkey FOREIGN KEY (fkey_inspire_category_id)
      REFERENCES inspire_category (inspire_category_id) MATCH SIMPLE
      ON UPDATE CASCADE ON DELETE CASCADE,
  CONSTRAINT mb_metadata_inspire_category_fkey_metadata_id_fkey FOREIGN KEY (fkey_metadata_id)
      REFERENCES mb_metadata (metadata_id) MATCH SIMPLE
      ON UPDATE CASCADE ON DELETE CASCADE
)
WITH (
  OIDS=FALSE
);
ALTER TABLE mb_metadata_inspire_category
  OWNER TO postgres;

-- Table: md_md_topic_category

-- DROP TABLE md_md_topic_category;

CREATE TABLE mb_metadata_md_topic_category
(
  fkey_metadata_id integer NOT NULL,
  fkey_md_topic_category_id integer NOT NULL,
  CONSTRAINT mb_metadata_md_topic_category_fkey_md_topic_category_id_fkey FOREIGN KEY (fkey_md_topic_category_id)
      REFERENCES md_topic_category (md_topic_category_id) MATCH SIMPLE
      ON UPDATE CASCADE ON DELETE CASCADE,
  CONSTRAINT mb_metadata_md_topic_category_fkey_metadata_id_fkey FOREIGN KEY (fkey_metadata_id)
      REFERENCES mb_metadata (metadata_id) MATCH SIMPLE
      ON UPDATE CASCADE ON DELETE CASCADE
)
WITH (
  OIDS=FALSE
);
ALTER TABLE mb_metadata_md_topic_category
  OWNER TO postgres;

-- Table: mb_metadata_custom_category

-- DROP TABLE mb_metadata_custom_category;

CREATE TABLE mb_metadata_custom_category
(
  fkey_metadata_id integer NOT NULL,
  fkey_custom_category_id integer NOT NULL,
  CONSTRAINT mb_metadata_custom_category_fkey_custom_category_id_fkey FOREIGN KEY (fkey_custom_category_id)
      REFERENCES custom_category (custom_category_id) MATCH SIMPLE
      ON UPDATE CASCADE ON DELETE CASCADE,
  CONSTRAINT mb_metadata_custom_category_fkey_metadata_id_fkey FOREIGN KEY (fkey_metadata_id)
      REFERENCES mb_metadata (metadata_id) MATCH SIMPLE
      ON UPDATE CASCADE ON DELETE CASCADE
)
WITH (
  OIDS=FALSE
);
ALTER TABLE mb_metadata_custom_category
  OWNER TO postgres;

--other keywords
-- Table: mb_metadata_keyword

-- DROP TABLE mb_metadata_keyword;

CREATE TABLE mb_metadata_keyword
(
  fkey_metadata_id integer NOT NULL,
  fkey_keyword_id integer NOT NULL,
  CONSTRAINT pk_mb_metadata_keyword PRIMARY KEY (fkey_metadata_id , fkey_keyword_id ),
  CONSTRAINT fkey_keyword_id_fkey_metadata_id FOREIGN KEY (fkey_keyword_id)
      REFERENCES keyword (keyword_id) MATCH SIMPLE
      ON UPDATE CASCADE ON DELETE CASCADE,
  CONSTRAINT fkey_metadata_id_fkey_keyword_id FOREIGN KEY (fkey_metadata_id)
      REFERENCES mb_metadata (metadata_id) MATCH SIMPLE
      ON UPDATE CASCADE ON DELETE CASCADE
)
WITH (
  OIDS=FALSE
);
ALTER TABLE mb_metadata_keyword
  OWNER TO postgres;
--extent mapbenders information modell for mb_user, mb_group, termsofuse timestamps to allow differential syncing of dynamically built metadata with e.g. ckan catalogues 
--mb_user 
--first creation
ALTER TABLE mb_user ADD COLUMN timestamp_create timestamp without time zone;
ALTER TABLE mb_user ALTER COLUMN timestamp_create SET DEFAULT now();
UPDATE mb_user SET timestamp_create = now() WHERE timestamp_create IS NULL;
ALTER TABLE mb_user ALTER COLUMN timestamp_create SET NOT NULL;
--last changed
ALTER TABLE mb_user ADD COLUMN timestamp timestamp without time zone;
UPDATE mb_user SET timestamp = now() WHERE timestamp IS NULL;
ALTER TABLE mb_user ALTER COLUMN timestamp SET DEFAULT now();
ALTER TABLE mb_user ALTER COLUMN timestamp SET NOT NULL;
--trigger function for all tables, that have a timestamp column!
CREATE OR REPLACE FUNCTION update_timestamp_column()
  RETURNS trigger AS
$BODY$
BEGIN
   NEW.timestamp = now(); 
   RETURN NEW;
END;
$BODY$
  LANGUAGE plpgsql VOLATILE
  COST 100;
ALTER FUNCTION update_timestamp_column()
  OWNER TO postgres;
--trigger itself
 CREATE TRIGGER update_mb_user_timestamp BEFORE UPDATE
        ON mb_user FOR EACH ROW EXECUTE PROCEDURE 
        update_timestamp_column();
--mb_group
ALTER TABLE mb_group ADD COLUMN timestamp_create timestamp without time zone;
ALTER TABLE mb_group ALTER COLUMN timestamp_create SET DEFAULT now();
UPDATE mb_group SET timestamp_create = now() WHERE timestamp_create IS NULL;
ALTER TABLE mb_group ALTER COLUMN timestamp_create SET NOT NULL;
--last changed
ALTER TABLE mb_group ADD COLUMN timestamp timestamp without time zone;
UPDATE mb_group SET timestamp = now() WHERE timestamp IS NULL;
ALTER TABLE mb_group ALTER COLUMN timestamp SET DEFAULT now();
ALTER TABLE mb_group ALTER COLUMN timestamp SET NOT NULL;
--trigger itself
 CREATE TRIGGER update_mb_group_timestamp BEFORE UPDATE
        ON mb_group FOR EACH ROW EXECUTE PROCEDURE 
        update_timestamp_column();
--termsofuse
ALTER TABLE termsofuse ADD COLUMN timestamp_create timestamp without time zone;
ALTER TABLE termsofuse ALTER COLUMN timestamp_create SET DEFAULT now();
UPDATE termsofuse SET timestamp_create = now() WHERE timestamp_create IS NULL;
ALTER TABLE termsofuse ALTER COLUMN timestamp_create SET NOT NULL;
--last changed
ALTER TABLE termsofuse ADD COLUMN timestamp timestamp without time zone;
UPDATE termsofuse SET timestamp = now() WHERE timestamp IS NULL;
ALTER TABLE termsofuse ALTER COLUMN timestamp SET DEFAULT now();
ALTER TABLE termsofuse ALTER COLUMN timestamp SET NOT NULL;
--trigger itself
 CREATE TRIGGER update_termsofuse_timestamp BEFORE UPDATE
        ON termsofuse FOR EACH ROW EXECUTE PROCEDURE 
        update_timestamp_column();
-- maybe some other information needed?

--------------------- db changes for wfs 2.0 integration ----------------------------------------
ALTER TABLE wfs_conf ADD COLUMN stored_query_id character varying(255);
ALTER TABLE wfs_conf ALTER COLUMN g_label TYPE character varying(255);

-- new table wfs_operation
CREATE TABLE wfs_operation
(
  wfs_op_id serial NOT NULL,
  fkey_wfs_id integer NOT NULL DEFAULT 0,
  op_name character varying(255),
  op_http_get character varying(255),
  op_http_post character varying(255),
  CONSTRAINT pk_wfs_op_id PRIMARY KEY (wfs_op_id ),
  CONSTRAINT wfs_op_ibfk_1 FOREIGN KEY (fkey_wfs_id)
      REFERENCES wfs (wfs_id) MATCH SIMPLE
      ON UPDATE CASCADE ON DELETE CASCADE
);

-- new table wfs_stored_query_params
CREATE TABLE wfs_stored_query_params
(
  query_param_id serial NOT NULL,
  fkey_wfs_conf_id integer NOT NULL DEFAULT 0,
  stored_query_id character varying(255),
  query_param_name character varying(255),
  query_param_type character varying(255),
  CONSTRAINT pk_query_param_id PRIMARY KEY (query_param_id ),
  CONSTRAINT query_params_ibfk_1 FOREIGN KEY (fkey_wfs_conf_id)
      REFERENCES wfs_conf (wfs_conf_id) MATCH SIMPLE
      ON UPDATE CASCADE ON DELETE CASCADE
);
--------------------- end of db changes for wfs 2.0 integration ----------------------------------------

-- Column: mb_group_address_location

-- ALTER TABLE mb_group DROP COLUMN mb_group_address_location;

ALTER TABLE mb_group ADD COLUMN mb_group_address_location geometry;

-- printPDF: legend_url and opacity fields in e_content for printPDF template print
UPDATE gui_element SET e_content = '<div id="printPDF_working_bg"></div><div id="printPDF_working"><img src="../img/indicator_wheel.gif" style="padding:10px 0 0 10px">Generating PDF</div><div id="printPDF_input"><form id="printPDF_form" action="../print/printFactory.php"><div id="printPDF_selector"></div><div class="print_option"><input type="hidden" id="map_url" name="map_url" value=""/><input type="hidden" id="legend_url" name="legend_url" value=""/><input type="hidden" id="opacity" name="opacity" value=""/> <input type="hidden" id="overview_url" name="overview_url" value=""/><input type="hidden" id="map_scale" name="map_scale" value=""/><input type="hidden" name="measured_x_values" /><input type="hidden" name="measured_y_values" /><br /></div><div class="print_option" id="printPDF_formsubmit"><input id="submit" type="submit" value="Print"><br /></div></form><div id="printPDF_result"></div></div>' 
WHERE e_id = 'printPDF' and fkey_gui_id = 'template_print';


-- printPDF: new element_var legendColumns for element printPDF
INSERT INTO gui_element_vars(fkey_gui_id, fkey_e_id, var_name, var_value, context, var_type)
SELECT gui_element.fkey_gui_id,
'printPDF', 'legendColumns', '2', 'define number of columns on legendpage' ,'php_var' from gui_element
WHERE
gui_element.e_id = 'printPDF' AND
gui_element.fkey_gui_id
NOT IN (SELECT fkey_gui_id FROM gui_element_vars WHERE fkey_e_id = 'printPDF' AND var_name = 'legendColumns');

-- printPDF: new element_var printLegend for element printPDF
INSERT INTO gui_element_vars(fkey_gui_id, fkey_e_id, var_name, var_value, context, var_type)
SELECT gui_element.fkey_gui_id, 'printPDF', 'printLegend', 'true', 'define whether the legend should be printed or not' ,'php_var' 
from gui_element
WHERE
gui_element.e_id = 'printPDF' AND
gui_element.fkey_gui_id
NOT IN (SELECT fkey_gui_id FROM gui_element_vars WHERE fkey_e_id = 'printPDF' AND var_name = 'printLegend');

-- allow inheritance of classification for coupled resources (layer/featuretype)
-- layer ***********************************************************************
-- Column: fkey_metadata_id

-- ALTER TABLE layer_inspire_category DROP COLUMN fkey_metadata_id;

ALTER TABLE layer_inspire_category ADD COLUMN fkey_metadata_id integer;

-- Foreign Key: layer_inspire_category_fkey_metadata_id_fkey

-- ALTER TABLE layer_inspire_category DROP CONSTRAINT layer_inspire_category_fkey_metadata_id_fkey;

ALTER TABLE layer_inspire_category
  ADD CONSTRAINT layer_inspire_category_fkey_metadata_id_fkey FOREIGN KEY (fkey_metadata_id)
      REFERENCES mb_metadata (metadata_id) MATCH SIMPLE
      ON UPDATE CASCADE ON DELETE CASCADE;


-- ALTER TABLE layer_md_topic_category DROP COLUMN fkey_metadata_id;

ALTER TABLE layer_md_topic_category ADD COLUMN fkey_metadata_id integer;

-- Foreign Key: layer_md_topic_category_fkey_metadata_id_fkey

-- ALTER TABLE layer_md_topic_category DROP CONSTRAINT layer_md_topic_category_fkey_metadata_id_fkey;

ALTER TABLE layer_md_topic_category
  ADD CONSTRAINT layer_md_topic_category_fkey_metadata_id_fkey FOREIGN KEY (fkey_metadata_id)
      REFERENCES mb_metadata (metadata_id) MATCH SIMPLE
      ON UPDATE CASCADE ON DELETE CASCADE;

-- ALTER TABLE layer_custom_category DROP COLUMN fkey_metadata_id;

ALTER TABLE layer_custom_category ADD COLUMN fkey_metadata_id integer;

-- Foreign Key: layer_custom_category_fkey_metadata_id_fkey

-- ALTER TABLE layer_custom_category DROP CONSTRAINT layer_custom_category_fkey_metadata_id_fkey;

ALTER TABLE layer_custom_category
  ADD CONSTRAINT layer_custom_category_fkey_metadata_id_fkey FOREIGN KEY (fkey_metadata_id)
      REFERENCES mb_metadata (metadata_id) MATCH SIMPLE
      ON UPDATE CASCADE ON DELETE CASCADE;

-- featuretype ***********************************************************************
-- Column: fkey_metadata_id

-- ALTER TABLE wfs_featuretype_inspire_category DROP COLUMN fkey_metadata_id;

ALTER TABLE wfs_featuretype_inspire_category ADD COLUMN fkey_metadata_id integer;

-- Foreign Key: wfs_featuretype_inspire_category_fkey_metadata_id_fkey

-- ALTER TABLE wfs_featuretype_inspire_category DROP CONSTRAINT wfs_featuretype_inspire_category_fkey_metadata_id_fkey;

ALTER TABLE wfs_featuretype_inspire_category
  ADD CONSTRAINT wfs_featuretype_inspire_category_fkey_metadata_id_fkey FOREIGN KEY (fkey_metadata_id)
      REFERENCES mb_metadata (metadata_id) MATCH SIMPLE
      ON UPDATE CASCADE ON DELETE CASCADE;


-- ALTER TABLE wfs_featuretype_md_topic_category DROP COLUMN fkey_metadata_id;

ALTER TABLE wfs_featuretype_md_topic_category ADD COLUMN fkey_metadata_id integer;

-- Foreign Key: wfs_featuretype_md_topic_category_fkey_metadata_id_fkey

-- ALTER TABLE wfs_featuretype_md_topic_category DROP CONSTRAINT wfs_featuretype_md_topic_category_fkey_metadata_id_fkey;

ALTER TABLE wfs_featuretype_md_topic_category
  ADD CONSTRAINT wfs_featuretype_md_topic_category_fkey_metadata_id_fkey FOREIGN KEY (fkey_metadata_id)
      REFERENCES mb_metadata (metadata_id) MATCH SIMPLE
      ON UPDATE CASCADE ON DELETE CASCADE;

-- ALTER TABLE wfs_featuretype_custom_category DROP COLUMN fkey_metadata_id;

ALTER TABLE wfs_featuretype_custom_category ADD COLUMN fkey_metadata_id integer;

-- Foreign Key: wfs_featuretype_custom_category_fkey_metadata_id_fkey

-- ALTER TABLE wfs_featuretype_custom_category DROP CONSTRAINT wfs_featuretype_custom_category_fkey_metadata_id_fkey;

ALTER TABLE wfs_featuretype_custom_category
  ADD CONSTRAINT wfs_featuretype_custom_category_fkey_metadata_id_fkey FOREIGN KEY (fkey_metadata_id)
      REFERENCES mb_metadata (metadata_id) MATCH SIMPLE
      ON UPDATE CASCADE ON DELETE CASCADE;

-- Function: f_collect_custom_cat_layer(integer)

-- DROP FUNCTION f_collect_custom_cat_layer(integer);

CREATE OR REPLACE FUNCTION f_collect_custom_cat_layer(integer)
  RETURNS text AS
$BODY$DECLARE
  i_layer_id ALIAS FOR $1;
  custom_cat_string  TEXT;
  custom_cat_record  RECORD;

BEGIN
custom_cat_string := '';

FOR custom_cat_record IN SELECT DISTINCT layer_custom_category.fkey_custom_category_id from layer_custom_category WHERE layer_custom_category.fkey_layer_id=$1  LOOP
custom_cat_string := custom_cat_string || '{' ||custom_cat_record.fkey_custom_category_id || '}';
END LOOP ;
  
RETURN custom_cat_string;

END;
$BODY$
  LANGUAGE plpgsql VOLATILE STRICT
  COST 100;
ALTER FUNCTION f_collect_custom_cat_layer(integer)
  OWNER TO postgres;

-- Function: f_collect_custom_cat_wfs_featuretype(integer)

-- DROP FUNCTION f_collect_custom_cat_wfs_featuretype(integer);

CREATE OR REPLACE FUNCTION f_collect_custom_cat_wfs_featuretype(integer)
  RETURNS text AS
$BODY$DECLARE
  i_featuretype_id ALIAS FOR $1;
  custom_cat_string  TEXT;
  custom_cat_record  RECORD;

BEGIN
custom_cat_string := '';

FOR custom_cat_record IN SELECT DISTINCT wfs_featuretype_custom_category.fkey_custom_category_id from wfs_featuretype_custom_category WHERE wfs_featuretype_custom_category.fkey_featuretype_id=$1  LOOP
custom_cat_string := custom_cat_string || '{' ||custom_cat_record.fkey_custom_category_id || '}';
END LOOP ;
  
RETURN custom_cat_string;

END;
$BODY$
  LANGUAGE plpgsql VOLATILE STRICT
  COST 100;
ALTER FUNCTION f_collect_custom_cat_wfs_featuretype(integer)
  OWNER TO postgres;

-- Function: f_collect_inspire_cat_layer(integer)

-- DROP FUNCTION f_collect_inspire_cat_layer(integer);

CREATE OR REPLACE FUNCTION f_collect_inspire_cat_layer(integer)
  RETURNS text AS
$BODY$DECLARE
  i_layer_id ALIAS FOR $1;
  inspire_cat_string  TEXT;
  inspire_cat_record  RECORD;

BEGIN
inspire_cat_string := '';

FOR inspire_cat_record IN SELECT DISTINCT layer_inspire_category.fkey_inspire_category_id from layer_inspire_category WHERE layer_inspire_category.fkey_layer_id=$1  LOOP
inspire_cat_string := inspire_cat_string || '{' ||inspire_cat_record.fkey_inspire_category_id || '}';
END LOOP ;
  
RETURN inspire_cat_string;

END;
$BODY$
  LANGUAGE plpgsql VOLATILE STRICT
  COST 100;
ALTER FUNCTION f_collect_inspire_cat_layer(integer)
  OWNER TO postgres;

-- Function: f_collect_inspire_cat_wfs_featuretype(integer)

-- DROP FUNCTION f_collect_inspire_cat_wfs_featuretype(integer);

CREATE OR REPLACE FUNCTION f_collect_inspire_cat_wfs_featuretype(integer)
  RETURNS text AS
$BODY$DECLARE
  i_wfs_featuretype_id ALIAS FOR $1;
  inspire_cat_string  TEXT;
  inspire_cat_record  RECORD;

BEGIN
inspire_cat_string := '';

FOR inspire_cat_record IN SELECT DISTINCT wfs_featuretype_inspire_category.fkey_inspire_category_id from wfs_featuretype_inspire_category WHERE wfs_featuretype_inspire_category.fkey_featuretype_id=$1  LOOP
inspire_cat_string := inspire_cat_string || '{' ||inspire_cat_record.fkey_inspire_category_id || '}';
END LOOP ;
  
RETURN inspire_cat_string;

END;
$BODY$
  LANGUAGE plpgsql VOLATILE STRICT
  COST 100;
ALTER FUNCTION f_collect_inspire_cat_wfs_featuretype(integer)
  OWNER TO postgres;

-- Function: f_collect_topic_cat_layer(integer)

-- DROP FUNCTION f_collect_topic_cat_layer(integer);

CREATE OR REPLACE FUNCTION f_collect_topic_cat_layer(integer)
  RETURNS text AS
$BODY$DECLARE
  i_layer_id ALIAS FOR $1;
  topic_cat_string  TEXT;
  topic_cat_record  RECORD;

BEGIN
topic_cat_string := '';

FOR topic_cat_record IN SELECT DISTINCT layer_md_topic_category.fkey_md_topic_category_id from layer_md_topic_category WHERE layer_md_topic_category.fkey_layer_id=$1  LOOP
topic_cat_string := topic_cat_string || '{' ||topic_cat_record.fkey_md_topic_category_id || '}';
END LOOP ;
  
RETURN topic_cat_string;

END;
$BODY$
  LANGUAGE plpgsql VOLATILE STRICT
  COST 100;
ALTER FUNCTION f_collect_topic_cat_layer(integer)
  OWNER TO postgres;

-- Function: f_collect_topic_cat_wfs_featuretype(integer)

-- DROP FUNCTION f_collect_topic_cat_wfs_featuretype(integer);

CREATE OR REPLACE FUNCTION f_collect_topic_cat_wfs_featuretype(integer)
  RETURNS text AS
$BODY$DECLARE
  i_featuretype_id ALIAS FOR $1;
  topic_cat_string  TEXT;
  topic_cat_record  RECORD;

BEGIN
topic_cat_string := '';

FOR topic_cat_record IN SELECT DISTINCT wfs_featuretype_md_topic_category.fkey_md_topic_category_id from wfs_featuretype_md_topic_category WHERE wfs_featuretype_md_topic_category.fkey_featuretype_id=$1  LOOP
topic_cat_string := topic_cat_string || '{' ||topic_cat_record.fkey_md_topic_category_id || '}';
END LOOP ;
  
RETURN topic_cat_string;

END;
$BODY$
  LANGUAGE plpgsql VOLATILE STRICT
  COST 100;
ALTER FUNCTION f_collect_topic_cat_wfs_featuretype(integer)
  OWNER TO postgres;


--exchange wms_max_imagesize with x and y components - see wms 1.3 spec
-- Column: wms_max_imagesize_x

-- ALTER TABLE wms DROP COLUMN wms_max_imagesize_x;

ALTER TABLE wms ADD COLUMN wms_max_imagesize_x integer;
ALTER TABLE wms ALTER COLUMN wms_max_imagesize_x SET DEFAULT 1000;

-- Column: wms_max_imagesize_y

-- ALTER TABLE wms DROP COLUMN wms_max_imagesize_y;

ALTER TABLE wms ADD COLUMN wms_max_imagesize_y integer;
ALTER TABLE wms ALTER COLUMN wms_max_imagesize_y SET DEFAULT 1000;

-- the old values have to be transfered to the new ones - if wished
-- the logic will get the lowest value from x and y for the wms_max_imagesize!


-- mb_metadata
-- New column: bounding_geom for generating inspire dls more accurate

-- ALTER TABLE mb_metadata DROP COLUMN bounding_geom;

ALTER TABLE mb_metadata ADD COLUMN bounding_geom geometry;


--inspire monitoring and reporting

-- ALTER TABLE mb_metadata DROP COLUMN inspire_whole_area;

ALTER TABLE mb_metadata ADD COLUMN inspire_whole_area bigint;

-- ALTER TABLE mb_metadata DROP COLUMN inspire_actual_coverage;

ALTER TABLE mb_metadata ADD COLUMN inspire_actual_coverage bigint; 

-- ALTER TABLE wms DROP COLUMN inspire_daily_requests;

ALTER TABLE wms ADD COLUMN inspire_daily_requests double precision;

-- ALTER TABLE wfs DROP COLUMN inspire_daily_requests;

ALTER TABLE wfs ADD COLUMN inspire_daily_requests double precision;


-- Check: enforce_geotype_the_geom - allow also polygons!

ALTER TABLE mb_metadata DROP CONSTRAINT enforce_geotype_the_geom;



ALTER TABLE mb_metadata
  ADD CONSTRAINT enforce_geotype_the_geom CHECK (geometrytype(the_geom) = 'MULTIPOLYGON'::text OR the_geom IS NULL OR geometrytype(the_geom) = 'POLYGON'::text);

-- Column: datalinks - for storing download links to datasets as json object

-- ALTER TABLE mb_metadata DROP COLUMN datalinks;

ALTER TABLE mb_metadata ADD COLUMN datalinks text;

INSERT INTO gui_element(fkey_gui_id, e_id, e_pos, e_public, e_comment, e_title, e_element, e_src, e_attributes, e_left, e_top, e_width, e_height, e_z_index, e_more_styles, e_content, e_closetag, e_js_file, e_mb_mod, e_target, e_requires, e_url) VALUES('admin_wms_metadata','mb_metadata_gml_import',1,1,'','','div','','',NULL ,NULL ,NULL ,NULL ,NULL ,'','','div','../plugins/mb_metadata_gml_import.js','','','','');

INSERT INTO gui_element(fkey_gui_id, e_id, e_pos, e_public, e_comment, e_title, e_element, e_src, e_attributes, e_left, e_top, e_width, e_height, e_z_index, e_more_styles, e_content, e_closetag, e_js_file, e_mb_mod, e_target, e_requires, e_url) VALUES('admin_wfs_metadata','mb_metadata_gml_import',1,1,'','','div','','',NULL ,NULL ,NULL ,NULL ,NULL ,'','','div','../plugins/mb_metadata_gml_import.js','','','','');



