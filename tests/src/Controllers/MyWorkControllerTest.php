<?php

declare(strict_types=1);

namespace Tests\Controllers;

use AaiEduHr\HeartPhrameModuleOrm\Database\QueryExecuted;
use App\Controllers\MyWorkController;
use HeartPhrame\Authn\AuthnHandlerInterface;
use HeartPhrame\Http\Request;
use HeartPhrame\Http\ResponseFactory;
use HeartPhrame\Http\StreamFactory;
use HeartPhrame\Localization\TranslatorInterface;
use HeartPhrame\Routing\UrlGenerator;
use HeartPhrame\Session\SessionInterface;
use HeartPhrame\View\View;
use PHPUnit\Framework\Attributes\CoversNothing;
use PHPUnit\Framework\TestCase;
use Tests\Work\MyWorkFixture;

#[CoversNothing]
final class MyWorkControllerTest extends TestCase
{
    private MyWorkFixture $fixture;

    /** @var array<string,mixed>|null */
    private ?array $user = ['id' => 2, 'is_admin' => false, 'is_active' => true];

    /** @var array<string,mixed> */
    private array $cursorState = [];

    /** @var array<string,mixed> */
    private array $rendered = [];

    private MyWorkController $controller;

    /**
     * EN: Uses the real list service and a private fixture, replacing only HTTP rendering and session boundaries.
     *
     * HR: Koristi stvarni servis popisa i privatni primjer, zamjenjujući samo granice HTTP prikaza i sjednice.
     */
    protected function setUp(): void
    {
        $auth = $this->createMock(AuthnHandlerInterface::class);
        $auth->method('userData')->willReturnCallback(fn(): ?array => $this->user);
        $auth->method('check')->willReturnCallback(fn(): bool => $this->user !== null);
        $session = $this->createMock(SessionInterface::class);
        $session->method('get')->willReturnCallback(
            fn(string $key, mixed $default = null): mixed => $this->cursorState[$key] ?? $default,
        );
        $session->method('set')->willReturnCallback(function (string $key, mixed $value): void {
            $this->cursorState[$key] = $value;
        });
        $this->fixture = new MyWorkFixture($auth, $session);
        $this->fixture->config->set('app.modules.enabled', ['aaieduhr/heartphrame-module-editor-html']);

        $urls = $this->createMock(UrlGenerator::class);
        $urls->method('getPathFor')->willReturnCallback(
            static fn(string $name, array $params = [], array $query = []): string =>
                '/' . $name . '?' . http_build_query([...$params, ...$query]),
        );
        $view = $this->createMock(View::class);
        $view->method('for')->willReturnCallback(function (string $name, array $data): string {
            $this->rendered = $data;
            return '<p>Private work overview</p>';
        });
        $translator = $this->createMock(TranslatorInterface::class);
        $translator->method('getLocale')->willReturn('hr');
        $this->controller = new MyWorkController(
            $this->fixture->service($urls),
            $auth,
            new ResponseFactory(new StreamFactory(), $view),
            $translator,
        );
    }

    /**
     * EN: Removes only the private fixture and restores its environment.
     *
     * HR: Briše samo privatni primjer i vraća njegovo okruženje.
     */
    protected function tearDown(): void
    {
        $this->fixture->close();
    }

    /**
     * EN: A guest receives no headers, optional-service queries, or cacheable response.
     *
     * HR: Gost ne dobiva metapodatke, upite opcionalnih servisa ni odgovor prikladan za predmemoriranje.
     */
    public function testGuestReceivesPrivateUnauthorizedResponseWithoutContentQueries(): void
    {
        $this->user = null;
        $queries = [];
        $this->fixture->db->listen(static function (QueryExecuted $event) use (&$queries): void {
            $queries[] = $event->sql;
        });
        $response = $this->controller->index(new Request('GET', 'https://example.test/my-work?user_id=2'));
        $this->fixture->db->forgetQueryListeners();
        $this->assertSame(401, $response->getStatusCode());
        $this->assertSame([], $queries);
        $this->assertSame([], $this->rendered);
        $this->assertStringContainsString('private', $response->getHeaderLine('Cache-Control'));
        $this->assertStringContainsString('no-store', $response->getHeaderLine('Cache-Control'));
        $this->assertSame('', $response->getHeaderLine('ETag'));
    }

    /**
     * EN: Query-string identity claims cannot switch the current user's list; results remain private and uncached.
     *
     * HR: Identitet u upitu ne može promijeniti popis prijavljenog korisnika; rezultati ostaju privatni bez cachea.
     */
    public function testForgedUserIdentifierCannotExposeAnotherUsersWork(): void
    {
        $this->fixture->html('My document');
        $this->fixture->html('Other users private title', author: 4);

        $request = (new Request('GET', 'https://example.test/my-work'))->withQueryParams([
            'user_id' => 4, 'actor_id' => 4, 'id' => 4, 'section' => 'recent', 'size' => 10,
        ]);
        $response = $this->controller->index($request);
        $this->assertSame(200, $response->getStatusCode());
        $this->assertSame('recent', $this->rendered['section']);
        $this->assertSame(['My document'], array_column($this->rendered['lists'][0]['items'], 'title'));
        $this->assertStringNotContainsString(
            'Other users private title',
            json_encode($this->rendered['lists'], JSON_THROW_ON_ERROR),
        );
        $this->assertSame('private, no-store, max-age=0', $response->getHeaderLine('Cache-Control'));
        $this->assertSame('Cookie, Accept-Language', $response->getHeaderLine('Vary'));
        $this->assertSame('', $response->getHeaderLine('ETag'));
        $this->assertSame('', $response->getHeaderLine('Last-Modified'));
    }

    /**
     * EN: Malformed sections, sizes, and old ordinal paging parameters cannot request unbounded or different lists.
     *
     * HR: Nevaljani odjeljci, veličine i stari redni parametri ne mogu tražiti neograničene ili druge popise.
     */
    public function testMalformedQueryKeepsAllowlistedSectionAndBoundedPageSize(): void
    {
        $this->fixture->html('My document');
        $request = (new Request('GET', 'https://example.test/my-work'))->withQueryParams([
            'section' => ['review'], 'size' => 1000000, 'html_start' => 2000, 'html_cursor' => ['forged'],
        ]);
        $response = $this->controller->index($request);
        $this->assertSame(200, $response->getStatusCode());
        $this->assertSame('recent', $this->rendered['section']);
        $this->assertSame(10, $this->rendered['pageSize']);
        $this->assertSame(['My document'], array_column($this->rendered['lists'][0]['items'], 'title'));
    }
}
