INSERT INTO gui_element(fkey_gui_id, e_id, e_pos, e_public, e_comment, e_title, e_element, e_src, e_attributes, e_left, e_top, e_width, e_height, e_z_index, e_more_styles, e_content, e_closetag, e_js_file, e_mb_mod, e_target, e_requires, e_url) VALUES('<app_id>','metadataCarouselTinySlider',10,1,'','Metadata carousel','div','','',NULL ,NULL ,586,180,4000,'box-sizing: border-box;','','','../plugins/mod_metadataCarouselTinySlider.php','','','','');
INSERT INTO gui_element_vars(fkey_gui_id, fkey_e_id, var_name, var_value, context, var_type) VALUES('<app_id>', 'metadataCarouselTinySlider', 'allowResize', 'true', 'This element var defines if the viewer should extent wmc to viewer screen size' ,'var');
INSERT INTO gui_element_vars(fkey_gui_id, fkey_e_id, var_name, var_value, context, var_type) VALUES('<app_id>', 'metadataCarouselTinySlider', 'css_file_metadata_carousel_mb', '../css/tiny-slider-mapbender.css', '' ,'file/css');
INSERT INTO gui_element_vars(fkey_gui_id, fkey_e_id, var_name, var_value, context, var_type) VALUES('<app_id>', 'metadataCarouselTinySlider', 'css_file_metadata_carousel_tinyslider', '../extensions/tiny-slider-master/dist/tiny-slider.css', '' ,'file/css');
INSERT INTO gui_element_vars(fkey_gui_id, fkey_e_id, var_name, var_value, context, var_type) VALUES('<app_id>', 'metadataCarouselTinySlider', 'css_file_metadata_carousel_tinyslider_demo', '../css/tiny-slider-demo-style.css', '' ,'file/css');
INSERT INTO gui_element_vars(fkey_gui_id, fkey_e_id, var_name, var_value, context, var_type) VALUES('<app_id>', 'metadataCarouselTinySlider', 'css_file_metadata_carousel_tinyslider_prism', '../extensions/tiny-slider-master/demo/css/prism.css', '' ,'file/css');
INSERT INTO gui_element_vars(fkey_gui_id, fkey_e_id, var_name, var_value, context, var_type) VALUES('<app_id>', 'metadataCarouselTinySlider', 'maxResults', '5', '' ,'var');
INSERT INTO gui_element_vars(fkey_gui_id, fkey_e_id, var_name, var_value, context, var_type) VALUES('<app_id>', 'metadataCarouselTinySlider', 'resourceFilter', '[]', 'array of ids to restrict the metadata resources ' ,'var');
INSERT INTO gui_element_vars(fkey_gui_id, fkey_e_id, var_name, var_value, context, var_type) VALUES('<app_id>', 'metadataCarouselTinySlider', 'searchUrl', '../php/mod_callMetadata.php?', '' ,'var');
INSERT INTO gui_element_vars(fkey_gui_id, fkey_e_id, var_name, var_value, context, var_type) VALUES('<app_id>', 'metadataCarouselTinySlider', 'slidesPerSide', '3', '' ,'var');

INSERT INTO gui_element(fkey_gui_id, e_id, e_pos, e_public, e_comment, e_title, e_element, e_src, e_attributes, e_left, e_top, e_width, e_height, e_z_index, e_more_styles, e_content, e_closetag, e_js_file, e_mb_mod, e_target, e_requires, e_url) VALUES('<app_id>','tinySliderModule',12,1,'','','','','',NULL ,NULL ,NULL ,NULL ,NULL ,'','','','','../extensions/tiny-slider-master/dist/min/tiny-slider.js','','','');

INSERT INTO gui_element(fkey_gui_id, e_id, e_pos, e_public, e_comment, e_title, e_element, e_src, e_attributes, e_left, e_top, e_width, e_height, e_z_index, e_more_styles, e_content, e_closetag, e_js_file, e_mb_mod, e_target, e_requires, e_url) VALUES('<app_id>','carouselDiv',5,1,'','Maßstab','div','','',NULL ,NULL ,NULL ,NULL ,99,'','<div id="carouselDiv_btn">
<svg style=''transform:rotate(0deg);margin: 0 auto;width: 100%;'' width="17" height="18" viewBox="0 0 18 18" fill="none"  xmlns="http://www.w3.org/2000/svg">
<path d="M16.0142 11.6191L14.6042 13.0291L9.01416 7.43914L3.42416 13.0291L2.01416 11.6191L9.01416 4.61914L16.0142 11.6191Z" stroke="currentColor" stroke-width="2"/>
</svg><p class=''carouselDiv_btn_name''>Karten entdecken?</p></div>','div','../plugins/mb_carouselContainer.js','','metadataCarouselTinySlider','','');
INSERT INTO gui_element_vars(fkey_gui_id, fkey_e_id, var_name, var_value, context, var_type) VALUES('<app_id>', 'carouselDiv', 'css', '/* INSERT carouselDiv -> elementVar -> css(text/css) */

#carouselDiv_btn{
position:absolute;
bottom:0px;
background-color:rgba(255,255,255,1);
font-family: Helvetica,Roboto,Arial,sans-serif;
color: #777;
font-style: normal;
font-weight: 700;
letter-spacing: 1px;
padding:5px 20px;
border-top: 2px solid #DDD;
border-left: 2px solid #DDD;
border-right: 2px solid #DDD;
box-sizing: border-box;
}
#carouselDiv_btn:hover{
cursor: pointer;
color: #333;

}
#carouselDiv_btn p{
margin:0;
padding:0;
text-align:center;
}
.carouselDiv_btn_Opened svg{
transform: rotate(-180deg) !important;
}

#carouselContainer{
display:none;
margin:0;
padding:0;
list-style-type: none;
float:left;
position: absolute;
bottom: 20px;
overflow:hidden;
}

#carouselDiv{
width: 100vw;
height: 20px;
position: fixed;
bottom: 0;
display: flex;
flex-direction: row;
justify-content: center;
}

.carouselDiv_btn_Opened {
bottom: 20px !important;
border-bottom: 2px solid #DDD;
padding: 5px !important;
}

.tns-liveregion {display:none;}

@media (max-width: 942px) {
#carouselContainer{
width: calc(100vw - 50px);
max-width: 630px;
height: 164px;
}

.carouselDiv_btn_Opened {
width: calc(100vw - 54px) !important;
max-width: 630px;
height: 194px;
}

}

@media (min-width: 943px) {

#carouselContainer{
width: calc(100vw - 477px);
min-width: 630px;
max-width: 630px;

height: 164px;
}

.carouselDiv_btn_Opened {
min-width: 630px;
max-width: 630px;
/*width: calc(100vw - 477px) !important;*/
height: 194px;
}
}

/* END INSERT carouselDiv -> elementVar -> css(text/css) */
', '' ,'text/css');


