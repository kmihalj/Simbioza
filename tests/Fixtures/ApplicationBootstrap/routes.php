<?php

declare(strict_types=1);

// HR: Registrira stvarne aplikacijske rute bez pokretanja njihovih kontrolera.
// EN: Registers actual application routes without executing their controllers.
return require dirname(__DIR__, 3) . '/config/routes.php';
