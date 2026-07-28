<?php

declare(strict_types=1);

/**
 * @var \HeartPhrame\View\View $this
 * @var ?string $title
 * @var string $content
 * @var ?\AaiEduHr\HeartPhrameModuleMenu\Service\MenuRenderer $menuRenderer
 * @var ?\AaiEduHr\HeartPhrameModuleTheme\Service\ThemeRenderer $themeRenderer
 * @var ?\AaiEduHr\HeartPhrameModuleTheme\Service\ThemeLayoutRenderer $themeLayoutRenderer
 * @var array<string, mixed>|false|null $themeHero
 */

$appName = $this->config->getAsNonEmptyString('app.name') ?? 'HeartPhrame';
$homePath = $this->urlGenerator->getPathFor('home');
$authFallbackPath = function (string $routeName, string $nextPath = ''): string {
    if (!$this->urlGenerator->namedRouteExists($routeName)) {
        return '';
    }

    $query = $nextPath !== '' ? ['next' => $nextPath] : [];

    try {
        return $this->urlGenerator->getPathFor($routeName, [], $query);
    } catch (Throwable) {
        return '';
    }
};
$authNextPath = '';
$requestUri = is_scalar($_SERVER['REQUEST_URI'] ?? null) ? trim((string)$_SERVER['REQUEST_URI']) : '';
if ($requestUri !== '' && str_starts_with($requestUri, '/') && !str_starts_with($requestUri, '//')) {
    $authNextPath = $requestUri;
}
$fallbackAuthMenuHtml = '';
$fallbackUser = $this->authnHandler->userData();
if (is_array($fallbackUser)) {
    $fallbackUserLabel = '';
    foreach (['display_name', 'email', 'login_identifier'] as $fallbackUserLabelKey) {
        $fallbackUserLabel = is_scalar($fallbackUser[$fallbackUserLabelKey] ?? null)
        ? trim((string)$fallbackUser[$fallbackUserLabelKey])
        : '';
        if ($fallbackUserLabel !== '') {
            break;
        }
    }
    $fallbackUserLabel = $fallbackUserLabel !== '' ? $fallbackUserLabel : __('Korisnik');
    $fallbackAuthItems = [];
    $fallbackHasAdminBlock = false;
    if ((bool)($fallbackUser['is_admin'] ?? false)) {
        $fallbackSettingsPath = $authFallbackPath('settings');
        if ($fallbackSettingsPath === '') {
            $fallbackSettingsPath = $authFallbackPath('auth.setup');
        }
        if ($fallbackSettingsPath !== '') {
            $fallbackAuthItems[] = '<li><span class="dropdown-header">'
            . $this->escape(__('Administracija')) . '</span></li>';
            $fallbackAuthItems[] = '<li><a class="dropdown-item" href="' . $this->escape($fallbackSettingsPath) . '">'
            . $this->escape(__('Postavke')) . '</a></li>';
            $fallbackHasAdminBlock = true;
        }
    }
    $fallbackProfilePath = $authFallbackPath('auth.account.profile');
    $fallbackPasswordPath = $authFallbackPath('auth.password.change');
    if ($fallbackProfilePath !== '' || $fallbackPasswordPath !== '') {
        if ($fallbackHasAdminBlock) {
            $fallbackAuthItems[] = '<li><hr class="dropdown-divider"></li>';
        }
        $fallbackAuthItems[] = '<li><span class="dropdown-header">'
        . $this->escape(__('Osobne postavke')) . '</span></li>';
        if ($fallbackProfilePath !== '') {
            $fallbackAuthItems[] = '<li><a class="dropdown-item" href="' . $this->escape($fallbackProfilePath) . '">'
            . $this->escape(__('Moj profil')) . '</a></li>';
        }
        if ($fallbackPasswordPath !== '') {
            $fallbackAuthItems[] = '<li><a class="dropdown-item" href="' . $this->escape($fallbackPasswordPath) . '">'
            . $this->escape(__('Promjena lozinke')) . '</a></li>';
        }
    }
    $fallbackLogoutPath = $authFallbackPath('auth.logout');
    if ($fallbackLogoutPath !== '') {
        if ($fallbackAuthItems !== []) {
            $fallbackAuthItems[] = '<li><hr class="dropdown-divider"></li>';
        }
        $fallbackAuthItems[] = '<li><a class="dropdown-item" href="' . $this->escape($fallbackLogoutPath) . '">'
        . $this->escape(__('Odjava')) . '</a></li>';
    }
    $fallbackIsImpersonating = false;
    if (is_object($this->authnHandler) && method_exists($this->authnHandler, 'isImpersonating')) {
        try {
            $fallbackIsImpersonating = (bool)$this->authnHandler->isImpersonating();
        } catch (Throwable) {
            $fallbackIsImpersonating = false;
        }
    }
    if ($fallbackIsImpersonating) {
        $fallbackStopImpersonationPath = $authFallbackPath('auth.impersonation.stop', $authNextPath);
        if ($fallbackStopImpersonationPath !== '') {
            if ($fallbackLogoutPath === '' && $fallbackAuthItems !== []) {
                $fallbackAuthItems[] = '<li><hr class="dropdown-divider"></li>';
            }
            $fallbackAuthItems[] = '<li><a class="dropdown-item" href="'
            . $this->escape($fallbackStopImpersonationPath) . '">'
            . $this->escape(__('Vrati administratorski račun')) . '</a></li>';
        }
    }
    if ($fallbackAuthItems !== []) {
        $fallbackAuthMenuHtml = '<li class="nav-item dropdown"><a class="nav-link dropdown-toggle" href="#" '
        . 'role="button" data-bs-toggle="dropdown" aria-expanded="false">'
        . $this->escape($fallbackUserLabel)
        . '</a><ul class="dropdown-menu dropdown-menu-end">'
        . implode('', $fallbackAuthItems)
        . '</ul></li>';
    }
} else {
    $fallbackLoginPath = $authFallbackPath('auth.login', $authNextPath);
    if ($fallbackLoginPath !== '') {
        $fallbackAuthMenuHtml = '<li class="nav-item"><a class="nav-link" href="'
        . $this->escape($fallbackLoginPath)
        . '">'
        . $this->escape(__('Prijava'))
        . '</a></li>';
    }
}

