INSERT INTO gui_element(fkey_gui_id, e_id, e_pos, e_public, e_comment, e_title, e_element, e_src, e_attributes, e_left, e_top, e_width, e_height, e_z_index, e_more_styles, e_content, e_closetag, e_js_file, e_mb_mod, e_target, e_requires, e_url) VALUES('Geoportal-Hessen-2019','exportMapimage_Button',1,1,'','Export Kartenbild','A','','onclick=''window.open("../javascripts/mod_exportMapImage.php?target=mapframe1&sessionID","exportMapImage","width=250, height=220, resizable=yes ")'' title="Export des aktuellen Kartenbilds"',NULL ,NULL ,NULL ,NULL ,NULL ,'','<svg xmlns="http://www.w3.org/2000/svg" width="18" height="18" viewBox="0 0 48 48"><g fill="none" stroke="currentColor" stroke-linejoin="round" stroke-width="4"><rect width="36" height="36" x="6" y="6" rx="3"/><path stroke-linecap="round" d="m6 28l10.693-9.802a2 2 0 0 1 2.653-.044L32 29m-4-5l4.773-4.773a2 2 0 0 1 2.615-.186L42 24M6 20v10m36-10v10"/></g></svg>Bildexport','A','','','exportMapimage','','');
INSERT INTO gui_element_vars(fkey_gui_id, fkey_e_id, var_name, var_value, context, var_type) VALUES('Geoportal-Hessen-2019', 'exportMapimage_Button', 'css', '/* INSERT exportMapimage_Button -> elementVar -> css(text/css) */
#toolsContainer #exportMapimage_Button svg {
margin-left:17px;
margin-right:8px;
}
#toolsContainer #exportMapimage_Button:hover {
  background-color: #EEE;
  color: #333;
}
/* END INSERT exportMapimage_Button -> elementVar -> css(text/css) */', '' ,'text/css');
INSERT INTO gui_element_vars(fkey_gui_id, fkey_e_id, var_name, var_value, context, var_type) VALUES('Geoportal-Hessen-2019', 'exportMapimage_Button', 'geotiffExport', 'true', '' ,'var');
INSERT INTO gui_element_vars(fkey_gui_id, fkey_e_id, var_name, var_value, context, var_type) VALUES('Geoportal-Hessen-2019', 'exportMapimage_Button', 'jpegExport', 'false', '' ,'var');
INSERT INTO gui_element_vars(fkey_gui_id, fkey_e_id, var_name, var_value, context, var_type) VALUES('Geoportal-Hessen-2019', 'exportMapimage_Button', 'pngExport', 'true', '' ,'var');

UPDATE gui_element set e_target = 'legendButton,printPdfButton,exportMapimage_Button,altitudeProfile,wfsConfTree,changeEPSG_Button,coordsLookUp_Button,showCoords_div,measure_widget,kmlTree_Button,addWMS,deleteSessionWmc' WHERE fkey_gui_id = 'Geoportal-Hessen-2019' AND e_id = 'toolbarContainer';
