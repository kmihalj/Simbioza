#!/bin/sh
set -eu

# EN: Verify the real wizard, finalization, namespace and helper on the disposable fixture only.
#
# HR: Provjerava stvarni čarobnjak, finalizaciju, izolaciju i helper samo na privremenoj instalaciji.
test -f /.dockerenv
test -f /usr/local/share/simbioza-fixture/secured-fpm
runuser -u fpm-simbioza -- php /srv/simbioza/tests/Fixtures/secured_fpm/verify_wizard.php
php /srv/simbioza/scripts/configure_fpm_setup.php --finalize --app-root=/srv/simbioza --maintainer=fixture-admin
runuser -u fixture-admin -- php /srv/simbioza/scripts/configure_fpm_setup.php --check --app-root=/srv/simbioza --maintainer=fixture-admin
php /srv/simbioza/tests/Fixtures/secured_fpm/verify_finalizer.php
cp /srv/simbioza/tests/Fixtures/secured_fpm/probe.php /srv/simbioza/public/__fixture_probe.php
chmod 0644 /srv/simbioza/public/__fixture_probe.php
curl --fail --silent http://127.0.0.1/simbioza/__fixture_probe.php > /tmp/secured-fpm-report.json
php -r '$r=json_decode(file_get_contents("/tmp/secured-fpm-report.json"),true,512,JSON_THROW_ON_ERROR); foreach(["is_fpm","helper_ready","package_changes_allowed","application_updates_allowed"] as $key){if(($r[$key]??false)!==true){fwrite(STDERR,json_encode($r,JSON_PRETTY_PRINT));throw new RuntimeException("Secured FPM diagnostic failed: ".$key);}} echo "PASS actual FPM namespace, helper, CLI runtime, GUI package/update availability and private permissions.\n";'
php /srv/simbioza/tests/Fixtures/secured_fpm/verify_failure.php
php /srv/simbioza/tests/Fixtures/secured_fpm/verify_language_updates.php
test ! -w /srv/simbioza/.git
