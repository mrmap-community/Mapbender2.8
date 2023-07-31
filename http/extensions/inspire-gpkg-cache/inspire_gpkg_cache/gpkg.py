import json
import logging
import os
import sqlite3
import uuid
import xml.etree.ElementTree as ET

from osgeo import gdal, ogr

# configure logging
logging.basicConfig(level=os.environ.get("LOGLEVEL", "INFO"))
log = logging.getLogger("Gpkg")

class Gpkg:
    """A simple class to manage datasets and metadata in gpkg files"""
    def __init__(self, uri):
        self.name = uri
        self.dr = ogr.GetDriverByName( 'GPKG' )
        self.md_standard_uri = "http://www.isotc211.org/2005/gmd"
        # try to open - if not exist, create it
        gpkg_ds = self.dr.Open( uri, update = 1 ) 
        if gpkg_ds is None:
            log.info("no geopackage with given name found - create a new one")
            gpkg_ds = self.dr.CreateDataSource( uri )
            gpkg_ds = None

    def get_content_list(self):
        conn = sqlite3.connect( str(self.name) )   
        cursor = conn.cursor()
        cursor.execute("SELECT table_name, data_type, last_change FROM gpkg_contents;")
        result = cursor.fetchall()
        for row in result: 
            log.info(json.dumps(row))
        conn.close()

    def get_content_list2(self):
        conn = sqlite3.connect( str(self.name) )   
        cursor = conn.cursor()
        # get al relevant information - also extract relevant metadata from iso xml
        script = "SELECT metadata, md_scope, gpkg_contents.table_name, last_change FROM gpkg_metadata INNER JOIN "
        script += "gpkg_metadata_reference ON gpkg_metadata.id = gpkg_metadata_reference.md_file_id "
        script += "INNER JOIN gpkg_contents ON gpkg_metadata_reference.table_name = gpkg_contents.table_name "
        script += "WHERE "
        script += "gpkg_metadata.md_standard_uri = 'http://www.isotc211.org/2005/gmd'"
        cursor.execute(script)
        result = cursor.fetchall()
        for row in result: 
            metadata_info = self.get_info_from_iso_xml(row[0])
            log.info(row[1] + " - " + metadata_info['title'] + " - " + row[3] + " - " + metadata_info['date'])
        conn.close()

    def get_info_from_iso_xml(self, iso_xml):
        # read title, format, date, spatial_dataset_identifier
        metadata_info = {}
        tree = ET.fromstring(iso_xml)
        date = tree.findall("./{http://www.isotc211.org/2005/gmd}dateStamp/{http://www.isotc211.org/2005/gco}Date")
        if date:
            metadata_info['date'] = date[0].text
        title = tree.findall("./{http://www.isotc211.org/2005/gmd}identificationInfo/{http://www.isotc211.org/2005/gmd}MD_DataIdentification/{http://www.isotc211.org/2005/gmd}citation/{http://www.isotc211.org/2005/gmd}CI_Citation/{http://www.isotc211.org/2005/gmd}title/{http://www.isotc211.org/2005/gco}CharacterString")
        if title:
            metadata_info['title'] = title[0].text
        md_identifier_code = tree.findall("./{http://www.isotc211.org/2005/gmd}identificationInfo/{http://www.isotc211.org/2005/gmd}MD_DataIdentification/{http://www.isotc211.org/2005/gmd}citation/{http://www.isotc211.org/2005/gmd}CI_Citation/{http://www.isotc211.org/2005/gmd}identifier/{http://www.isotc211.org/2005/gmd}MD_Identifier/{http://www.isotc211.org/2005/gmd}code/{http://www.isotc211.org/2005/gco}CharacterString")
        
        md_identifier_codespace = tree.findall("./{http://www.isotc211.org/2005/gmd}identificationInfo/{http://www.isotc211.org/2005/gmd}MD_DataIdentification/{http://www.isotc211.org/2005/gmd}citation/{http://www.isotc211.org/2005/gmd}CI_Citation/{http://www.isotc211.org/2005/gmd}identifier/{http://www.isotc211.org/2005/gmd}MD_Identifier/{http://www.isotc211.org/2005/gmd}codespace/{http://www.isotc211.org/2005/gco}CharacterString")
        if md_identifier_code:
            metadata_info['spatial_dataset_identifier'] = md_identifier_code[0].text
            if md_identifier_codespace:
                metadata_info['spatial_dataset_identifier'] += md_identifier_codespace[0].text
        return metadata_info

    def get_original_metadata(self, tablename):
        # read original metadata - e.g. to compare it with new metadata from catalogue
        pass

    def get_current_iso_metadata(self, layer_name):
        layer_type = self.gpkg_layer_exists(layer_name)
        if layer_type:
            conn = sqlite3.connect(str(self.name))                                
            cursor = conn.cursor()
            data = [
                str(layer_name), str(self.md_standard_uri),
            ]
            script = "SELECT metadata FROM gpkg_metadata INNER JOIN "
            script += "gpkg_metadata_reference ON gpkg_metadata.id = gpkg_metadata_reference.md_file_id WHERE "
            script += "gpkg_metadata_reference.table_name = ? and md_standard_uri = ? ORDER BY gpkg_metadata_reference.timestamp DESC;"
            cursor.execute(script, data)
            result = cursor.fetchmany()[0]
            conn.close()
            if result:
                log.info("metadata for layer with name " + layer_name + ": " + json.dumps(result[0]))
                return result
            else:
                log.info("no metadata for layer with name: " + layer_name + " found")
                return False
        else:
            log.info("no layer with metadata found with name " + layer_name)
            return False

    def gpkg_layer_exists(self, layer_name):
        # check if layer_name exists as raster or vector layer
        layer_type = False
        # first check vector layer
        gpkg_ds = self.dr.Open(str(self.name), update = 1)
        if gpkg_ds.GetLayerCount() == 0:
            log.info("gpkg does not have any ogr layer")
        else:
            if gpkg_ds.GetLayerByName(layer_name):
                layer_type = "ogr"        
        gpkg_ds = None
        # try to open as raster layer
        gpkg_ds = gdal.Open( 'GPKG:' + self.name + ':' + layer_name, gdal.GA_ReadOnly)
        if gpkg_ds:
            layer_type = "raster"
            gpkg_ds = None
        log.info("layer exists returned: " + str(layer_type))
        return layer_type

    def delete_raster_layer(self, layer_name):
        layer_type = self.gpkg_layer_exists(layer_name)
        if layer_type == 'raster':
            conn = sqlite3.connect(str(self.name))                                
            cursor = conn.cursor()
            data = (str(layer_name),)
            cursor.execute("DELETE FROM gpkg_tile_matrix_set WHERE table_name = ?", data)
            cursor.execute("DELETE FROM gpkg_tile_matrix WHERE table_name = ?", data)
            cursor.execute("DELETE FROM gpkg_metadata_reference WHERE table_name = ?", data)
            # TODO - also delete metadata for those references!
            cursor.execute("DELETE FROM gpkg_contents WHERE table_name = ?", data)
            cursor.execute("DROP TABLE \"" + layer_name + "\"")
            conn.commit()
            cursor.execute("VACUUM;")
            conn.close()
            log.info("existing raster layer was deleted by using sqlite3")
            return True
        else:
            if layer_type == "ogr":
                log.info("layer is not a raster layer!")
            else:
                log.info("no layer with name " + layer_name + " found")
            return False
        
    def add_original_metadata(self, md_id, metadata):
        uuid_value = uuid.uuid4()
        conn = sqlite3.connect(str(self.name))                                
        cursor = conn.cursor()
        data = [
            ("dataset", str(uuid_value), "text/xml", str(metadata)),
        ]
        cursor.executemany("INSERT INTO gpkg_metadata (md_scope, md_standard_uri, mime_type, metadata) VALUES (?, ?, ?, ?)", data)
        conn.commit()
        # then select the serial id of this created record 
        cursor.execute("SELECT * FROM gpkg_metadata WHERE md_standard_uri = '" + str(uuid_value) + "';")
        result = cursor.fetchmany()[0]
        log.info(json.dumps(result[0]))
        # test if only one result came back
        # test if result is an integer
        # insert reference 
        md_id_parent = result[0]
        # update md_standard_uri with relevant spec
        data = [
            (self.md_standard_uri, str(uuid_value)), 
        ]
        cursor.executemany("UPDATE gpkg_metadata SET md_standard_uri = ? WHERE md_standard_uri = ?", data)
        data = [
            (md_id_parent, md_id), 
        ]
        cursor.executemany("UPDATE gpkg_metadata_reference SET md_parent_id = ? WHERE md_file_id = ?", data)
        conn.commit()
        conn.close()
        log.info("original metadata for md with id " +  str(md_id) + " stored as parent metadata into gpkg!")
        # update reference table - set parent
        return True

    def add_table_metadata(self, layer_name, metadata):
        layer_type = self.gpkg_layer_exists(layer_name)
        if layer_type:
            # optional: check if metadata for layer already exists
            # insert metadata 
            uuid_value = uuid.uuid4()
            conn = sqlite3.connect(str(self.name))                                
            cursor = conn.cursor()
            # first insert metadata with md_standard_uri is uuid
            # cursor.execute("INSERT INTO gpkg_metadata (md_scope, md_standard_uri, mime_type, metadata) VALUES ('dataset', '" + str(uuid) + "', 'text/xml', " + metadata + ");")
            data = [
                ("dataset", str(uuid_value), "text/xml", str(metadata)),
            ]
            cursor.executemany("INSERT INTO gpkg_metadata (md_scope, md_standard_uri, mime_type, metadata) VALUES (?, ?, ?, ?)", data)
            conn.commit()
            # then select the serial id of this created record 
            cursor.execute("SELECT * FROM gpkg_metadata WHERE md_standard_uri = '" + str(uuid_value) + "';")
            result = cursor.fetchmany()[0]
            log.info(json.dumps(result[0]))
            # test if only one result came back
            # test if result is an integer
            # insert reference 
            md_id = result[0]
            # insert reference to table/layer
            data = [
                ("table", str(layer_name), str(md_id)),
            ]
            cursor.executemany("INSERT INTO gpkg_metadata_reference (reference_scope, table_name, md_file_id) VALUES (?, ?, ?)", data)
            # update md_standard_uri with relevant spec
            data = [
                (self.md_standard_uri, str(uuid_value)), 
            ]
            cursor.executemany("UPDATE gpkg_metadata SET md_standard_uri = ? WHERE md_standard_uri = ?", data)
            conn.commit()
            conn.close()
            log.info("metadata for " +  layer_type + " layer with name " + layer_name + " written to gpkg!")
            return md_id
        else:
            log.info("no layer with name " + layer_name + " found in gpkg!")
            return False

    def add_dummy_tif_layer(self, layer_name):
        #gpkg_ds = self.dr.Open( str(self.name), update = 1)
        gtif = gdal.Open("2b009ae4-aa3e-ff21-870b-49846d9561b2_0.tif") 

        #https://gis.stackexchange.com/questions/262768/unexpected-corner-coordinates-after-change-in-reference-system-using-gdal

        #gtif_4326 = gdal.Open("2b009ae4-aa3e-ff21-870b-49846d9561b2_0.tif")
        #create_options1 = gdal.TranslateOptions(outputSRS='EPSG:4326')

        #gdal.Translate("geotif_4326.tif", gtif_orig, options = create_options1)

        #gtif_4326 = gdal.Open("geotif_4326.tif") 

        create_options = gdal.TranslateOptions(creationOptions=['RASTER_TABLE=' + layer_name, 'APPEND_SUBDATASET=YES'])
        log.info("import tif into gpkg")
        # first delete existing raster layer
        layer_type = self.gpkg_layer_exists(layer_name)
        if layer_type:
            if layer_type == "ogr":
                log.info("an ogr layer with name " + layer_name + " already exists in gpkg")
            # TODO: https://gis.stackexchange.com/questions/252782/remove-a-raster-from-geopackage
            log.info("delete already existing raster layer: " + str(layer_name))
            self.delete_raster_layer(layer_name)
        log.info("gdaltranslate geotif to gpkg with name " + str(layer_name))
        gdal.Translate(str(self.name), gtif, options = create_options)
        gpkg_ds = gdal.Open( 'GPKG:' + str(self.name) + ':' + layer_name, gdal.GA_Update)
        gpkg_ds.BuildOverviews("nearest", overviewlist=[2, 4, 8, 16, 32, 64])
        log.info("build overviews")
        gpkg_ds = None

    def add_tif_layer(self, layer_name, tif_filename):
        gtif = gdal.Open(tif_filename) 
        create_options = gdal.TranslateOptions(creationOptions=['RASTER_TABLE=' + layer_name, 'APPEND_SUBDATASET=YES', 'TILING_SCHEME=GoogleMapsCompatible'])
        log.info("import tif into gpkg")
        # first delete existing raster layer
        layer_type = self.gpkg_layer_exists(layer_name)
        if layer_type:
            if layer_type == "ogr":
                log.info("an ogr layer with name " + layer_name + " already exists in gpkg")
            # TODO: https://gis.stackexchange.com/questions/252782/remove-a-raster-from-geopackage
            log.info("delete already existing raster layer: " + str(layer_name))
            self.delete_raster_layer(layer_name)
        log.info("gdaltranslate geotif to gpkg with name " + str(layer_name))
        gdal.Translate(str(self.name), gtif, options = create_options)
        gpkg_ds = gdal.Open( 'GPKG:' + str(self.name) + ':' + layer_name, gdal.GA_Update)
        gpkg_ds.BuildOverviews("nearest", overviewlist=[2, 4, 8, 16, 32, 64])
        log.info("build overviews")
        gpkg_ds = None

    def add_dummy_geojson_layer(self):
        log.info("add geojson layer")
        geojson = ogr.Open("extent_new.geojson", update = 1)
        #https://gis.stackexchange.com/questions/404338/replicating-ogr2ogr-operation-with-gdal-vector-translate
        gdal.VectorTranslate(self.name, "extent_new.geojson", layerName="testlayer", accessMode="overwrite")
        geojson = None
    
    def add_geojson_layer(self, layer_name, geojson_filename):
        log.info("add geojson layer")
        gdal.VectorTranslate(self.name, geojson_filename, layerName=layer_name, accessMode="overwrite")

