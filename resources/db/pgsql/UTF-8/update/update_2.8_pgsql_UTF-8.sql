UPDATE gui_element SET e_content='<div id="printPDF_working_bg"></div><div id="printPDF_working"><img src="../img/indicator_wheel.gif" style="padding:10px 0 0 10px">Generating PDF</div><div id="printPDF_input"><form id="printPDF_form" action="../print/printFactory.php"><div id="printPDF_selector"></div><div class="print_option"><input type="hidden" id="map_url" name="map_url" value=""/><input type="hidden" id="legend_url" name="legend_url" value=""/><input type="hidden" id="opacity" name="opacity" value=""/> <input type="hidden" id="overview_url" name="overview_url" value=""/><input type="hidden" id="map_scale" name="map_scale" value=""/><input type="hidden" name="measured_x_values" /><input type="hidden" name="measured_y_values" /><input type="hidden" name="map_svg_kml" /><input type="hidden" name="svg_extent" /><input type="hidden" name="map_svg_measures" /><br /></div><div class="print_option" id="printPDF_formsubmit"><input id="submit" type="submit" value="Print"><br /></div></form><div id="printPDF_result"></div></div>' WHERE e_id='printPDF';
/*
--create new database content 
--geoportal specific extensions 
ALTER TABLE mb_user ADD COLUMN mb_user_glossar character varying(5);
--ALTER TABLE mb_user ADD COLUMN mb_user_glossar character varying(14);
--ALTER TABLE mb_user ADD COLUMN mb_user_textsize character varying(14);
ALTER TABLE mb_user ADD COLUMN mb_user_textsize character varying(14);
ALTER TABLE mb_user ADD COLUMN mb_user_last_login_date date;
ALTER TABLE mb_user ADD COLUMN mb_user_spatial_suggest character varying(5);

UPDATE gui_category SET category_name='Anwendung' WHERE category_id=2;
UPDATE gui_category SET category_description='Anwendungen (Applications)' WHERE category_id=2;

--add anonymous user
INSERT INTO mb_user (mb_user_id, mb_user_name, mb_user_password, mb_user_owner, mb_user_description, mb_user_login_count, mb_user_email, mb_user_phone, mb_user_department, mb_user_resolution, mb_user_organisation_name, mb_user_position_name, mb_user_phone1, mb_user_facsimile, mb_user_delivery_point, mb_user_city, mb_user_postal_code, mb_user_country, mb_user_online_resource, mb_user_textsize, mb_user_glossar, mb_user_last_login_date, mb_user_digest, mb_user_realname, mb_user_street, mb_user_housenumber, mb_user_reference, mb_user_for_attention_of, mb_user_valid_from, mb_user_valid_to, mb_user_password_ticket, mb_user_firstname, mb_user_lastname, mb_user_academictitle, timestamp_create, timestamp, mb_user_spatial_suggest, mb_user_newsletter, mb_user_allow_survey, mb_user_aldigest) VALUES (2, 'guest', '084e0343a0486ff05530df6c705c8bb4', 1, 'test', 0, 'kontakt@geoportal.rlp.de', NULL, '', 72, '', '', NULL, NULL, NULL, '', NULL, NULL, NULL, 'textsize3', 'ja', '2012-01-26', '', NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, '2013-07-05 08:09:25.560359', '2015-08-20 10:04:04.952796', 'nein', true, true, NULL);

INSERT INTO mb_user (mb_user_id, mb_user_name, mb_user_password, mb_user_owner, mb_user_description, mb_user_login_count, mb_user_email, mb_user_phone, mb_user_department, mb_user_resolution, mb_user_organisation_name, mb_user_position_name, mb_user_phone1, mb_user_facsimile, mb_user_delivery_point, mb_user_city, mb_user_postal_code, mb_user_country, mb_user_online_resource, mb_user_textsize, mb_user_glossar, mb_user_last_login_date, mb_user_digest, mb_user_realname, mb_user_street, mb_user_housenumber, mb_user_reference, mb_user_for_attention_of, mb_user_valid_from, mb_user_valid_to, mb_user_password_ticket, mb_user_firstname, mb_user_lastname, mb_user_academictitle, timestamp_create, timestamp, mb_user_spatial_suggest, mb_user_newsletter, mb_user_allow_survey, mb_user_aldigest) VALUES (3, 'bereichsadmin1', '3ad58afdc417b975256af7a6d3eda7a5', 1, '', 0, 'kontakt@geoportal.rlp.de', NULL, NULL, 72, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, 'nein', '2017-07-28', '3c345c2af80400e1e4c94ed0a967e713', NULL, NULL, NULL, NULL, NULL, NULL, NULL, '', 'bereichsadmin1', 'bereichsadmin1', '', '2013-07-05 08:09:25.560359', '2017-07-28 10:12:13.926954', 'nein', false, false, '2a32c845b23d82bea4653810f146397b');


INSERT INTO mb_group VALUES (21, 'Bereichsadmin', 1, 'Diensteadministratoren der Behörden', '', NULL, '', '', '', '', '', '', '', '', '', NULL, NULL, '2013-07-05 08:09:25.732456', '2018-05-25 08:57:07.988259', NULL, NULL, NULL, NULL, NULL, NULL, true);

INSERT INTO mb_group VALUES (22, 'guest', 1, 'Gastgruppe', '', NULL, '', '', '', '', '', '', '', '', '', NULL, NULL, '2013-07-05 08:09:25.732456', '2018-05-25 08:57:07.988259', NULL, NULL, NULL, NULL, NULL, NULL, true);

INSERT INTO mb_group VALUES (23, 'testgruppe1', 1, 'testgruppe1', 'testgruppe1', NULL, 'Musterstraße 11', '11111 Musterstadt', 'Musterstadt', 'DE-RP', 'DE', '1111', '1111', 'mustermail@musterdomain.com', 'http://www.geoportal.rlp.de/metadata/GDI-RP_mit_Markenschutz_RGB_70.png', NULL, NULL, '2013-07-05 08:09:25.732456', '2018-05-25 08:57:07.988259', NULL, NULL, NULL, NULL, NULL, NULL, true);

--guest user into guest group
INSERT INTO mb_user_mb_group VALUES (2, 22, 1);

--bereichsadmin1 into guest group
INSERT INTO mb_user_mb_group VALUES (3, 22, 1);

--bereichsadmin1 into Bereichsadmin group
INSERT INTO mb_user_mb_group VALUES (3, 21, 1);

--bereichsadmin1 into testgruppe1 group - role primary
INSERT INTO mb_user_mb_group VALUES (3, 23, 2);

--bereichsadmin1 into testgruppe1 group - role standard
INSERT INTO mb_user_mb_group VALUES (3, 23, 1);

--root into guest group
INSERT INTO mb_user_mb_group VALUES (1, 22, 1);

--guis: Geoportal-RLP, Geoportal-RLP_erwSuche2, Administration_DE, Portal_Admin, Owsproxy_csv - admin_metadata fehlt noch!!!!

INSERT INTO gui (gui_id, gui_name, gui_description, gui_public) VALUES ('service_container1', 'service_container1', 'service_container1', 1);

INSERT INTO gui (gui_id, gui_name, gui_description, gui_public) VALUES ('service_container1_free', 'service_container1_free', 'service_container1_free', 1);

--guis: Geoportal-RLP, Administration_DE, Owsproxy_csv, admin_metadata, .....
DELETE FROM gui WHERE gui_id IN ('Geoportal-RLP', 'Owsproxy_csv', 'admin_wms_metadata', 'admin_wfs_metadata', 'admin_wmc_metadata', 'admin_metadata', 'admin_ows_scheduler', 'PortalAdmin_DE', 'Administration_DE');


--recreate them via psql
--psql -d mapbender -f /home/armin/GDI-RP/devel/Geoportal/mapbender_trunk/resources/db/gui_
psql -d mapbender -f /home/armin/GDI-RP/devel/Geoportal/mapbender_trunk/resources/db/gui_Geoportal-RLP.sql -- problem: too long entry ...
psql -d mapbender -f /home/armin/GDI-RP/devel/Geoportal/mapbender_trunk/resources/db/gui_Owsproxy_csv.sql
psql -d mapbender -f /home/armin/GDI-RP/devel/Geoportal/mapbender_trunk/resources/db/gui_admin_wms_metadata.sql
psql -d mapbender -f /home/armin/GDI-RP/devel/Geoportal/mapbender_trunk/resources/db/gui_admin_wfs_metadata.sql
psql -d mapbender -f /home/armin/GDI-RP/devel/Geoportal/mapbender_trunk/resources/db/gui_admin_wmc_metadata.sql
psql -d mapbender -f /home/armin/GDI-RP/devel/Geoportal/mapbender_trunk/resources/db/gui_admin_metadata.sql
psql -d mapbender -f /home/armin/GDI-RP/devel/Geoportal/mapbender_trunk/resources/db/gui_admin_ows_scheduler.sql
psql -d mapbender -f /home/armin/GDI-RP/devel/Geoportal/mapbender_trunk/resources/db/gui_PortalAdmin_DE.sql
psql -d mapbender -f /home/armin/GDI-RP/devel/Geoportal/mapbender_trunk/resources/db/gui_Administration_DE.sql

INSERT INTO gui_gui_category (fkey_gui_id, fkey_gui_category_id) VALUES ('Geoportal-RLP', 2);
INSERT INTO gui_gui_category (fkey_gui_id, fkey_gui_category_id) VALUES ('Administration_DE', 2);
INSERT INTO gui_gui_category (fkey_gui_id, fkey_gui_category_id) VALUES ('PortalAdmin_DE', 2);


INSERT INTO gui_mb_user (fkey_gui_id, fkey_mb_user_id, mb_user_type) VALUES ('PortalAdmin_DE', 1, 'owner');
INSERT INTO gui_mb_user (fkey_gui_id, fkey_mb_user_id, mb_user_type) VALUES ('Geoportal-RLP', 1, 'owner');
INSERT INTO gui_mb_user (fkey_gui_id, fkey_mb_user_id, mb_user_type) VALUES ('Administration_DE', 1, 'owner');
INSERT INTO gui_mb_user (fkey_gui_id, fkey_mb_user_id, mb_user_type) VALUES ('Owsproxy_csv', 1, 'owner');

INSERT INTO gui_mb_user (fkey_gui_id, fkey_mb_user_id, mb_user_type) VALUES ('admin_wms_metadata', 1, 'owner');
INSERT INTO gui_mb_user (fkey_gui_id, fkey_mb_user_id, mb_user_type) VALUES ('admin_wfs_metadata', 1, 'owner');
INSERT INTO gui_mb_user (fkey_gui_id, fkey_mb_user_id, mb_user_type) VALUES ('admin_wmc_metadata', 1, 'owner');
INSERT INTO gui_mb_user (fkey_gui_id, fkey_mb_user_id, mb_user_type) VALUES ('admin_metadata', 1, 'owner');

INSERT INTO gui_mb_user (fkey_gui_id, fkey_mb_user_id, mb_user_type) VALUES ('admin_ows_scheduler', 1, 'owner');

INSERT INTO gui_mb_user (fkey_gui_id, fkey_mb_user_id, mb_user_type) VALUES ('service_container1', 3, 'owner');
INSERT INTO gui_mb_user (fkey_gui_id, fkey_mb_user_id, mb_user_type) VALUES ('service_container1_free', 3, 'owner');

INSERT INTO gui_mb_group (fkey_gui_id, fkey_mb_group_id) VALUES ('Administration_DE', 21);
INSERT INTO gui_mb_group (fkey_gui_id, fkey_mb_group_id) VALUES ('Owsproxy_csv', 21);

INSERT INTO gui_mb_group (fkey_gui_id, fkey_mb_group_id) VALUES ('admin_wmc_metadata', 21);
INSERT INTO gui_mb_group (fkey_gui_id, fkey_mb_group_id) VALUES ('admin_wms_metadata', 21);
INSERT INTO gui_mb_group (fkey_gui_id, fkey_mb_group_id) VALUES ('admin_wfs_metadata', 21);
INSERT INTO gui_mb_group (fkey_gui_id, fkey_mb_group_id) VALUES ('admin_ows_scheduler', 21);
INSERT INTO gui_mb_group (fkey_gui_id, fkey_mb_group_id) VALUES ('admin_metadata', 21);


INSERT INTO gui_mb_group (fkey_gui_id, fkey_mb_group_id) VALUES ('Geoportal-RLP', 22);
INSERT INTO gui_mb_group (fkey_gui_id, fkey_mb_group_id) VALUES ('service_container1_free', 22);
*/