/*
 * HR: Globalne flash poruke pripremamo prije ispisa <head> elementa kako bi
 *     zajednički Auth partial mogao registrirati tematske CSS i JavaScript assete.
 * EN: Prepare global flash messages before writing the <head> element so the
 *     shared Auth partial can register its themed CSS and JavaScript assets.
 */
$layoutToastHtml = '';
$layoutMessages = $this->alertHandler->getAllAndForget();
if (is_array($layoutMessages) && $layoutMessages !== []) {
    $layoutToastMessages = [];
    foreach ($layoutMessages as $layoutMessage) {
        $layoutToastMessages[] = [
            'level' => $layoutMessage->level->value,
            'message' => $layoutMessage->message,
        ];
    }

    $layoutToastHtml = $this->forModulePartial(
        'aaieduhr/heartphrame-module-auth',
        'auth/toasts',
        ['toast_messages' => $layoutToastMessages, 'consume_alerts' => false],
    );
}

/*
 * HR: Strukturne dijelove pripremamo prije ispisa layouta. Theme i Menu moduli
 *     ostaju opcionalni: svaki od njih može nedostajati bez prekida prikaza.
 * EN: Structural parts are prepared before layout output. Theme and Menu modules
 *     remain optional: either may be absent without breaking the page.
 */
$layoutThemeEnabled = isset($themeLayoutRenderer)
&& is_object($themeLayoutRenderer)
&& method_exists($themeLayoutRenderer, 'isEnabled')
&& $themeLayoutRenderer->isEnabled();
$layoutSkipLinkHtml = '';
$layoutHeaderHtml = '';
$layoutHeroHtml = '';
$layoutNavigationPresentation = [];
$layoutNavigationPlacement = 'standalone';
if ($layoutThemeEnabled) {
    if (method_exists($themeLayoutRenderer, 'renderSkipLink')) {
        $candidate = $themeLayoutRenderer->renderSkipLink('main-content');
        $layoutSkipLinkHtml = is_string($candidate) ? $candidate : '';
    }
    if (method_exists($themeLayoutRenderer, 'navigationPresentation')) {
        $candidate = $themeLayoutRenderer->navigationPresentation();
        $layoutNavigationPresentation = is_array($candidate) ? $candidate : [];
    }
    if (method_exists($themeLayoutRenderer, 'navigationPlacement')) {
        $candidate = $themeLayoutRenderer->navigationPlacement();
        $layoutNavigationPlacement = is_string($candidate) ? $candidate : 'standalone';
    }
}