# initialize class with name of gpkg

"""test = Gpkg('test1.gpkg')
log.info("test1 instantiated")
test.get_content_list()
test.add_dummy_geojson_layer()
log.info("geojson layer added")
test.get_content_list()
test.add_dummy_tif_layer("2b009ae4-aa3e-ff21-870b-49846d9561b2_0_tif")
log.info("dummy tif layer added")
test.get_content_list()
test.add_dummy_tif_layer("2b009ae4-aa3e-ff21-870b-49846d9561b2_0_tif")
test.get_content_list()
test.add_dummy_geojson_layer()
test.add_dummy_geojson_layer()

test.get_content_list()
exit()"""
#test.list_tables()
#test.list_raster_datasets()
#test.get_contents()
#test = Gpkg('spatialcache.gpkg')
#test.get_content_list2()
"""test.add_table_metadata("2b009ae4-aa3e-ff21-870b-49846d9561b2_0_tiff", "testxml")
test.add_table_metadata("2b009ae4-aa3e-ff21-870b-49846d9561b2_0_tiff", "testxml33")
test.add_table_metadata("2b009ae4-aa3e-ff21-870b-49846d9561b2_0_tiff", "testxml3")
test.add_table_metadata("2b009ae4-aa3e-ff21-870b-49846d9561b2_0_tiff", "testxml3455")
test.get_current_iso_metadata("2b009ae4-aa3e-ff21-870b-49846d9561b2_0_tiff")"""