-- Function: f_simple_alter_service_operation_url(character varying, character varying, character varying)

-- DROP FUNCTION f_simple_alter_service_operation_url(character varying, character varying, character varying);

CREATE OR REPLACE FUNCTION f_simple_alter_service_operation_url(
    s_service_type character varying,
    s_old_service_operation_url character varying,
    s_new_service_operation_url character varying)
  RETURNS text AS
$BODY$DECLARE
	s_service_type ALIAS FOR $1;
	s_old_service_operation_url ALIAS FOR $2;
	s_new_service_operation_url ALIAS FOR $3;
	
  -- s_service_type is 'wms' or 'wfs'
  -- select f_simple_alter_service_operation_url('wms', 'http://1', 'http://2');

BEGIN

IF s_service_type='wms' THEN 
     update wms set wms_upload_url = replace(wms_upload_url,s_old_service_operation_url,s_new_service_operation_url), wms_getcapabilities = replace(wms_getcapabilities,s_old_service_operation_url,s_new_service_operation_url), wms_getlegendurl = replace(wms_getlegendurl,s_old_service_operation_url,s_new_service_operation_url), wms_getmap = replace(wms_getmap,s_old_service_operation_url,s_new_service_operation_url), wms_getfeatureinfo = replace(wms_getfeatureinfo,s_old_service_operation_url,s_new_service_operation_url) where wms_upload_url like (s_old_service_operation_url || '%');