$renderedTopMenu = '';
$renderedRouteLeftMenu = '';
$menuConfigurationError = null;
$layoutLanguageControlHtml = '';
$layoutAccountControlHtml = $fallbackAuthMenuHtml;
if (
    isset($menuRenderer)
    && is_object($menuRenderer)
    && method_exists($menuRenderer, 'isEnabled')
    && method_exists($menuRenderer, 'renderTopMenu')
    && $menuRenderer->isEnabled()
) {
    try {
        try {
            $topMenuCandidate = $menuRenderer->renderTopMenu($layoutNavigationPresentation);
        } catch (\ArgumentCountError) {
            /*
             * HR: Tijekom lokalne nadogradnje stariji Menu može još imati metodu bez parametra.
             * EN: During a local upgrade, an older Menu may still expose a parameterless method.
             */
            $topMenuCandidate = $menuRenderer->renderTopMenu();
        }
        $renderedTopMenu = is_string($topMenuCandidate) ? $topMenuCandidate : '';
        if (method_exists($menuRenderer, 'renderRouteLeftMenu')) {
            $routeLeftMenuCandidate = $menuRenderer->renderRouteLeftMenu();
            $renderedRouteLeftMenu = is_string($routeLeftMenuCandidate) ? $routeLeftMenuCandidate : '';
        }
        if (method_exists($menuRenderer, 'renderLanguageControl')) {
            $candidate = $menuRenderer->renderLanguageControl();
            $layoutLanguageControlHtml = is_string($candidate) ? $candidate : '';
        }
        if (method_exists($menuRenderer, 'renderAccountControl')) {
            $candidate = $menuRenderer->renderAccountControl();
            $layoutAccountControlHtml = is_string($candidate) ? $candidate : $fallbackAuthMenuHtml;
        }
    } catch (\AaiEduHr\HeartPhrameModuleMenu\Exception\MenuConfigurationException $exception) {
        $menuConfigurationError = $exception->getMessage();
    }
}

/*
 * HR: Fallback navigacija postoji samo za instalacije bez menu modula.
 * EN: Fallback navigation exists only for installations without the menu module.
 */
if ($renderedTopMenu === '') {
    $fallbackShowBrand = (bool)($layoutNavigationPresentation['show_brand'] ?? true);
    $fallbackShowAccount = (bool)($layoutNavigationPresentation['show_account'] ?? true);
    $fallbackContainerValue = is_scalar($layoutNavigationPresentation['container'] ?? null)
    ? strtolower(trim((string)$layoutNavigationPresentation['container']))
    : 'fluid';
    $fallbackContainer = match ($fallbackContainerValue) {
        'contained' => 'container',
        'wide' => 'container-xl',
        'sm', 'md', 'lg', 'xl', 'xxl' => 'container-' . $fallbackContainerValue,
        default => 'container-fluid',
    };
    $fallbackBreakpoint = is_scalar($layoutNavigationPresentation['breakpoint'] ?? null)
    ? strtolower(trim((string)$layoutNavigationPresentation['breakpoint']))
    : 'lg';
    if (!in_array($fallbackBreakpoint, ['sm', 'md', 'lg', 'xl', 'xxl'], true)) {
        $fallbackBreakpoint = 'lg';
    }
    $fallbackMobileLabel = is_scalar($layoutNavigationPresentation['mobile_label'] ?? null)
    ? trim((string)$layoutNavigationPresentation['mobile_label'])
    : __('Menu');
    $fallbackMobileLabelHtml = (bool)($layoutNavigationPresentation['show_mobile_label'] ?? false)
    ? '<span class="me-2">' . $this->escape($fallbackMobileLabel) . '</span>'
    : '';
    $fallbackBrandHtml = $fallbackShowBrand
    ? '<a class="navbar-brand" href="' . $this->escape($homePath) . '">' . $this->escape($appName) . '</a>'
    : '';
    $fallbackAccountHtml = $fallbackShowAccount && $fallbackAuthMenuHtml !== ''
    ? '<ul class="navbar-nav ms-auto">' . $fallbackAuthMenuHtml . '</ul>'
    : '';
    $fallbackCalendarHtml = $this->urlGenerator->namedRouteExists('calendar.index')
    ? '<li class="nav-item"><a class="nav-link" href="'
    . $this->escape($this->urlGenerator->getPathFor('calendar.index')) . '">'
    . $this->escape(__('Calendars')) . '</a></li>'
    : '';
    $renderedTopMenu = '<nav class="navbar navbar-expand-' . $this->escape($fallbackBreakpoint)
    . ' navbar-dark bg-dark hph-primary-navigation"><div class="' . $this->escape($fallbackContainer) . '">'
    . $fallbackBrandHtml
    . '<button class="navbar-toggler" type="button" '
    . 'data-bs-toggle="collapse" data-bs-target="#navbarNav" aria-controls="navbarNav" '
    . 'aria-expanded="false" aria-label="' . $this->escape($fallbackMobileLabel) . '">'
    . $fallbackMobileLabelHtml . '<span class="navbar-toggler-icon"></span></button>'
    . '<div class="collapse navbar-collapse" id="navbarNav"><ul class="navbar-nav">'
    . '<li class="nav-item"><a class="nav-link" href="' . $this->escape($homePath) . '">'
    . $this->escape(__('Home')) . '</a></li>'
    . '<li class="nav-item"><a class="nav-link" href="'
    . $this->escape($this->urlGenerator->getPathFor('about')) . '">'
    . $this->escape(__('About')) . '</a></li>'
    . $fallbackCalendarHtml
    . '</ul>' . $fallbackAccountHtml . '</div></div></nav>';
}

