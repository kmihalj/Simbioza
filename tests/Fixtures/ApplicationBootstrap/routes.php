<?php

declare(strict_types=1);

// EN: Registers actual application routes without executing their controllers.
//
// HR: Registrira stvarne aplikacijske rute bez pokretanja njihovih kontrolera.
return require dirname(__DIR__, 3) . '/config/routes.php';