ELSIF s_service_type='wfs' THEN 
     update wfs set wfs_upload_url = replace(wfs_upload_url, s_old_service_operation_url, s_new_service_operation_url), wfs_getcapabilities = replace(wfs_getcapabilities, s_old_service_operation_url, s_new_service_operation_url), wfs_getfeature = replace(wfs_getfeature, s_old_service_operation_url, s_new_service_operation_url), wfs_describefeaturetype = replace(wfs_describefeaturetype, s_old_service_operation_url, s_new_service_operation_url), wfs_transaction = replace(wfs_transaction, s_old_service_operation_url, s_new_service_operation_url) where wfs_upload_url like (s_old_service_operation_url || '%');
END IF;

RETURN 'urls exchanged';

END;
$BODY$
  LANGUAGE plpgsql VOLATILE STRICT
  COST 100;
ALTER FUNCTION f_simple_alter_service_operation_url(character varying, character varying, character varying)
  OWNER TO postgres;

-- 2019-09-13 - update css to have a nicer default gui!
update gui_element set e_width=null, e_height=null where e_id = 'body' AND fkey_gui_id IN ( 'Administration_DE', 'PortalAdmin_DE', 'admin1');
--update gui_element set e_width=null, e_height=null where e_id = 'body' AND fkey_gui_id IN ( 'BPlan-ID-Vergabe-Verbände','FPlan-ID-Vergabe');

