<?php

declare(strict_types=1);

namespace App\Controllers;

use App\Work\MyWorkService;
use App\Work\MyWorkValue;
use HeartPhrame\Authn\AuthnHandlerInterface;
use HeartPhrame\Http\ResponseFactory;
use HeartPhrame\Localization\TranslatorInterface;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;

/**
 * EN: Shows a signed-in user's authoring overview without accepting an impersonated URL identity.
 *
 * HR: Prikazuje autorski pregled prijavljenog korisnika bez prihvaćanja tuđeg identiteta iz URL-a.
 */
final readonly class MyWorkController
{
    /**
     * EN: Uses the current application session and preserves the normal site homepage.
     *
     * HR: Koristi aktualnu aplikacijsku sjednicu i zadržava uobičajenu naslovnicu sitea.
     */
    public function __construct(
        private MyWorkService $work,
        private AuthnHandlerInterface $auth,
        private ResponseFactory $responses,
        private TranslatorInterface $translator,
    ) {
    }

    /**
     * EN: Renders private no-store lists with one allowlisted section and bounded page sizes.
     *
     * HR: Prikazuje privatne nepredmemorirane popise s dopuštenim odjeljcima i ograničenim veličinama stranica.
     */
    public function index(ServerRequestInterface $request): ResponseInterface
    {
        $user = $this->auth->userData();
        if (!is_array($user) || !is_numeric($user['id'] ?? null) || (int)$user['id'] < 1) {
            return $this->responses->text(__('Za pristup je potrebna prijava.'), 401)
                ->withHeader('Cache-Control', 'private, no-store');
        }

        $user = MyWorkValue::named($user);
        $query = MyWorkValue::named($request->getQueryParams());
        $section = is_string($query['section'] ?? null)
        && in_array($query['section'], ['recent', 'drafts', 'review'], true)
        ? $query['section'] : 'recent';
        return $this->responses->view(
            'work/index',
            [
            'title' => __('Moj rad'), 'section' => $section, 'query' => $query,
            'pageSize' => $this->work->pageSize($query['size'] ?? null),
            'lists' => $this->work->lists($user, $this->translator->getLocale(), $section, $query),
            'themeTitleContext' => 'integrated',
            'themeHero' => ['title' => __('Moj rad'),
                'subtitle' => __('Vaši nedavni sadržaji, nacrti i sadržaji koji čekaju vaš pregled.')],
            ],
        )->withHeader('Cache-Control', 'private, no-store, max-age=0')
            ->withHeader('Vary', 'Cookie, Accept-Language');
    }
}
