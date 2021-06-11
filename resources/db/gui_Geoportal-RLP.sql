INSERT INTO gui (gui_id, gui_name, gui_description, gui_public) VALUES ('Geoportal-RLP','Geoportal-RLP','GUI combining most of the Mapbender functionality',1);
INSERT INTO gui_element (fkey_gui_id, e_id, e_pos, e_public, e_comment, e_title, e_element,e_src, e_attributes, e_left, e_top, e_width, e_height, e_z_index, e_more_styles, e_content, e_closetag, e_js_file, e_mb_mod, e_target, e_requires,e_url) VALUES ('Geoportal-RLP','sessionWmc',1,1,'','Please confirm','div','','',NULL ,NULL,NULL ,NULL,NULL ,'','','div','../plugins/mb_sessionWmc.js','','','mapframe1','');
INSERT INTO gui_element_vars(fkey_gui_id, fkey_e_id, var_name, var_value, context, var_type) VALUES ('Geoportal-RLP','sessionWmc','displayTermsOfUse','1','','var');
INSERT INTO gui_element (fkey_gui_id, e_id, e_pos, e_public, e_comment, e_title, e_element,e_src, e_attributes, e_left, e_top, e_width, e_height, e_z_index, e_more_styles, e_content, e_closetag, e_js_file, e_mb_mod, e_target, e_requires,e_url) VALUES ('Geoportal-RLP','jq_upload',1,1,'','','','','',NULL ,NULL,NULL ,NULL,NULL ,'','','','','../plugins/jq_upload.js','','','');
INSERT INTO gui_element (fkey_gui_id, e_id, e_pos, e_public, e_comment, e_title, e_element,e_src, e_attributes, e_left, e_top, e_width, e_height, e_z_index, e_more_styles, e_content, e_closetag, e_js_file, e_mb_mod, e_target, e_requires,e_url) VALUES ('Geoportal-RLP','jq_ui',1,1,'The jQuery UI core','','','','',NULL ,NULL,NULL ,NULL,NULL ,'','','','','../extensions/jquery-ui-1.8.16.custom/development-bundle/ui/jquery.ui.core.js','','','');
INSERT INTO gui_element_vars(fkey_gui_id, fkey_e_id, var_name, var_value, context, var_type) VALUES ('Geoportal-RLP','jq_ui','css','../extensions/jquery-ui-1.7.2.custom/css/ui-lightness/jquery-ui-1.7.2.custom.css','','file/css');
INSERT INTO gui_element (fkey_gui_id, e_id, e_pos, e_public, e_comment, e_title, e_element,e_src, e_attributes, e_left, e_top, e_width, e_height, e_z_index, e_more_styles, e_content, e_closetag, e_js_file, e_mb_mod, e_target, e_requires,e_url) VALUES ('Geoportal-RLP','jq_ui_widget',1,1,'jQuery UI widget','','','','',NULL ,NULL,NULL ,NULL,NULL ,'','','','','../extensions/jquery-ui-1.8.16.custom/development-bundle/ui/jquery.ui.widget.js','','','');
INSERT INTO gui_element (fkey_gui_id, e_id, e_pos, e_public, e_comment, e_title, e_element,e_src, e_attributes, e_left, e_top, e_width, e_height, e_z_index, e_more_styles, e_content, e_closetag, e_js_file, e_mb_mod, e_target, e_requires,e_url) VALUES ('Geoportal-RLP','toggleModule',1,1,'','','div','','',1,1,1,1,2,'','','div','mod_toggleModule.php','','pan1','','');
INSERT INTO gui_element (fkey_gui_id, e_id, e_pos, e_public, e_comment, e_title, e_element,e_src, e_attributes, e_left, e_top, e_width, e_height, e_z_index, e_more_styles, e_content, e_closetag, e_js_file, e_mb_mod, e_target, e_requires,e_url) VALUES ('Geoportal-RLP','wfsConfTree',1,1,'','Such- und Downloadmodule','ul','','',10,400,1,1,NULL ,'','','ul','../plugins/wfsConfTree.js','','mapframe1','','');
INSERT INTO gui_element_vars(fkey_gui_id, fkey_e_id, var_name, var_value, context, var_type) VALUES ('Geoportal-RLP','wfsConfTree','wfs_spatial_request_conf_filename','wfs_default.conf','','php_var');
INSERT INTO gui_element (fkey_gui_id, e_id, e_pos, e_public, e_comment, e_title, e_element,e_src, e_attributes, e_left, e_top, e_width, e_height, e_z_index, e_more_styles, e_content, e_closetag, e_js_file, e_mb_mod, e_target, e_requires,e_url) VALUES ('Geoportal-RLP','lzw_compression',1,1,'','','','','',NULL ,NULL,NULL ,NULL,NULL ,'','','','','lzw.js','','','');
INSERT INTO gui_element (fkey_gui_id, e_id, e_pos, e_public, e_comment, e_title, e_element,e_src, e_attributes, e_left, e_top, e_width, e_height, e_z_index, e_more_styles, e_content, e_closetag, e_js_file, e_mb_mod, e_target, e_requires,e_url) VALUES ('Geoportal-RLP','body',1,1,'body (obligatory)','','body','','',NULL ,NULL,NULL ,NULL,NULL ,'','','','','../extensions/wz_jsgraphics.js,geometry.js,../extensions/RaphaelJS/raphael-1.4.7.min.js,../extensions/RaphaelJS/raphael-1.4.7.min.js,../extensions/RaphaelJS/raphael-1.4.7.min.js,../extensions/spectrum-min.js,../extensions/uuid.js,../extensions/tokml.js,../extensions/togpx.js','','','');
INSERT INTO gui_element_vars(fkey_gui_id, fkey_e_id, var_name, var_value, context, var_type) VALUES ('Geoportal-RLP','body','css_file_wfstree','../css/wfsconftree.css','file/css','file/css');
INSERT INTO gui_element_vars(fkey_gui_id, fkey_e_id, var_name, var_value, context, var_type) VALUES ('Geoportal-RLP','body','css_file_body','../css/mapbender.css','file/css','file/css');
INSERT INTO gui_element_vars(fkey_gui_id, fkey_e_id, var_name, var_value, context, var_type) VALUES ('Geoportal-RLP','body','favicon','../img/favicon.ico','favicon','php_var');
INSERT INTO gui_element_vars(fkey_gui_id, fkey_e_id, var_name, var_value, context, var_type) VALUES ('Geoportal-RLP','body','jq_ui_theme','../extensions/jquery-ui-1.7.2.custom/css/smoothness/jquery-ui-1.7.2.custom.css','UI Theme from Themeroller','file/css');
INSERT INTO gui_element_vars(fkey_gui_id, fkey_e_id, var_name, var_value, context, var_type) VALUES ('Geoportal-RLP','body','jquery_datatables','../extensions/dataTables-1.5/media/css/demo_table_jui.css','','file/css');
INSERT INTO gui_element_vars(fkey_gui_id, fkey_e_id, var_name, var_value, context, var_type) VALUES ('Geoportal-RLP','body','popupcss','../css/popup.css','file css','file/css');
INSERT INTO gui_element_vars(fkey_gui_id, fkey_e_id, var_name, var_value, context, var_type) VALUES ('Geoportal-RLP','body','tablesortercss','../css/tablesorter.css','file css','file/css');
INSERT INTO gui_element_vars(fkey_gui_id, fkey_e_id, var_name, var_value, context, var_type) VALUES ('Geoportal-RLP','body','use_load_message','true','','php_var');
INSERT INTO gui_element_vars(fkey_gui_id, fkey_e_id, var_name, var_value, context, var_type) VALUES ('Geoportal-RLP','body','css_file_feedtree','../css/feedtree.css','file/css','file/css');
INSERT INTO gui_element_vars(fkey_gui_id, fkey_e_id, var_name, var_value, context, var_type) VALUES ('Geoportal-RLP','body','includeWhileLoading','../geoportal/geoportal_splash.php','','php_var');
INSERT INTO gui_element_vars(fkey_gui_id, fkey_e_id, var_name, var_value, context, var_type) VALUES ('Geoportal-RLP','body','print_css','../geoportal/print_div.css','','file/css');
INSERT INTO gui_element_vars(fkey_gui_id, fkey_e_id, var_name, var_value, context, var_type) VALUES ('Geoportal-RLP','body','digitize_kml_css','../css/digitize_new.css','file/css','file/css');
INSERT INTO gui_element_vars(fkey_gui_id, fkey_e_id, var_name, var_value, context, var_type) VALUES ('Geoportal-RLP','body','jq_ui_autocomplete_css','../extensions/jquery-ui-1.8.16.custom/development-bundle/themes/base/jquery.ui.autocomplete.css','file/css','file/css');
INSERT INTO gui_element_vars(fkey_gui_id, fkey_e_id, var_name, var_value, context, var_type) VALUES ('Geoportal-RLP','body','spectrum','../extensions/spectrum.css','spectrum color picker css','file/css');
INSERT INTO gui_element_vars(fkey_gui_id, fkey_e_id, var_name, var_value, context, var_type) VALUES ('Geoportal-RLP','body','iconpicker','../extensions/fontIconPicker-2.0.0/css/jquery.fonticonpicker.min.css','icon picker css','file/css');
INSERT INTO gui_element_vars(fkey_gui_id, fkey_e_id, var_name, var_value, context, var_type) VALUES ('Geoportal-RLP','body','iconpickertheme','../extensions/fontIconPicker-2.0.0/themes/grey-theme/jquery.fonticonpicker.grey.min.css','icon picker theme css','file/css');
INSERT INTO gui_element_vars(fkey_gui_id, fkey_e_id, var_name, var_value, context, var_type) VALUES ('Geoportal-RLP','body','makiicons','../extensions/makiicons/style.css','maki icon css','file/css');
INSERT INTO gui_element_vars(fkey_gui_id, fkey_e_id, var_name, var_value, context, var_type) VALUES ('Geoportal-RLP','body','css_class_bg','body{ background-color: #e2e2e2; }','to define the color of the body','text/css');
INSERT INTO gui_element_vars(fkey_gui_id, fkey_e_id, var_name, var_value, context, var_type) VALUES ('Geoportal-RLP','body','cacheGuiHtml','true','','php_var');
INSERT INTO gui_element (fkey_gui_id, e_id, e_pos, e_public, e_comment, e_title, e_element,e_src, e_attributes, e_left, e_top, e_width, e_height, e_z_index, e_more_styles, e_content, e_closetag, e_js_file, e_mb_mod, e_target, e_requires,e_url) VALUES ('Geoportal-RLP','mapframe1',1,1,'Frame for a map','','div','','',215,15,480,360,3,'overflow:hidden;background-color:#ffffff','','div','../plugins/mb_map.js','../../lib/history.js,map_obj.js,map.js,wms.js,wfs_obj.js,initWmcObj.php','','','http://www.mapbender.org/index.php/Mapframe');
INSERT INTO gui_element_vars(fkey_gui_id, fkey_e_id, var_name, var_value, context, var_type) VALUES ('Geoportal-RLP','mapframe1','skipWmsIfSrsNotSupported','0','if set to 1, it skips the WMS request if the current SRS is not supported by the WMS; if set to 0, the WMS is always queried. Default is 0, because of backwards compatibility','var');
INSERT INTO gui_element_vars(fkey_gui_id, fkey_e_id, var_name, var_value, context, var_type) VALUES ('Geoportal-RLP','mapframe1','slippy','1','1 = Activates an animated, pseudo slippy map','var');
INSERT INTO gui_element_vars(fkey_gui_id, fkey_e_id, var_name, var_value, context, var_type) VALUES ('Geoportal-RLP','mapframe1','wfsConfIdString','94','','var');
INSERT INTO gui_element (fkey_gui_id, e_id, e_pos, e_public, e_comment, e_title, e_element,e_src, e_attributes, e_left, e_top, e_width, e_height, e_z_index, e_more_styles, e_content, e_closetag, e_js_file, e_mb_mod, e_target, e_requires,e_url) VALUES ('Geoportal-RLP','jq_datatables',1,1,'Includes the jQuery plugin datatables, use like this
$(selector).datatables(options)','','','','',NULL ,NULL,NULL ,NULL,NULL ,'','','','../plugins/jq_datatables.js','../extensions/dataTables-1.7.5/media/js/jquery.dataTables.min.js,../extensions/dataTables-1.7.5/extras/TableTools-2.0.0/media/js/TableTools.min.js','','','http://www.datatables.net/');
INSERT INTO gui_element_vars(fkey_gui_id, fkey_e_id, var_name, var_value, context, var_type) VALUES ('Geoportal-RLP','jq_datatables','defaultCss','../extensions/dataTables-1.5/media/css/demo_table_jui.css','','file/css');
INSERT INTO gui_element (fkey_gui_id, e_id, e_pos, e_public, e_comment, e_title, e_element,e_src, e_attributes, e_left, e_top, e_width, e_height, e_z_index, e_more_styles, e_content, e_closetag, e_js_file, e_mb_mod, e_target, e_requires,e_url) VALUES ('Geoportal-RLP','savewmc',1,1,'save workspace as WMC','Ausschnitt als WebMapContext Dokument speichern','img','../img/button_blue_red/wmc_save_off.png','',115,155,24,24,1,'','','','mod_savewmc.php','','mapframe1','jq_ui_dialog','http://www.mapbender.org/index.php/SaveWMC');
INSERT INTO gui_element_vars(fkey_gui_id, fkey_e_id, var_name, var_value, context, var_type) VALUES ('Geoportal-RLP','savewmc','lzwCompressed','false','','var');
INSERT INTO gui_element_vars(fkey_gui_id, fkey_e_id, var_name, var_value, context, var_type) VALUES ('Geoportal-RLP','savewmc','overwrite','1','','var');
INSERT INTO gui_element_vars(fkey_gui_id, fkey_e_id, var_name, var_value, context, var_type) VALUES ('Geoportal-RLP','savewmc','saveInSession','1','','var');
INSERT INTO gui_element (fkey_gui_id, e_id, e_pos, e_public, e_comment, e_title, e_element,e_src, e_attributes, e_left, e_top, e_width, e_height, e_z_index, e_more_styles, e_content, e_closetag, e_js_file, e_mb_mod, e_target, e_requires,e_url) VALUES ('Geoportal-RLP','loadwmc',1,1,'load workspace from WMC','Laden eines WebMapContext Dokumentes','img','../img/button_blue_red/wmc_load_off.png','onmouseover=''this.src = this.src.replace(/_off/,"_over");''  onmouseout=''this.src = this.src.replace(/_over/, "_off");''',145,155,24,24,1,'','','','mod_loadwmc.php','popup.js','mapframe1','jq_ui_dialog,jq_ui_tabs,jq_upload,jq_datatables','http://www.mapbender.org/index.php/LoadWMC');
INSERT INTO gui_element_vars(fkey_gui_id, fkey_e_id, var_name, var_value, context, var_type) VALUES ('Geoportal-RLP','loadwmc','mobileUrl','1','','var');
INSERT INTO gui_element_vars(fkey_gui_id, fkey_e_id, var_name, var_value, context, var_type) VALUES ('Geoportal-RLP','loadwmc','dialogHeight','350','','var');
INSERT INTO gui_element_vars(fkey_gui_id, fkey_e_id, var_name, var_value, context, var_type) VALUES ('Geoportal-RLP','loadwmc','mobileUrlNewWindow','1','','var');
INSERT INTO gui_element_vars(fkey_gui_id, fkey_e_id, var_name, var_value, context, var_type) VALUES ('Geoportal-RLP','loadwmc','deleteWmc','1','','var');
INSERT INTO gui_element_vars(fkey_gui_id, fkey_e_id, var_name, var_value, context, var_type) VALUES ('Geoportal-RLP','loadwmc','editWmc','0','','var');
INSERT INTO gui_element_vars(fkey_gui_id, fkey_e_id, var_name, var_value, context, var_type) VALUES ('Geoportal-RLP','loadwmc','loadFromSession','1','','php_var');
INSERT INTO gui_element_vars(fkey_gui_id, fkey_e_id, var_name, var_value, context, var_type) VALUES ('Geoportal-RLP','loadwmc','publishWmc','0','','var');
INSERT INTO gui_element_vars(fkey_gui_id, fkey_e_id, var_name, var_value, context, var_type) VALUES ('Geoportal-RLP','loadwmc','saveWmcTarget','savewmc','','var');
INSERT INTO gui_element_vars(fkey_gui_id, fkey_e_id, var_name, var_value, context, var_type) VALUES ('Geoportal-RLP','loadwmc','showPublic','0','','var');
INSERT INTO gui_element_vars(fkey_gui_id, fkey_e_id, var_name, var_value, context, var_type) VALUES ('Geoportal-RLP','loadwmc','dialogWidth','300','','text/css');
INSERT INTO gui_element (fkey_gui_id, e_id, e_pos, e_public, e_comment, e_title, e_element,e_src, e_attributes, e_left, e_top, e_width, e_height, e_z_index, e_more_styles, e_content, e_closetag, e_js_file, e_mb_mod, e_target, e_requires,e_url) VALUES ('Geoportal-RLP','loadData',2,1,'IFRAME to load data','','iframe','../html/mod_blank.html','frameborder = "0" ',0,0,1,1,0,'visibility:visible','','iframe','','','','','');
INSERT INTO gui_element (fkey_gui_id, e_id, e_pos, e_public, e_comment, e_title, e_element,e_src, e_attributes, e_left, e_top, e_width, e_height, e_z_index, e_more_styles, e_content, e_closetag, e_js_file, e_mb_mod, e_target, e_requires,e_url) VALUES ('Geoportal-RLP','resultList',2,1,'','Result List','div','','',NULL ,NULL,NULL ,NULL,NULL ,'','','div','mod_ResultList.js','../../lib/resultGeometryListController.js, ../../lib/resultGeometryListModel.js','mapframe1','','');
INSERT INTO gui_element_vars(fkey_gui_id, fkey_e_id, var_name, var_value, context, var_type) VALUES ('Geoportal-RLP','resultList','position','[600,50]','position of the result list dialog','var');
INSERT INTO gui_element_vars(fkey_gui_id, fkey_e_id, var_name, var_value, context, var_type) VALUES ('Geoportal-RLP','resultList','resultListHeight','400','height of the result list dialog','var');
INSERT INTO gui_element_vars(fkey_gui_id, fkey_e_id, var_name, var_value, context, var_type) VALUES ('Geoportal-RLP','resultList','resultListTitle','Search results','title of the result list dialog','var');
INSERT INTO gui_element_vars(fkey_gui_id, fkey_e_id, var_name, var_value, context, var_type) VALUES ('Geoportal-RLP','resultList','resultListWidth','600','width of the result list dialog','var');
INSERT INTO gui_element_vars(fkey_gui_id, fkey_e_id, var_name, var_value, context, var_type) VALUES ('Geoportal-RLP','resultList','tableTools','[
      {
          "sExtends": "xls",
          "sButtonText": "Export to CSV",
          "sFileName": "result.csv"
      }
]','set the initialization options for tableTools','var');
INSERT INTO gui_element (fkey_gui_id, e_id, e_pos, e_public, e_comment, e_title, e_element,e_src, e_attributes, e_left, e_top, e_width, e_height, e_z_index, e_more_styles, e_content, e_closetag, e_js_file, e_mb_mod, e_target, e_requires,e_url) VALUES ('Geoportal-RLP','navFrame',2,1,'navigation mapborder','Navigationsfenster','div','','',0,0,0,0,10,'font-size:1px;','','div','mod_navFrame.php','','mapframe1','','http://www.mapbender.org/index.php/NavFrame');
INSERT INTO gui_element (fkey_gui_id, e_id, e_pos, e_public, e_comment, e_title, e_element,e_src, e_attributes, e_left, e_top, e_width, e_height, e_z_index, e_more_styles, e_content, e_closetag, e_js_file, e_mb_mod, e_target, e_requires,e_url) VALUES ('Geoportal-RLP','georss',2,1,'','','div','','',NULL ,NULL,NULL ,NULL,NULL ,'','','div','','../../lib/mb.ui.displayFeatures.js','','','');
INSERT INTO gui_element (fkey_gui_id, e_id, e_pos, e_public, e_comment, e_title, e_element,e_src, e_attributes, e_left, e_top, e_width, e_height, e_z_index, e_more_styles, e_content, e_closetag, e_js_file, e_mb_mod, e_target, e_requires,e_url) VALUES ('Geoportal-RLP','sandclock',2,1,'displays a sand clock while waiting for requests','','div','','',0,0,0,0,0,'','','div','mod_sandclock.js','','mapframe1','','');
INSERT INTO gui_element_vars(fkey_gui_id, fkey_e_id, var_name, var_value, context, var_type) VALUES ('Geoportal-RLP','sandclock','mod_sandclock_image','../img/sandclock.gif','define a sandclock image ','var');
INSERT INTO gui_element (fkey_gui_id, e_id, e_pos, e_public, e_comment, e_title, e_element,e_src, e_attributes, e_left, e_top, e_width, e_height, e_z_index, e_more_styles, e_content, e_closetag, e_js_file, e_mb_mod, e_target, e_requires,e_url) VALUES ('Geoportal-RLP','tabs',2,1,'vertical tabs to handle iframes','','div','','',5,215,195,20,3,'font-family: Arial,Helvetica;font-weight:bold;','','div','mod_tab.php','','','','');
INSERT INTO gui_element_vars(fkey_gui_id, fkey_e_id, var_name, var_value, context, var_type) VALUES ('Geoportal-RLP','tabs','tab_frameHeight[0]','200','','var');
INSERT INTO gui_element_vars(fkey_gui_id, fkey_e_id, var_name, var_value, context, var_type) VALUES ('Geoportal-RLP','tabs','tab_frameHeight[1]','260','','var');
INSERT INTO gui_element_vars(fkey_gui_id, fkey_e_id, var_name, var_value, context, var_type) VALUES ('Geoportal-RLP','tabs','tab_frameHeight[2]','380','','var');
INSERT INTO gui_element_vars(fkey_gui_id, fkey_e_id, var_name, var_value, context, var_type) VALUES ('Geoportal-RLP','tabs','tab_prefix','  ','','var');
INSERT INTO gui_element_vars(fkey_gui_id, fkey_e_id, var_name, var_value, context, var_type) VALUES ('Geoportal-RLP','tabs','expandable','0','1 = expand the tabs to fit the document vertically, default is 0','php_var');
INSERT INTO gui_element_vars(fkey_gui_id, fkey_e_id, var_name, var_value, context, var_type) VALUES ('Geoportal-RLP','tabs','tab_style','position:absolute;visibility:visible;cursor:pointer;','','var');
INSERT INTO gui_element_vars(fkey_gui_id, fkey_e_id, var_name, var_value, context, var_type) VALUES ('Geoportal-RLP','tabs','tab_ids[0]','kmlTree','','php_var');
INSERT INTO gui_element_vars(fkey_gui_id, fkey_e_id, var_name, var_value, context, var_type) VALUES ('Geoportal-RLP','tabs','tab_ids[2]','wfsConfTree','','php_var');
INSERT INTO gui_element_vars(fkey_gui_id, fkey_e_id, var_name, var_value, context, var_type) VALUES ('Geoportal-RLP','tabs','tab_ids[1]','treeGDE','','php_var');
INSERT INTO gui_element_vars(fkey_gui_id, fkey_e_id, var_name, var_value, context, var_type) VALUES ('Geoportal-RLP','tabs','open_tab','1','define which tab should be opened when a gui is opened','var');
INSERT INTO gui_element (fkey_gui_id, e_id, e_pos, e_public, e_comment, e_title, e_element,e_src, e_attributes, e_left, e_top, e_width, e_height, e_z_index, e_more_styles, e_content, e_closetag, e_js_file, e_mb_mod, e_target, e_requires,e_url) VALUES ('Geoportal-RLP','resultList_DetailPopup',2,1,'Detail Popup For resultList','','div','','',NULL ,NULL,NULL ,NULL,NULL ,'','','','../plugins/mb_resultList_DetailPopup.js','','resultList','','http://www.mapbender.org/resultList_DetailPopup');
INSERT INTO gui_element_vars(fkey_gui_id, fkey_e_id, var_name, var_value, context, var_type) VALUES ('Geoportal-RLP','resultList_DetailPopup','detailPopupHeight','250','height of the result list detail popup','var');
INSERT INTO gui_element_vars(fkey_gui_id, fkey_e_id, var_name, var_value, context, var_type) VALUES ('Geoportal-RLP','resultList_DetailPopup','detailPopupTitle','Details','title of the result list detail popup','var');
INSERT INTO gui_element_vars(fkey_gui_id, fkey_e_id, var_name, var_value, context, var_type) VALUES ('Geoportal-RLP','resultList_DetailPopup','detailPopupWidth','350','width of the result list detail popup','var');
INSERT INTO gui_element_vars(fkey_gui_id, fkey_e_id, var_name, var_value, context, var_type) VALUES ('Geoportal-RLP','resultList_DetailPopup','openLinkFromSearch','0','open link directly if feature attr is defined as link','var');
INSERT INTO gui_element_vars(fkey_gui_id, fkey_e_id, var_name, var_value, context, var_type) VALUES ('Geoportal-RLP','resultList_DetailPopup','position','[700,300]','position of the result list detail popup','var');
INSERT INTO gui_element (fkey_gui_id, e_id, e_pos, e_public, e_comment, e_title, e_element,e_src, e_attributes, e_left, e_top, e_width, e_height, e_z_index, e_more_styles, e_content, e_closetag, e_js_file, e_mb_mod, e_target, e_requires,e_url) VALUES ('Geoportal-RLP','setPOI2Scale',2,1,'zoom to a poi (get-parameter)','','div','','',1,1,1,1,NULL ,'visibility:hidden','','div','mod_setPOI2Scale.php','','mapframe1','','http://www.mapbender.org/index.php/Mod_setPoi2Scale');
INSERT INTO gui_element_vars(fkey_gui_id, fkey_e_id, var_name, var_value, context, var_type) VALUES ('Geoportal-RLP','setPOI2Scale','mod_setPOI2Scale_defScale','5000','default scale','var');
INSERT INTO gui_element (fkey_gui_id, e_id, e_pos, e_public, e_comment, e_title, e_element,e_src, e_attributes, e_left, e_top, e_width, e_height, e_z_index, e_more_styles, e_content, e_closetag, e_js_file, e_mb_mod, e_target, e_requires,e_url) VALUES ('Geoportal-RLP','scalebar',2,1,'scalebar','Maßstabsleiste','div','','',0,0,NULL ,NULL,NULL ,'','','div','mod_scalebar.js','','mapframe1','','');
INSERT INTO gui_element (fkey_gui_id, e_id, e_pos, e_public, e_comment, e_title, e_element,e_src, e_attributes, e_left, e_top, e_width, e_height, e_z_index, e_more_styles, e_content, e_closetag, e_js_file, e_mb_mod, e_target, e_requires,e_url) VALUES ('Geoportal-RLP','jq_ui_mouse',2,1,'jQuery UI mouse','','','','',NULL ,NULL,NULL ,NULL,NULL ,'','','','','../extensions/jquery-ui-1.8.16.custom/development-bundle/ui/jquery.ui.mouse.js','','jq_ui_widget','');
INSERT INTO gui_element (fkey_gui_id, e_id, e_pos, e_public, e_comment, e_title, e_element,e_src, e_attributes, e_left, e_top, e_width, e_height, e_z_index, e_more_styles, e_content, e_closetag, e_js_file, e_mb_mod, e_target, e_requires,e_url) VALUES ('Geoportal-RLP','feeds',2,1,'','GeoRSS Feeds','ul','','',1,1,1,1,NULL ,'','','ul','../plugins/feedTree.js','','mapframe1','','');
INSERT INTO gui_element (fkey_gui_id, e_id, e_pos, e_public, e_comment, e_title, e_element,e_src, e_attributes, e_left, e_top, e_width, e_height, e_z_index, e_more_styles, e_content, e_closetag, e_js_file, e_mb_mod, e_target, e_requires,e_url) VALUES ('Geoportal-RLP','overview',2,1,'OverviewFrame','Übersichtskarte','div','','',5,5,110,115,2,'overflow:hidden;background-color:#ffffff','<div id="overview_maps" style="position:absolute;left:0px;right:0px;"></div>','div','../plugins/mb_overview.js','../../lib/history.js,map_obj.js,map.js,wms.js,wfs_obj.js,initWmcObj.php','mapframe1','mapframe1','http://www.mapbender.org/index.php/Overview');
INSERT INTO gui_element_vars(fkey_gui_id, fkey_e_id, var_name, var_value, context, var_type) VALUES ('Geoportal-RLP','overview','skipWmsIfSrsNotSupported','0','if set to 1, it skips the WMS request if the current SRS is not supported by the WMS; if set to 0, the WMS is always queried. Default is 0, because of backwards compatibility','var');
INSERT INTO gui_element_vars(fkey_gui_id, fkey_e_id, var_name, var_value, context, var_type) VALUES ('Geoportal-RLP','overview','overview_wms','0','wms that shows up as overview','var');
INSERT INTO gui_element (fkey_gui_id, e_id, e_pos, e_public, e_comment, e_title, e_element,e_src, e_attributes, e_left, e_top, e_width, e_height, e_z_index, e_more_styles, e_content, e_closetag, e_js_file, e_mb_mod, e_target, e_requires,e_url) VALUES ('Geoportal-RLP','setBBOX',2,1,'set Extent for mapframe and overviewframe','','div','','',0,0,0,0,0,'','','div','mod_setBBOX1.php','','mapframe1','','');
INSERT INTO gui_element (fkey_gui_id, e_id, e_pos, e_public, e_comment, e_title, e_element,e_src, e_attributes, e_left, e_top, e_width, e_height, e_z_index, e_more_styles, e_content, e_closetag, e_js_file, e_mb_mod, e_target, e_requires,e_url) VALUES ('Geoportal-RLP','dependentDiv',2,1,'displays infos in a sticky div-tag','','div','','',-59,1,1,1,NULL ,'visibility:visible;position:absolute;font-size: 11px;font-family: "Arial", sans-serif;','','div','mod_dependentDiv.php','','mapframe1','','http://www.mapbender.org/index.php/DependentDiv');
INSERT INTO gui_element (fkey_gui_id, e_id, e_pos, e_public, e_comment, e_title, e_element,e_src, e_attributes, e_left, e_top, e_width, e_height, e_z_index, e_more_styles, e_content, e_closetag, e_js_file, e_mb_mod, e_target, e_requires,e_url) VALUES ('Geoportal-RLP','mousewheelZoom',2,1,'adds mousewheel zoom to map module (target)','Mousewheel zoom','div','','',NULL ,NULL,NULL ,NULL,NULL ,'','','div','mod_mousewheelZoom.js','../extensions/jquery.mousewheel.min.js','mapframe1','','');
INSERT INTO gui_element_vars(fkey_gui_id, fkey_e_id, var_name, var_value, context, var_type) VALUES ('Geoportal-RLP','mousewheelZoom','factor','3','The factor by which the map is zoomed on each mousewheel unit','var');
INSERT INTO gui_element (fkey_gui_id, e_id, e_pos, e_public, e_comment, e_title, e_element,e_src, e_attributes, e_left, e_top, e_width, e_height, e_z_index, e_more_styles, e_content, e_closetag, e_js_file, e_mb_mod, e_target, e_requires,e_url) VALUES ('Geoportal-RLP','jq_ui_position',2,1,'jQuery UI position','','','','',NULL ,NULL,NULL ,NULL,NULL ,'','','','','../extensions/jquery-ui-1.8.16.custom/development-bundle/ui/jquery.ui.position.js','','','');
INSERT INTO gui_element (fkey_gui_id, e_id, e_pos, e_public, e_comment, e_title, e_element,e_src, e_attributes, e_left, e_top, e_width, e_height, e_z_index, e_more_styles, e_content, e_closetag, e_js_file, e_mb_mod, e_target, e_requires,e_url) VALUES ('Geoportal-RLP','legend',2,1,'legend','Legende','div','','',0,0,NULL ,NULL,600,'','','div','../javascripts/mod_legendDiv.php','','mapframe1','','');
INSERT INTO gui_element_vars(fkey_gui_id, fkey_e_id, var_name, var_value, context, var_type) VALUES ('Geoportal-RLP','legend','reverse','true','','var');
INSERT INTO gui_element_vars(fkey_gui_id, fkey_e_id, var_name, var_value, context, var_type) VALUES ('Geoportal-RLP','legend','reverseLegend','true','','var');
INSERT INTO gui_element_vars(fkey_gui_id, fkey_e_id, var_name, var_value, context, var_type) VALUES ('Geoportal-RLP','legend','checkbox_on_off','false','display or hide the checkbox to set the legend on/off','var');
INSERT INTO gui_element_vars(fkey_gui_id, fkey_e_id, var_name, var_value, context, var_type) VALUES ('Geoportal-RLP','legend','css_file_legend','../css/legend.css','','file/css');
INSERT INTO gui_element_vars(fkey_gui_id, fkey_e_id, var_name, var_value, context, var_type) VALUES ('Geoportal-RLP','legend','legendlink','false','','var');
INSERT INTO gui_element_vars(fkey_gui_id, fkey_e_id, var_name, var_value, context, var_type) VALUES ('Geoportal-RLP','legend','showgroupedlayertitle','true','show the title of the grouped layers in the legend','var');
INSERT INTO gui_element_vars(fkey_gui_id, fkey_e_id, var_name, var_value, context, var_type) VALUES ('Geoportal-RLP','legend','showlayertitle','true','show the layer title in the legend','var');
INSERT INTO gui_element_vars(fkey_gui_id, fkey_e_id, var_name, var_value, context, var_type) VALUES ('Geoportal-RLP','legend','showwmstitle','true','show the wms title in the legend','var');
INSERT INTO gui_element_vars(fkey_gui_id, fkey_e_id, var_name, var_value, context, var_type) VALUES ('Geoportal-RLP','legend','stickylegend','false','parameter to decide wether the legend should stick on the mapframe1','var');
INSERT INTO gui_element (fkey_gui_id, e_id, e_pos, e_public, e_comment, e_title, e_element,e_src, e_attributes, e_left, e_top, e_width, e_height, e_z_index, e_more_styles, e_content, e_closetag, e_js_file, e_mb_mod, e_target, e_requires,e_url) VALUES ('Geoportal-RLP','digitize_widget',2,1,'Digitize','Digitize distance','img','../img/button_blue_red/measure_off.png','',600,600,1,1,1,'','','','../plugins/mb_digitize_widget.php','../extensions/JSON-Schema-Instantiator/instantiator.js,../widgets/w_digitize.js,../extensions/RaphaelJS/raphael-1.4.7.min.js','mapframe1','jq_ui_dialog,jq_ui_widget','http://www.mapbender.org/index.php/Digitize');
INSERT INTO gui_element_vars(fkey_gui_id, fkey_e_id, var_name, var_value, context, var_type) VALUES ('Geoportal-RLP','digitize_widget','featureCategoriesSchema','{
  "$schema": "http://json-schema.org/draft-04/schema#",
  "id": "/",
  "type": "object",
  "properties": {
    "categories": {
      "id": "categories",
      "type": "object",
      "properties": {
        "Editable-Data": {
          "id": "Editable-Data",
          "type": "string",
          "default": "Editable-Data"
        },
        "Style-Data": {
          "id": "Style-Data",
          "type": "string",
          "default": "Style-Data"
        },
       "Fix-Data": {
          "id": "Fix-Data",
          "type": "string",
          "default": "Fix-Data"
        },
        "Custom-Data": {
          "id": "Custom-Data",
          "type": "string",
          "default": "Custom-Data"
        }
      },
      "required": [
        "Fix-Data",
        "Editable-Data",
        "Style-Data",
        "Custom-Data"
      ]
    }
  },
  "required": [
    "categories"
  ]
}','','var');
INSERT INTO gui_element_vars(fkey_gui_id, fkey_e_id, var_name, var_value, context, var_type) VALUES ('Geoportal-RLP','digitize_widget','lineStrokeDefault','#808080','','var');
INSERT INTO gui_element_vars(fkey_gui_id, fkey_e_id, var_name, var_value, context, var_type) VALUES ('Geoportal-RLP','digitize_widget','lineStrokeSnapped','#F30','','var');
INSERT INTO gui_element_vars(fkey_gui_id, fkey_e_id, var_name, var_value, context, var_type) VALUES ('Geoportal-RLP','digitize_widget','lineStrokeWidthDefault','2','','var');
INSERT INTO gui_element_vars(fkey_gui_id, fkey_e_id, var_name, var_value, context, var_type) VALUES ('Geoportal-RLP','digitize_widget','lineStrokeWidthSnapped','2','','var');
INSERT INTO gui_element_vars(fkey_gui_id, fkey_e_id, var_name, var_value, context, var_type) VALUES ('Geoportal-RLP','digitize_widget','opacity','0.5','','var');
INSERT INTO gui_element_vars(fkey_gui_id, fkey_e_id, var_name, var_value, context, var_type) VALUES ('Geoportal-RLP','digitize_widget','pointAttributesSchema','{
  "$schema": "http://json-schema.org/draft-04/schema#",
  "id": "/",
  "type": "object",
  "properties": {
    "Point": {
      "id": "Point",
      "type": "object",
      "properties": {
        "created": {
          "id": "created",
          "type": "object",
          "properties": {
            "category": {
              "id": "category",
              "type": "string",
              "default": "Fix-Data"
            },
            "value": {
              "id": "value",
              "type": "string",
              "default": ""
            }
          },
          "required": [
            "category",
            "value"
          ]
        },
        "description": {
          "id": "description",
          "type": "object",
          "properties": {
            "category": {
              "id": "category",
              "type": "string",
              "default": "Editable-Data"
            },
            "value": {
              "id": "value",
              "type": "string",
              "default": "description"
            }
          }
        },
        "iconOffsetX": {
          "id": "iconOffsetX",
          "type": "object",
          "properties": {
            "category": {
              "id": "category",
              "type": "string",
              "default": "Style-Data"
            },
            "value": {
              "id": "value",
              "type": "integer",
              "default": -10
            }
          }
        },
        "iconOffsetY": {
          "id": "iconOffsetY",
          "type": "object",
          "properties": {
            "category": {
              "id": "category",
              "type": "string",
              "default": "Style-Data"
            },
            "value": {
              "id": "value",
              "type": "integer",
              "default": -10
            }
          }
        },
        "marker-color": {
          "id": "marker-color",
          "type": "object",
          "properties": {
            "category": {
              "id": "category",
              "type": "string",
              "default": "Style-Data"
            },
            "value": {
              "id": "value",
              "type": "string",
              "default": "#7e7e7e"
            }
          }
        },
        "marker-size": {
          "id": "marker-size",
          "type": "object",
          "properties": {
            "category": {
              "id": "category",
              "type": "string",
              "default": "Style-Data"
            },
            "value": {
              "id": "value",
              "type": "integer",
              "default": 34
            }
          }
        },
        "marker-symbol": {
          "id": "marker-symbol",
          "type": "object",
          "properties": {
            "category": {
              "id": "category",
              "type": "string",
              "default": "Style-Data"
            },
            "value": {
              "id": "value",
              "type": "string",
              "default": "marker"
            }
          }
        },
        "name": {
          "id": "name",
          "type": "object",
          "properties": {
            "category": {
              "id": "category",
              "type": "string",
              "default": "Editable-Data"
            },
            "value": {
              "id": "value",
              "type": "string",
              "default": "name"
            }
          }
        },
        "title": {
          "id": "title",
          "type": "object",
          "properties": {
            "category": {
              "id": "category",
              "type": "string",
              "default": "Editable-Data"
            },
            "value": {
              "id": "value",
              "type": "string",
              "default": "title"
            }
          }
        },
        "updated": {
          "id": "updated",
          "type": "object",
          "properties": {
            "category": {
              "id": "category",
              "type": "string",
              "default": "Fix-Data"
            },
            "value": {
              "id": "value",
              "type": "string",
              "default": ""
            }
          }
        },
        "uuid": {
          "id": "uuid",
          "type": "object",
          "properties": {
            "category": {
              "id": "category",
              "type": "string",
              "default": "Fix-Data"
            },
            "value": {
              "id": "value",
              "type": "integer",
              "default": -10
            }
          }
        }
      },
      "required": [
        "created",
        "description",
        "iconOffsetX",
        "iconOffsetY",
        "marker-color",
        "marker-size",
        "marker-symbol",
        "name",
        "title",
        "updated",
        "uuid"
      ]
    }
  },
  "required": [
    "Point"
  ]
}','','var');
INSERT INTO gui_element_vars(fkey_gui_id, fkey_e_id, var_name, var_value, context, var_type) VALUES ('Geoportal-RLP','digitize_widget','pointFillDefault','#B2DFEE','','var');
INSERT INTO gui_element_vars(fkey_gui_id, fkey_e_id, var_name, var_value, context, var_type) VALUES ('Geoportal-RLP','digitize_widget','pointFillSnapped','#FF0000','','var');
INSERT INTO gui_element_vars(fkey_gui_id, fkey_e_id, var_name, var_value, context, var_type) VALUES ('Geoportal-RLP','digitize_widget','pointStrokeDefault','#FF0000','','var');
INSERT INTO gui_element_vars(fkey_gui_id, fkey_e_id, var_name, var_value, context, var_type) VALUES ('Geoportal-RLP','digitize_widget','pointStrokeSnapped','#FF0000','','var');
INSERT INTO gui_element_vars(fkey_gui_id, fkey_e_id, var_name, var_value, context, var_type) VALUES ('Geoportal-RLP','digitize_widget','pointStrokeWidthDefault','2','','var');
INSERT INTO gui_element_vars(fkey_gui_id, fkey_e_id, var_name, var_value, context, var_type) VALUES ('Geoportal-RLP','digitize_widget','polygonAttributesSchema','{
  "$schema": "http://json-schema.org/draft-04/schema#",
  "id": "/",
  "type": "object",
  "properties": {
    "Polygon": {
      "id": "Polygon",
      "type": "object",
      "properties": {
        "created": {
          "id": "created",
          "type": "object",
          "properties": {
            "category": {
              "id": "category",
              "type": "string",
              "default": "Fix-Data"
            },
            "value": {
              "id": "value",
              "type": "string",
              "default": ""
            }
          },
          "required": [
            "category",
            "value"
          ]
        },
        "description": {
          "id": "description",
          "type": "object",
          "properties": {
            "category": {
              "id": "category",
              "type": "string",
              "default": "Editable-Data"
            },
            "value": {
              "id": "value",
              "type": "string",
              "default": "description"
            }
          }
        },
        "fill": {
          "id": "fill",
          "type": "object",
          "properties": {
            "category": {
              "id": "category",
              "type": "string",
              "default": "Style-Data"
            },
            "value": {
              "id": "value",
              "type": "string",
              "default": "#555555"
            }
          }
        },
        "fill-opacity": {
          "id": "fill-opacity",
          "type": "object",
          "properties": {
            "category": {
              "id": "category",
              "type": "string",
              "default": "Style-Data"
            },
            "value": {
              "id": "value",
              "type": "number",
              "default": 0.5
            }
          }
        },
        "marker-size": {
          "id": "marker-size",
          "type": "object",
          "properties": {
            "category": {
              "id": "category",
              "type": "string",
              "default": "Style-Data"
            },
            "value": {
              "id": "value",
              "type": "integer",
              "default": 34
            }
          }
        },
        "marker-symbol": {
          "id": "marker-symbol",
          "type": "object",
          "properties": {
            "category": {
              "id": "category",
              "type": "string",
              "default": "Style-Data"
            },
            "value": {
              "id": "value",
              "type": "string",
              "default": "marker"
            }
          }
        },
        "name": {
          "id": "name",
          "type": "object",
          "properties": {
            "category": {
              "id": "category",
              "type": "string",
              "default": "Editable-Data"
            },
            "value": {
              "id": "value",
              "type": "string",
              "default": "name"
            }
          }
        },
        "stroke": {
          "id": "stroke",
          "type": "object",
          "properties": {
            "category": {
              "id": "category",
              "type": "string",
              "default": "Style-Data"
            },
            "value": {
              "id": "value",
              "type": "string",
              "default": "#555555"
            }
          }
        },
        "stroke-opacity": {
          "id": "stroke-opacity",
          "type": "object",
          "properties": {
            "category": {
              "id": "category",
              "type": "string",
              "default": "Style-Data"
            },
            "value": {
              "id": "value",
              "type": "integer",
              "default": 1
            }
          }
        },
        "stroke-width": {
          "id": "stroke-width",
          "type": "object",
          "properties": {
            "category": {
              "id": "category",
              "type": "string",
              "default": "Style-Data"
            },
            "value": {
              "id": "value",
              "type": "integer",
              "default": 2
            }
          }
        },
        "title": {
          "id": "title",
          "type": "object",
          "properties": {
            "category": {
              "id": "category",
              "type": "string",
              "default": "Editable-Data"
            },
            "value": {
              "id": "value",
              "type": "string",
              "default": "title"
            }
          }
        },
        "updated": {
          "id": "updated",
          "type": "object",
          "properties": {
            "category": {
              "id": "category",
              "type": "string",
              "default": "Fix-Data"
            },
            "value": {
              "id": "value",
              "type": "string",
              "default": ""
            }
          }
        },
        "uuid": {
          "id": "uuid",
          "type": "object",
          "properties": {
            "category": {
              "id": "category",
              "type": "string",
              "default": "Fix-Data"
            },
            "value": {
              "id": "value",
              "type": "integer",
              "default": -10
            }
          }
        }
      },
      "required": [
        "created",
        "description",
        "fill",
        "fill-opacity",
        "marker-size",
        "marker-symbol",
        "name",
        "stroke",
        "stroke-opacity",
        "stroke-width",
        "title",
        "updated",
        "uuid"
      ]
    }
  },
  "required": [
    "Polygon"
  ]
}','','var');
INSERT INTO gui_element_vars(fkey_gui_id, fkey_e_id, var_name, var_value, context, var_type) VALUES ('Geoportal-RLP','digitize_widget','polygonFillDefault','#FFFF00','','var');
INSERT INTO gui_element_vars(fkey_gui_id, fkey_e_id, var_name, var_value, context, var_type) VALUES ('Geoportal-RLP','digitize_widget','polygonFillSnapped','#FC3','','var');
INSERT INTO gui_element_vars(fkey_gui_id, fkey_e_id, var_name, var_value, context, var_type) VALUES ('Geoportal-RLP','digitize_widget','polygonStrokeWidthDefault','1','','var');
INSERT INTO gui_element_vars(fkey_gui_id, fkey_e_id, var_name, var_value, context, var_type) VALUES ('Geoportal-RLP','digitize_widget','polygonStrokeWidthSnapped','3','','var');
INSERT INTO gui_element_vars(fkey_gui_id, fkey_e_id, var_name, var_value, context, var_type) VALUES ('Geoportal-RLP','digitize_widget','polylineAttributesSchema','{
  "$schema": "http://json-schema.org/draft-04/schema#",
  "id": "/",
  "type": "object",
  "properties": {
    "Polyline": {
      "id": "Polyline",
      "type": "object",
      "properties": {
        "created": {
          "id": "created",
          "type": "object",
          "properties": {
            "category": {
              "id": "category",
              "type": "string",
              "default": "Fix-Data"
            },
            "value": {
              "id": "value",
              "type": "string",
              "default": ""
            }
          },
          "required": [
            "category",
            "value"
          ]
        },
        "description": {
          "id": "description",
          "type": "object",
          "properties": {
            "category": {
              "id": "category",
              "type": "string",
              "default": "Editable-Data"
            },
            "value": {
              "id": "value",
              "type": "string",
              "default": "description"
            }
          }
        },
        "marker-size": {
          "id": "marker-size",
          "type": "object",
          "properties": {
            "category": {
              "id": "category",
              "type": "string",
              "default": "Style-Data"
            },
            "value": {
              "id": "value",
              "type": "integer",
              "default": 34
            }
          }
        },
        "marker-symbol": {
          "id": "marker-symbol",
          "type": "object",
          "properties": {
            "category": {
              "id": "category",
              "type": "string",
              "default": "Style-Data"
            },
            "value": {
              "id": "value",
              "type": "string",
              "default": "marker"
            }
          }
        },
        "name": {
          "id": "name",
          "type": "object",
          "properties": {
            "category": {
              "id": "category",
              "type": "string",
              "default": "Editable-Data"
            },
            "value": {
              "id": "value",
              "type": "string",
              "default": "name"
            }
          }
        },
        "stroke": {
          "id": "stroke",
          "type": "object",
          "properties": {
            "category": {
              "id": "category",
              "type": "string",
              "default": "Style-Data"
            },
            "value": {
              "id": "value",
              "type": "string",
              "default": "#555555"
            }
          }
        },
        "stroke-opacity": {
          "id": "stroke-opacity",
          "type": "object",
          "properties": {
            "category": {
              "id": "category",
              "type": "string",
              "default": "Style-Data"
            },
            "value": {
              "id": "value",
              "type": "integer",
              "default": 1
            }
          }
        },
        "stroke-width": {
          "id": "stroke-width",
          "type": "object",
          "properties": {
            "category": {
              "id": "category",
              "type": "string",
              "default": "Style-Data"
            },
            "value": {
              "id": "value",
              "type": "integer",
              "default": 2
            }
          }
        },
        "title": {
          "id": "title",
          "type": "object",
          "properties": {
            "category": {
              "id": "category",
              "type": "string",
              "default": "Editable-Data"
            },
            "value": {
              "id": "value",
              "type": "string",
              "default": "title"
            }
          }
        },
        "updated": {
          "id": "updated",
          "type": "object",
          "properties": {
            "category": {
              "id": "category",
              "type": "string",
              "default": "Fix-Data"
            },
            "value": {
              "id": "value",
              "type": "string",
              "default": ""
            }
          }
        },
        "uuid": {
          "id": "uuid",
          "type": "object",
          "properties": {
            "category": {
              "id": "category",
              "type": "string",
              "default": "Fix-Data"
            },
            "value": {
              "id": "value",
              "type": "integer",
              "default": -10
            }
          }
        }
      },
      "required": [
        "created",
        "description",
        "marker-size",
        "marker-symbol",
        "name",
        "stroke",
        "stroke-opacity",
        "stroke-width",
        "title",
        "updated",
        "uuid"
      ]
    }
  },
  "required": [
    "Polyline"
  ]
}','','var');
INSERT INTO gui_element_vars(fkey_gui_id, fkey_e_id, var_name, var_value, context, var_type) VALUES ('Geoportal-RLP','digitize_widget','digitizePointDiameter','7','','var');
INSERT INTO gui_element (fkey_gui_id, e_id, e_pos, e_public, e_comment, e_title, e_element,e_src, e_attributes, e_left, e_top, e_width, e_height, e_z_index, e_more_styles, e_content, e_closetag, e_js_file, e_mb_mod, e_target, e_requires,e_url) VALUES ('Geoportal-RLP','exportMapimage',2,1,'export the Images of the Mapframe as png or jpg','Export des aktuellen Kartenbilds','img','../img/button_blink_red/exportMapimage_off.png','onclick=''window.open("../javascripts/mod_exportMapImage.php?target=mapframe1&sessionID","exportMapImage","width=250, height=220, resizable=yes ")''  onmouseover=''this.src = this.src.replace(/_off/,"_over");''  onmouseout=''this.src = this.src.replace(/_over/, "_off");'' title="export Mapimage"',175,155,24,24,1,'','','','','','mapframe1','','');
INSERT INTO gui_element_vars(fkey_gui_id, fkey_e_id, var_name, var_value, context, var_type) VALUES ('Geoportal-RLP','exportMapimage','geotiffExport','true','','var');
INSERT INTO gui_element_vars(fkey_gui_id, fkey_e_id, var_name, var_value, context, var_type) VALUES ('Geoportal-RLP','exportMapimage','jpegExport','true','','var');
INSERT INTO gui_element_vars(fkey_gui_id, fkey_e_id, var_name, var_value, context, var_type) VALUES ('Geoportal-RLP','exportMapimage','pngExport','true','','var');
INSERT INTO gui_element (fkey_gui_id, e_id, e_pos, e_public, e_comment, e_title, e_element,e_src, e_attributes, e_left, e_top, e_width, e_height, e_z_index, e_more_styles, e_content, e_closetag, e_js_file, e_mb_mod, e_target, e_requires,e_url) VALUES ('Geoportal-RLP','kml',2,1,'','','div','','',NULL ,NULL,NULL ,NULL,NULL ,'','','div','','../../lib/mb.ui.displayKmlFeatures.js','','','');
INSERT INTO gui_element (fkey_gui_id, e_id, e_pos, e_public, e_comment, e_title, e_element,e_src, e_attributes, e_left, e_top, e_width, e_height, e_z_index, e_more_styles, e_content, e_closetag, e_js_file, e_mb_mod, e_target, e_requires,e_url) VALUES ('Geoportal-RLP','mapbender',2,1,'Mapbender-Logo','','div','','onclick="javascript:window.open(''http://www.mapbender.org'','''','''');"',-59,1,1,1,30,'font-size : 10px;font-weight : bold;font-family: Arial, Helvetica, sans-serif;color:white;cursor:help;','<span>Ma</span><span style="color: blue;">P</span><span style="color: red;">b</span><span>ender</span><script type="text/javascript"> mb_registerSubFunctions("mod_mapbender()"); function mod_mapbender(){ document.getElementById("mapbender").style.left = parseInt(document.getElementById("mapframe1").style.left) + parseInt(document.getElementById("mapframe1").style.width) - 90; document.getElementById("mapbender").style.top = parseInt(document.getElementById("mapframe1").style.top) + parseInt(document.getElementById("mapframe1").style.height) -1; } </script>','div','','','','','');
INSERT INTO gui_element (fkey_gui_id, e_id, e_pos, e_public, e_comment, e_title, e_element,e_src, e_attributes, e_left, e_top, e_width, e_height, e_z_index, e_more_styles, e_content, e_closetag, e_js_file, e_mb_mod, e_target, e_requires,e_url) VALUES ('Geoportal-RLP','resultList_Highlight',2,1,'highlighting functionality for resultList','','div','','',NULL ,NULL,NULL ,NULL,NULL ,'','','','../plugins/mb_resultList_Highlight.js','','resultList,mapframe1,overview','','http://www.mapbender.org/resultList_Highlight');
INSERT INTO gui_element_vars(fkey_gui_id, fkey_e_id, var_name, var_value, context, var_type) VALUES ('Geoportal-RLP','resultList_Highlight','maxHighlightedPoints','500','max number of points to highlight','var');
INSERT INTO gui_element_vars(fkey_gui_id, fkey_e_id, var_name, var_value, context, var_type) VALUES ('Geoportal-RLP','resultList_Highlight','resultHighlightColor','#ff0000','color of the highlighting','var');
INSERT INTO gui_element_vars(fkey_gui_id, fkey_e_id, var_name, var_value, context, var_type) VALUES ('Geoportal-RLP','resultList_Highlight','resultHighlightLineWidth','2','width of the highlighting line','var');
INSERT INTO gui_element_vars(fkey_gui_id, fkey_e_id, var_name, var_value, context, var_type) VALUES ('Geoportal-RLP','resultList_Highlight','resultHighlightZIndex','100','zindex of the highlighting','var');
INSERT INTO gui_element (fkey_gui_id, e_id, e_pos, e_public, e_comment, e_title, e_element,e_src, e_attributes, e_left, e_top, e_width, e_height, e_z_index, e_more_styles, e_content, e_closetag, e_js_file, e_mb_mod, e_target, e_requires,e_url) VALUES ('Geoportal-RLP','resultList_Zoom',2,1,'zoom functionality for resultList','','div','','',NULL ,NULL,NULL ,NULL,NULL ,'','','','../plugins/mb_resultList_Zoom.js','','resultList,mapframe1','','http://www.mapbender.org/resultList_Zoom');
INSERT INTO gui_element (fkey_gui_id, e_id, e_pos, e_public, e_comment, e_title, e_element,e_src, e_attributes, e_left, e_top, e_width, e_height, e_z_index, e_more_styles, e_content, e_closetag, e_js_file, e_mb_mod, e_target, e_requires,e_url) VALUES ('Geoportal-RLP','dragMapSize',2,1,'drag & drop Mapsize','Kartenausschnitt verschieben','div','','class="ui-state-default"',-59,1,15,15,2,'font-size:1px; cursor:move; width:10; height:10;','<span class="ui-icon ui-icon ui-icon-arrow-4"></span>','div','mod_dragMapSize.php','','mapframe1','','http://www.mapbender.org/index.php/DragMapSize');
INSERT INTO gui_element (fkey_gui_id, e_id, e_pos, e_public, e_comment, e_title, e_element,e_src, e_attributes, e_left, e_top, e_width, e_height, e_z_index, e_more_styles, e_content, e_closetag, e_js_file, e_mb_mod, e_target, e_requires,e_url) VALUES ('Geoportal-RLP','WMS_preferencesButton',2,1,'button for configure the preferences of each loaded wms','WMS Einstellungen','img','../img/button_blue_red/preferences_new_off.png','',145,125,24,24,1,'','','','../plugins/mb_button.js','','WMS_preferencesDiv','','http://www.mapbender.org/index.php/mb_button');
INSERT INTO gui_element_vars(fkey_gui_id, fkey_e_id, var_name, var_value, context, var_type) VALUES ('Geoportal-RLP','WMS_preferencesButton','dialogHeight','500','','var');
INSERT INTO gui_element_vars(fkey_gui_id, fkey_e_id, var_name, var_value, context, var_type) VALUES ('Geoportal-RLP','WMS_preferencesButton','dialogWidth','475','','var');
INSERT INTO gui_element (fkey_gui_id, e_id, e_pos, e_public, e_comment, e_title, e_element,e_src, e_attributes, e_left, e_top, e_width, e_height, e_z_index, e_more_styles, e_content, e_closetag, e_js_file, e_mb_mod, e_target, e_requires,e_url) VALUES ('Geoportal-RLP','addWMS',2,1,'add a WMS to the running application','WebMapService hinzufügen','img','../img/button_blue_red/add_off.png','',115,125,24,24,1,'','','','mod_addWMS.php','mod_addWMSgeneralFunctions.js','treeGDE,mapframe1','loadData','http://www.mapbender.org/index.php/AddWMS');
INSERT INTO gui_element (fkey_gui_id, e_id, e_pos, e_public, e_comment, e_title, e_element,e_src, e_attributes, e_left, e_top, e_width, e_height, e_z_index, e_more_styles, e_content, e_closetag, e_js_file, e_mb_mod, e_target, e_requires,e_url) VALUES ('Geoportal-RLP','deleteSessionWmc',2,1,'delete Session Wmc','Kartenansicht zurücksetzen','img','../img/button_blue_red/repaint_off.png','onclick=''$("#sessionWmc").mapbender().deleteWmc();''
onmouseover="this.src = this.src.replace(/_off/,''_over'');" onmouseout="this.src = this.src.replace(/_over/, ''_off'');"',175,5,24,24,NULL ,'','','','','','mapframe1','sessionWmc','');
INSERT INTO gui_element (fkey_gui_id, e_id, e_pos, e_public, e_comment, e_title, e_element,e_src, e_attributes, e_left, e_top, e_width, e_height, e_z_index, e_more_styles, e_content, e_closetag, e_js_file, e_mb_mod, e_target, e_requires,e_url) VALUES ('Geoportal-RLP','featureInfo1',2,1,'Get feature information','Datenabfrage','img','../img/button_blue_red/query_off.png','',175,65,24,24,1,'','','','mod_featureInfo.php','','mapframe1','','http://www.mapbender.org/index.php/FeatureInfo');
INSERT INTO gui_element_vars(fkey_gui_id, fkey_e_id, var_name, var_value, context, var_type) VALUES ('Geoportal-RLP','featureInfo1','featureInfoCollectLayers','true','','var');
INSERT INTO gui_element_vars(fkey_gui_id, fkey_e_id, var_name, var_value, context, var_type) VALUES ('Geoportal-RLP','featureInfo1','featureInfoPopupPosition','[100,100]','position of the featureInfoPopup','var');
INSERT INTO gui_element_vars(fkey_gui_id, fkey_e_id, var_name, var_value, context, var_type) VALUES ('Geoportal-RLP','featureInfo1','featureInfoLayerPopup','false','display featureInfo in dialog popup','var');
INSERT INTO gui_element_vars(fkey_gui_id, fkey_e_id, var_name, var_value, context, var_type) VALUES ('Geoportal-RLP','featureInfo1','reverseInfo','true','Reorder featureInfo result','var');
INSERT INTO gui_element_vars(fkey_gui_id, fkey_e_id, var_name, var_value, context, var_type) VALUES ('Geoportal-RLP','featureInfo1','featureInfoLayerPreselect','true','','var');
INSERT INTO gui_element_vars(fkey_gui_id, fkey_e_id, var_name, var_value, context, var_type) VALUES ('Geoportal-RLP','featureInfo1','featureInfoDrawClick','true','','var');
INSERT INTO gui_element_vars(fkey_gui_id, fkey_e_id, var_name, var_value, context, var_type) VALUES ('Geoportal-RLP','featureInfo1','featureInfoPopupHeight','350','height of the featureInfo dialog popup','var');
INSERT INTO gui_element_vars(fkey_gui_id, fkey_e_id, var_name, var_value, context, var_type) VALUES ('Geoportal-RLP','featureInfo1','featureInfoPopupWidth','380','width of the featureInfo dialog popup','var');
INSERT INTO gui_element (fkey_gui_id, e_id, e_pos, e_public, e_comment, e_title, e_element,e_src, e_attributes, e_left, e_top, e_width, e_height, e_z_index, e_more_styles, e_content, e_closetag, e_js_file, e_mb_mod, e_target, e_requires,e_url) VALUES ('Geoportal-RLP','measure_widget',2,1,'Measure','Messen','img','../img/button_blue_red/measure_off.png','',175,95,24,24,1,'','','','../plugins/mb_measure_widget.php','../widgets/w_measure.js,../extensions/RaphaelJS/raphael-1.4.7.min.js','mapframe1','jq_ui_dialog,jq_ui_widget','http://www.mapbender.org/index.php/Measure');
INSERT INTO gui_element_vars(fkey_gui_id, fkey_e_id, var_name, var_value, context, var_type) VALUES ('Geoportal-RLP','measure_widget','lineStrokeSnapped','#F30','','var');
INSERT INTO gui_element_vars(fkey_gui_id, fkey_e_id, var_name, var_value, context, var_type) VALUES ('Geoportal-RLP','measure_widget','pointStrokeDefault','#FF0000','','var');
INSERT INTO gui_element_vars(fkey_gui_id, fkey_e_id, var_name, var_value, context, var_type) VALUES ('Geoportal-RLP','measure_widget','pointStrokeSnapped','#FF0000','','var');
INSERT INTO gui_element_vars(fkey_gui_id, fkey_e_id, var_name, var_value, context, var_type) VALUES ('Geoportal-RLP','measure_widget','pointStrokeWidthDefault','2','','var');
INSERT INTO gui_element_vars(fkey_gui_id, fkey_e_id, var_name, var_value, context, var_type) VALUES ('Geoportal-RLP','measure_widget','lineStrokeDefault','#C9F','','var');
INSERT INTO gui_element_vars(fkey_gui_id, fkey_e_id, var_name, var_value, context, var_type) VALUES ('Geoportal-RLP','measure_widget','lineStrokeWidthDefault','3','','var');
INSERT INTO gui_element_vars(fkey_gui_id, fkey_e_id, var_name, var_value, context, var_type) VALUES ('Geoportal-RLP','measure_widget','lineStrokeWidthSnapped','5','','var');
INSERT INTO gui_element_vars(fkey_gui_id, fkey_e_id, var_name, var_value, context, var_type) VALUES ('Geoportal-RLP','measure_widget','measurePointDiameter','7','','var');
INSERT INTO gui_element_vars(fkey_gui_id, fkey_e_id, var_name, var_value, context, var_type) VALUES ('Geoportal-RLP','measure_widget','opacity','0.4','','var');
INSERT INTO gui_element_vars(fkey_gui_id, fkey_e_id, var_name, var_value, context, var_type) VALUES ('Geoportal-RLP','measure_widget','pointFillDefault','#CCF','','var');
INSERT INTO gui_element_vars(fkey_gui_id, fkey_e_id, var_name, var_value, context, var_type) VALUES ('Geoportal-RLP','measure_widget','pointFillSnapped','#F90','','var');
INSERT INTO gui_element_vars(fkey_gui_id, fkey_e_id, var_name, var_value, context, var_type) VALUES ('Geoportal-RLP','measure_widget','polygonFillDefault','#FFF','','var');
INSERT INTO gui_element_vars(fkey_gui_id, fkey_e_id, var_name, var_value, context, var_type) VALUES ('Geoportal-RLP','measure_widget','polygonFillSnapped','#FC3','','var');
INSERT INTO gui_element_vars(fkey_gui_id, fkey_e_id, var_name, var_value, context, var_type) VALUES ('Geoportal-RLP','measure_widget','polygonStrokeWidthDefault','1','','var');
INSERT INTO gui_element_vars(fkey_gui_id, fkey_e_id, var_name, var_value, context, var_type) VALUES ('Geoportal-RLP','measure_widget','polygonStrokeWidthSnapped','5','','var');
INSERT INTO gui_element_vars(fkey_gui_id, fkey_e_id, var_name, var_value, context, var_type) VALUES ('Geoportal-RLP','measure_widget','dialogHeight','250','','var');
INSERT INTO gui_element_vars(fkey_gui_id, fkey_e_id, var_name, var_value, context, var_type) VALUES ('Geoportal-RLP','measure_widget','dialogWidth','300','','var');
INSERT INTO gui_element (fkey_gui_id, e_id, e_pos, e_public, e_comment, e_title, e_element,e_src, e_attributes, e_left, e_top, e_width, e_height, e_z_index, e_more_styles, e_content, e_closetag, e_js_file, e_mb_mod, e_target, e_requires,e_url) VALUES ('Geoportal-RLP','pan1',2,1,'pan','Kartenausschnitt verschieben','img','../img/button_blue_red/pan_off.png','',145,65,24,24,1,'','','','mod_pan.js','','mapframe1','','http://www.mapbender.org/index.php/Pan');
INSERT INTO gui_element (fkey_gui_id, e_id, e_pos, e_public, e_comment, e_title, e_element,e_src, e_attributes, e_left, e_top, e_width, e_height, e_z_index, e_more_styles, e_content, e_closetag, e_js_file, e_mb_mod, e_target, e_requires,e_url) VALUES ('Geoportal-RLP','resizeMapsize',2,1,'resize_mapsize','Vollbild','img','../img/button_blue_red/resizemapsize_off.png','onmouseover="this.src = this.src.replace(/_off/,''_over'');" onmouseout="this.src = this.src.replace(/_over/, ''_off'');"',115,65,24,24,NULL ,'','','','mod_resize_mapsize.js','','mapframe1','','');
INSERT INTO gui_element_vars(fkey_gui_id, fkey_e_id, var_name, var_value, context, var_type) VALUES ('Geoportal-RLP','resizeMapsize','adjust_height','-35','to adjust the height of the mapframe on the bottom of the window','var');
INSERT INTO gui_element_vars(fkey_gui_id, fkey_e_id, var_name, var_value, context, var_type) VALUES ('Geoportal-RLP','resizeMapsize','adjust_width','-50','to adjust the width of the mapframe on the right side of the window','var');
INSERT INTO gui_element_vars(fkey_gui_id, fkey_e_id, var_name, var_value, context, var_type) VALUES ('Geoportal-RLP','resizeMapsize','resize_option','auto','auto (autoresize on load), button (resize by button)','var');
INSERT INTO gui_element_vars(fkey_gui_id, fkey_e_id, var_name, var_value, context, var_type) VALUES ('Geoportal-RLP','resizeMapsize','max_width','1000','auto to maximum of 1000px','var');
INSERT INTO gui_element_vars(fkey_gui_id, fkey_e_id, var_name, var_value, context, var_type) VALUES ('Geoportal-RLP','resizeMapsize','max_height','800','auto to maximum of 800px','var');
INSERT INTO gui_element (fkey_gui_id, e_id, e_pos, e_public, e_comment, e_title, e_element,e_src, e_attributes, e_left, e_top, e_width, e_height, e_z_index, e_more_styles, e_content, e_closetag, e_js_file, e_mb_mod, e_target, e_requires,e_url) VALUES ('Geoportal-RLP','selArea1',2,1,'zoombox','Ausschnitt mit Box aufziehen','img','../img/button_blue_red/selArea_off.png','',175,35,24,24,1,'','','','mod_selArea.js','mod_box1.js','mapframe1','','http://www.mapbender.org/index.php/SelArea1');
INSERT INTO gui_element (fkey_gui_id, e_id, e_pos, e_public, e_comment, e_title, e_element,e_src, e_attributes, e_left, e_top, e_width, e_height, e_z_index, e_more_styles, e_content, e_closetag, e_js_file, e_mb_mod, e_target, e_requires,e_url) VALUES ('Geoportal-RLP','showCoords_div',2,1,'displays coordinates by onmouseover','Koordinaten anzeigen','img','../img/button_blue_red/coords_off.png','onmouseover = "mb_regButton(''init_mod_showCoords_div'')" ',145,95,24,24,1,'','','','mod_coords_div.php','../extensions/mapcode-js-master/mapcode.js,../extensions/mapcode-js-master/ndata.js,../extensions/mapcode-js-master/ctrynams.js','mapframe1','','http://www.mapbender.org/index.php/ShowCoords_div');
INSERT INTO gui_element_vars(fkey_gui_id, fkey_e_id, var_name, var_value, context, var_type) VALUES ('Geoportal-RLP','showCoords_div','useMapcode','true','','var');
INSERT INTO gui_element (fkey_gui_id, e_id, e_pos, e_public, e_comment, e_title, e_element,e_src, e_attributes, e_left, e_top, e_width, e_height, e_z_index, e_more_styles, e_content, e_closetag, e_js_file, e_mb_mod, e_target, e_requires,e_url) VALUES ('Geoportal-RLP','zoomFull',2,1,'zoom to full extent button','Auf gesamte Karte zoomen','img','../img/button_blue_red/zoomFull_off.png','',115,5,24,24,2,'','','','mod_zoomFull.js','','mapframe1','','http://www.mapbender.org/index.php/ZoomFull');
INSERT INTO gui_element (fkey_gui_id, e_id, e_pos, e_public, e_comment, e_title, e_element,e_src, e_attributes, e_left, e_top, e_width, e_height, e_z_index, e_more_styles, e_content, e_closetag, e_js_file, e_mb_mod, e_target, e_requires,e_url) VALUES ('Geoportal-RLP','zoomIn1',2,1,'zoomIn button','In die Karte hineinzoomen','img','../img/button_blue_red/zoomIn2_off.png','',145,35,24,24,1,'','','','mod_zoomIn1.js','','mapframe1','','http://www.mapbender.org/index.php/ZoomIn');
INSERT INTO gui_element (fkey_gui_id, e_id, e_pos, e_public, e_comment, e_title, e_element,e_src, e_attributes, e_left, e_top, e_width, e_height, e_z_index, e_more_styles, e_content, e_closetag, e_js_file, e_mb_mod, e_target, e_requires,e_url) VALUES ('Geoportal-RLP','zoomOut1',2,1,'zoomOut button','Aus der Karte herauszoomen','img','../img/button_blue_red/zoomOut2_off.png','',115,35,24,24,1,'','','','mod_zoomOut1.js','','mapframe1','','http://www.mapbender.org/index.php/ZoomOut');
INSERT INTO gui_element (fkey_gui_id, e_id, e_pos, e_public, e_comment, e_title, e_element,e_src, e_attributes, e_left, e_top, e_width, e_height, e_z_index, e_more_styles, e_content, e_closetag, e_js_file, e_mb_mod, e_target, e_requires,e_url) VALUES ('Geoportal-RLP','treeGDE',2,1,'new treegde2 - directory tree, checkbox for visible, checkbox for querylayer
for more infos have a look at http://www.mapbender.org/index.php/TreeGDE2','Kartenebenen','div','','class="ui-widget"',12,144,195,275,2,'visibility:hidden;overflow:auto','','div','../html/mod_treefolderPlain.php','jsTree.js','mapframe1','mapframe1','http://www.mapbender.org/index.php/TreeGde');
INSERT INTO gui_element_vars(fkey_gui_id, fkey_e_id, var_name, var_value, context, var_type) VALUES ('Geoportal-RLP','treeGDE','activatedimension','true','','var');
INSERT INTO gui_element_vars(fkey_gui_id, fkey_e_id, var_name, var_value, context, var_type) VALUES ('Geoportal-RLP','treeGDE','datalink','true','','var');
INSERT INTO gui_element_vars(fkey_gui_id, fkey_e_id, var_name, var_value, context, var_type) VALUES ('Geoportal-RLP','treeGDE','css','../css/treeGDE2.css','','file/css');
INSERT INTO gui_element_vars(fkey_gui_id, fkey_e_id, var_name, var_value, context, var_type) VALUES ('Geoportal-RLP','treeGDE','ficheckbox','true','checkbox for featureInfo requests','var');
INSERT INTO gui_element_vars(fkey_gui_id, fkey_e_id, var_name, var_value, context, var_type) VALUES ('Geoportal-RLP','treeGDE','handlesublayer','true','','var');
INSERT INTO gui_element_vars(fkey_gui_id, fkey_e_id, var_name, var_value, context, var_type) VALUES ('Geoportal-RLP','treeGDE','imagedir','../img/tree_new','image directory','var');
INSERT INTO gui_element_vars(fkey_gui_id, fkey_e_id, var_name, var_value, context, var_type) VALUES ('Geoportal-RLP','treeGDE','localizetree','false','','var');
INSERT INTO gui_element_vars(fkey_gui_id, fkey_e_id, var_name, var_value, context, var_type) VALUES ('Geoportal-RLP','treeGDE','metadatalink','true','link for layer-metadata','var');
INSERT INTO gui_element_vars(fkey_gui_id, fkey_e_id, var_name, var_value, context, var_type) VALUES ('Geoportal-RLP','treeGDE','openfolder','false','initial open folder','var');
INSERT INTO gui_element_vars(fkey_gui_id, fkey_e_id, var_name, var_value, context, var_type) VALUES ('Geoportal-RLP','treeGDE','switchwms','true','enables/disables all layer of a wms','var');
INSERT INTO gui_element_vars(fkey_gui_id, fkey_e_id, var_name, var_value, context, var_type) VALUES ('Geoportal-RLP','treeGDE','wmsbuttons','false','wms management buttons','var');
INSERT INTO gui_element_vars(fkey_gui_id, fkey_e_id, var_name, var_value, context, var_type) VALUES ('Geoportal-RLP','treeGDE','reverse','true','','var');
INSERT INTO gui_element_vars(fkey_gui_id, fkey_e_id, var_name, var_value, context, var_type) VALUES ('Geoportal-RLP','treeGDE','showstatus','true','show status in folderimages','var');
INSERT INTO gui_element_vars(fkey_gui_id, fkey_e_id, var_name, var_value, context, var_type) VALUES ('Geoportal-RLP','treeGDE','enlargetreewidth','300','','var');
INSERT INTO gui_element_vars(fkey_gui_id, fkey_e_id, var_name, var_value, context, var_type) VALUES ('Geoportal-RLP','treeGDE','alerterror','false','alertbox for wms loading error','var');
INSERT INTO gui_element_vars(fkey_gui_id, fkey_e_id, var_name, var_value, context, var_type) VALUES ('Geoportal-RLP','treeGDE','menu','wms_up,wms_down,opacity_up,opacity_down,remove,layer_up,layer_down,zoom,hide,change_style','context menu elements','var');
INSERT INTO gui_element_vars(fkey_gui_id, fkey_e_id, var_name, var_value, context, var_type) VALUES ('Geoportal-RLP','treeGDE','featuretypeCoupling','true','','var');
INSERT INTO gui_element (fkey_gui_id, e_id, e_pos, e_public, e_comment, e_title, e_element,e_src, e_attributes, e_left, e_top, e_width, e_height, e_z_index, e_more_styles, e_content, e_closetag, e_js_file, e_mb_mod, e_target, e_requires,e_url) VALUES ('Geoportal-RLP','scaleSelect',2,1,'Scale-Selectbox','Maßstabsauswahl','select','','onchange=''mod_scaleSelect(this)''',5,125,105,20,1,'','<option value = ''''>Scale</option> <option value=''100''>1 : 100</option> <option value=''250''>1 : 250</option> <option value=''500''>1 : 500</option> <option value=''1000''>1 : 1000</option> <option value=''2500''>1 : 2500</option> <option value=''5000''>1 : 5000</option> <option value=''10000''>1 : 10000</option> <option value=''25000''>1 : 25000</option> <option value=''30000''>1 : 30000</option> <option value=''50000''>1 : 50000</option> <option value=''75000''>1 : 75000</option> <option value=''100000''>1 : 100000</option> <option value=''200000''>1 : 200000</option> <option value=''300000''>1 : 300000</option> <option value=''400000''>1 : 400000</option> <option value=''500000''>1 : 500000</option> <option value=''600000''>1 : 600000</option> <option value=''700000''>1 : 700000</option> <option value=''800000''>1 : 800000</option> <option value=''900000''>1 : 900000</option> <option value=''1000000''>1 : 1000000</option>','select','../plugins/mb_selectScale.js','','mapframe1','','http://www.mapbender.org/index.php?title=ScaleSelect');
INSERT INTO gui_element (fkey_gui_id, e_id, e_pos, e_public, e_comment, e_title, e_element,e_src, e_attributes, e_left, e_top, e_width, e_height, e_z_index, e_more_styles, e_content, e_closetag, e_js_file, e_mb_mod, e_target, e_requires,e_url) VALUES ('Geoportal-RLP','changeEPSG',2,1,'change EPSG, Postgres required, overview is targed for full extent','Kartenprojektion ändern','select','','',5,185,196,26,1,'','<option value="">undefined</option>','select','mod_changeEPSG.php','../extensions/proj4js/lib/proj4js-compressed.js','overview','','');
INSERT INTO gui_element_vars(fkey_gui_id, fkey_e_id, var_name, var_value, context, var_type) VALUES ('Geoportal-RLP','changeEPSG','projections','EPSG:4326;Geographic Coordinates,EPSG:31466;Gauss-Krueger 2,EPSG:31467;Gauss-Krueger 3,EPSG:31468;Gauss-Krueger 4,EPSG:31469;Gauss-Krueger 5,EPSG:25832;UTM zone 32N','','php_var');
INSERT INTO gui_element (fkey_gui_id, e_id, e_pos, e_public, e_comment, e_title, e_element,e_src, e_attributes, e_left, e_top, e_width, e_height, e_z_index, e_more_styles, e_content, e_closetag, e_js_file, e_mb_mod, e_target, e_requires,e_url) VALUES ('Geoportal-RLP','scaleText',2,1,'Scale-description field','Maßstab per Texteingabe','form','','action="window.location.href" onsubmit="return mod_scaleText()" ',5,153,30,25,NULL ,'','<input type="text" style="width:87px;">','form','mod_scaleText.php','','mapframe1','','');
INSERT INTO gui_element (fkey_gui_id, e_id, e_pos, e_public, e_comment, e_title, e_element,e_src, e_attributes, e_left, e_top, e_width, e_height, e_z_index, e_more_styles, e_content, e_closetag, e_js_file, e_mb_mod, e_target, e_requires,e_url) VALUES ('Geoportal-RLP','printPDF',2,1,'pdf print','Druck','div','','',860,10,200,380,5,'','<div id="printPDF_working_bg"></div><div id="printPDF_working"><img src="../img/indicator_wheel.gif" style="padding:10px 0 0 10px">Generating PDF</div><div id="printPDF_input"><form id="printPDF_form" action="../print/printFactory.php"><div id="printPDF_selector"></div><div class="print_option"><input type="hidden" id="map_url" name="map_url" value=""/><input type="hidden" id="legend_url" name="legend_url" value=""/><input type="hidden" id="opacity" name="opacity" value=""/> <input type="hidden" id="overview_url" name="overview_url" value=""/><input type="hidden" id="map_scale" name="map_scale" value=""/><input type="hidden" name="measured_x_values" /><input type="hidden" name="measured_y_values" /><input type="hidden" name="map_svg_kml" /><input type="hidden" name="svg_extent" /><input type="hidden" name="map_svg_measures" /><br /></div><div class="print_option" id="printPDF_formsubmit"><input id="submit" type="submit" value="Print"><br /></div></form><div id="printPDF_result"></div></div>','div','../plugins/mb_print.php','../../lib/printbox.js,../extensions/jquery-ui-1.8.16.custom/development-bundle/external/jquery.bgiframe-2.1.2.js,../extensions/jquery.form.min.js,../extensions/wz_jsgraphics.js','mapframe1','','http://www.mapbender.org/index.php/Print');
INSERT INTO gui_element_vars(fkey_gui_id, fkey_e_id, var_name, var_value, context, var_type) VALUES ('Geoportal-RLP','printPDF','mbPrintConfig','{"Format wählen": "Dummy_A4.json","A4 Hochformat": "Hochformat_A4.json","A4 Hochformat mit Legende": "Hochformat_A4_Legende_mehrseitig.json","A4 Querformat": "Querformat_A4.json","A3 Hochformat": "Hochformat_A3.json","A3 Querformat": "Querformat_A3.json"}','','var');
INSERT INTO gui_element_vars(fkey_gui_id, fkey_e_id, var_name, var_value, context, var_type) VALUES ('Geoportal-RLP','printPDF','reverseLegend','false','define whether the legend should be printed in reverse order','var');
INSERT INTO gui_element_vars(fkey_gui_id, fkey_e_id, var_name, var_value, context, var_type) VALUES ('Geoportal-RLP','printPDF','legendColumns','2','define number of columns on legendpage','php_var');
INSERT INTO gui_element_vars(fkey_gui_id, fkey_e_id, var_name, var_value, context, var_type) VALUES ('Geoportal-RLP','printPDF','printLegend','true','define whether the legend should be printed or not','php_var');
INSERT INTO gui_element (fkey_gui_id, e_id, e_pos, e_public, e_comment, e_title, e_element,e_src, e_attributes, e_left, e_top, e_width, e_height, e_z_index, e_more_styles, e_content, e_closetag, e_js_file, e_mb_mod, e_target, e_requires,e_url) VALUES ('Geoportal-RLP','copyright',2,1,'a Copyright in the map','Copyright','div','','',0,0,NULL ,NULL,NULL ,'','','div','mod_termsOfUse.php','','mapframe1','','http://www.mapbender.org/index.php/Copyright');
INSERT INTO gui_element_vars(fkey_gui_id, fkey_e_id, var_name, var_value, context, var_type) VALUES ('Geoportal-RLP','copyright','mod_copyright_text','mapbender.org','define a copyright text which should be displayed','var');
INSERT INTO gui_element (fkey_gui_id, e_id, e_pos, e_public, e_comment, e_title, e_element,e_src, e_attributes, e_left, e_top, e_width, e_height, e_z_index, e_more_styles, e_content, e_closetag, e_js_file, e_mb_mod, e_target, e_requires,e_url) VALUES ('Geoportal-RLP','kmlTree',3,1,'','Meine Geodaten','ul','','',1,1,1,1,NULL ,'','','ul','../plugins/kmlTree.php','../extensions/togeojson.js,../extensions/jquery-ui-1.8.16.custom/development-bundle/ui/jquery.ui.sortable.js,../extensions/fontIconPicker-2.0.0/jquery.fonticonpicker.js','mapframe1','','');
INSERT INTO gui_element_vars(fkey_gui_id, fkey_e_id, var_name, var_value, context, var_type) VALUES ('Geoportal-RLP','kmlTree','activateRegistratingGroupFilter','1','','var');
INSERT INTO gui_element_vars(fkey_gui_id, fkey_e_id, var_name, var_value, context, var_type) VALUES ('Geoportal-RLP','kmlTree','buffer','100','','var');
INSERT INTO gui_element_vars(fkey_gui_id, fkey_e_id, var_name, var_value, context, var_type) VALUES ('Geoportal-RLP','kmlTree','kmlTree','../css/kmltree.css','','file/css');
INSERT INTO gui_element_vars(fkey_gui_id, fkey_e_id, var_name, var_value, context, var_type) VALUES ('Geoportal-RLP','kmlTree','openData_only','1','','var');
INSERT INTO gui_element (fkey_gui_id, e_id, e_pos, e_public, e_comment, e_title, e_element,e_src, e_attributes, e_left, e_top, e_width, e_height, e_z_index, e_more_styles, e_content, e_closetag, e_js_file, e_mb_mod, e_target, e_requires,e_url) VALUES ('Geoportal-RLP','layout_1',3,0,'layout, background for buttons','','div','','',383,93,670,28,NULL ,'background-color:#FFFFFF;','','div','','','','','');
INSERT INTO gui_element (fkey_gui_id, e_id, e_pos, e_public, e_comment, e_title, e_element,e_src, e_attributes, e_left, e_top, e_width, e_height, e_z_index, e_more_styles, e_content, e_closetag, e_js_file, e_mb_mod, e_target, e_requires,e_url) VALUES ('Geoportal-RLP','jq_ui_button',4,1,'jQuery UI button','','','','',NULL ,NULL,NULL ,NULL,NULL ,'','','','','../extensions/jquery-ui-1.8.16.custom/development-bundle/ui/jquery.ui.button.js','','jq_ui,jq_ui_widget','');
INSERT INTO gui_element (fkey_gui_id, e_id, e_pos, e_public, e_comment, e_title, e_element,e_src, e_attributes, e_left, e_top, e_width, e_height, e_z_index, e_more_styles, e_content, e_closetag, e_js_file, e_mb_mod, e_target, e_requires,e_url) VALUES ('Geoportal-RLP','renderGML',4,1,'renders a gml contained in $_SESSION[''GML'']','','div','','',NULL ,NULL,NULL ,NULL,1,'','','','../javascripts/mod_renderGML.php','','overview,mapframe1','','');
INSERT INTO gui_element (fkey_gui_id, e_id, e_pos, e_public, e_comment, e_title, e_element,e_src, e_attributes, e_left, e_top, e_width, e_height, e_z_index, e_more_styles, e_content, e_closetag, e_js_file, e_mb_mod, e_target, e_requires,e_url) VALUES ('Geoportal-RLP','jq_ui_droppable',4,1,'jQuery UI droppable','','','','',NULL ,NULL,NULL ,NULL,NULL ,'','','','','../extensions/jquery-ui-1.8.16.custom/development-bundle/ui/jquery.ui.droppable.js','','jq_ui,jq_ui_widget,jq_ui_mouse,jq_ui_draggable','');
INSERT INTO gui_element (fkey_gui_id, e_id, e_pos, e_public, e_comment, e_title, e_element,e_src, e_attributes, e_left, e_top, e_width, e_height, e_z_index, e_more_styles, e_content, e_closetag, e_js_file, e_mb_mod, e_target, e_requires,e_url) VALUES ('Geoportal-RLP','jq_ui_slider',5,1,'slider from the jQuery UI framework','','','','',NULL ,NULL,NULL ,NULL,NULL ,'','','','','../extensions/jquery-ui-1.8.16.custom/development-bundle/ui/minified/jquery.ui.slider.min.js','','jq_ui,jq_ui_mouse,jq_ui_widget','');
INSERT INTO gui_element (fkey_gui_id, e_id, e_pos, e_public, e_comment, e_title, e_element,e_src, e_attributes, e_left, e_top, e_width, e_height, e_z_index, e_more_styles, e_content, e_closetag, e_js_file, e_mb_mod, e_target, e_requires,e_url) VALUES ('Geoportal-RLP','jq_ui_autocomplete',5,1,'Module to manage jQuery UI autocomplete module','','div','','',-1,-1,15,15,NULL ,'','','div','','../extensions/jquery-ui-1.8.16.custom/development-bundle/ui/jquery.ui.autocomplete.js','','jq_ui,jq_ui_widget,jq_ui_position','');
INSERT INTO gui_element (fkey_gui_id, e_id, e_pos, e_public, e_comment, e_title, e_element,e_src, e_attributes, e_left, e_top, e_width, e_height, e_z_index, e_more_styles, e_content, e_closetag, e_js_file, e_mb_mod, e_target, e_requires,e_url) VALUES ('Geoportal-RLP','jq_ui_resizable',5,1,'Resizable from the jQuery UI framework','','','','',NULL ,NULL,NULL ,NULL,NULL ,'','','','../plugins/jq_ui_resizable.js','../extensions/jquery-ui-1.8.16.custom/development-bundle/ui/jquery.ui.resizable.js','','jq_ui,jq_ui_mouse,jq_ui_widget','http://jqueryui.com/demos/resizable/');
INSERT INTO gui_element (fkey_gui_id, e_id, e_pos, e_public, e_comment, e_title, e_element,e_src, e_attributes, e_left, e_top, e_width, e_height, e_z_index, e_more_styles, e_content, e_closetag, e_js_file, e_mb_mod, e_target, e_requires,e_url) VALUES ('Geoportal-RLP','vis_timeline',5,1,'VIS Core','','','','',NULL ,NULL,NULL ,NULL,NULL ,'','','','','../extensions/vis/dist/vis.js','','','');
INSERT INTO gui_element_vars(fkey_gui_id, fkey_e_id, var_name, var_value, context, var_type) VALUES ('Geoportal-RLP','vis_timeline','file_vis_css','../extensions/vis/dist/vis.css','','file/css');
INSERT INTO gui_element (fkey_gui_id, e_id, e_pos, e_public, e_comment, e_title, e_element,e_src, e_attributes, e_left, e_top, e_width, e_height, e_z_index, e_more_styles, e_content, e_closetag, e_js_file, e_mb_mod, e_target, e_requires,e_url) VALUES ('Geoportal-RLP','jq_ui_dialog',5,1,'Module to manage jQuery UI dialog windows with multiple options for customization.','','div','','',-1,-1,15,15,NULL ,'','','div','','../extensions/jquery-ui-1.8.16.custom/development-bundle/ui/jquery.ui.dialog.js','','jq_ui,jq_ui_widget,jq_ui_button,jq_ui_draggable,jq_ui_mouse,jq_ui_position,jq_ui_resizable','');
INSERT INTO gui_element (fkey_gui_id, e_id, e_pos, e_public, e_comment, e_title, e_element,e_src, e_attributes, e_left, e_top, e_width, e_height, e_z_index, e_more_styles, e_content, e_closetag, e_js_file, e_mb_mod, e_target, e_requires,e_url) VALUES ('Geoportal-RLP','jq_ui_tabs',5,1,'horizontal tabs from the jQuery UI framework','','','','',NULL ,NULL,NULL ,NULL,NULL ,'','','','','../extensions/jquery-ui-1.8.16.custom/development-bundle/ui/jquery.ui.tabs.js','','jq_ui,jq_ui_widget','');
INSERT INTO gui_element (fkey_gui_id, e_id, e_pos, e_public, e_comment, e_title, e_element,e_src, e_attributes, e_left, e_top, e_width, e_height, e_z_index, e_more_styles, e_content, e_closetag, e_js_file, e_mb_mod, e_target, e_requires,e_url) VALUES ('Geoportal-RLP','jq_ui_draggable',5,1,'Draggable from the jQuery UI framework','','','','',NULL ,NULL,NULL ,NULL,NULL ,'','','','','../extensions/jquery-ui-1.8.16.custom/development-bundle/ui/jquery.ui.draggable.js','','jq_ui,jq_ui_mouse,jq_ui_widget','http://jqueryui.com/demos/draggable/');
INSERT INTO gui_element (fkey_gui_id, e_id, e_pos, e_public, e_comment, e_title, e_element,e_src, e_attributes, e_left, e_top, e_width, e_height, e_z_index, e_more_styles, e_content, e_closetag, e_js_file, e_mb_mod, e_target, e_requires,e_url) VALUES ('Geoportal-RLP','popup',6,1,'popup replacement','','div','','',NULL ,NULL,NULL ,NULL,NULL ,'','','div','popup.js','','','','');
INSERT INTO gui_element (fkey_gui_id, e_id, e_pos, e_public, e_comment, e_title, e_element,e_src, e_attributes, e_left, e_top, e_width, e_height, e_z_index, e_more_styles, e_content, e_closetag, e_js_file, e_mb_mod, e_target, e_requires,e_url) VALUES ('Geoportal-RLP','legendButton',7,1,'popup','Legende','img','../img/button_blue_red/select_choose_off.png','',145,5,24,24,NULL ,'','','','../plugins/mb_button.js','','legend','','');
INSERT INTO gui_element_vars(fkey_gui_id, fkey_e_id, var_name, var_value, context, var_type) VALUES ('Geoportal-RLP','legendButton','dialogHeight','400','','var');
INSERT INTO gui_element_vars(fkey_gui_id, fkey_e_id, var_name, var_value, context, var_type) VALUES ('Geoportal-RLP','legendButton','dialogWidth','350','','var');
INSERT INTO gui_element (fkey_gui_id, e_id, e_pos, e_public, e_comment, e_title, e_element,e_src, e_attributes, e_left, e_top, e_width, e_height, e_z_index, e_more_styles, e_content, e_closetag, e_js_file, e_mb_mod, e_target, e_requires,e_url) VALUES ('Geoportal-RLP','printPdfButton',7,1,'popup','PDF drucken','img','../img/button_blue_red/print_off.png','',175,125,24,24,NULL ,'','','','../plugins/mb_button.js','','printPDF','','');
INSERT INTO gui_element_vars(fkey_gui_id, fkey_e_id, var_name, var_value, context, var_type) VALUES ('Geoportal-RLP','printPdfButton','dialogHeight','300','','var');
INSERT INTO gui_element_vars(fkey_gui_id, fkey_e_id, var_name, var_value, context, var_type) VALUES ('Geoportal-RLP','printPdfButton','dialogWidth','350','','var');
INSERT INTO gui_element (fkey_gui_id, e_id, e_pos, e_public, e_comment, e_title, e_element,e_src, e_attributes, e_left, e_top, e_width, e_height, e_z_index, e_more_styles, e_content, e_closetag, e_js_file, e_mb_mod, e_target, e_requires,e_url) VALUES ('Geoportal-RLP','coordsLookUpButton',7,1,'popup','Koordinatensuche','img','../img/button_blue_red/user_off.png','',115,95,24,24,NULL ,'','','','../plugins/mb_button.js','','coordsLookup','','');
INSERT INTO gui_element_vars(fkey_gui_id, fkey_e_id, var_name, var_value, context, var_type) VALUES ('Geoportal-RLP','coordsLookUpButton','dialogHeight','250','','var');
INSERT INTO gui_element_vars(fkey_gui_id, fkey_e_id, var_name, var_value, context, var_type) VALUES ('Geoportal-RLP','coordsLookUpButton','dialogWidth','300','','var');
INSERT INTO gui_element_vars(fkey_gui_id, fkey_e_id, var_name, var_value, context, var_type) VALUES ('Geoportal-RLP','coordsLookUpButton','useMapcode','true','','var');
INSERT INTO gui_element (fkey_gui_id, e_id, e_pos, e_public, e_comment, e_title, e_element,e_src, e_attributes, e_left, e_top, e_width, e_height, e_z_index, e_more_styles, e_content, e_closetag, e_js_file, e_mb_mod, e_target, e_requires,e_url) VALUES ('Geoportal-RLP','coordsLookup',10,1,'','Koordinatensuche','div','','',1000,0,NULL ,NULL,NULL ,'z-index:9999;','','div','mod_coordsLookup.php','','mapframe1','','');
INSERT INTO gui_element_vars(fkey_gui_id, fkey_e_id, var_name, var_value, context, var_type) VALUES ('Geoportal-RLP','coordsLookup','perimeters','[50,200,1000,10000]','','var');
INSERT INTO gui_element_vars(fkey_gui_id, fkey_e_id, var_name, var_value, context, var_type) VALUES ('Geoportal-RLP','coordsLookup','projections','EPSG:4326;Geographic Coordinates,EPSG:31466;Gauss-Krueger 2,EPSG:31467;Gauss-Krueger 3,EPSG:31468;Gauss-Krueger 4,EPSG:31469;Gauss-Krueger 5,EPSG:25832;UTM zone 32N','','php_var');
INSERT INTO gui_element_vars(fkey_gui_id, fkey_e_id, var_name, var_value, context, var_type) VALUES ('Geoportal-RLP','coordsLookup','useMapcode','true','','var');
INSERT INTO gui_element (fkey_gui_id, e_id, e_pos, e_public, e_comment, e_title, e_element,e_src, e_attributes, e_left, e_top, e_width, e_height, e_z_index, e_more_styles, e_content, e_closetag, e_js_file, e_mb_mod, e_target, e_requires,e_url) VALUES ('Geoportal-RLP','jsonAutocompleteGazetteer',12,1,'Client for json webservices like geonames.org','Gazetteer','div','','',220,20,NULL ,NULL,999,'','','div','../plugins/mod_jsonAutocompleteGazetteer.php','','mapframe1','','http://www.mapbender.org/index.php/mod_jsonAutocompleteGazetteer.php');
INSERT INTO gui_element_vars(fkey_gui_id, fkey_e_id, var_name, var_value, context, var_type) VALUES ('Geoportal-RLP','jsonAutocompleteGazetteer','gazetteerUrl','https://www.geoportal.rlp.de/mapbender/geoportal/gaz_geom_mobile.php','','var');
INSERT INTO gui_element_vars(fkey_gui_id, fkey_e_id, var_name, var_value, context, var_type) VALUES ('Geoportal-RLP','jsonAutocompleteGazetteer','helpText','Orts- und Straßennamen sind bei der Adresssuche mit einem Komma voneinander zu trennen!

Auch Textfragmente der gesuchten Adresse reichen hierbei aus.

     Beispiel:
     Am Zehnthof 10 , St. Goar oder
     zehnt 10 , goar

Der passende Treffer muss in der erscheinenden Auswahlliste per Mausklick ausgewählt werden!','','var');
INSERT INTO gui_element_vars(fkey_gui_id, fkey_e_id, var_name, var_value, context, var_type) VALUES ('Geoportal-RLP','jsonAutocompleteGazetteer','isGeonames','false','','var');
INSERT INTO gui_element (fkey_gui_id, e_id, e_pos, e_public, e_comment, e_title, e_element,e_src, e_attributes, e_left, e_top, e_width, e_height, e_z_index, e_more_styles, e_content, e_closetag, e_js_file, e_mb_mod, e_target, e_requires,e_url) VALUES ('Geoportal-RLP','WMS_preferencesDiv',12,1,'Configure WMS preferences - div tag','WMS Einstellungen','div','','',870,60,NULL ,NULL,NULL ,'z-index:9999;','','div','../plugins/mod_WMSpreferencesDiv.php','','mapframe1','jq_ui_dialog','http://www.mapbender.org/index.php/WMS_preferencesDiv');
INSERT INTO gui_element (fkey_gui_id, e_id, e_pos, e_public, e_comment, e_title, e_element,e_src, e_attributes, e_left, e_top, e_width, e_height, e_z_index, e_more_styles, e_content, e_closetag, e_js_file, e_mb_mod, e_target, e_requires,e_url) VALUES ('Geoportal-RLP','mb_horizontal_accordion',100,1,'Put existing divs in new horizontal accordion div object. List the elements comma-separated under target, and make sure they have a title.','','div','','',0,0,NULL ,NULL,NULL ,'../../extensions/jquery-ui-1.7.2.custom/development-bundle/themes/base/ui.accordion.css','<dl></dl>','div','','../../extensions/jquery-ui-1.8.1.custom/development-bundle/ui/jquery.ui.accordion.js','menu_wms,menu_wfs,menu_wmc,menu_user,menu_gui,menu_auth','','');

