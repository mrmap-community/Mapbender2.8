#mapbender database install script for debian 11 bullseye

# mapbender database config
mapbender_database_name="mapbender"
mapbender_database_port="5432"
mapbender_database_user="mapbender_user"
mapbender_database_password="mapbender_password"

# mapbender user specific stuff
mapbender_guest_user_id="2"
mapbender_guest_group_id="22"
mapbender_subadmin_group_id="21"
mapbender_subadmin_default_user_id="3"
mapbender_subadmin_default_group_id="23"

# other things
installation_log="install.log"
installation_folder="/home/armin/devel/mapbender2/" 
default_gui_name="Geoportal-RLP"



# Installing mapbenders database  
echo -e "\n Installing Mapbender database \n" | tee -a $installation_log
# DROP existing database
su - postgres -c "dropdb --if-exists -p $mapbender_database_port $mapbender_database_name" | tee -a $installation_log
# CREATE new database
su - postgres -c "createdb -p $mapbender_database_port -E UTF8 $mapbender_database_name -T template0" | tee -a $installation_log
# DROP user
sudo -u postgres psql -q -p $mapbender_database_port -d $mapbender_database_name -c "DROP USER IF EXISTS $mapbender_database_user" | tee -a $installation_log
# CREATE user
sudo -u postgres psql -q -p $mapbender_database_port -d $mapbender_database_name -c "CREATE USER $mapbender_database_user WITH ENCRYPTED PASSWORD '$mapbender_database_password'" | tee -a $installation_log
# Install postgis extensions
su - postgres -c "psql -q -p $mapbender_database_port -d $mapbender_database_name -f /usr/share/postgresql/13/contrib/postgis-3.1/postgis.sql" | tee -a $installation_log
su - postgres -c "psql -q -p $mapbender_database_port -d $mapbender_database_name -f /usr/share/postgresql/13/contrib/postgis-3.1/spatial_ref_sys.sql" | tee -a $installation_log
su - postgres -c "psql -q -p $mapbender_database_port -d $mapbender_database_name -f /usr/share/postgresql/13/contrib/postgis-3.1/legacy.sql" | tee -a $installation_log
su - postgres -c "PGOPTIONS='--client-min-messages=warning' psql -q -p $mapbender_database_port -d $mapbender_database_name -f /usr/share/postgresql/13/contrib/postgis-3.1/topology.sql" | tee -a $installation_log
# Grant rights to mapbender user
su - postgres -c "psql -q -p $mapbender_database_port -d $mapbender_database_name -c 'GRANT ALL PRIVILEGES ON DATABASE $mapbender_database_name TO $mapbender_database_user'" | tee -a $installation_log
# Switch databse owner
su - postgres -c "psql -q -p $mapbender_database_port -d $mapbender_database_name -c 'ALTER DATABASE $mapbender_database_name OWNER TO $mapbender_database_user'" | tee -a $installation_log
# Create schema
sudo -u postgres psql -q -p $mapbender_database_port -d $mapbender_database_name -c 'CREATE SCHEMA mapbender' | tee -a $installation_log
# Set search path
sudo -u postgres psql -q -p $mapbender_database_port -d $mapbender_database_name -c "ALTER DATABASE $mapbender_database_name SET search_path TO public,mapbender,pg_catalog,topology" | tee -a $installation_log