if ($layoutThemeEnabled && method_exists($themeLayoutRenderer, 'renderHeader')) {
    $candidate = $themeLayoutRenderer->renderHeader([
        'language' => $layoutLanguageControlHtml,
        'account' => $layoutAccountControlHtml,
    ]);
    $layoutHeaderHtml = is_string($candidate) ? $candidate : '';
}

$layoutHeroSuppressed = isset($themeHero) && $themeHero === false;
$layoutHeroContext = isset($themeHero) && is_array($themeHero) ? $themeHero : [];
$layoutAutomaticInnerHeroTitle = '';
if (
    !$layoutHeroSuppressed
    && $layoutHeroContext === []
    && isset($title)
    && is_scalar($title)
    && trim((string)$title) !== ''
) {
    /*
     * HR: Unutarnje stranice automatski dobivaju hero iz naslova layouta. Kontroler
     *     i dalje može poslati bogatiji `$themeHero` ili vrijednost `false` za isključivanje.
     * EN: Inner pages automatically receive a hero from the layout title. A controller
     *     may still supply richer `$themeHero` data or `false` to suppress it.
     */
    $layoutHeroContext = [
        'is_home' => false,
        'title' => __((string)$title),
    ];
    $layoutAutomaticInnerHeroTitle = $layoutHeroContext['title'];
}
if (
    $layoutThemeEnabled
    && !$layoutHeroSuppressed
    && $layoutHeroContext !== []
    && method_exists($themeLayoutRenderer, 'renderHero')
) {
    $heroNavigation = $layoutNavigationPlacement === 'hero' ? $renderedTopMenu : '';
    $candidate = $themeLayoutRenderer->renderHero($layoutHeroContext, $heroNavigation);
    $layoutHeroHtml = is_string($candidate) ? $candidate : '';
}
$layoutStandaloneNavigationHtml = $layoutNavigationPlacement === 'hero' && $layoutHeroHtml !== ''
? ''
: $renderedTopMenu;
$layoutAutomaticInnerHeroTitleJson = json_encode(
    $layoutAutomaticInnerHeroTitle,
    JSON_HEX_TAG | JSON_HEX_AMP | JSON_HEX_APOS | JSON_HEX_QUOT,
);
if (!is_string($layoutAutomaticInnerHeroTitleJson)) {
    $layoutAutomaticInnerHeroTitleJson = '""';
}

