#!/bin/sh
set -eu

# EN: Run only inside the disposable systemd fixture, never against a host installation.
#
# HR: Pokretati samo unutar privremenog systemd testa, nikada nad instalacijom poslužitelja.
test -f /.dockerenv
test -f /usr/local/share/simbioza-fixture/secured-fpm
fixture_source=${1:-/source/app}
case "$fixture_source" in /source/app|/candidate/app) ;; *) exit 64 ;; esac
test ! -e /srv/simbioza
install -d -o fixture-admin -g fixture-admin -m 0755 /srv/simbioza
rsync -a "$fixture_source/" /srv/simbioza/
chown -R fixture-admin:fixture-admin /srv/simbioza
runuser -u fixture-admin -- sh -c 'cd /srv/simbioza && composer update --no-interaction --prefer-dist --with-all-dependencies --optimize-autoloader && composer check-platform-reqs'
runuser -u fixture-admin -- mkdir -p /srv/simbioza/data/cache /srv/simbioza/data/logs /srv/simbioza/data/sessions /srv/simbioza/data/setup-requests /srv/simbioza/data/tmp
php /srv/simbioza/scripts/configure_fpm_setup.php --install --app-root=/srv/simbioza --maintainer=fixture-admin
runuser -u fpm-simbioza -- php /srv/simbioza/scripts/configure_fpm_setup.php --check --app-root=/srv/simbioza --maintainer=fixture-admin
php /srv/simbioza/tests/Fixtures/secured_fpm/configure_apache.php
a2enmod rewrite proxy proxy_fcgi
apache2ctl configtest
systemctl restart apache2
curl --fail --silent --output /dev/null http://127.0.0.1/simbioza/
printf '%s\n' 'PASS fresh secured-FPM provisioning and real helper checks.'
