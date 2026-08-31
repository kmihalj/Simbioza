<?php

declare(strict_types=1);

namespace App\Installation;

use Throwable;

/**
 * HR: Upravlja sigurnim višekoračnim web-installerom prije boota glavne aplikacije.
 * EN: Controls the secure multi-step web installer before the main application boots.
 */
final readonly class InstallationWebApplication
{
    private const SESSION_AUTHORIZED = 'authorized';

    private const SESSION_CSRF = 'csrf';

    private const SESSION_DATABASE = 'database';

    private const SESSION_APPLICATION = 'application';

    private const SESSION_ADMINISTRATOR = 'administrator';

    private const SESSION_LOCALE = 'locale';

    private const SESSION_STAGE = 'stage';

    private const SESSION_LAST_ACTIVITY = 'last_activity';

    private const SESSION_TIMEOUT_SECONDS = 1800;

    /** HR: Inicijalizira sigurnosne i instalacijske servise. EN: Initializes security and installation services. */
    public function __construct(
        private InstallationPaths $paths,
        private InstallationAccessToken $accessToken,
        private InstallationRequirements $requirements,
        private InstallationDatabaseTester $databaseTester,
        private InstallationInputValidator $validator,
        private InstallationRunner $runner,
        private InstallationLogger $logger,
    ) {
    }

    /**
     * HR: Obrađuje jedan HTTP zahtjev bez oslanjanja na framework ili globalni output.
     * EN: Handles one HTTP request without relying on framework or global output.
     *
     * @param array<array-key, mixed> $query
     * @param array<array-key, mixed> $post
     * @param array<array-key, mixed> $session
     */
    public function handle(
        string $method,
        string $requestUri,
        string $scriptName,
        array $query,
        array $post,
        array &$session,
    ): InstallationResponse {
        if ($this->paths->isInstalled()) {
            return $this->notFound();
        }

        $installerPath = $this->installerPath($scriptName);
        $requestPath = parse_url($requestUri, PHP_URL_PATH);
        if (!is_string($requestPath) || $requestPath !== $installerPath) {
            return $this->redirect($installerPath);
        }

        $this->expireSession($session);
        if (($session[self::SESSION_AUTHORIZED] ?? false) !== true) {
            $token = $this->scalarString($query['token'] ?? '');
            if (strtoupper($method) !== 'GET' || !$this->accessToken->consume($token)) {
                return $this->notFound();
            }

            $session = [
                self::SESSION_AUTHORIZED => true,
                self::SESSION_CSRF => bin2hex(random_bytes(32)),
                self::SESSION_LOCALE => $this->requestedLocale($query, 'hr'),
                self::SESSION_STAGE => 'requirements',
                self::SESSION_LAST_ACTIVITY => time(),
                'regenerate_id' => true,
            ];

            return $this->redirect($installerPath);
        }

        $session[self::SESSION_LAST_ACTIVITY] = time();
        $storedLocale = $this->scalarString($session[self::SESSION_LOCALE] ?? 'hr');
        $locale = $this->requestedLocale($query, $storedLocale);
        if ($locale !== $storedLocale) {
            $session[self::SESSION_LOCALE] = $locale;
            return $this->redirect($installerPath);
        }

        if (strtoupper($method) === 'GET') {
            return $this->renderStage($session, $installerPath, []);
        }

        if (strtoupper($method) !== 'POST') {
            return $this->response(405, $this->simplePage($locale, $this->text('method_not_allowed', $locale)));
        }

        if (!$this->validCsrf($post, $session)) {
            return $this->response(400, $this->simplePage($locale, $this->text('csrf_error', $locale)));
        }

        $action = $this->scalarString($post['action'] ?? '');
        if ($action === 'back_requirements') {
            $session[self::SESSION_STAGE] = 'requirements';
            return $this->redirect($installerPath);
        }

        if ($action === 'back_database') {
            $session[self::SESSION_STAGE] = 'database';
            return $this->redirect($installerPath);
        }

        if ($action === 'back_application') {
            $session[self::SESSION_STAGE] = 'application';
            unset($session[self::SESSION_ADMINISTRATOR]);
            return $this->redirect($installerPath);
        }

        return match ($action) {
            'continue_requirements' => $this->continueFromRequirements($session, $installerPath),
            'save_database' => $this->saveDatabase($post, $session, $installerPath),
            'save_application' => $this->saveApplication($post, $session, $installerPath),
            'install' => $this->install($session, $installerPath),
            default => $this->response(400, $this->simplePage($locale, $this->text('invalid_action', $locale))),
        };
    }

    /**
     * HR: Nastavlja samo kada svi opći uvjeti prolaze.
     * EN: Continues only when all general checks pass.
     *
     * @param array<array-key, mixed> $session
     */
    private function continueFromRequirements(array &$session, string $installerPath): InstallationResponse
    {
        if (!$this->requirements->passes()) {
            return $this->renderStage($session, $installerPath, ['requirements_failed']);
        }

        $session[self::SESSION_STAGE] = 'database';
        $this->rotateCsrf($session);

        return $this->redirect($installerPath);
    }

    /**
     * HR: Provjerava unos i stvarnu vezu s bazom prije spremanja idućeg koraka.
     * EN: Validates input and the real database connection before saving the next step.
     *
     * @param array<array-key, mixed> $post
     * @param array<array-key, mixed> $session
     */
    private function saveDatabase(array $post, array &$session, string $installerPath): InstallationResponse
    {
        try {
            $database = $this->validator->database($post);
            $driver = $database['driver'];
            if (!$this->requirements->passes($driver)) {
                throw new InstallationValidationException(['database_extension']);
            }

            $this->databaseTester->test($database);
            $session[self::SESSION_DATABASE] = $database;
            $session[self::SESSION_STAGE] = 'application';
            $this->rotateCsrf($session);

            return $this->redirect($installerPath);
        } catch (InstallationValidationException $exception) {
            $session[self::SESSION_STAGE] = 'database';
            return $this->renderStage($session, $installerPath, $exception->errorCodes(), $post);
        } catch (Throwable $throwable) {
            $this->logger->error('Database connection verification failed.', $throwable);
            $session[self::SESSION_STAGE] = 'database';
            return $this->renderStage($session, $installerPath, ['database_connection'], $post);
        }
    }

    /**
     * HR: Provjerava aplikacijske i administratorske podatke bez prikaza lozinke u pregledu.
     * EN: Validates application and administrator data without showing the password in review.
     *
     * @param array<array-key, mixed> $post
     * @param array<array-key, mixed> $session
     */
    private function saveApplication(array $post, array &$session, string $installerPath): InstallationResponse
    {
        try {
            $session[self::SESSION_APPLICATION] = $this->validator->application($post);
            $session[self::SESSION_ADMINISTRATOR] = $this->validator->administrator($post);
            $session[self::SESSION_STAGE] = 'review';
            $this->rotateCsrf($session);

            return $this->redirect($installerPath);
        } catch (InstallationValidationException $installationValidationException) {
            $session[self::SESSION_STAGE] = 'application';
            unset($session[self::SESSION_ADMINISTRATOR]);
            return $this->renderStage($session, $installerPath, $installationValidationException->errorCodes(), $post);
        }
    }

    /**
     * HR: Pokreće završnu instalaciju i nakon uspjeha briše osjetljivo stanje sesije.
     * EN: Runs final installation and clears sensitive session state after success.
     *
     * @param array<array-key, mixed> $session
     */
    private function install(array &$session, string $installerPath): InstallationResponse
    {
        $locale = $this->scalarString($session[self::SESSION_LOCALE] ?? 'hr');
        $database = $session[self::SESSION_DATABASE] ?? null;
        $application = $session[self::SESSION_APPLICATION] ?? null;
        $administrator = $session[self::SESSION_ADMINISTRATOR] ?? null;
        if (!is_array($database) || !is_array($application) || !is_array($administrator)) {
            $session[self::SESSION_STAGE] = 'requirements';
            return $this->renderStage($session, $installerPath, ['incomplete_state']);
        }

        try {
            $session['regenerate_id'] = true;
            $basePath = substr($installerPath, 0, -strlen('/install'));
            $result = $this->runner->run($database, $application, $administrator, $basePath);
            $body = $this->successPage(
                $locale,
                $installerPath,
                $this->scalarString($application['name'] ?? ''),
                $result['administrator_login'],
            );
            $session = ['destroy' => true];

            return $this->response(200, $body);
        } catch (InstallationValidationException $exception) {
            return $this->renderStage($session, $installerPath, $exception->errorCodes());
        } catch (Throwable $throwable) {
            $this->logger->error('Final installation failed.', $throwable);
            return $this->renderStage($session, $installerPath, ['installation_failed']);
        }
    }

    /**
     * HR: Renderira trenutačni korak s lokaliziranim sigurnim porukama.
     * EN: Renders the current stage with localized safe messages.
     *
     * @param array<array-key, mixed> $session
     * @param list<string> $errorCodes
     * @param array<array-key, mixed> $submitted
     */
    private function renderStage(
        array $session,
        string $installerPath,
        array $errorCodes,
        array $submitted = [],
    ): InstallationResponse {
        $locale = $this->scalarString($session[self::SESSION_LOCALE] ?? 'hr');
        $stage = $this->scalarString($session[self::SESSION_STAGE] ?? 'requirements');
        $csrf = $this->scalarString($session[self::SESSION_CSRF] ?? '');
        $content = match ($stage) {
            'database' => $this->databaseForm($locale, $installerPath, $csrf, $session, $submitted),
            'application' => $this->applicationForm($locale, $installerPath, $csrf, $session, $submitted),
            'review' => $this->reviewForm($locale, $installerPath, $csrf, $session),
            default => $this->requirementsForm($locale, $installerPath, $csrf),
        };

        return $this->response(200, $this->layout($locale, $stage, $installerPath, $content, $errorCodes));
    }

    /** HR: Renderira provjeru preduvjeta. EN: Renders the requirement check. */
    private function requirementsForm(string $locale, string $installerPath, string $csrf): string
    {
        $items = '';
        foreach ($this->requirements->checks() as $check) {
            $label = $locale === 'en' ? $check['label_en'] : $check['label_hr'];
            $state = $check['passed'] ? 'passed' : ($check['required'] ? 'failed' : 'optional');
            $items .= sprintf(
                '<li class="requirement requirement--%s"><span aria-hidden="true">%s</span> %s</li>',
                $state,
                $check['passed'] ? '&#10003;' : '&#10007;',
                $this->escape($label),
            );
        }

        return sprintf(
            '<h2>%s</h2><p>%s</p><ul class="requirements">%s</ul>'
            . '<form method="post" action="%s"><input type="hidden" name="csrf" value="%s">'
            . '<button class="button button--primary" type="submit" name="action" '
            . 'value="continue_requirements">%s</button></form>',
            $this->escape($this->text('requirements_title', $locale)),
            $this->escape($this->text('requirements_intro', $locale)),
            $items,
            $this->escape($installerPath),
            $this->escape($csrf),
            $this->escape($this->text('continue', $locale)),
        );
    }

    /**
     * HR: Renderira odabir baze bez vraćanja lozinke u HTML.
     * EN: Renders database selection without returning its password to HTML.
     *
     * @param array<array-key, mixed> $session
     * @param array<array-key, mixed> $submitted
     */
    private function databaseForm(
        string $locale,
        string $installerPath,
        string $csrf,
        array $session,
        array $submitted,
    ): string {
        $stored = is_array($session[self::SESSION_DATABASE] ?? null) ? $session[self::SESSION_DATABASE] : [];
        $values = array_merge($stored, $submitted);
        $driver = in_array($values['driver'] ?? null, ['sqlite', 'mysql', 'pgsql'], true)
        ? $this->scalarString($values['driver'])
        : 'sqlite';
        $port = $this->scalarString($values['port'] ?? ($driver === 'pgsql' ? '5432' : '3306'));

        return sprintf(
            '<h2>%s</h2><p>%s</p><form method="post" action="%s" autocomplete="off">'
            . '<input type="hidden" name="csrf" value="%s"><fieldset><legend>%s</legend>%s</fieldset>'
            . '<div class="grid"><label>%s<input name="host" maxlength="255" value="%s" inputmode="url"></label>'
            . '<label>%s<input name="port" maxlength="5" value="%s" inputmode="numeric"></label>'
            . '<label>%s<input name="database" maxlength="128" value="%s"></label>'
            . '<label>%s<input name="username" maxlength="128" value="%s" autocomplete="username"></label>'
            . '<label class="grid__wide">%s<input type="password" name="password" maxlength="1024" value="" '
            . 'autocomplete="new-password"></label></div><p class="hint">%s</p><div class="actions">'
            . '<button class="button" type="submit" name="action" value="back_requirements">%s</button>'
            . '<button class="button button--primary" type="submit" name="action" '
            . 'value="save_database">%s</button></div></form>',
            $this->escape($this->text('database_title', $locale)),
            $this->escape($this->text('database_intro', $locale)),
            $this->escape($installerPath),
            $this->escape($csrf),
            $this->escape($this->text('database_type', $locale)),
            $this->driverRadios($driver),
            $this->escape($this->text('database_host_label', $locale)),
            $this->escape($this->scalarString($values['host'] ?? '127.0.0.1')),
            $this->escape($this->text('database_port_label', $locale)),
            $this->escape($port),
            $this->escape($this->text('database_name_label', $locale)),
            $this->escape($this->scalarString($values['database'] ?? '')),
            $this->escape($this->text('database_user_label', $locale)),
            $this->escape($this->scalarString($values['username'] ?? '')),
            $this->escape($this->text('database_password_label', $locale)),
            $this->escape($this->text('database_hint', $locale)),
            $this->escape($this->text('back', $locale)),
            $this->escape($this->text('test_database', $locale)),
        );
    }

    /** HR: Renderira radio gumbe podržanih drivera. EN: Renders supported-driver radio buttons. */
    private function driverRadios(string $selected): string
    {
        $html = '';
        foreach (['sqlite' => 'SQLite', 'mysql' => 'MySQL', 'pgsql' => 'PostgreSQL'] as $value => $label) {
            $html .= sprintf(
                '<label class="choice"><input type="radio" name="driver" value="%s"%s> %s</label>',
                $value,
                $selected === $value ? ' checked' : '',
                $label,
            );
        }

        return $html;
    }

    /**
     * HR: Renderira identitet aplikacije i prvog administratora bez prepunjavanja lozinke.
     * EN: Renders application identity and the first administrator without prefilling passwords.
     *
     * @param array<array-key, mixed> $session
     * @param array<array-key, mixed> $submitted
     */
    private function applicationForm(
        string $locale,
        string $installerPath,
        string $csrf,
        array $session,
        array $submitted,
    ): string {
        $stored = is_array($session[self::SESSION_APPLICATION] ?? null) ? $session[self::SESSION_APPLICATION] : [];
        $values = array_merge($stored, $submitted);
        $primaryLocale = in_array($values['primary_locale'] ?? null, ['hr', 'en'], true)
        ? $this->scalarString($values['primary_locale'])
        : $locale;
        $supported = is_array($values['supported_locales'] ?? null)
        ? $values['supported_locales']
        : ['hr', 'en'];
        $timezone = $this->scalarString(
            $values['timezone'] ?? ($primaryLocale === 'hr' ? 'Europe/Zagreb' : 'UTC'),
        );

        return sprintf(
            '<h2>%s</h2><p>%s</p><form method="post" action="%s" autocomplete="off">'
            . '<input type="hidden" name="csrf" value="%s"><fieldset><legend>%s</legend><div class="grid">'
            . '<label class="grid__wide">%s<input name="name" required maxlength="100" value="%s"></label>'
            . '<label>%s<select name="primary_locale">%s</select></label><fieldset><legend>%s</legend>%s</fieldset>'
            . '<label class="grid__wide">%s<select name="timezone">%s</select></label></div></fieldset>'
            . '<fieldset><legend>%s</legend><div class="grid">%s</div></fieldset><p class="hint">%s</p>'
            . '<div class="actions"><button class="button" type="submit" name="action" '
            . 'value="back_database">%s</button><button class="button button--primary" type="submit" '
            . 'name="action" value="save_application">%s</button></div></form>',
            $this->escape($this->text('application_title', $locale)),
            $this->escape($this->text('application_intro', $locale)),
            $this->escape($installerPath),
            $this->escape($csrf),
            $this->escape($this->text('site_settings', $locale)),
            $this->escape($this->text('application_name_label', $locale)),
            $this->escape($this->scalarString($values['name'] ?? 'Simbioza')),
            $this->escape($this->text('primary_locale_label', $locale)),
            $this->localeOptions($primaryLocale),
            $this->escape($this->text('supported_locales_label', $locale)),
            $this->localeCheckboxes($supported, $locale),
            $this->escape($this->text('timezone_label', $locale)),
            $this->timezoneOptions($timezone),
            $this->escape($this->text('administrator_title', $locale)),
            $this->administratorFields($locale, $values),
            $this->escape($this->text('password_hint', $locale)),
            $this->escape($this->text('back', $locale)),
            $this->escape($this->text('continue', $locale)),
        );
    }

    /** HR: Renderira opcije jezika. EN: Renders locale options. */
    private function localeOptions(string $selected): string
    {
        return sprintf(
            '<option value="hr"%s>Hrvatski</option><option value="en"%s>English</option>',
            $selected === 'hr' ? ' selected' : '',
            $selected === 'en' ? ' selected' : '',
        );
    }

    /**
     * HR: Renderira dostupne jezike.
     * EN: Renders enabled locales.
     *
     * @param array<mixed> $selected
     */
    private function localeCheckboxes(array $selected, string $locale): string
    {
        return sprintf(
            '<label class="choice"><input type="checkbox" name="supported_locales[]" value="hr"%s> %s</label>'
            . '<label class="choice"><input type="checkbox" name="supported_locales[]" value="en"%s> English</label>',
            in_array('hr', $selected, true) ? ' checked' : '',
            $locale === 'en' ? 'Croatian' : 'Hrvatski',
            in_array('en', $selected, true) ? ' checked' : '',
        );
    }

    /** HR: Renderira sve PHP vremenske zone. EN: Renders every PHP timezone. */
    private function timezoneOptions(string $selected): string
    {
        $options = '';
        foreach (timezone_identifiers_list() as $timezone) {
            $options .= sprintf(
                '<option value="%s"%s>%s</option>',
                $this->escape($timezone),
                $timezone === $selected ? ' selected' : '',
                $this->escape($timezone),
            );
        }

        return $options;
    }

    /**
     * HR: Renderira polja prvog administratora uz sigurne autocomplete oznake.
     * EN: Renders first-administrator fields with safe autocomplete hints.
     *
     * @param array<array-key, mixed> $values
     */
    private function administratorFields(string $locale, array $values): string
    {
        $fields = [
            ['login', 'administrator_login_label', 'username', true, 128],
            ['display_name', 'administrator_display_name_label', 'name', true, 150],
            ['first_name', 'administrator_first_name_label', 'given-name', false, 100],
            ['last_name', 'administrator_last_name_label', 'family-name', false, 100],
            ['email', 'administrator_email_label', 'email', true, 254],
        ];
        $html = '';
        foreach ($fields as [$name, $labelKey, $autocomplete, $required, $maxlength]) {
            $type = $name === 'email' ? 'email' : 'text';
            $html .= sprintf(
                '<label>%s<input type="%s" name="%s" maxlength="%d" value="%s" autocomplete="%s"%s></label>',
                $this->escape($this->text($labelKey, $locale)),
                $type,
                $name,
                $maxlength,
                $this->escape($this->scalarString($values[$name] ?? '')),
                $autocomplete,
                $required ? ' required' : '',
            );
        }

        return $html . sprintf(
            '<label>%s<input type="password" name="password" minlength="12" maxlength="128" '
            . 'autocomplete="new-password" required></label><label>%s<input type="password" '
            . 'name="password_confirmation" minlength="12" maxlength="128" autocomplete="new-password" '
            . 'required></label>',
            $this->escape($this->text('administrator_password_label', $locale)),
            $this->escape($this->text('administrator_password_confirmation_label', $locale)),
        );
    }

    /**
     * HR: Renderira završni pregled bez ijedne lozinke ili tokena.
     * EN: Renders the final review without any password or token.
     *
     * @param array<array-key, mixed> $session
     */
    private function reviewForm(string $locale, string $installerPath, string $csrf, array $session): string
    {
        $database = is_array($session[self::SESSION_DATABASE] ?? null) ? $session[self::SESSION_DATABASE] : [];
        $application = is_array($session[self::SESSION_APPLICATION] ?? null) ? $session[self::SESSION_APPLICATION] : [];
        $administrator = is_array($session[self::SESSION_ADMINISTRATOR] ?? null)
        ? $session[self::SESSION_ADMINISTRATOR]
        : [];
        $driverLabel = match ($database['driver'] ?? '') {
            'mysql' => 'MySQL',
            'pgsql' => 'PostgreSQL',
            default => 'SQLite',
        };
        $locales = is_array($application['supported_locales'] ?? null)
        ? implode(', ', $this->stringList($application['supported_locales']))
        : '';
        $rows = [
            [$this->text('application_name_label', $locale), $this->scalarString($application['name'] ?? '')],
            [$this->text('database_type', $locale), $driverLabel],
            [
                $this->text('primary_locale_label', $locale),
                strtoupper($this->scalarString($application['primary_locale'] ?? '')),
            ],
            [$this->text('supported_locales_label', $locale), strtoupper($locales)],
            [$this->text('timezone_label', $locale), $this->scalarString($application['timezone'] ?? '')],
            [$this->text('administrator_login_label', $locale), $this->scalarString($administrator['login'] ?? '')],
            [$this->text('administrator_email_label', $locale), $this->scalarString($administrator['email'] ?? '')],
        ];
        $details = '';
        foreach ($rows as [$label, $value]) {
            $details .= sprintf('<dt>%s</dt><dd>%s</dd>', $this->escape($label), $this->escape($value));
        }

        return sprintf(
            '<h2>%s</h2><p>%s</p><dl class="review">%s</dl><p class="notice">%s</p>'
            . '<form method="post" action="%s"><input type="hidden" name="csrf" value="%s"><div class="actions">'
            . '<button class="button" type="submit" name="action" value="back_application">%s</button>'
            . '<button class="button button--primary" type="submit" name="action" '
            . 'value="install">%s</button></div></form>',
            $this->escape($this->text('review_title', $locale)),
            $this->escape($this->text('review_intro', $locale)),
            $details,
            $this->escape($this->text('review_notice', $locale)),
            $this->escape($installerPath),
            $this->escape($csrf),
            $this->escape($this->text('back', $locale)),
            $this->escape($this->text('install_now', $locale)),
        );
    }

    /** HR: Renderira potvrdu i vezu za prvu prijavu. EN: Renders confirmation and the first-login link. */
    private function successPage(string $locale, string $installerPath, string $name, string $login): string
    {
        $basePath = substr($installerPath, 0, -strlen('/install'));
        $loginPath = $basePath . '/auth/login';
        $content = sprintf(
            '<div class="success-mark" aria-hidden="true">&#10003;</div><h2>%s</h2><p>%s</p>'
            . '<dl class="review"><dt>%s</dt><dd>%s</dd><dt>%s</dt><dd>%s</dd></dl>'
            . '<p><a class="button button--primary" href="%s">%s</a></p>',
            $this->escape($this->text('success_title', $locale)),
            $this->escape($this->text('success_intro', $locale)),
            $this->escape($this->text('application_name_label', $locale)),
            $this->escape($name),
            $this->escape($this->text('administrator_login_label', $locale)),
            $this->escape($login),
            $this->escape($loginPath),
            $this->escape($this->text('sign_in', $locale)),
        );

        return $this->layout($locale, 'success', $installerPath, $content, []);
    }

    /**
     * HR: Gradi zajednički dokument instalera s jasnim koracima i izmjenom jezika.
     * EN: Builds the common installer document with clear steps and locale switching.
     *
     * @param list<string> $errorCodes
     */
    private function layout(
        string $locale,
        string $stage,
        string $installerPath,
        string $content,
        array $errorCodes,
    ): string {
        $basePath = substr($installerPath, 0, -strlen('/install'));
        $errors = '';
        if ($errorCodes !== []) {
            $items = '';
            foreach ($errorCodes as $errorCode) {
                $items .= '<li>' . $this->escape($this->text($errorCode, $locale)) . '</li>';
            }

            $errors = sprintf(
                '<div class="alert" role="alert"><strong>%s</strong><ul>%s</ul></div>',
                $this->escape($this->text('please_correct', $locale)),
                $items,
            );
        }

        $steps = '';
        foreach (['requirements', 'database', 'application', 'review'] as $index => $step) {
            $class = $step === $stage ? 'step step--active' : 'step';
            $steps .= sprintf(
                '<li class="%s"><span>%d</span>%s</li>',
                $class,
                $index + 1,
                $this->escape($this->text('step_' . $step, $locale)),
            );
        }

        $alternateLocale = $locale === 'hr' ? 'en' : 'hr';
        $alternateLabel = $locale === 'hr' ? 'English' : 'Hrvatski';

        return '<!doctype html><html lang="' . $this->escape($locale) . '"><head><meta charset="utf-8">'
        . '<meta name="viewport" content="width=device-width, initial-scale=1">'
        . '<title>' . $this->escape($this->text('page_title', $locale)) . '</title>'
        . '<link rel="stylesheet" href="' . $this->escape($basePath . '/install-assets/installer.css') . '"></head>'
        . '<body data-step="' . $this->escape($stage) . '"><header class="topbar"><div><strong>Simbioza</strong><span>'
        . $this->escape($this->text('installer_label', $locale)) . '</span></div><a href="'
        . $this->escape($installerPath . '?lang=' . $alternateLocale) . '">' . $alternateLabel . '</a></header>'
        . '<main><ol class="steps">' . $steps . '</ol><section class="panel">'
        . $errors . $content . '</section></main>'
        . '<footer>' . $this->escape($this->text('footer_security', $locale)) . '</footer></body></html>';
    }

    /** HR: Vraća jednostavnu sigurnu stranicu. EN: Returns a simple secure page. */
    private function simplePage(string $locale, string $message): string
    {
        return '<!doctype html><html lang="' . $this->escape($locale) . '"><head><meta charset="utf-8">'
        . '<meta name="viewport" content="width=device-width, initial-scale=1"><title>Simbioza</title></head>'
        . '<body><main><h1>Simbioza</h1><p>' . $this->escape($message) . '</p></main></body></html>';
    }

    /** HR: Vraća zaključani 404 bez otkrivanja instalera. EN: Returns a locked 404 without revealing the installer. */
    private function notFound(): InstallationResponse
    {
        return $this->response(404, $this->simplePage('en', 'Not found.'));
    }

    /** HR: Gradi odgovor preusmjeravanja bez cacheiranja. EN: Builds a non-cacheable redirect. */
    private function redirect(string $location): InstallationResponse
    {
        $headers = $this->securityHeaders();
        $headers['Location'] = $location;

        return new InstallationResponse(303, $headers, '');
    }

    /** HR: Gradi HTML odgovor sa svim sigurnosnim zaglavljima. EN: Builds an HTML response with every security header. */
    private function response(int $status, string $body): InstallationResponse
    {
        return new InstallationResponse($status, $this->securityHeaders(), $body);
    }

    /**
     * HR: Vraća zaglavlja protiv XSS-a, clickjackinga, sniffinga i curenja podataka.
     * EN: Returns headers protecting against XSS, clickjacking, sniffing, and data leakage.
     *
     * @return array<string, string>
     */
    private function securityHeaders(): array
    {
        return [
            'Content-Type' => 'text/html; charset=UTF-8',
            'Cache-Control' => 'no-store, max-age=0',
            'Pragma' => 'no-cache',
            'Content-Security-Policy' => "default-src 'none'; style-src 'self'; form-action 'self'; "
            . "base-uri 'none'; frame-ancestors 'none'",
            'X-Frame-Options' => 'DENY',
            'X-Content-Type-Options' => 'nosniff',
            'Referrer-Policy' => 'no-referrer',
            'Permissions-Policy' => 'camera=(), microphone=(), geolocation=(), payment=()',
            'Cross-Origin-Opener-Policy' => 'same-origin',
            'Strict-Transport-Security' => 'max-age=31536000; includeSubDomains',
        ];
    }

    /**
     * HR: Prepoznaje base path i kada je aplikacija u poddirektoriju.
     * EN: Detects the base path when the application is mounted below a subdirectory.
     */
    private function installerPath(string $scriptName): string
    {
        $basePath = str_replace('\\', '/', dirname($scriptName));
        $basePath = $basePath === '/' || $basePath === '.' ? '' : rtrim($basePath, '/');

        return $basePath . '/install';
    }

    /**
     * HR: Ograničava izbor sučelja na HR ili EN.
     * EN: Restricts the interface locale to HR or EN.
     *
     * @param array<array-key, mixed> $query
     */
    private function requestedLocale(array $query, string $fallback): string
    {
        $requested = strtolower($this->scalarString($query['lang'] ?? ''));

        return in_array($requested, ['hr', 'en'], true) ? $requested : ($fallback === 'en' ? 'en' : 'hr');
    }

    /**
     * HR: Ističe neaktivnu autorizaciju nakon 30 minuta.
     * EN: Expires inactive authorization after 30 minutes.
     *
     * @param array<array-key, mixed> $session
     */
    private function expireSession(array &$session): void
    {
        $lastActivity = is_int($session[self::SESSION_LAST_ACTIVITY] ?? null)
        ? $session[self::SESSION_LAST_ACTIVITY]
        : 0;
        if ($lastActivity > 0 && time() - $lastActivity > self::SESSION_TIMEOUT_SECONDS) {
            $session = [];
        }
    }

    /**
     * HR: Provjerava CSRF tajnu konstantnim vremenom.
     * EN: Verifies the CSRF secret in constant time.
     *
     * @param array<array-key, mixed> $post
     * @param array<array-key, mixed> $session
     */
    private function validCsrf(array $post, array $session): bool
    {
        $expected = is_string($session[self::SESSION_CSRF] ?? null) ? $session[self::SESSION_CSRF] : '';
        $provided = $this->scalarString($post['csrf'] ?? '');

        return $expected !== '' && hash_equals($expected, $provided);
    }

    /**
     * HR: Rotira CSRF tajnu nakon svakog uspješnog koraka.
     * EN: Rotates the CSRF secret after each successful step.
     *
     * @param array<array-key, mixed> $session
     */
    private function rotateCsrf(array &$session): void
    {
        $session[self::SESSION_CSRF] = bin2hex(random_bytes(32));
    }

    /** HR: Sigurno pretvara samo skalaran HTTP unos. EN: Safely converts scalar HTTP input only. */
    private function scalarString(mixed $value): string
    {
        return is_scalar($value) ? (string)$value : '';
    }

    /**
     * HR: Zadržava samo skalarne jezične oznake za siguran prikaz.
     * EN: Keeps only scalar locale identifiers for safe presentation.
     *
     * @param array<array-key, mixed> $values
     * @return list<string>
     */
    private function stringList(array $values): array
    {
        $strings = [];
        foreach ($values as $value) {
            if (is_scalar($value)) {
                $strings[] = (string)$value;
            }
        }

        return $strings;
    }

    /** HR: HTML-escapa svaki dinamički izlaz. EN: HTML-escapes every dynamic value. */
    private function escape(string $value): string
    {
        return htmlspecialchars($value, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');
    }

    /** HR: Vraća jednu potpunu HR/EN poruku. EN: Returns one complete HR/EN message. */
    private function text(string $key, string $locale): string
    {
        $messages = $this->messages();
        $translation = $messages[$key] ?? $messages['installation_failed'];

        return $translation[$locale === 'en' ? 'en' : 'hr'];
    }

    /**
     * HR: Središnji prijevod cijelog instalacijskog sučelja.
     * EN: Central translation map for the complete installation interface.
     *
     * @return array<string, array{hr:string,en:string}>
     */
    private function messages(): array
    {
        return [
            'page_title' => ['hr' => 'Instalacija Simbioze', 'en' => 'Install Simbioza'],
            'installer_label' => ['hr' => 'Sigurna početna instalacija', 'en' => 'Secure initial installation'],
            'footer_security' => [
                'hr' => 'Instalacijski pristup je jednokratan i bit će trajno zaključan nakon uspjeha.',
                'en' => 'Installer access is one-time and will be permanently locked after success.',
            ],
            'step_requirements' => ['hr' => 'Preduvjeti', 'en' => 'Requirements'],
            'step_database' => ['hr' => 'Baza', 'en' => 'Database'],
            'step_application' => ['hr' => 'Aplikacija i administrator', 'en' => 'App and administrator'],
            'step_review' => ['hr' => 'Pregled i instalacija', 'en' => 'Review and install'],
            'requirements_title' => ['hr' => 'Provjera sustava', 'en' => 'System check'],
            'requirements_intro' => [
                'hr' => 'Prije nastavka provjeravaju se PHP, obvezne ekstenzije, migracije, početna tema i javne '
                . 'upute te prava pisanja.',
                'en' => 'PHP, required extensions, migrations, starter theme and public guides, and write '
                . 'permissions are checked first.',
            ],
            'database_title' => ['hr' => 'Odabir i provjera baze', 'en' => 'Choose and verify the database'],
            'database_intro' => [
                'hr' => 'Čarobnjak će otvoriti stvarnu vezu i izvršiti probni upit. Za SQLite se mrežna polja '
                . 'zanemaruju.',
                'en' => 'The wizard opens a real connection and runs a probe query. Network fields are ignored for '
                . 'SQLite.',
            ],
            'database_type' => ['hr' => 'Vrsta baze', 'en' => 'Database type'],
            'database_host_label' => ['hr' => 'Poslužitelj', 'en' => 'Host'],
            'database_port_label' => ['hr' => 'Port', 'en' => 'Port'],
            'database_name_label' => ['hr' => 'Naziv baze', 'en' => 'Database name'],
            'database_user_label' => ['hr' => 'Korisnik baze', 'en' => 'Database user'],
            'database_password_label' => ['hr' => 'Lozinka baze', 'en' => 'Database password'],
            'database_hint' => [
                'hr' => 'Lozinka baze nikada se ne vraća u HTML niti prikazuje u pregledu.',
                'en' => 'The database password is never returned to HTML or shown in review.',
            ],
            'application_title' => [
                'hr' => 'Aplikacija i prvi administrator',
                'en' => 'Application and first administrator',
            ],
            'application_intro' => [
                'hr' => 'Odaberite identitet sitea, jezike i sigurne vjerodajnice prvog računa.',
                'en' => 'Choose site identity, languages, and secure credentials for the first account.',
            ],
            'site_settings' => ['hr' => 'Postavke sitea', 'en' => 'Site settings'],
            'application_name_label' => ['hr' => 'Naziv aplikacije', 'en' => 'Application name'],
            'primary_locale_label' => ['hr' => 'Primarni jezik', 'en' => 'Primary language'],
            'supported_locales_label' => ['hr' => 'Dostupni jezici', 'en' => 'Available languages'],
            'timezone_label' => ['hr' => 'Vremenska zona', 'en' => 'Timezone'],
            'administrator_title' => ['hr' => 'Prvi administratorski račun', 'en' => 'First administrator account'],
            'administrator_login_label' => ['hr' => 'Login oznaka', 'en' => 'Login identifier'],
            'administrator_display_name_label' => ['hr' => 'Prikazno ime', 'en' => 'Display name'],
            'administrator_first_name_label' => ['hr' => 'Ime', 'en' => 'First name'],
            'administrator_last_name_label' => ['hr' => 'Prezime', 'en' => 'Last name'],
            'administrator_email_label' => ['hr' => 'E-mail', 'en' => 'Email'],
            'administrator_password_label' => ['hr' => 'Sigurna lozinka', 'en' => 'Secure password'],
            'administrator_password_confirmation_label' => ['hr' => 'Ponovite lozinku', 'en' => 'Repeat password'],
            'password_hint' => [
                'hr' => 'Najmanje 12 znakova i tri skupine znakova; lozinka ne smije sadržavati login ili početak '
                . 'e-mail adrese.',
                'en' => 'Use at least 12 characters and three character groups; do not include the login or email '
                . 'prefix.',
            ],
            'review_title' => ['hr' => 'Provjerite instalaciju', 'en' => 'Review the installation'],
            'review_intro' => [
                'hr' => 'Povjerljive vrijednosti namjerno nisu prikazane. Završni korak pokreće stvarne migracije.',
                'en' => 'Sensitive values are intentionally hidden. The final step runs the real migrations.',
            ],
            'review_notice' => [
                'hr' => 'Nakon uspjeha token se uklanja, installer se trajno zaključava i tema Simbioza postavlja '
                . 'kao zadana.',
                'en' => 'After success, the token is removed, the installer is permanently locked, and Simbioza '
                . 'becomes the default theme.',
            ],
            'success_title' => [
                'hr' => 'Simbioza je uspješno instalirana',
                'en' => 'Simbioza was installed successfully',
            ],
            'success_intro' => [
                'hr' => 'Migracije, prvi administrator i tema dovršeni su. Instalacijska adresa više se ne može '
                . 'ponovno koristiti.',
                'en' => 'Migrations, the first administrator, and the theme are complete. The installer URL cannot '
                . 'be reused.',
            ],
            'continue' => ['hr' => 'Nastavi', 'en' => 'Continue'],
            'back' => ['hr' => 'Natrag', 'en' => 'Back'],
            'test_database' => ['hr' => 'Provjeri vezu i nastavi', 'en' => 'Test connection and continue'],
            'install_now' => ['hr' => 'Instaliraj Simbiozu', 'en' => 'Install Simbioza'],
            'sign_in' => ['hr' => 'Otvori prijavu', 'en' => 'Open sign in'],
            'please_correct' => ['hr' => 'Provjerite sljedeće:', 'en' => 'Please check the following:'],
            'requirements_failed' => [
                'hr' => 'Jedan ili više obveznih preduvjeta nije zadovoljen.',
                'en' => 'One or more required checks did not pass.',
            ],
            'database_driver' => [
                'hr' => 'Odaberite podržanu vrstu baze.',
                'en' => 'Choose a supported database type.',
            ],
            'database_host' => ['hr' => 'Unesite valjani poslužitelj baze.', 'en' => 'Enter a valid database host.'],
            'database_port' => ['hr' => 'Unesite valjani port baze.', 'en' => 'Enter a valid database port.'],
            'database_name' => ['hr' => 'Unesite valjani naziv baze.', 'en' => 'Enter a valid database name.'],
            'database_username' => [
                'hr' => 'Unesite valjano korisničko ime baze.',
                'en' => 'Enter a valid database username.',
            ],
            'database_password' => ['hr' => 'Lozinka baze je preduga.', 'en' => 'The database password is too long.'],
            'database_extension' => [
                'hr' => 'PDO ekstenzija odabrane baze nije dostupna.',
                'en' => 'The selected database PDO extension is unavailable.',
            ],
            'database_connection' => [
                'hr' => 'Veza s bazom nije uspjela. Provjerite podatke; tehnički detalji zapisani su u privatni '
                . 'log.',
                'en' => 'The database connection failed. Check the values; technical details were written to the '
                . 'private log.',
            ],
            'application_name' => [
                'hr' => 'Naziv aplikacije je obvezan i smije imati do 100 znakova.',
                'en' => 'Application name is required and may contain up to 100 characters.',
            ],
            'supported_locales' => [
                'hr' => 'Odaberite najmanje jedan dostupan jezik.',
                'en' => 'Choose at least one available language.',
            ],
            'primary_locale' => [
                'hr' => 'Primarni jezik mora biti među dostupnim jezicima.',
                'en' => 'The primary language must be one of the available languages.',
            ],
            'timezone' => ['hr' => 'Odaberite valjanu vremensku zonu.', 'en' => 'Choose a valid timezone.'],
            'administrator_login' => [
                'hr' => 'Login mora imati 3 do 128 dopuštenih znakova.',
                'en' => 'Login must contain 3 to 128 allowed characters.',
            ],
            'administrator_display_name' => ['hr' => 'Prikazno ime je obvezno.', 'en' => 'Display name is required.'],
            'administrator_name' => ['hr' => 'Ime ili prezime je predugo.', 'en' => 'First or last name is too long.'],
            'administrator_email' => ['hr' => 'Unesite valjanu e-mail adresu.', 'en' => 'Enter a valid email address.'],
            'administrator_password' => [
                'hr' => 'Administratorska lozinka ne zadovoljava sigurnosna pravila.',
                'en' => 'The administrator password does not meet the security policy.',
            ],
            'administrator_password_confirmation' => [
                'hr' => 'Lozinke se ne podudaraju.',
                'en' => 'The passwords do not match.',
            ],
            'incomplete_state' => [
                'hr' => 'Instalacijska sesija nije potpuna; krenite od početka.',
                'en' => 'The installer session is incomplete; start again.',
            ],
            'installation_failed' => [
                'hr' => 'Instalacija nije dovršena. Tehnički detalji zapisani su u privatni log; ništa povjerljivo '
                . 'nije prikazano.',
                'en' => 'Installation did not complete. Technical details were written to the private log; no '
                . 'sensitive data was shown.',
            ],
            'csrf_error' => [
                'hr' => 'Sigurnosni token obrasca nije valjan. Ponovno otvorite korak.',
                'en' => 'The form security token is invalid. Reopen the step.',
            ],
            'invalid_action' => [
                'hr' => 'Instalacijska radnja nije valjana.',
                'en' => 'The installer action is invalid.',
            ],
            'method_not_allowed' => ['hr' => 'HTTP metoda nije dopuštena.', 'en' => 'The HTTP method is not allowed.'],
        ];
    }
}
