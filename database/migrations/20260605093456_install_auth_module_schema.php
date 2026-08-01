<?php

declare(strict_types=1);

use AaiEduHr\HeartPhrameModuleAuth\ModuleAuth;
use AaiEduHr\HeartPhrameModuleAuth\Service\AuthSettingsService;
use AaiEduHr\HeartPhrameModuleOrm\Database\Database;
use AaiEduHr\HeartPhrameModuleOrm\Database\Migration\ReversibleMigrationInterface;
use AaiEduHr\HeartPhrameModuleOrm\Database\Schema\Blueprint;

return new class implements ReversibleMigrationInterface {
    /**
     * HR: Kreira cjelovitu početnu auth shemu, bazne provider postavke i jedini
     * početni račun `Administrator`. Privremena lozinka mora se promijeniti pri
     * prvoj prijavi, a migracija ne dodaje druge korisničke ili testne podatke.
     *
     * EN: Creates the complete initial auth schema, base provider settings, and
     * the sole bootstrap `Administrator` account. Its temporary password must be
     * changed on first login, and no other user or test data is inserted.
     */
    public function up(Database $db): void
    {
        $schema = $db->schema();

        if (!$schema->hasTable(ModuleAuth::TABLE_AUTH_USERS)) {
            $schema->create(ModuleAuth::TABLE_AUTH_USERS, static function (Blueprint $table): void {
                $table->charset('utf8mb4');
                $table->collation('utf8mb4_unicode_ci');

                $table->id();
                $table->string('login_identifier', 190)->unique();
                $table->string('password_hash', 255)->nullable();
                $table->boolean('is_admin')->default(0)->index();
                $table->boolean('is_active')->default(1)->index();
                $table->string('auth_source', 64)->default(AuthSettingsService::PROVIDER_LOCAL);
                $table->timestamp('last_login_at')->nullable();
                $table->boolean('must_change_password')->default(0)->index();
                $table->timestamp('force_local_password_reset_at')->nullable();
                $table->timestamps();
            });
        }

        if (!$schema->hasTable(ModuleAuth::TABLE_AUTH_USER_ATTRIBUTE_FIELDS)) {
            $schema->create(ModuleAuth::TABLE_AUTH_USER_ATTRIBUTE_FIELDS, static function (Blueprint $table): void {
                $table->charset('utf8mb4');
                $table->collation('utf8mb4_unicode_ci');

                $table->string('field_key', 64)->primary();
                $table->text('labels_json')->nullable();
                $table->string('field_type', 32)->default('text')->index();
                $table->boolean('is_required')->default(0)->index();
                $table->boolean('show_on_registration')->default(1)->index();
                $table->boolean('show_on_profile')->default(1)->index();
                $table->boolean('is_enabled')->default(1)->index();
                $table->integer('sort_order')->default(0)->index();
                $table->timestamps();
            });
        }

        if (!$schema->hasTable(ModuleAuth::TABLE_AUTH_USER_ATTRIBUTE_VALUES)) {
            $schema->create(ModuleAuth::TABLE_AUTH_USER_ATTRIBUTE_VALUES, static function (Blueprint $table): void {
                $table->charset('utf8mb4');
                $table->collation('utf8mb4_unicode_ci');

                $table->bigInteger('user_id')->unsigned();
                $table->string('field_key', 64);
                $table->text('value_text')->nullable();
                // HR: `value_text` čuva primarnu vrijednost radi brzog prikaza i
                // postojećih pretraga, a `value_json` čuva cijelu listu kada
                // provider vrati višestruke vrijednosti (npr. email + aliasi).
                //
                // EN: `value_text` stores the primary value for quick display and
                // existing searches, while `value_json` stores the full list when
                // a provider returns multiple values (for example email + aliases).
                $table->text('value_json')->nullable();
                $table->timestamps();

                $table->primary(['user_id', 'field_key']);
                $table->index('field_key');
            });
        }

        if (!$schema->hasTable(ModuleAuth::TABLE_AUTH_PROVIDER_SETTINGS)) {
            $schema->create(ModuleAuth::TABLE_AUTH_PROVIDER_SETTINGS, static function (Blueprint $table): void {
                $table->charset('utf8mb4');
                $table->collation('utf8mb4_unicode_ci');

                $table->string('provider', 32)->primary();
                $table->boolean('enabled')->default(0)->index();
                $table->text('config_json')->nullable();
                $table->timestamp('updated_at')->nullable();
            });
        }

        if (!$schema->hasTable(ModuleAuth::TABLE_AUTH_PROVIDER_PROFILES)) {
            $schema->create(ModuleAuth::TABLE_AUTH_PROVIDER_PROFILES, static function (Blueprint $table): void {
                $table->charset('utf8mb4');
                $table->collation('utf8mb4_unicode_ci');

                $table->id();
                $table->string('provider', 32)->index();
                $table->string('profile_key', 64)->unique();
                $table->string('profile_name', 190);
                $table->bigInteger('root_profile_id')->unsigned()->nullable()->index();
                $table->boolean('is_enabled')->default(1)->index();
                $table->boolean('is_verified')->default(0)->index();
                $table->boolean('allow_auto_create')->default(0)->index();
                $table->text('config_json')->nullable();
                $table->text('mapping_json')->nullable();
                $table->text('last_test_attribute_names_json')->nullable();
                $table->text('last_test_error')->nullable();
                $table->timestamp('last_tested_at')->nullable();
                $table->timestamps();

                $table->index(['provider', 'is_enabled']);
                $table->index(['provider', 'is_verified']);
                $table->index(['provider', 'root_profile_id']);
            });
        }

        if (!$schema->hasTable(ModuleAuth::TABLE_AUTH_SYSTEM_SETTINGS)) {
            $schema->create(ModuleAuth::TABLE_AUTH_SYSTEM_SETTINGS, static function (Blueprint $table): void {
                $table->charset('utf8mb4');
                $table->collation('utf8mb4_unicode_ci');

                $table->string('setting_key', 64)->primary();
                $table->text('setting_value')->nullable();
                $table->timestamp('updated_at')->nullable();
            });
        }

        if (!$schema->hasTable(ModuleAuth::TABLE_AUTH_USER_PROVIDER_ACCESS)) {
            $schema->create(ModuleAuth::TABLE_AUTH_USER_PROVIDER_ACCESS, static function (Blueprint $table): void {
                $table->charset('utf8mb4');
                $table->collation('utf8mb4_unicode_ci');

                $table->bigInteger('user_id')->unsigned();
                $table->string('provider', 32);
                $table->boolean('enabled')->default(1)->index();
                $table->timestamps();

                $table->primary(['user_id', 'provider']);
                $table->index('provider');
            });
        }

        if (!$schema->hasTable(ModuleAuth::TABLE_AUTH_USER_IDENTITIES)) {
            $schema->create(ModuleAuth::TABLE_AUTH_USER_IDENTITIES, static function (Blueprint $table): void {
                $table->charset('utf8mb4');
                $table->collation('utf8mb4_unicode_ci');

                $table->id();
                $table->bigInteger('user_id')->unsigned()->index();
                $table->bigInteger('provider_profile_id')->unsigned()->index();
                $table->string('subject_identifier', 255);
                $table->string('login_identifier', 190)->nullable();
                $table->string('email', 190)->nullable();
                $table->text('email_aliases_json')->nullable();
                $table->boolean('email_verified')->default(0)->index();
                $table->timestamps();

                $table->unique(
                    ['provider_profile_id', 'subject_identifier'],
                    'auth_ui_profile_subject_uq',
                );
                $table->index(['user_id', 'provider_profile_id']);
            });
        }

        if (!$schema->hasTable(ModuleAuth::TABLE_AUTH_GROUPS)) {
            $schema->create(ModuleAuth::TABLE_AUTH_GROUPS, static function (Blueprint $table): void {
                $table->charset('utf8mb4');
                $table->collation('utf8mb4_unicode_ci');

                $table->id();
                $table->string('group_key', 64)->unique();
                $table->string('group_name', 190);
                $table->boolean('is_system')->default(0)->index();
                $table->boolean('is_enabled')->default(1)->index();
                $table->integer('sort_order')->default(0)->index();
                $table->timestamps();
            });
        }

        if (!$schema->hasTable(ModuleAuth::TABLE_AUTH_USER_GROUPS)) {
            $schema->create(ModuleAuth::TABLE_AUTH_USER_GROUPS, static function (Blueprint $table): void {
                $table->charset('utf8mb4');
                $table->collation('utf8mb4_unicode_ci');

                $table->id();
                $table->bigInteger('user_id')->unsigned()->index();
                $table->bigInteger('group_id')->unsigned()->index();
                // HR: `source` razlikuje ručno dodijeljene grupe, automatske
                // grupe iz provider atributa i administratorska izuzeća
                // (`excluded`). Time automatski sync ne briše ručne odluke niti
                // vraća grupu koju je admin svjesno uklonio.
                //
                // EN: `source` separates manually assigned groups, automatic
                // groups derived from provider attributes, and administrator
                // exclusions (`excluded`). Automatic sync therefore does not
                // remove manual choices or re-add a group the admin deliberately
                // removed.
                $table->string('source', 32)->default('manual')->index();
                $table->string('source_field_key', 64)->nullable()->index();
                $table->string('source_provider', 32)->nullable()->index();
                $table->timestamps();

                $table->index(['user_id', 'group_id']);
                $table->index(['source', 'source_provider']);
            });
        }

        if (!$schema->hasTable(ModuleAuth::TABLE_AUTH_GROUP_MAPPING_RULES)) {
            $schema->create(ModuleAuth::TABLE_AUTH_GROUP_MAPPING_RULES, static function (Blueprint $table): void {
                $table->charset('utf8mb4');
                $table->collation('utf8mb4_unicode_ci');

                $table->id();
                $table->string('field_key', 64)->index();
                $table->bigInteger('group_id')->unsigned()->index();
                $table->text('match_pattern')->nullable();
                $table->boolean('is_enabled')->default(1)->index();
                $table->integer('sort_order')->default(0)->index();
                $table->timestamps();

                $table->index(['field_key', 'group_id']);
            });
        }

        if (!$schema->hasTable(ModuleAuth::TABLE_AUTH_AUDIT_LOGS)) {
            $schema->create(ModuleAuth::TABLE_AUTH_AUDIT_LOGS, static function (Blueprint $table): void {
                $table->charset('utf8mb4');
                $table->collation('utf8mb4_unicode_ci');

                $table->id();
                $table->string('event_key', 120)->index();
                $table->bigInteger('actor_user_id')->unsigned()->nullable()->index();
                $table->bigInteger('target_user_id')->unsigned()->nullable()->index();
                $table->text('context_json')->nullable();
                $table->timestamp('created_at')->nullable()->index();
            });
        }

        if (!$schema->hasTable(ModuleAuth::TABLE_AUTH_API_KEYS)) {
            $schema->create(ModuleAuth::TABLE_AUTH_API_KEYS, static function (Blueprint $table): void {
                $table->charset('utf8mb4');
                $table->collation('utf8mb4_unicode_ci');

                $table->id();
                $table->string('public_id', 64)->unique();
                $table->bigInteger('user_id')->unsigned()->index();
                $table->bigInteger('created_by_user_id')->unsigned()->nullable()->index();
                $table->string('name', 190);
                $table->text('description')->nullable();
                $table->string('secret_hash', 255);
                $table->text('scopes_json');
                $table->text('allowed_ips_json')->nullable();
                $table->timestamp('expires_at')->nullable()->index();
                $table->timestamp('last_used_at')->nullable()->index();
                $table->string('last_used_ip', 64)->nullable();
                $table->timestamp('revoked_at')->nullable()->index();
                $table->timestamps();

                $table->index(['user_id', 'revoked_at']);
            });
        }

        $now = date('Y-m-d H:i:s');

        $this->seedProviderSetting($db, AuthSettingsService::PROVIDER_LOCAL, true, [], $now);
        $this->seedProviderSetting($db, AuthSettingsService::PROVIDER_SAML, false, [], $now);
        $this->seedProviderSetting($db, AuthSettingsService::PROVIDER_CAS, false, [], $now);
        $this->seedProviderSetting($db, AuthSettingsService::PROVIDER_OIDC, false, [], $now);
        $this->seedProviderSetting($db, AuthSettingsService::PROVIDER_OAUTH2, false, [], $now);

        $this->seedSystemSetting($db, 'allow_local_admin_breakglass', '1', $now);
        $this->seedSystemSetting($db, 'allow_local_registration', '0', $now);
        $this->seedDefaultUserAttributeFields($db, $now);
        $this->seedAdministratorGroup($db, $now);
        $this->seedAdministratorUser($db, $now);
    }

    /**
     * HR: Briše auth tablice kreirane ovom migracijom.
     *
     * EN: Drops auth tables created by this migration.
     */
    public function down(Database $db): void
    {
        $schema = $db->schema();
        $schema->dropIfExists(ModuleAuth::TABLE_AUTH_API_KEYS);
        $schema->dropIfExists(ModuleAuth::TABLE_AUTH_USER_IDENTITIES);
        $schema->dropIfExists(ModuleAuth::TABLE_AUTH_AUDIT_LOGS);
        $schema->dropIfExists(ModuleAuth::TABLE_AUTH_GROUP_MAPPING_RULES);
        $schema->dropIfExists(ModuleAuth::TABLE_AUTH_USER_GROUPS);
        $schema->dropIfExists(ModuleAuth::TABLE_AUTH_GROUPS);
        $schema->dropIfExists(ModuleAuth::TABLE_AUTH_USER_PROVIDER_ACCESS);
        $schema->dropIfExists(ModuleAuth::TABLE_AUTH_PROVIDER_PROFILES);
        $schema->dropIfExists(ModuleAuth::TABLE_AUTH_PROVIDER_SETTINGS);
        $schema->dropIfExists(ModuleAuth::TABLE_AUTH_SYSTEM_SETTINGS);
        $schema->dropIfExists(ModuleAuth::TABLE_AUTH_USER_ATTRIBUTE_VALUES);
        $schema->dropIfExists(ModuleAuth::TABLE_AUTH_USER_ATTRIBUTE_FIELDS);
        $schema->dropIfExists(ModuleAuth::TABLE_AUTH_USERS);
    }

    /**
     * HR: Upisuje početna korisnička atributna polja za generički model.
     * Auth korisnik u novoj shemi više nema fiksne kolone za ime/email; aplikacija
     * kroz ovu tablicu odlučuje koja polja želi imati i koja su obavezna.
     *
     * EN: Inserts initial user-attribute fields for the generic model.
     * In the new schema, auth user no longer has fixed name/email columns; the
     * application decides through this table which fields it wants and which
     * ones are required.
     */
    private function seedDefaultUserAttributeFields(Database $db, string $now): void
    {
        $defaults = [
            [
                'field_key' => 'display_name',
                'labels' => ['hr' => 'Prikazno ime', 'en' => 'Display name'],
                'field_type' => 'text',
                'is_required' => false,
                'sort_order' => 10,
            ],
            [
                'field_key' => 'email',
                'labels' => ['hr' => 'Email', 'en' => 'Email'],
                'field_type' => 'email',
                'is_required' => false,
                'sort_order' => 20,
            ],
            [
                'field_key' => 'first_name',
                'labels' => ['hr' => 'Ime', 'en' => 'First name'],
                'field_type' => 'text',
                'is_required' => false,
                'sort_order' => 30,
            ],
            [
                'field_key' => 'last_name',
                'labels' => ['hr' => 'Prezime', 'en' => 'Last name'],
                'field_type' => 'text',
                'is_required' => false,
                'sort_order' => 40,
            ],
        ];

        foreach ($defaults as $field) {
            $existing = $db->table(ModuleAuth::TABLE_AUTH_USER_ATTRIBUTE_FIELDS)
                ->where('field_key', '=', $field['field_key'])
                ->first();

            if (is_array($existing)) {
                continue;
            }

            $db->table(ModuleAuth::TABLE_AUTH_USER_ATTRIBUTE_FIELDS)->insert([
                'field_key' => $field['field_key'],
                'labels_json' => json_encode($field['labels'], JSON_THROW_ON_ERROR),
                'field_type' => $field['field_type'],
                'is_required' => $field['is_required'] ? 1 : 0,
                'show_on_registration' => 1,
                'show_on_profile' => 1,
                'is_enabled' => 1,
                'sort_order' => $field['sort_order'],
                'created_at' => $now,
                'updated_at' => $now,
            ]);
        }
    }

    /**
     * HR: Osigurava sistemsku grupu `Administrator` na svježoj instalaciji.
     * Korisnici se ne seedaju kroz modul; članstvo u ovoj grupi održava se kroz
     * postojeći `Admin` checkbox kada se korisnici kasnije kreiraju ili uređuju.
     *
     * EN: Ensures the system `Administrator` group on a fresh installation.
     * The module does not seed users; membership in this group is maintained
     * through the existing `Admin` checkbox when users are created or edited.
     */
    private function seedAdministratorGroup(Database $db, string $now): void
    {
        if (!$db->schema()->hasTable(ModuleAuth::TABLE_AUTH_GROUPS)) {
            return;
        }

        $group = $db->table(ModuleAuth::TABLE_AUTH_GROUPS)
            ->where('group_key', '=', 'administrator')
            ->first();

        if (is_array($group)) {
            $db->table(ModuleAuth::TABLE_AUTH_GROUPS)
                ->where('group_key', '=', 'administrator')
                ->update([
                    'group_name' => 'Administrator',
                    'is_system' => 1,
                    'is_enabled' => 1,
                    'sort_order' => 10,
                    'updated_at' => $now,
                ]);
        } else {
            $db->table(ModuleAuth::TABLE_AUTH_GROUPS)->insert([
                'group_key' => 'administrator',
                'group_name' => 'Administrator',
                'is_system' => 1,
                'is_enabled' => 1,
                'sort_order' => 10,
                'created_at' => $now,
                'updated_at' => $now,
            ]);
        }
    }

    /**
     * HR: Kreira jedini početni lokalni administratorski račun, zapisuje njegovo
     * prikazno ime i povezuje ga sa sistemskom grupom. `must_change_password`
     * prisiljava promjenu privremene lozinke `Admin123!` odmah nakon prve prijave.
     *
     * EN: Creates the sole bootstrap local administrator account, stores its
     * display name, and links it to the system group. `must_change_password`
     * forces replacement of the temporary `Admin123!` password after first login.
     */
    private function seedAdministratorUser(Database $db, string $now): void
    {
        $user = $db->table(ModuleAuth::TABLE_AUTH_USERS)
            ->where('login_identifier', '=', 'Administrator')
            ->first();

        if (!is_array($user)) {
            $db->table(ModuleAuth::TABLE_AUTH_USERS)->insert([
                'login_identifier' => 'Administrator',
                'password_hash' => password_hash('Admin123!', PASSWORD_DEFAULT),
                'is_admin' => 1,
                'is_active' => 1,
                'auth_source' => AuthSettingsService::PROVIDER_LOCAL,
                'must_change_password' => 1,
                'created_at' => $now,
                'updated_at' => $now,
            ]);

            $user = $db->table(ModuleAuth::TABLE_AUTH_USERS)
                ->where('login_identifier', '=', 'Administrator')
                ->first();
        }

        if (!is_array($user)) {
            throw new RuntimeException('Failed to create the bootstrap Administrator account.');
        }

        $userId = (int)($user['id'] ?? 0);
        $group = $db->table(ModuleAuth::TABLE_AUTH_GROUPS)
            ->where('group_key', '=', 'administrator')
            ->first();

        if ($userId < 1 || !is_array($group) || (int)($group['id'] ?? 0) < 1) {
            throw new RuntimeException('Failed to resolve bootstrap Administrator membership.');
        }

        $attribute = $db->table(ModuleAuth::TABLE_AUTH_USER_ATTRIBUTE_VALUES)
            ->where('user_id', '=', $userId)
            ->where('field_key', '=', 'display_name')
            ->first();

        if (!is_array($attribute)) {
            $db->table(ModuleAuth::TABLE_AUTH_USER_ATTRIBUTE_VALUES)->insert([
                'user_id' => $userId,
                'field_key' => 'display_name',
                'value_text' => 'Administrator',
                'value_json' => null,
                'created_at' => $now,
                'updated_at' => $now,
            ]);
        }

        $groupId = (int)$group['id'];
        $membership = $db->table(ModuleAuth::TABLE_AUTH_USER_GROUPS)
            ->where('user_id', '=', $userId)
            ->where('group_id', '=', $groupId)
            ->where('source', '=', 'manual')
            ->first();

        if (!is_array($membership)) {
            $db->table(ModuleAuth::TABLE_AUTH_USER_GROUPS)->insert([
                'user_id' => $userId,
                'group_id' => $groupId,
                'source' => 'manual',
                'source_field_key' => null,
                'source_provider' => null,
                'created_at' => $now,
                'updated_at' => $now,
            ]);
        }
    }

    /**
     * HR: Upisuje početnu postavku providera samo ako još ne postoji.
     * Ponovno pokretanje inicijalne migracije ne smije poništiti administratorsku
     * konfiguraciju postojeće aplikacije.
     *
     * EN: Inserts an initial provider setting only when it does not exist yet.
     * Re-running the initial migration must not reset administrator configuration
     * in an existing application.
     *
     * @param array<string,mixed> $config
     */
    private function seedProviderSetting(
        Database $db,
        string $provider,
        bool $enabled,
        array $config,
        string $now,
    ): void {
        $row = $db->table(ModuleAuth::TABLE_AUTH_PROVIDER_SETTINGS)
            ->where('provider', '=', $provider)
            ->first();

        if (is_array($row)) {
            return;
        }

        $db->table(ModuleAuth::TABLE_AUTH_PROVIDER_SETTINGS)
            ->insert([
                'provider' => $provider,
                'enabled' => $enabled ? 1 : 0,
                'config_json' => json_encode($config, JSON_THROW_ON_ERROR),
                'updated_at' => $now,
            ]);
    }

    /**
     * HR: Upisuje početnu sistemsku postavku samo ako još ne postoji.
     *
     * EN: Inserts an initial system setting only when it does not exist yet.
     *
     */
    private function seedSystemSetting(Database $db, string $key, string $value, string $now): void
    {
        $row = $db->table(ModuleAuth::TABLE_AUTH_SYSTEM_SETTINGS)
            ->where('setting_key', '=', $key)
            ->first();

        if (is_array($row)) {
            return;
        }

        $db->table(ModuleAuth::TABLE_AUTH_SYSTEM_SETTINGS)
            ->insert([
                'setting_key' => $key,
                'setting_value' => $value,
                'updated_at' => $now,
            ]);
    }
};
