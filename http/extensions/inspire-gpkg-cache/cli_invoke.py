import json
import sys
import os
import subprocess

from inspire_gpkg_cache.spatial_data_cache import SpatialDataCache
#from builtins import False

print(sys.argv[1])

#os.environ["HTTP_PROXY"] = "http://{proxyhost}:{proxyport}"
#os.environ["HTTPS_PROXY"] = "http://{proxyhost}:{proxyport}"

# https://stackoverflow.com/questions/50607908/how-to-send-mail-in-python-on-linux-server-via-mail
def send_mail(subject: str, body: str, mail_address:str):
    body_str_encoded_to_byte = body.encode()
    return_stat = subprocess.run([f"mail", f"-s {subject}", f"-aFrom:kontakt@geoportal.rlp.de", mail_address], input=body_str_encoded_to_byte)
    print(return_stat) 

configuration = json.loads(sys.argv[1])

if sys.argv[2] == 'checkOptions':
    cache = SpatialDataCache(configuration['dataset_configuration'], json.dumps(configuration['area_of_interest']), 'https://vocabulary.geoportal.rlp.de/geonetwork/srv/ger/csw')
    json_result = cache.check_options()
    # give back json with download options
    print(json_result)
    sys.exit()
if sys.argv[2] == 'generateCache':
    output_filename = None
    output_folder = None
    if configuration['output_folder']:
        output_folder = str(configuration['output_folder'])
    if configuration['output_filename']:
        output_filename = str(configuration['output_filename']) 
    cache = SpatialDataCache(configuration['dataset_configuration'], json.dumps(configuration['area_of_interest']), 'https://vocabulary.geoportal.rlp.de/geonetwork/srv/ger/csw', output_filename=output_filename, output_folder=output_folder)
    print('start generate cache')
    cache.generate_cache()
    # send downloadlink via email
    send_mail(configuration['notification']['subject'], configuration['notification']['text'], configuration['notification']['email_address'])   
    sys.exit()



