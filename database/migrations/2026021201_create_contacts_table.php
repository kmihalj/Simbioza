<?php

declare(strict_types=1);

use HeartPhrame\Database\Database;
use HeartPhrame\Database\MigrationInterface;

return new class implements MigrationInterface
{
    public function up(Database $db): void
    {
        $db->execute("
            CREATE TABLE contacts (
                id INTEGER PRIMARY KEY AUTOINCREMENT,
                name VARCHAR(50) NOT NULL,
                email VARCHAR(50) NOT NULL,
                subject VARCHAR(250) NOT NULL,
                message TEXT NOT NULL,
                created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
                updated_at DATETIME DEFAULT CURRENT_TIMESTAMP
            )
        ");
    }
};
