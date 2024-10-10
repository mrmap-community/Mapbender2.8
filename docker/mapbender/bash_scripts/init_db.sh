#!/bin/bash
sudo -u postgres psql -q  \
"postgresql://$POSTGRES_USER:$POSTGRES_PASSWORD@$POSTGRES_HOST:$POSTGRES_PORT/$POSTGRES_DB" \
-c "CREATE SCHEMA django AUTHORIZATION $POSTGRES_USER"

sudo -u postgres psql -q  \
"postgresql://$POSTGRES_USER:$POSTGRES_PASSWORD@$POSTGRES_HOST:$POSTGRES_PORT/$POSTGRES_DB" \
-c "ALTER DATABASE $POSTGRES_DB SET search_path TO mapbender,public,pg_catalog,topology"

sudo -u postgres PGOPTIONS='--client-min-messages=warning' psql -q \
"postgresql://$POSTGRES_USER:$POSTGRES_PASSWORD@$POSTGRES_HOST:$POSTGRES_PORT/$POSTGRES_DB" \
-f /mapbender/resources/db/pgsql/pgsql_schema_2.5.sql \
-f /mapbender/resources/db/pgsql/pgsql_data_2.5.sql \
-f /mapbender/resources/db/pgsql/pgsql_serial_set_sequences_2.5.sql \
-f /mapbender/resources/db/pgsql/UTF-8/update/update_2.5_to_2.5.1rc1_pgsql_UTF-8.sql \
-f /mapbender/resources/db/pgsql/UTF-8/update/update_2.5.1rc1_to_2.5.1_pgsql_UTF-8.sql \
-f /mapbender/resources/db/pgsql/UTF-8/update/update_2.5.1_to_2.6rc1_pgsql_UTF-8.sql \
-f /mapbender/resources/db/pgsql/UTF-8/update/update_2.6rc1_to_2.6_pgsql_UTF-8.sql \
-f /mapbender/resources/db/pgsql/UTF-8/update/update_2.6_to_2.6.1_pgsql_UTF-8.sql \
-f /mapbender/resources/db/pgsql/UTF-8/update/update_2.6.1_to_2.6.2_pgsql_UTF-8.sql \
-f /mapbender/resources/db/pgsql/UTF-8/update/update_2.6.2_to_2.7rc1_pgsql_UTF-8.sql \
-f /mapbender/resources/db/pgsql/UTF-8/update/update_2.7rc1_to_2.7rc2_pgsql_UTF-8.sql \
-f /mapbender/resources/db/pgsql/UTF-8/update/update_2.7.1_to_2.7.2_pgsql_UTF-8.sql \
-f /mapbender/resources/db/pgsql/UTF-8/update/update_2.7.2_to_2.7.3_pgsql_UTF-8.sql \
-f /mapbender/resources/db/pgsql/UTF-8/update/update_2.7.3_to_2.7.4_pgsql_UTF-8.sql \
-f /mapbender/resources/db/pgsql/UTF-8/update/update_2.8_pgsql_UTF-8.sql

exec "$@"