# Install mapbender database - with migrations from initial schema
sudo -u postgres PGOPTIONS='--client-min-messages=warning' psql -q -p $mapbender_database_port -d $mapbender_database_name -f ${installation_folder}mapbender/resources/db/pgsql/pgsql_schema_2.5.sql | tee -a $installation_log
sudo -u postgres PGOPTIONS='--client-min-messages=warning' psql -q -p $mapbender_database_port -d $mapbender_database_name -f ${installation_folder}mapbender/resources/db/pgsql/UTF-8/pgsql_data_2.5.sql | tee -a $installation_log
sudo -u postgres PGOPTIONS='--client-min-messages=warning' psql -q -p $mapbender_database_port -d $mapbender_database_name -f ${installation_folder}mapbender/resources/db/pgsql/pgsql_serial_set_sequences_2.5.sql | tee -a $installation_log
sudo -u postgres PGOPTIONS='--client-min-messages=warning' psql -q -p $mapbender_database_port -d $mapbender_database_name -f ${installation_folder}mapbender/resources/db/pgsql/UTF-8/update/update_2.5_to_2.5.1rc1_pgsql_UTF-8.sql | tee -a $installation_log
sudo -u postgres PGOPTIONS='--client-min-messages=warning' psql -q -p $mapbender_database_port -d $mapbender_database_name -f ${installation_folder}mapbender/resources/db/pgsql/UTF-8/update/update_2.5.1rc1_to_2.5.1_pgsql_UTF-8.sql | tee -a $installation_log
sudo -u postgres PGOPTIONS='--client-min-messages=warning' psql -q -p $mapbender_database_port -d $mapbender_database_name -f ${installation_folder}mapbender/resources/db/pgsql/UTF-8/update/update_2.5.1_to_2.6rc1_pgsql_UTF-8.sql | tee -a $installation_log
sudo -u postgres PGOPTIONS='--client-min-messages=warning' psql -q -p $mapbender_database_port -d $mapbender_database_name -f ${installation_folder}mapbender/resources/db/pgsql/UTF-8/update/update_2.6rc1_to_2.6_pgsql_UTF-8.sql | tee -a $installation_log
sudo -u postgres PGOPTIONS='--client-min-messages=warning' psql -q -p $mapbender_database_port -d $mapbender_database_name -f ${installation_folder}mapbender/resources/db/pgsql/UTF-8/update/update_2.6_to_2.6.1_pgsql_UTF-8.sql | tee -a $installation_log
sudo -u postgres PGOPTIONS='--client-min-messages=warning' psql -q -p $mapbender_database_port -d $mapbender_database_name -f ${installation_folder}mapbender/resources/db/pgsql/UTF-8/update/update_2.6.1_to_2.6.2_pgsql_UTF-8.sql | tee -a $installation_log
sudo -u postgres PGOPTIONS='--client-min-messages=warning' psql -q -p $mapbender_database_port -d $mapbender_database_name -f ${installation_folder}mapbender/resources/db/pgsql/UTF-8/update/update_2.6.2_to_2.7rc1_pgsql_UTF-8.sql | tee -a $installation_log
sudo -u postgres PGOPTIONS='--client-min-messages=warning' psql -q -p $mapbender_database_port -d $mapbender_database_name -f ${installation_folder}mapbender/resources/db/pgsql/UTF-8/update/update_2.7rc1_to_2.7rc2_pgsql_UTF-8.sql | tee -a $installation_log
sudo -u postgres PGOPTIONS='--client-min-messages=warning' psql -q -p $mapbender_database_port -d $mapbender_database_name -f ${installation_folder}mapbender/resources/db/pgsql/UTF-8/update/update_2.7.1_to_2.7.2_pgsql_UTF-8.sql | tee -a $installation_log
sudo -u postgres PGOPTIONS='--client-min-messages=warning' psql -q -p $mapbender_database_port -d $mapbender_database_name -f ${installation_folder}mapbender/resources/db/pgsql/UTF-8/update/update_2.7.2_to_2.7.3_pgsql_UTF-8.sql | tee -a $installation_log
sudo -u postgres PGOPTIONS='--client-min-messages=warning' psql -q -p $mapbender_database_port -d $mapbender_database_name -f ${installation_folder}mapbender/resources/db/pgsql/UTF-8/update/update_2.7.3_to_2.7.4_pgsql_UTF-8.sql | tee -a $installation_log

sed -i "s/mapbenderdbuser/${mapbender_database_user}/g" ${installation_folder}mapbender/resources/db/pgsql/UTF-8/update/update_2.8_pgsql_UTF-8.sql | tee -a $installation_log
  
sudo -u postgres PGOPTIONS='--client-min-messages=warning' psql -q -p $mapbender_database_port -d $mapbender_database_name -f ${installation_folder}mapbender/resources/db/pgsql/UTF-8/update/update_2.7.4_to_2.8_pgsql_UTF-8.sql | tee -a $installation_log
sudo -u postgres PGOPTIONS='--client-min-messages=warning' psql -q -p $mapbender_database_port -d $mapbender_database_name -f ${installation_folder}mapbender/resources/db/pgsql/UTF-8/update/update_2.8_pgsql_UTF-8.sql | tee -a $installation_log

