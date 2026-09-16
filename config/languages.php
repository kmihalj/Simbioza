<?php

declare(strict_types=1);

// HR: Ova datoteka je trajni registar jezika instalacije. Alat `languages add`
//     dodaje nove zapise zajedno s prevedenim nazivima i zastavicom.
// EN: This file is the installation's durable language registry. The
//     `languages add` tool appends new entries with translated names and a flag.
return [
    'hr' => [
        'names' => [
            'hr' => 'Hrvatski',
            'en' => 'Croatian',
        ],
        'native_name' => 'Hrvatski',
        'flag' => 'hr.svg',
    ],
    'en' => [
        'names' => [
            'hr' => 'Engleski',
            'en' => 'English',
        ],
        'native_name' => 'English',
        'flag' => 'en.svg',
    ],
];