/*
 * HR: Kada runtime tema nije aktivna, osnovni layout sam osigurava razmak između
 *     navigacije i sadržaja. Uključena tema razmak oblikuje kroz Hero i vlastiti CSS.
 * EN: When the runtime theme is inactive, the base layout provides spacing between
 *     navigation and content. An enabled theme controls that spacing through Hero and its CSS.
 */
$layoutMainClasses = 'container-fluid px-4' . ($layoutThemeEnabled ? '' : ' pt-3');
?>

<!DOCTYPE html>
<html lang="<?= $this->translator->getLocale() ?>">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= isset($title) ? $this->escape(__($title)) : $this->escape($appName) ?></title>
    <link rel="icon"
          href="<?= $this->urlGenerator->getBasePath() ?>/favicon.svg"
          type="image/svg+xml">

    <?php // phpcs:ignore ?>
    <link rel="stylesheet" href="<?= $this->urlGenerator->getBasePath() ?>/http_cdn.jsdelivr.net_npm_bootstrap@5.2.3_dist_css_bootstrap.css">

    <?php
    if (
        isset($themeRenderer)
        && is_object($themeRenderer)
        && method_exists($themeRenderer, 'isEnabled')
        && method_exists($themeRenderer, 'renderHead')
        && $themeRenderer->isEnabled()
    ) {
        echo $themeRenderer->renderHead(); // phpcs:ignore
    }
    ?>

    <style>
        body {
            padding-top: 0;
            padding-bottom: 2rem;
        }
        .navbar:not(.hph-primary-navigation) {
            margin-bottom: 2rem;
        }
    </style>

    <?php // Render additional head content, if any. ?>
    <?= $this->renderPlaceholder('head') ?>
</head>
<body>
    <?= $layoutSkipLinkHtml ?>
    <?= $layoutHeaderHtml ?>
    <?= $layoutStandaloneNavigationHtml ?>
    <?= $layoutHeroHtml ?>

    <main id="main-content" class="<?= $this->escape($layoutMainClasses) ?>">
        <?php if ($menuConfigurationError !== null) : ?>
            <div class="alert alert-warning" role="alert">
            <?= $this->escape($menuConfigurationError) ?>
            </div>
        <?php endif; ?>

        <?php if ($layoutToastHtml !== '') : ?>
            <?php
            // HR: Toast je pripremljen prije layouta, ali se vizualno prikazuje uz sadržaj.
            // EN: The toast is prepared before the layout but is visually rendered alongside content.
            ?>
            <?= $layoutToastHtml ?>
        <?php endif; ?>

        <?php if ($renderedRouteLeftMenu !== '') : ?>
            <div class="row g-4">
                <div class="col-lg-3">
            <?= $renderedRouteLeftMenu ?>
                </div>
                <div class="col-lg-9">
            <?= $content ?>
                </div>
            </div>
        <?php else : ?>
            <?= $content ?>
        <?php endif; ?>
    </main>

    <?php if ($layoutHeroHtml !== '' && $layoutAutomaticInnerHeroTitle !== '') : ?>
        <script>
            (() => {
                /*
                 * HR: Uklanjamo samo prvi sadržajni H1 čiji se tekst potpuno podudara
                 *     s automatskim hero naslovom; ostala dokumentna zaglavlja ostaju vidljiva.
                 * EN: Hide only the first content H1 whose text exactly matches the automatic
                 *     hero title; all other document headings remain visible.
                 */
                <?php // phpcs:ignore ?>
                const heroTitle = <?= $layoutAutomaticInnerHeroTitleJson ?>;
                const headings = document.querySelectorAll('#main-content h1');

                for (const heading of headings) {
                    if ((heading.textContent || '').trim() !== heroTitle.trim()) {
                        continue;
                    }

                    heading.hidden = true;
                    heading.setAttribute('data-hph-duplicate-hero-title', '');
                    break;
                }
            })();
        </script>
    <?php endif; ?>

    <footer class="mt-5 pt-3 border-top text-center text-muted">
        <div class="container-fluid">
            <p>&copy; <?= date('Y') ?> <?= __('HeartPhrame. All rights reserved.') ?></p>
        </div>
    </footer>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.2.3/dist/js/bootstrap.bundle.min.js"></script>

    <?php // Render additional HTML content at the end, if any. ?>
    <?= $this->renderPlaceholder('tail') ?>
</body>
</html>