# Adopt the database to have some standard users and groups 
echo  -e '\n Adopting mapbenders default database to default options. \n'
# There are 2 different sql scripts, that are used as templates, copy them to the installation folder and delete them afterwards

# first sql script
cp -a ${installation_folder}mapbender/resources/db/pgsql/UTF-8/geoportal_database_adoption_1.sql ${installation_folder}
# Exchange values in the placeholders
sed -i "s/\${mapbender_guest_user_id}/${mapbender_guest_user_id}/g" ${installation_folder}geoportal_database_adoption_1.sql
sed -i "s/\${mapbender_subadmin_default_user_id}/${mapbender_subadmin_default_user_id}/g" ${installation_folder}geoportal_database_adoption_1.sql
sed -i "s/\${mapbender_subadmin_group_id}/${mapbender_subadmin_group_id}/g" ${installation_folder}geoportal_database_adoption_1.sql
sed -i "s/\${mapbender_subadmin_default_group_id}/${mapbender_subadmin_default_group_id}/g" ${installation_folder}geoportal_database_adoption_1.sql
sed -i "s/\${mapbender_guest_group_id}/${mapbender_guest_group_id}/g" ${installation_folder}geoportal_database_adoption_1.sql
sed -i "s/\${default_gui_name}/${default_gui_name}/g" ${installation_folder}geoportal_database_adoption_1.sql
#sed -i "s/\${extended_search_default_gui_name}/${extended_search_default_gui_name}/g" ${installation_folder}geoportal_database_adoption_1.sql

#second sql script
cp -a ${installation_folder}mapbender/resources/db/pgsql/UTF-8/geoportal_database_adoption_2.sql ${installation_folder}geoportal_database_adoption_2.sql
sed -i "s/\${mapbender_subadmin_default_user_id}/${mapbender_subadmin_default_user_id}/g" ${installation_folder}geoportal_database_adoption_2.sql
sed -i "s/\${mapbender_subadmin_group_id}/${mapbender_subadmin_group_id}/g" ${installation_folder}geoportal_database_adoption_2.sql
sed -i "s/\${mapbender_guest_group_id}/${mapbender_guest_group_id}/g" ${installation_folder}geoportal_database_adoption_2.sql
sed -i "s/\${default_gui_name}/${default_gui_name}/g" ${installation_folder}geoportal_database_adoption_2.sql
sed -i "s/\${extended_search_default_gui_name}/${extended_search_default_gui_name}/g" ${installation_folder}geoportal_database_adoption_2.sql
sed -i "s/\${mapbender_database_user}/${mapbender_database_user}/g" ${installation_folder}geoportal_database_adoption_2.sql
sed -i "s/\$mapbender_database_user/$mapbender_database_user/g" ${installation_folder}geoportal_database_adoption_2.sql

# before doing this, the mapbender database user has to be the owner of mb_user table!

su - postgres -c "psql -q -p $mapbender_database_port -d $mapbender_database_name -c 'ALTER TABLE mb_user OWNER TO $mapbender_database_user'" | tee -a $installation_log

sudo -u postgres psql -q -p $mapbender_database_port -d $mapbender_database_name -f ${installation_folder}geoportal_database_adoption_1.sql | tee -a $installation_log

# recreate the default guis via psql - first copy the templates to the installation folder
cp ${installation_folder}mapbender/resources/db/gui_Geoportal-RLP.sql ${installation_folder}gui_${default_gui_name}.sql
cp ${installation_folder}mapbender/resources/db/gui_Geoportal-RLP_2019.sql ${installation_folder}gui_${default_gui_name}_2019.sql

# exchange all occurences of old default gui name in sql
sed -i "s/Geoportal-RLP/${default_gui_name}/g" ${installation_folder}gui_${default_gui_name}.sql
sed -i "s/Geoportal-RLP_2019/${default_gui_name}_2019/g" ${installation_folder}gui_${default_gui_name}_2019.sql

# recreate the guis via psql - default gui definition is in installation folder!
sudo -u postgres psql -q -p $mapbender_database_port -d $mapbender_database_name -f ${installation_folder}gui_${default_gui_name}.sql | tee -a $installation_log
sudo -u postgres psql -q -p $mapbender_database_port -d $mapbender_database_name -f ${installation_folder}gui_${default_gui_name}_2019.sql | tee -a $installation_log

 # fix invocation of javascript functions for digitize module
