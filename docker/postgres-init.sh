#!/usr/bin/env bash
set -euo pipefail

# EN: The application owns only its database, never a PostgreSQL superuser role.
# psql quotes the secret as a SQL literal; credentials are not printed or passed as arguments.
# This first-volume initialization is not a password-rotation or upgrade script.
#
# HR: Aplikacija je vlasnik samo svoje baze, nikada PostgreSQL superuser uloge.
# psql citira tajnu kao SQL literal; vjerodajnice se ne ispisuju niti prosljeđuju kao argumenti.
# Ovo je inicijalizacija novog volumena, ne rotacija lozinke niti nadogradnja.
SIMBIOZA_APP_PASSWORD="$(cat /run/secrets/database_password)"
export SIMBIOZA_APP_PASSWORD
psql --username "$POSTGRES_USER" --dbname "$POSTGRES_DB" --set ON_ERROR_STOP=1 <<'SQL'
\getenv app_password SIMBIOZA_APP_PASSWORD
CREATE ROLE simbioza LOGIN PASSWORD :'app_password' NOSUPERUSER NOCREATEDB NOCREATEROLE NOREPLICATION;
ALTER DATABASE simbioza OWNER TO simbioza;
REVOKE CREATE ON SCHEMA public FROM PUBLIC;
ALTER SCHEMA public OWNER TO simbioza;
SQL
unset SIMBIOZA_APP_PASSWORD
