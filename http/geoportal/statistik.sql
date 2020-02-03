CREATE OR REPLACE VIEW statistik AS 

SELECT a.users, b.total_authorities, c.substitute_authorities, d.publishing_authorities, e.wms, f.wms_layer, g.wfs, h.wfs_modul, i.wmc
   FROM ( SELECT count(mb_user.mb_user_id) AS users
           FROM mb_user) a, ( SELECT count(total_registrating_authorities.mb_group_id) AS total_authorities
           FROM total_registrating_authorities) b, ( SELECT count(substitute_registrating_authorities.mb_group_id) AS substitute_authorities
           FROM substitute_registrating_authorities) c, ( SELECT count(publishing_registrating_authorities.mb_group_id) AS publishing_authorities
           FROM publishing_registrating_authorities) d, ( SELECT count(DISTINCT search_wms_view.wms_id) AS wms
           FROM search_wms_view) e, ( SELECT count(search_wms_view.layer_id) AS wms_layer
           FROM search_wms_view) f, ( SELECT count(DISTINCT search_wfs_view.wfs_id) AS wfs
           FROM search_wfs_view) g, ( SELECT count(search_wfs_view.wfs_conf_id) AS wfs_modul
           FROM search_wfs_view) h, ( SELECT count(search_wmc_view.wmc_id) AS wmc
           FROM search_wmc_view) i;
