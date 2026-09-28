<?php

declare(strict_types=1);

$instanceHelper = getenv('SIMBIOZA_SETUP_HELPER');
// HR: Root-owned FPM pool može zadati samo pomoćni program svoje instance;
//     bez te oznake ostaje kompatibilan dosadašnji zadani program.
// EN: A root-owned FPM pool may select only its own instance helper;
//     without the marker the legacy default remains unchanged.
$helper = is_string($instanceHelper)
&& preg_match('/\A\/usr\/local\/sbin\/simbioza-setup(?:-[a-z][a-z0-9-]{0,11})?\z/D', $instanceHelper) === 1
? $instanceHelper
: '/usr/local/sbin/simbioza-setup';

return [
    // HR: Web proces nikada ne pokreće Composer izravno. Root-owned helper
    //     prihvaća samo ID provjerenog zahtjeva iz privatnog direktorija.
    // EN: The web process never runs Composer directly. A root-owned helper
    //     accepts only a validated request ID from the private directory.
    'helper' => $helper,
    'request_dir' => __DIR__ . '/../data/setup-requests',
    // HR: Ovu oznaku postavlja isključivo namjenski pool iz upute. Obični FPM
    //     nije dovoljan za privilegirane GUI radnje.
    // EN: Only the documented dedicated pool sets this marker. Generic FPM is
    //     not sufficient for privileged GUI operations.
    'required_pool_marker' => 'SIMBIOZA_SETUP_POOL=1',
    'direct_local_testing' => false,
];