sudo -u postgres psql -q -p $mapbender_database_port -d $mapbender_database_name -c "UPDATE gui_element SET e_pos = '3' where e_id = 'kml' AND fkey_gui_id = '${default_gui_name}'" | tee -a $installation_log

# install the special admin guis
sudo -u postgres PGOPTIONS='--client-min-messages=warning' psql -q -p $mapbender_database_port -d $mapbender_database_name -f ${installation_folder}mapbender/resources/db/gui_Owsproxy_csv.sql | tee -a $installation_log
sudo -u postgres PGOPTIONS='--client-min-messages=warning' psql -q -p $mapbender_database_port -d $mapbender_database_name -f ${installation_folder}mapbender/resources/db/gui_admin_wms_metadata.sql | tee -a $installation_log
sudo -u postgres PGOPTIONS='--client-min-messages=warning' psql -q -p $mapbender_database_port -d $mapbender_database_name -f ${installation_folder}mapbender/resources/db/gui_admin_wfs_metadata.sql | tee -a $installation_log
sudo -u postgres PGOPTIONS='--client-min-messages=warning' psql -q -p $mapbender_database_port -d $mapbender_database_name -f ${installation_folder}mapbender/resources/db/gui_admin_wmc_metadata.sql | tee -a $installation_log
sudo -u postgres PGOPTIONS='--client-min-messages=warning' psql -q -p $mapbender_database_port -d $mapbender_database_name -f ${installation_folder}mapbender/resources/db/gui_admin_metadata.sql | tee -a $installation_log
sudo -u postgres PGOPTIONS='--client-min-messages=warning' psql -q -p $mapbender_database_port -d $mapbender_database_name -f ${installation_folder}mapbender/resources/db/gui_admin_ows_scheduler.sql | tee -a $installation_log
sudo -u postgres PGOPTIONS='--client-min-messages=warning' psql -q -p $mapbender_database_port -d $mapbender_database_name -f ${installation_folder}mapbender/resources/db/gui_PortalAdmin_DE.sql | tee -a $installation_log
sudo -u postgres PGOPTIONS='--client-min-messages=warning' psql -q -p $mapbender_database_port -d $mapbender_database_name -f ${installation_folder}mapbender/resources/db/gui_Administration_DE.sql | tee -a $installation_log
sudo -u postgres PGOPTIONS='--client-min-messages=warning' psql -q -p $mapbender_database_port -d $mapbender_database_name -f ${installation_folder}mapbender/resources/db/gui_admin_application_metadata.sql | tee -a $installation_log

# add rights for new gui variant
sudo -u postgres psql -d mapbender -c "INSERT INTO gui_mb_user (fkey_gui_id, fkey_mb_user_id, mb_user_type) values ('${default_gui_name}_2019',1,'owner')"
sudo -u postgres psql -d mapbender -c "INSERT INTO gui_mb_group (fkey_gui_id, fkey_mb_group_id) values ('${default_gui_name}_2019',$mapbender_guest_group_id)"
sudo -u postgres psql -d mapbender -c "INSERT INTO gui_gui_category (fkey_gui_id, fkey_gui_category_id) values ('${default_gui_name}_2019',2);"

# invoke the second sql script
sudo -u postgres psql -q -d $mapbender_database_name -f ${installation_folder}geoportal_database_adoption_2.sql | tee -a $installation_log
  
