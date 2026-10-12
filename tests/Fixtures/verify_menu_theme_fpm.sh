#!/bin/bash
# EN: Verify only the named test pools, including manual edits and read-only rejection.
#
# HR: Provjeri samo imenovane testne poolove, uključujući ručne izmjene i read-only zabranu.
set -euo pipefail
fixture=/srv/simbioza-config-test
test -f "$fixture/run/fpm.pid"
for port in 18081 18082; do
  curl -fsS "http://127.0.0.1:$port/menu-theme-probe.php"
  path="$fixture/normal/config"
  if [ "$port" = 18082 ]; then path="$fixture/secure-override"; fi
  php -r 'file_put_contents($argv[1]."/menu/settings.json", "[{\"id\":\"manual-edit\"}]"); file_put_contents($argv[1]."/theme/settings.json", "{\"enabled\":false}");' "$path"
  curl -fsS "http://127.0.0.1:$port/menu-theme-probe.php?mode=read"
  chmod 0440 "$path/menu/settings.json" "$path/theme/settings.json"
  curl -fsS "http://127.0.0.1:$port/menu-theme-probe.php?mode=readonly"
  chmod 0660 "$path/menu/settings.json" "$path/theme/settings.json"
  # EN: Owned content must remain writable under both normal and secured FPM.
  #
  # HR: Vlasnički sadržaj mora ostati zapisiv pod standardnim i sigurnim FPM-om.
  curl -fsS "http://127.0.0.1:$port/workspace-data-probe.php"
  curl -fsS "http://127.0.0.1:$port/workspace-data-probe.php?mode=read"
  curl -fsS "http://127.0.0.1:$port/backup-api-probe.php"
done
