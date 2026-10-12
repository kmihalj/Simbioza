<?php

declare(strict_types=1);

// EN: The host application may add its own providers without changing the
// generic Backup module. Simbioza registers only its application settings here.
//
// HR: Host aplikacija može dodati vlastite providere bez mijenjanja generičkog
// Backup modula. Simbioza ovdje prijavljuje samo svoje aplikacijske postavke.
return ['providers' => ['simbioza.backup.provider.application-config']];
