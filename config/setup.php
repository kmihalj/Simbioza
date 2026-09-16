<?php

declare(strict_types=1);

return [
    // HR: Web proces nikada ne pokreće Composer izravno. Root-owned helper
    //     prihvaća samo ID provjerenog zahtjeva iz privatnog direktorija.
    // EN: The web process never runs Composer directly. A root-owned helper
    //     accepts only a validated request ID from the private directory.
    'helper' => '/usr/local/sbin/simbioza-setup',
    'request_dir' => __DIR__ . '/../data/setup-requests',
    // HR: Ovu oznaku postavlja isključivo namjenski pool iz upute. Obični FPM
    //     nije dovoljan za privilegirane GUI radnje.
    // EN: Only the documented dedicated pool sets this marker. Generic FPM is
    //     not sufficient for privileged GUI operations.
    'required_pool_marker' => 'SIMBIOZA_SETUP_POOL=1',
    'direct_local_testing' => false,
];
