# Database

This document explains how to use the database features of the HeartPhrame
framework: configuration, the `Database` API, models, migrations, and the
`MigrateCommand`. It also documents important driver quirks, concurrency notes,
and examples.

Files referenced in this document live in the framework at:

- `HeartPhrame\Database\Database` — low-level PDO wrapper and utility helpers
- `HeartPhrame\Database\Model` — active-record style base model
- `HeartPhrame\Database\ModelFactory` — model factory/helper for building
and finding models
- `HeartPhrame\Database\MigrationInterface` — migration contract (up)
- `HeartPhrame\Command\MigrateCommand` — CLI migration runner

If you need to read the implementation while following this doc, open those 
files.

## 1. Configuration

Database connections are configured via the framework `Config` system. Each
connection is defined under the `database.connections` key. The framework
supports at least typical PHP PDO SQL drivers (SQLite, MySQL, PostgreSQL).
Example database config file structure:

```php
// config/database.php (example)
return [
    'connections' => [
        'default' => [
            'driver' => 'sqlite',
            'database' => ':memory:', // or '/path/to/db.sqlite'
            // optional keys for mysql/postgres:
            // 'host' => '127.0.0.1',
            // 'port' => 3306,
            // 'database' => 'dbname',
            // 'username' => 'user',
            // 'password' => 'secret',
            // 'charset' => 'utf8mb4',
            // 'options' => [ PDO options array ],
        ],
    ],
];
```

Notes:
- The `Database` class uses PDO under the hood and config options influence
DSN and PDO options.
- `sqlite` connections use `sqlite:{database}` DSN (the database value can be
`:memory:` for tests).

## 2. The `Database` API (quick reference)

`HeartPhrame\Database\Database` is a small wrapper over PDO and exposes the
methods below. It centralizes error handling and connection management.

Important methods:

- `getConnection(string $name = 'default'): PDO` — returns a PDO instance
(connects lazily).
- `query(string $sql, array $params = [], string $connectionName = 'default'):
PDOStatement` — prepare + execute and returns the PDOStatement.
- `execute(string $sql, array $params = [], string $connectionName = 'default'):
int` — executes a write statement and returns `rowCount()` (integer).
Note: some drivers return 0 for `INSERT` even if successful. See the Model
section for how the framework deals with that.
- `fetchAll(string $sql, array $params = [], string $connectionName =
'default'): array` — returns all rows.
- `fetchOne(string $sql, array $params = [], string $connectionName =
'default'): ?array` — returns single row or `null`.
- `lastInsertId(string $connectionName = 'default'): string|false` — returns
last inserted ID (string) or `false`. Often used by models after insert.
- `quoteIdentifier(string $identifier, string $connectionName = 'default'):
string` — quotes table/column names (backticks for MySQL, double quotes otherwise).
- Transaction helpers: `beginTransaction()`, `commit()`, `rollBack()`
(accepts connection name).

Driver quirks:
- `PDO::rowCount()` can behave differently across drivers for
`INSERT`/`UPDATE`/`DELETE`. The Model layer treats successful execution
(no exception) as success and exposes the driver-provided rowCount via
`Model::getAffectedRows()` when callers need it.

## 3. Models (`Model` and `ModelFactory`)

The framework includes a lightweight active-record `Model` base class and a
`ModelFactory` helper.

Paths:
- `HeartPhrame\Database\Model`
- `HeartPhrame\Database\ModelFactory`

Key concepts and API:

### Constructing models
- Use `ModelFactory::build()` to create model instances with framework
dependencies wired (`Database` and `Helper`). Example:

```php
$modelFactory = new \HeartPhrame\Database\ModelFactory($database, $helper);
$user = $modelFactory->build(User::class, ['name' => 'Alice', 'email' => 'a@example.com']);
```

- The `Model` constructor accepts an `$attributes` array and a `$trusted` flag.
If `$trusted` is true the attributes are set verbatim (used when hydrating from
DB). If false, attributes may be filtered by the model's `$fillable` list
for mass-assignment protection.

### Model properties to know
- `protected array $fillable = []` — whitelisted attributes for mass-assignment.
Empty `$fillable` means "no mass-assignment." When `$trusted` is false 
(default), only keys in `$fillable` are set on the model.
- `protected bool $timestamps = false` — when true, `created_at` and
`updated_at` columns are automatically set/updated on insert/save (make sure
the table has those columns).
- `protected string $primaryKey = 'id'` — name of the primary key column.
- `protected string $table` — table name. If not set explicitly,
`deriveTableName()` uses the short class name lowercased and adds an
`s` (e.g., `User` -> `users`). 

### Core methods
- `save(): bool` — insert or update the model. Returns `true` when the SQL
statement ran without throwing an exception.
- `delete(): bool` — deletes the record; returns `true` when the delete
statement executed (even if affected rows are 0 on some drivers).
- `getDirty(): array` — returns attributes changed since the model was
loaded or last saved.
- `wasChanged(): bool` — whether any attributes were changed.
- `getAffectedRows(): ?int` — returns the last reported affected rows from
the most recent write operation (INSERT/UPDATE/DELETE) executed by this model,
or `null` if none ran.
- `toArray()` / `jsonSerialize()` — export attributes.
- `validate()` - by default does nothing, but can be overridden to perform
custom validation on model instantiation.

### Using the model for CRUD
- 
- Create model definition:

```php
class User extends \HeartPhrame\Database\Model
{
    protected string $table = 'users';

    protected array $fillable = ['name', 'email'];

    protected bool $timestamps = true;

    public function id(): ?int
    {
        return is_null($id = $this->attributes['id'] ?? null) ? null : $this->helper->type()->ensureInt($id);
    }

    public function name(?string $name = null): string
    {
        if (is_string($name)) {
            $this->attributes['name'] = $name;
        }

        if (!is_string($name = $this->attributes['name'] ?? null)) {
            throw new \RuntimeException('Name not set.');
        }

        return $name;
    }

    public function email(): string
    {
        if (!is_string($email = $this->attributes['email'] ?? null)) {
            throw new \RuntimeException('Email not set.');
        }

        return $email;
    }
}
```

- Create and save:

```php

$user = $modelFactory->build(User::class, ['name' => 'John', 'email' => 'j@example.com']);
$user->save();
// ID is populated from lastInsertId()
$id = $user->id();
```

- Find:

```php
$found = $modelFactory->find(User::class, $id); // returns model|null
$found = $modelFactory->findOrFail(User::class, $id); // throws ModelNotFoundException if not found
```

- Update:

```php
// Active-record style:
$found->name = 'New name';
// Better to introduce dedicated setter methods:
$found->name('New name');
if ($found->wasChanged()) {
    $found->save();
}
```

- Delete:

```php
$found->delete();
```

Notes on `save()` and affected rows:
- `save()` returns `true` whenever the underlying SQL executed without
throwing. To inspect how many rows the DB driver reported as affected, call
`$model->getAffectedRows()` immediately after save/update/delete. Some drivers
(like certain SQLite builds) report `rowCount()` as 0 after insert even though
the operation succeeded — the framework preserves lastInsertId via
`Database::lastInsertId()` and sets the primary key on the model.

Mass-assignment security:
- Always set `$fillable` for models that accept user-supplied attributes
(forms, requests) to avoid accidental mass-assignment vulnerabilities. The
`fill()` method only assigns keys listed in `$fillable`.


## 4. ModelFactory

`ModelFactory` helps build and hydrate models, and provides convenience methods:

- `build(string $className, array $attributes = [], bool $trusted = false):
Model` — constructs a model with dependencies.
- `find(string $className, mixed $id): ?Model` — queries a single row by
primary key and returns a hydrated model or null.
- `findOrFail(string $className, mixed $id): Model` — same as `find`
but throws `ModelNotFoundException` if not found.
- `all(string $className): array` — returns all rows as model instances.

Notes:
- `find()` validates the provided class name is a `Model` subclass before
attempting to build it. If you pass a non-Model class, a
`ModelNotFoundException` is thrown.
- `find()`/`all()` use `quoteIdentifier()` from `Database` to safely
quote table and column names.


## 5. Migrations

Migrations follow a minimal contract: a migration file should return an 
instance of `MigrationInterface` which provides `up(Database $db)` method.

Example migration file (`database/migrations/20260101_create_foo.php`):

```php
<?php

declare(strict_types=1);

return new class implements \HeartPhrame\Database\MigrationInterface {
    public function up(\HeartPhrame\Database\Database $db): void
    {
        $db->execute("CREATE TABLE foo (id INTEGER PRIMARY KEY AUTOINCREMENT)");
    }
};
```

Important rules and expectations:
- The migration file must `return` an instance of `MigrationInterface`
- Filenames should be sortable (timestamp or numeric prefix). The migration
runner sorts files (`SORT_NATURAL`) before running to ensure deterministic
order.


## 6. `MigrateCommand` (CLI runner)

Path: `HeartPhrame\Command\MigrateCommand`.

Behavior summary:
- Ensures a `migrations` table exists. The table uses a `migration` column
which is `UNIQUE` to avoid duplicate records when two processes run migrations
concurrently.
- Scans the configured `database/migrations` directory, sorts files
deterministically, filters out migrations already recorded in the `migrations`
table, and runs the rest in order.
- For each migration file:
  - Validates the file realpath is inside the configured `migrations` directory.
  - `require`s the file and expects it to return a `MigrationInterface`
  instance.
  - Runs `up($db)` inside a transaction.
  - Inserts a record into `migrations(migration)` on success.
  - If the insert fails with a unique-constraint violation (concurrent run),
  the runner treats this as benign: it logs and commits the transaction
  (keeping DB changes), avoiding a hard failure.
  - If `up()` throws, the transaction is rolled back and the exception
  is rethrown with context.

Usage (from project root):

```bash
php vendor/bin/hph migrate:up
# or if you call the command programmatically:
$command = new \HeartPhrame\Command\MigrateCommand($database, $config, $helper);
$exitCode = $command->up();
```

Concurrency and idempotence:
- The migrations table has a UNIQUE constraint on `migration`. If two processes
attempt to run the same migration concurrently, one insert will succeed, and
the other will get a constraint error; the runner detects this and treats it
as a concurrent benign case.
- This makes `MigrateCommand::up()` safe to call in parallel during
deployments in most common scenarios.


## 10. Best practices and recommendations

- Always set `$fillable` for models that accept user input. This prevents
accidental mass-assignment.
- Keep migration filenames prefixed with a sortable timestamp or version number
so ordering is deterministic.
- Use transactions in migration `up()` methods if the migration touches
multiple tables or must be atomic.


## 11. Example checklist for adding a new model + migration

1. Create migration file `database/migrations/20260101_create_things.php` that
`return`s a `MigrationInterface` instance implementing `up()`.
2. Run `MigrateCommand` to apply the migration (via CLI or programmatic call).
3. Create a Model (e.g., `src/Model/Thing.php`) extending
`\HeartPhrame\Database\Model` with `$table`, `$fillable`, and `$timestamps`
as needed.
4. Use `ModelFactory::build()` or `find()`/`findOrFail()` to interact with
the DB.
5. Add unit tests using sqlite `:memory:` to assert the model behavior.
