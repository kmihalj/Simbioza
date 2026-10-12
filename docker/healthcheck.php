<?php

declare(strict_types=1);

// EN: A bounded local HTTP check, without credentials, external endpoints or mutations.
//
// HR: Ograničena lokalna HTTP provjera, bez tajni, vanjskih endpointa ili izmjena.
$socket = @stream_socket_client('tcp://127.0.0.1:8080', $code, $message, 2);
if ($socket === false) {
    exit(1);
}
stream_set_timeout($socket, 2);
fwrite($socket, "GET / HTTP/1.0\r\nHost: localhost\r\nConnection: close\r\n\r\n");
$status = fgets($socket, 256);
fclose($socket);
exit(is_string($status) && preg_match('~\AHTTP/1\.[01] (200|302|303) ~', $status) === 1 ? 0 : 1);