# add privilegs for mapbender database_user
su - postgres -c "psql -q -p $mapbender_database_port -d $mapbender_database_name -c 'GRANT USAGE ON SCHEMA mapbender TO $mapbender_database_user'" | tee -a $installation_log
su - postgres -c "psql -q -p $mapbender_database_port -d $mapbender_database_name -c 'GRANT USAGE ON SCHEMA public TO $mapbender_database_user'" | tee -a $installation_log
su - postgres -c "psql -q -p $mapbender_database_port -d $mapbender_database_name -c 'GRANT SELECT, INSERT, UPDATE, DELETE ON ALL TABLES IN SCHEMA mapbender TO $mapbender_database_user'" | tee -a $installation_log
su - postgres -c "psql -q -p $mapbender_database_port -d $mapbender_database_name -c 'GRANT SELECT, INSERT, UPDATE, DELETE ON ALL TABLES IN SCHEMA public TO $mapbender_database_user'" | tee -a $installation_log
su - postgres -c "psql -q -p $mapbender_database_port -d $mapbender_database_name -c 'GRANT ALL PRIVILEGES ON DATABASE $mapbender_database_name TO $mapbender_database_user'" | tee -a $installation_log
#su - postgres -c "psql -q -p $mapbender_database_port -d $mapbender_database_name -c 'GRANT  INSERT, UPDATE, DELETE ON DATABASE $mapbender_database_name TO $mapbender_database_user'" | tee -a $installation_log
su - postgres -c "psql -q -p $mapbender_database_port -d $mapbender_database_name -c 'GRANT USAGE, SELECT ON ALL SEQUENCES IN SCHEMA mapbender TO $mapbender_database_user'" | tee -a $installation_log
su - postgres -c "psql -q -p $mapbender_database_port -d $mapbender_database_name -c 'GRANT USAGE, SELECT ON ALL SEQUENCES IN SCHEMA public TO $mapbender_database_user'" | tee -a $installation_log
su - postgres -c "psql -q -p $mapbender_database_port -d $mapbender_database_name -c 'GRANT ALL PRIVILEGES ON ALL TABLES IN SCHEMA mapbender TO $mapbender_database_user'" | tee -a $installation_log
su - postgres -c "psql -q -p $mapbender_database_port -d $mapbender_database_name -c 'GRANT ALL PRIVILEGES ON ALL TABLES IN SCHEMA public TO $mapbender_database_user'" | tee -a $installation_log
su - postgres -c "psql -q -p $mapbender_database_port -d $mapbender_database_name -c 'GRANT CREATE ON DATABASE $mapbender_database_name TO $mapbender_database_user'" | tee -a $installation_log
su - postgres -c "psql -q -p $mapbender_database_port -d $mapbender_database_name -c 'GRANT CREATE ON SCHEMA mapbender TO $mapbender_database_user'" | tee -a $installation_log

# recreate sequences
sudo -u postgres psql -q -p $mapbender_database_port -d $mapbender_database_name -f ${installation_folder}mapbender/resources/db/pgsql/pgsql_serial_set_sequences_2.7.sql

# copy chown script to installation_folder   
cp -a ${installation_folder}mapbender/resources/db/pgsql/UTF-8/change_owner.sql ${installation_folder}change_owner.sql

# adopt database user name
sed -i "s/mapbenderdbuser/$mapbender_database_user/g" ${installation_folder}change_owner.sql

# change owner to database user
sudo -u postgres psql -q -p $mapbender_database_port -d $mapbender_database_name -f ${installation_folder}change_owner.sql | tee -a $installation_log

# run these two update scripts again to fix Administration_GUI, 2.7.4 to 2.8 destroys search_wms_view, change mapbender_subadmin_group_id - TODO needed anymore?
sed -i "s/s.fkey_mb_group_id = 36/s.fkey_mb_group_id = ${mapbender_subadmin_group_id}/g" ${installation_folder}mapbender/resources/db/pgsql/UTF-8/update/update_2.7.4_to_2.8_pgsql_UTF-8.sql

sudo -u postgres PGOPTIONS='--client-min-messages=warning' psql -q -p $mapbender_database_port -d $mapbender_database_name -f ${installation_folder}mapbender/resources/db/pgsql/UTF-8/update/update_2.7.4_to_2.8_pgsql_UTF-8.sql | tee -a $installation_log

sudo -u postgres PGOPTIONS='--client-min-messages=warning' psql -q -p $mapbender_database_port -d $mapbender_database_name -f ${installation_folder}mapbender/resources/db/pgsql/UTF-8/update/update_2.8_pgsql_UTF-8.sql | tee -a $installation_log


# second change owner
sudo -u postgres psql -q -p $mapbender_database_port -d $mapbender_database_name -f ${installation_folder}change_owner.sql | tee -a $installation_log

echo -e "\n ${green}Successfully installed Mapbender Database!${reset} \n" | tee -a $installation_log


