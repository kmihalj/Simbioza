#!/bin/bash
# EN: Creates only a new synthetic Colima fixture; remove it after verifying the results.
#
# HR: Stvara samo novu sintetičku Colima fixture; ukloniti je nakon provjere rezultata.
set -euo pipefail
fixture=/srv/simbioza-config-test
source_root=/Volumes/Ext/Development/CR/HFClean
test ! -e "$fixture"
mkdir -p "$fixture/normal" "$fixture/secure" "$fixture/run"
for site in normal secure; do
  rsync -a --exclude=/.git --exclude=/config --exclude=/data --exclude=/build --exclude=/node_modules --exclude=/framework.php --exclude='/.env*' "$source_root/" "$fixture/$site/"
done
php "$fixture/normal/tests/Fixtures/prepare_configuration_fpm.php" "$fixture"
chown -R root:www-data "$fixture"
chmod -R o-rwx "$fixture"
chmod -R g+rX "$fixture"
chmod 0750 "$fixture"
for site in normal secure; do
  chown -R www-data:www-data "$fixture/$site/data"
  chmod -R g+rwX "$fixture/$site/data"
done
chown -R www-data:www-data "$fixture/normal/config" "$fixture/secure-override"
chmod -R g+rwX "$fixture/normal/config" "$fixture/secure-override"
chmod -R g-w "$fixture/secure/src" "$fixture/secure/vendor" "$fixture/secure-config"
for path in "$fixture/normal/config" "$fixture/secure-override"; do
  chown root:www-data "$path/menu/settings.json" "$path/theme/settings.json"
  chmod 0660 "$path/menu/settings.json" "$path/theme/settings.json"
done
php-fpm8.3 -y /Volumes/Ext/Development/CR/HFClean/tests/Fixtures/menu_theme_fpm.conf
nginx -p "$fixture/" -c /Volumes/Ext/Development/CR/HFClean/tests/Fixtures/menu_theme_fpm_nginx.conf
bash "$source_root/tests/Fixtures/verify_menu_theme_fpm.sh"
