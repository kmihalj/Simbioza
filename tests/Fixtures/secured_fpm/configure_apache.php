<?php

declare(strict_types=1);

// EN: Test the exact maintained alias/Directory example on a shared, loopback-only fixture vhost.
// Only the disposable fixture's Apache file is written; no real server configuration is read.
//
// HR: Provjerava točan održavani Alias/Directory primjer na zajedničkom testnom vhostu.
// Piše samo Apache datoteku privremenog testa; ne čita stvarne postavke poslužitelja.
if (!is_file('/.dockerenv') || !is_file('/usr/local/share/simbioza-fixture/secured-fpm')) {
    throw new RuntimeException('Disposable fixture required.');
}

$document = file_get_contents('/srv/simbioza/docs/installation_fpm_secured_en.md');
if (!preg_match('~```apache\R(Alias /simbioza "/srv/simbioza/public".*?)```~s', $document, $match)) {
    throw new RuntimeException('The documented shared-vhost example is missing.');
}

$configuration = "<VirtualHost *:80>\nServerName fixture.invalid\nDocumentRoot /var/www/html\nProxyTimeout 900\n"
. $match[1] . "\n</VirtualHost>\n";
file_put_contents('/etc/apache2/sites-available/000-default.conf', $configuration);