--todo sql body element e_width=null ... e_styles: overflow:scroll; ....

-- new for search for applications - materialized view:

-- needs postgres 9.3+!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!
-- View: search_dataset_view

DROP  MATERIALIZED VIEW search_application_view;

CREATE MATERIALIZED VIEW search_application_view AS 
 SELECT DISTINCT ON (datasets.metadata_id) datasets.user_id,
    datasets.dataset_id,
    datasets.metadata_id,
    datasets.dataset_srs,
    datasets.title,
    datasets.dataset_abstract,
    datasets.accessconstraints,
    datasets.isopen,
    datasets.termsofuse,
    datasets.searchtext,
    datasets.dataset_timestamp,
    datasets.department,
    datasets.mb_group_name,
    datasets.mb_group_title,
    datasets.mb_group_country,
    datasets.load_count,
    datasets.mb_group_stateorprovince,
    datasets.md_inspire_cats,
    datasets.md_custom_cats,
    datasets.md_topic_cats,
    datasets.the_geom,
    datasets.bbox,
    datasets.preview_url,
    datasets.fileidentifier,
    datasets.coupled_resources,
    datasets.mb_group_logo_path,
    datasets.timebegin,
    datasets.timeend,
--new for application metadata
    datasets.link,
    datasets.fkey_gui_id,
    datasets.fkey_wmc_serial_id,
    datasets.fkey_mapviewer_id

   FROM ( SELECT dataset_dep.fkey_mb_user_id AS user_id,
            dataset_dep.dataset_id,
            dataset_dep.dataset_id AS metadata_id,
            dataset_dep.srs AS dataset_srs,
            dataset_dep.title,
            dataset_dep.abstract AS dataset_abstract,
            dataset_dep.accessconstraints,
            dataset_dep.isopen,
            dataset_dep.termsofuse,
            f_collect_searchtext_dataset(dataset_dep.dataset_id) AS searchtext,
            dataset_dep.dataset_timestamp,
            dataset_dep.department,
            dataset_dep.mb_group_name,
            dataset_dep.mb_group_title,
            dataset_dep.mb_group_country,
                CASE
                    WHEN dataset_dep.load_count IS NULL THEN 0::bigint
                    ELSE dataset_dep.load_count
                END AS load_count,
            dataset_dep.mb_group_stateorprovince,
            f_collect_inspire_cat_dataset(dataset_dep.dataset_id) AS md_inspire_cats,
            f_collect_custom_cat_dataset(dataset_dep.dataset_id) AS md_custom_cats,
            f_collect_topic_cat_dataset(dataset_dep.dataset_id) AS md_topic_cats,
            dataset_dep.bbox AS the_geom,
            (((((st_xmin(dataset_dep.bbox::box3d)::text || ','::text) || st_ymin(dataset_dep.bbox::box3d)::text) || ','::text) || st_xmax(dataset_dep.bbox::box3d)::text) || ','::text) || st_ymax(dataset_dep.bbox::box3d)::text AS bbox,
            dataset_dep.preview_url,
            dataset_dep.fileidentifier,
            f_get_coupled_resources(dataset_dep.dataset_id) AS coupled_resources,
            dataset_dep.mb_group_logo_path,
            dataset_dep.timebegin::date AS timebegin,
dataset_dep.link AS link,
dataset_dep.fkey_gui_id AS fkey_gui_id,
dataset_dep.fkey_wmc_serial_id AS fkey_wmc_serial_id,
dataset_dep.fkey_mapviewer_id AS fkey_mapviewer_id,

                CASE
                    WHEN dataset_dep.update_frequency::text = 'continual'::text THEN now()::date
                    WHEN dataset_dep.update_frequency::text = 'daily'::text THEN now()::date
                    WHEN dataset_dep.update_frequency::text = 'weekly'::text THEN (now() - '7 days'::interval)::date
                    WHEN dataset_dep.update_frequency::text = 'fortnightly'::text THEN (now() - '14 days'::interval)::date
                    WHEN dataset_dep.update_frequency::text = 'monthly'::text THEN (now() - '1 mon'::interval)::date
                    WHEN dataset_dep.update_frequency::text = 'quarterly'::text THEN (now() - '3 mons'::interval)::date
                    WHEN dataset_dep.update_frequency::text = 'biannually'::text THEN (now() - '6 mons'::interval)::date
                    WHEN dataset_dep.update_frequency::text = 'annually'::text THEN (now() - '1 year'::interval)::date
                    ELSE dataset_dep.timeend::date
                END AS timeend
           FROM ( SELECT mb_metadata.the_geom AS bbox,
                    mb_metadata.ref_system AS srs,
                    mb_metadata.metadata_id AS dataset_id,
                    mb_metadata.title,
                    mb_metadata.abstract,
                    mb_metadata.lastchanged AS dataset_timestamp,
                    mb_metadata.tmp_reference_1 AS timebegin,
                    mb_metadata.tmp_reference_2 AS timeend,
                    mb_metadata.uuid AS fileidentifier,
                    mb_metadata.preview_image AS preview_url,
                    mb_metadata.load_count,
                    mb_metadata.fkey_mb_user_id,
                    mb_metadata.constraints AS accessconstraints,
                    mb_metadata.update_frequency,
                    f_getmd_tou(mb_metadata.metadata_id) AS termsofuse,
                    f_tou_isopen(f_getmd_tou(mb_metadata.metadata_id)) AS isopen,
                    mb_metadata.mb_group_id AS department,
                    mb_metadata.mb_group_name,
                    mb_metadata.mb_group_title,
                    mb_metadata.mb_group_country,
                    mb_metadata.mb_group_stateorprovince,
                    mb_metadata.mb_group_logo_path,
mb_metadata.link,
mb_metadata.fkey_gui_id,
mb_metadata.fkey_wmc_serial_id,
mb_metadata.fkey_mapviewer_id

                   FROM ( SELECT mb_metadata_1.metadata_id,
                            mb_metadata_1.uuid,
                            mb_metadata_1.origin,
                            mb_metadata_1.includeincaps,
                            mb_metadata_1.fkey_mb_group_id,
                            mb_metadata_1.schema,
                            mb_metadata_1.createdate,
                            mb_metadata_1.changedate,
                            mb_metadata_1.lastchanged,
                            mb_metadata_1.link,
                            mb_metadata_1.linktype,
                            mb_metadata_1.md_format,
                            mb_metadata_1.title,
                            mb_metadata_1.abstract,
                            mb_metadata_1.searchtext,
                            mb_metadata_1.status,
                            mb_metadata_1.type,
                            mb_metadata_1.harvestresult,
                            mb_metadata_1.harvestexception,
                            mb_metadata_1.export2csw,
                            mb_metadata_1.tmp_reference_1,
                            mb_metadata_1.tmp_reference_2,
                            mb_metadata_1.spatial_res_type,
                            mb_metadata_1.spatial_res_value,
                            mb_metadata_1.ref_system,
                            mb_metadata_1.format,
                            mb_metadata_1.inspire_charset,
                            mb_metadata_1.inspire_top_consistence,
                            mb_metadata_1.fkey_mb_user_id,
                            mb_metadata_1.responsible_party,
                            mb_metadata_1.individual_name,
                            mb_metadata_1.visibility,
                            mb_metadata_1.locked,
                            mb_metadata_1.copyof,
                            mb_metadata_1.constraints,
                            mb_metadata_1.fees,
                            mb_metadata_1.classification,
                            mb_metadata_1.browse_graphic,
                            mb_metadata_1.inspire_conformance,
                            mb_metadata_1.preview_image,
                            mb_metadata_1.the_geom,
                            mb_metadata_1.lineage,
                            mb_metadata_1.datasetid,
                            mb_metadata_1.randomid,
                            mb_metadata_1.update_frequency,
                            mb_metadata_1.datasetid_codespace,
                            mb_metadata_1.bounding_geom,
                            mb_metadata_1.inspire_whole_area,
                            mb_metadata_1.inspire_actual_coverage,
                            mb_metadata_1.datalinks,
                            mb_metadata_1.inspire_download,
                            mb_metadata_1.transfer_size,
                            mb_metadata_1.md_license_source_note,
                            mb_metadata_1.responsible_party_name,
                            mb_metadata_1.responsible_party_email,
                            mb_metadata_1.searchable,
                            mb_metadata_1.load_count,

mb_metadata_1.fkey_gui_id,
mb_metadata_1.fkey_wmc_serial_id,
mb_metadata_1.fkey_mapviewer_id,

                            user_dep.fkey_mb_group_id,
                            user_dep.mb_group_id,
                            user_dep.mb_group_name,
                            user_dep.mb_group_title,
                            user_dep.mb_group_country,
                            user_dep.mb_group_stateorprovince,
                            user_dep.mb_group_logo_path,
                            user_dep.fkey_mb_user_id_from_users
                           FROM ( SELECT mb_metadata_2.metadata_id,
                                    mb_metadata_2.uuid,
                                    mb_metadata_2.origin,
                                    mb_metadata_2.includeincaps,
                                    mb_metadata_2.fkey_mb_group_id,
                                    mb_metadata_2.schema,
                                    mb_metadata_2.createdate,
                                    mb_metadata_2.changedate,
                                    mb_metadata_2.lastchanged,
                                    mb_metadata_2.link,
                                    mb_metadata_2.linktype,
                                    mb_metadata_2.md_format,
                                    mb_metadata_2.title,
                                    mb_metadata_2.abstract,
                                    mb_metadata_2.searchtext,
                                    mb_metadata_2.status,
                                    mb_metadata_2.type,
                                    mb_metadata_2.harvestresult,
                                    mb_metadata_2.harvestexception,
                                    mb_metadata_2.export2csw,
                                    mb_metadata_2.tmp_reference_1,
                                    mb_metadata_2.tmp_reference_2,
                                    mb_metadata_2.spatial_res_type,
                                    mb_metadata_2.spatial_res_value,
                                    mb_metadata_2.ref_system,
                                    mb_metadata_2.format,
                                    mb_metadata_2.inspire_charset,
                                    mb_metadata_2.inspire_top_consistence,
                                    mb_metadata_2.fkey_mb_user_id,
                                    mb_metadata_2.responsible_party,
                                    mb_metadata_2.individual_name,
                                    mb_metadata_2.visibility,
                                    mb_metadata_2.locked,
                                    mb_metadata_2.copyof,
                                    mb_metadata_2.constraints,
                                    mb_metadata_2.fees,
                                    mb_metadata_2.classification,
                                    mb_metadata_2.browse_graphic,
                                    mb_metadata_2.inspire_conformance,
                                    mb_metadata_2.preview_image,
                                    mb_metadata_2.the_geom,
                                    mb_metadata_2.lineage,
                                    mb_metadata_2.datasetid,
                                    mb_metadata_2.randomid,
                                    mb_metadata_2.update_frequency,
                                    mb_metadata_2.datasetid_codespace,
                                    mb_metadata_2.bounding_geom,
                                    mb_metadata_2.inspire_whole_area,
                                    mb_metadata_2.inspire_actual_coverage,
                                    mb_metadata_2.datalinks,
                                    mb_metadata_2.inspire_download,
                                    mb_metadata_2.transfer_size,
                                    mb_metadata_2.md_license_source_note,
                                    mb_metadata_2.responsible_party_name,
                                    mb_metadata_2.responsible_party_email,
                                    mb_metadata_2.searchable,

mb_metadata_2.fkey_gui_id,
mb_metadata_2.fkey_wmc_serial_id,
mb_metadata_2.fkey_mapviewer_id,

                                    metadata_load_count.load_count
                                   FROM mb_metadata mb_metadata_2
                                     LEFT JOIN metadata_load_count ON mb_metadata_2.metadata_id = metadata_load_count.fkey_metadata_id) mb_metadata_1,
                            ( SELECT groups_for_publishing.fkey_mb_group_id,
                                    groups_for_publishing.mb_group_id,
                                    groups_for_publishing.mb_group_name,
                                    groups_for_publishing.mb_group_title,
                                    groups_for_publishing.mb_group_country,
                                    groups_for_publishing.mb_group_stateorprovince,
                                    groups_for_publishing.mb_group_logo_path,
                                    0 AS fkey_mb_user_id_from_users
                                   FROM groups_for_publishing) user_dep
                          WHERE mb_metadata_1.fkey_mb_group_id = user_dep.mb_group_id AND mb_metadata_1.the_geom IS NOT NULL AND mb_metadata_1.searchable IS TRUE AND mb_metadata_1.type = 'application'
                        UNION ALL
                         SELECT mb_metadata_1.metadata_id,
                            mb_metadata_1.uuid,
                            mb_metadata_1.origin,
                            mb_metadata_1.includeincaps,
                            mb_metadata_1.fkey_mb_group_id,
                            mb_metadata_1.schema,
                            mb_metadata_1.createdate,
                            mb_metadata_1.changedate,
                            mb_metadata_1.lastchanged,
                            mb_metadata_1.link,
                            mb_metadata_1.linktype,
                            mb_metadata_1.md_format,
                            mb_metadata_1.title,
                            mb_metadata_1.abstract,
                            mb_metadata_1.searchtext,
                            mb_metadata_1.status,
                            mb_metadata_1.type,
                            mb_metadata_1.harvestresult,
                            mb_metadata_1.harvestexception,
                            mb_metadata_1.export2csw,
                            mb_metadata_1.tmp_reference_1,
                            mb_metadata_1.tmp_reference_2,
                            mb_metadata_1.spatial_res_type,
                            mb_metadata_1.spatial_res_value,
                            mb_metadata_1.ref_system,
                            mb_metadata_1.format,
                            mb_metadata_1.inspire_charset,
                            mb_metadata_1.inspire_top_consistence,
                            mb_metadata_1.fkey_mb_user_id,
                            mb_metadata_1.responsible_party,
                            mb_metadata_1.individual_name,
                            mb_metadata_1.visibility,
                            mb_metadata_1.locked,
                            mb_metadata_1.copyof,
                            mb_metadata_1.constraints,
                            mb_metadata_1.fees,
                            mb_metadata_1.classification,
                            mb_metadata_1.browse_graphic,
                            mb_metadata_1.inspire_conformance,
                            mb_metadata_1.preview_image,
                            mb_metadata_1.the_geom,
                            mb_metadata_1.lineage,
                            mb_metadata_1.datasetid,
                            mb_metadata_1.randomid,
                            mb_metadata_1.update_frequency,
                            mb_metadata_1.datasetid_codespace,
                            mb_metadata_1.bounding_geom,
                            mb_metadata_1.inspire_whole_area,
                            mb_metadata_1.inspire_actual_coverage,
                            mb_metadata_1.datalinks,
                            mb_metadata_1.inspire_download,
                            mb_metadata_1.transfer_size,
                            mb_metadata_1.md_license_source_note,
                            mb_metadata_1.responsible_party_name,
                            mb_metadata_1.responsible_party_email,
                            mb_metadata_1.searchable,
                            mb_metadata_1.load_count,

mb_metadata_1.fkey_gui_id,
mb_metadata_1.fkey_wmc_serial_id,
mb_metadata_1.fkey_mapviewer_id,

                            user_dep.fkey_mb_group_id,
                            user_dep.mb_group_id,
                            user_dep.mb_group_name,
                            user_dep.mb_group_title,
                            user_dep.mb_group_country,
                            user_dep.mb_group_stateorprovince,
                            user_dep.mb_group_logo_path,
                            user_dep.fkey_mb_user_id_from_users
                           FROM ( SELECT mb_metadata_2.metadata_id,
                                    mb_metadata_2.uuid,
                                    mb_metadata_2.origin,
                                    mb_metadata_2.includeincaps,
                                    mb_metadata_2.fkey_mb_group_id,
                                    mb_metadata_2.schema,
                                    mb_metadata_2.createdate,
                                    mb_metadata_2.changedate,
                                    mb_metadata_2.lastchanged,
                                    mb_metadata_2.link,
                                    mb_metadata_2.linktype,
                                    mb_metadata_2.md_format,
                                    mb_metadata_2.title,
                                    mb_metadata_2.abstract,
                                    mb_metadata_2.searchtext,
                                    mb_metadata_2.status,
                                    mb_metadata_2.type,
                                    mb_metadata_2.harvestresult,
                                    mb_metadata_2.harvestexception,
                                    mb_metadata_2.export2csw,
                                    mb_metadata_2.tmp_reference_1,
                                    mb_metadata_2.tmp_reference_2,
                                    mb_metadata_2.spatial_res_type,
                                    mb_metadata_2.spatial_res_value,
                                    mb_metadata_2.ref_system,
                                    mb_metadata_2.format,
                                    mb_metadata_2.inspire_charset,
                                    mb_metadata_2.inspire_top_consistence,
                                    mb_metadata_2.fkey_mb_user_id,
                                    mb_metadata_2.responsible_party,
                                    mb_metadata_2.individual_name,
                                    mb_metadata_2.visibility,
                                    mb_metadata_2.locked,
                                    mb_metadata_2.copyof,
                                    mb_metadata_2.constraints,
                                    mb_metadata_2.fees,
                                    mb_metadata_2.classification,
                                    mb_metadata_2.browse_graphic,
                                    mb_metadata_2.inspire_conformance,
                                    mb_metadata_2.preview_image,
                                    mb_metadata_2.the_geom,
                                    mb_metadata_2.lineage,
                                    mb_metadata_2.datasetid,
                                    mb_metadata_2.randomid,
                                    mb_metadata_2.update_frequency,
                                    mb_metadata_2.datasetid_codespace,
                                    mb_metadata_2.bounding_geom,
                                    mb_metadata_2.inspire_whole_area,
                                    mb_metadata_2.inspire_actual_coverage,
                                    mb_metadata_2.datalinks,
                                    mb_metadata_2.inspire_download,
                                    mb_metadata_2.transfer_size,
                                    mb_metadata_2.md_license_source_note,
                                    mb_metadata_2.responsible_party_name,
                                    mb_metadata_2.responsible_party_email,
                                    mb_metadata_2.searchable,

mb_metadata_2.fkey_gui_id,
mb_metadata_2.fkey_wmc_serial_id,
mb_metadata_2.fkey_mapviewer_id,

                                    metadata_load_count.load_count
                                   FROM mb_metadata mb_metadata_2
                                     LEFT JOIN metadata_load_count ON mb_metadata_2.metadata_id = metadata_load_count.fkey_metadata_id) mb_metadata_1,
                            ( SELECT publishing_registrating_authorities.fkey_mb_group_id,
                                    publishing_registrating_authorities.mb_group_id,
                                    publishing_registrating_authorities.mb_group_name,
                                    publishing_registrating_authorities.mb_group_title,
                                    publishing_registrating_authorities.mb_group_country,
                                    publishing_registrating_authorities.mb_group_stateorprovince,
                                    publishing_registrating_authorities.mb_group_logo_path,
                                    users_for_publishing.fkey_mb_user_id AS fkey_mb_user_id_from_users
                                   FROM groups_for_publishing publishing_registrating_authorities,
                                    users_for_publishing
                                  WHERE users_for_publishing.primary_group_id = publishing_registrating_authorities.fkey_mb_group_id) user_dep
                          WHERE (mb_metadata_1.fkey_mb_group_id IS NULL OR mb_metadata_1.fkey_mb_group_id = 0) AND mb_metadata_1.fkey_mb_user_id = user_dep.fkey_mb_user_id_from_users AND mb_metadata_1.the_geom IS NOT NULL AND mb_metadata_1.searchable IS TRUE AND mb_metadata_1.type = 'application') mb_metadata(metadata_id, uuid, origin, includeincaps, fkey_mb_group_id, schema, createdate, changedate, lastchanged, link, linktype, md_format, title, abstract, searchtext, status, type, harvestresult, harvestexception, export2csw, tmp_reference_1, tmp_reference_2, spatial_res_type, spatial_res_value, ref_system, format, inspire_charset, inspire_top_consistence, fkey_mb_user_id, responsible_party, individual_name, visibility, locked, copyof, constraints, fees, classification, browse_graphic, inspire_conformance, preview_image, the_geom, lineage, datasetid, randomid, update_frequency, datasetid_codespace, bounding_geom, inspire_whole_area, inspire_actual_coverage, datalinks, inspire_download, transfer_size, md_license_source_note, responsible_party_name, responsible_party_email, searchable, load_count, fkey_gui_id, fkey_wmc_serial_id, fkey_mapviewer_id, fkey_mb_group_id_1, mb_group_id, mb_group_name, mb_group_title, mb_group_country, mb_group_stateorprovince, mb_group_logo_path, fkey_mb_user_id_from_users)) dataset_dep
          ORDER BY dataset_dep.dataset_id) datasets;

