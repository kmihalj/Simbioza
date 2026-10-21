<?php

declare(strict_types=1);

namespace Tests\Setup;

use DOMDocument;
use DOMXPath;
use PHPUnit\Framework\Attributes\CoversNothing;
use PHPUnit\Framework\TestCase;

/**
 * EN: Renders the real Setup template without running any updater or touching installation data.
 *
 * HR: Renderira stvarni Setup predložak bez pokretanja updatera ili diranja instalacijskih podataka.
 */
#[CoversNothing]
final class SetupUpdatePresentationTest extends TestCase
{
    /**
     * EN: Cached module-only updates are visible without JavaScript and separate from enabled state.
     *
     * HR: Predmemorirane nadogradnje samo modula vidljive su bez JavaScripta i odvojene od uključenosti.
     */
    public function testModuleOnlyUpdateHasProminentSummaryAndRowBadge(): void
    {
        $html = $this->render('update_available');
        $xpath = $this->xpath($html);
        $this->assertSame(1, $xpath->query('//*[@data-setup-pending-updates and not(@hidden)]')->length);
        $this->assertSame(1, $xpath->query('//*[@data-setup-pending-list]/li')->length);
        $list = (string)$xpath->evaluate('string(//*[@data-setup-pending-list])');
        $this->assertStringContainsString('Email', $list);
        $this->assertStringContainsString('0.3.1', $list);
        $this->assertStringContainsString('0.3.2', $list);
        $this->assertSame(1, $xpath->query('//*[@data-component-package and contains(@class,"has-update")]')->length);
        $this->assertSame(
            1,
            $xpath->query('//*[@data-component-update-badge and not(contains(@class,"d-none"))]')->length,
        );
        $this->assertSame(1, $xpath->query('//*[@data-component-installed-version]')->length);
        $this->assertStringContainsString('text-bg-success', $html);
        $this->assertStringNotContainsString('class="small text-warning-emphasis', $html);
        $this->assertStringNotContainsString('small text-body-secondary mt-2" data-setup-update-summary', $html);
    }

    /**
     * EN: A current installation has no stale highlighted rows or empty update warning.
     *
     * HR: Ažurna instalacija nema zaostale istaknute retke ni prazno upozorenje nadogradnje.
     */
    public function testCurrentInstallationHidesSummaryAndUpdateBadge(): void
    {
        $xpath = $this->xpath($this->render('up_to_date'));
        $this->assertSame(1, $xpath->query('//*[@data-setup-pending-updates and @hidden]')->length);
        $this->assertSame(0, $xpath->query('//*[@data-setup-pending-list]/li')->length);
        $this->assertSame(0, $xpath->query('//*[@data-component-package and contains(@class,"has-update")]')->length);
        $this->assertSame(1, $xpath->query('//*[@data-component-update-badge and contains(@class,"d-none")]')->length);
    }

    /**
     * EN: Repository labels and versions are escaped rather than interpreted as executable markup.
     *
     * HR: Oznake repozitorija i verzije su escapirane, a ne protumačene kao izvršivi HTML.
     */
    public function testReleaseMetadataCannotInjectMarkup(): void
    {
        $html = $this->render('update_available', '<img src=x onerror=alert(1)>');
        $xpath = $this->xpath($html);
        $this->assertSame(0, $xpath->query('//*[@data-setup-pending-list]//img')->length);
        $this->assertStringContainsString('&lt;img src=x onerror=alert(1)&gt;', $html);
        $this->assertStringContainsString('pendingList.replaceChildren()', $html);
        $this->assertStringNotContainsString('pendingList.innerHTML', $html);
    }

    /**
     * EN: Parses rendered output for semantic presentation assertions.
     *
     * HR: Parsira renderirani izlaz radi semantičke provjere prikaza.
     */
    private function xpath(string $html): DOMXPath
    {
        $document = new DOMDocument();
        $document->loadHTML('<?xml encoding="UTF-8">' . $html, LIBXML_NOERROR | LIBXML_NOWARNING);
        return new DOMXPath($document);
    }

    /**
     * EN: Supplies fictional state to the real view; form tokens are a harmless test stub.
     *
     * HR: Stvarnom prikazu daje izmišljeno stanje; tokeni obrazaca su bezopasna testna zamjena.
     */
    private function render(string $status, string $name = 'Email'): string
    {
        $view = new class {
            public object $csrfHandler;

            /**
             * EN: Uses no session or real authentication state.
             *
             * HR: Ne koristi sesiju ni stvarno stanje prijave.
             */
            public function __construct()
            {
                $this->csrfHandler = new class {
                    /**
                     * EN: Produces no real token in this render-only fixture.
                     *
                     * HR: Ne stvara stvarni token u ovoj fixture za renderiranje.
                     */
                    public function generateCsrfTokenInputField(): string
                    {
                        return '';
                    }
                };
            }

            /**
             * EN: Escapes template output with the normal HTML rules.
             *
             * HR: Escapira izlaz predloška uobičajenim HTML pravilima.
             */
            public function escape(string $value): string
            {
                return htmlspecialchars($value, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');
            }

            /**
             * EN: Evaluates the unchanged template contract with fictional data.
             *
             * HR: Izvršava neizmijenjeni ugovor predloška s izmišljenim podacima.
             *
             * @param array<string, mixed> $data
             */
            public function render(array $data): string
            {
                extract($data, EXTR_SKIP);
                ob_start();
                try {
                    require dirname(__DIR__, 3) . '/views/setup/index.php';
                    return (string)ob_get_contents();
                } finally {
                    ob_end_clean();
                }
            }
        };

        return $view->render([
            'title' => 'Setup', 'locale' => 'en', 'settingsMenuActiveSection' => 'setup', 'menuRenderer' => null,
            'modules' => [[
                'slug' => 'email', 'package' => 'aaieduhr/heartphrame-module-email',
                'label_hr' => 'E-pošta', 'label_en' => 'Email', 'optional' => true, 'recommended' => false,
                'package_installed' => true, 'enabled' => true, 'schema_installed' => true,
                'backup_available' => false, 'state' => 'enabled',
            ]],
            'definitions' => ['email' => ['dependencies' => ['auth']]],
            'diagnostics' => [
                'immutable' => false, 'is_fpm' => true, 'helper_ready' => true,
                'state_changes_allowed' => true, 'language_changes_allowed' => true,
                'package_changes_allowed' => true, 'application_updates_allowed' => true, 'checks' => [],
            ],
            'languages' => [], 'repositoryLanguages' => [],
            'componentUpdates' => [
                'components' => [[
                    'package' => 'aaieduhr/heartphrame-module-email', 'name' => $name, 'kind' => 'module',
                    'installed_version' => '0.3.1', 'latest_version' => '0.3.2', 'status' => $status,
                ]],
                'checked_at' => null, 'failures' => 0, 'updates' => $status === 'update_available' ? 1 : 0,
            ],
            'applicationUpdate' => [
                'state' => 'idle', 'stage' => 'idle', 'progress' => null, 'started_at' => null,
                'finished_at' => null, 'pid' => null, 'message' => '', 'current_version' => '0.3.13',
            ],
            'applicationUpdateStatusPath' => '/settings/setup/application-update-status',
            'automaticUpdateCheckPath' => '/settings/check-updates/status', 'actionPath' => '/settings/setup',
        ]);
    }
}