ALTER TABLE search_application_view
  OWNER TO postgres;
GRANT ALL ON TABLE search_application_view TO postgres;
GRANT ALL ON TABLE search_application_view TO mapbenderdbuser;

-- create indices:
--UPDATE search_application_view SET load_count=0 WHERE load_count is NULL;

-- Index: gist_wst_application_the_geom

-- DROP INDEX gist_wst_application_the_geom;

CREATE INDEX gist_wst_application_the_geom
  ON search_application_view
  USING gist
  (the_geom);

-- Index: idx_wst_application_searchtext

-- DROP INDEX idx_wst_application_searchtext;

CREATE INDEX idx_wst_application_searchtext
  ON search_application_view
  USING btree
  (searchtext);

-- Index: idx_wst_application_department

-- DROP INDEX idx_wst_application_department;

CREATE INDEX idx_wst_application_department
  ON search_application_view
  USING btree
  (department);
-- Index: idx_wst_application_md_topic_cats

-- DROP INDEX idx_wst_application_md_topic_cats;

CREATE INDEX idx_wst_application_md_topic_cats
  ON search_application_view
  USING btree
  (md_topic_cats);
-- Index: idx_wst_application_dataset_id

-- DROP INDEX idx_wst_application_metadata_id;

CREATE INDEX idx_wst_application_metadata_id
  ON search_application_view
  USING btree
  (metadata_id);

-- DROP INDEX idx_wst_application_metadata_id;

CREATE INDEX idx_wst_application_dataset_id
  ON search_application_view
  USING btree
  (dataset_id);
-- Index: idx_wst_application_md_inspire_cats

-- DROP INDEX idx_wst_application_md_inspire_cats;

CREATE INDEX idx_wst_application_md_inspire_cats
  ON search_application_view
  USING btree
  (md_inspire_cats);

-- Index: idx_wst_application_md_custom_cats

-- DROP INDEX idx_wst_application_md_custom_cats;

CREATE INDEX idx_wst_application_md_custom_cats
  ON search_application_view
  USING btree
  (md_custom_cats);

-- Index: idx_wst_application_timebegin

-- DROP INDEX idx_wst_application_timebegin;

CREATE INDEX idx_wst_application_timebegin
  ON search_application_view
  USING btree
  (timebegin);

-- Index: idx_wst_application_timeend

-- DROP INDEX idx_wst_application_timeend;

CREATE INDEX idx_wst_application_timeend
  ON search_application_view
  USING btree
  (timeend);

-- Index: idx_wst_application_department

-- DROP INDEX idx_wst_application_department;

CREATE INDEX idx_wst_application_timestamp
  ON search_application_view
  USING btree
  (dataset_timestamp);
  

--new table to log invocation of mapbender ogc api features proxy

-- Table: oaf_proxy_log

-- DROP TABLE oaf_proxy_log;

CREATE TABLE oaf_proxy_log
(
  log_id serial NOT NULL,
  createdate timestamp without time zone,
  lastchanged timestamp without time zone NOT NULL DEFAULT now(),
  referrer text,
  fkey_wfs_id integer,
  fkey_wfs_featuretype_id integer,
  log_count bigint,
  CONSTRAINT oaf_proxy_logc_fkey_wfs_id_fkey FOREIGN KEY (fkey_wfs_id)
      REFERENCES wfs (wfs_id) MATCH SIMPLE
      ON UPDATE CASCADE ON DELETE CASCADE
  CONSTRAINT oaf_proxy_logc_fkey_wfs_featuretype_id_fkey FOREIGN KEY (fkey_wfs_featuretype_id)
      REFERENCES wfs_featuretype (featuretype_id) MATCH SIMPLE
      ON UPDATE CASCADE ON DELETE CASCADE
)
WITH (
  OIDS=FALSE
);
ALTER TABLE oaf_proxy_log
  OWNER TO postgres;

-- Index: idx_oaf_proxy_log_referrer

-- DROP INDEX idx_oaf_proxy_log_referrer;

CREATE INDEX idx_oaf_proxy_log_referrer
  ON oaf_proxy_log
  USING btree
  (referrer);

-- Trigger: update_oaf_proxy_log_lastchanged on oaf_proxy_log

-- DROP TRIGGER update_oaf_proxy_log_lastchanged ON oaf_proxy_log;

CREATE TRIGGER update_oaf_proxy_log_lastchanged
  BEFORE UPDATE
  ON oaf_proxy_log
  FOR EACH ROW
  EXECUTE PROCEDURE update_lastchanged_column();

GRANT ALL ON TABLE oaf_proxy_log TO mapbenderdbuser;
GRANT ALL ON SEQUENCE oaf_proxy_log_log_id_seq TO mapbenderdbuser;

