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
        'wide' => 'container-fluid hph-container-wide',
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
    ? '<ul class="navbar-nav ms-' . $this->escape($fallbackBreakpoint) . '-auto">'
    . $fallbackAuthMenuHtml . '</ul>'
    : '';
    $fallbackCalendarHtml = $this->urlGenerator->namedRouteExists('calendar.index')
    ? '<li class="nav-item"><a class="nav-link" href="'
    . $this->escape($this->urlGenerator->getPathFor('calendar.index')) . '">'
    . $this->escape(__('Calendars')) . '</a></li>'
    : '';
    $renderedTopMenu = '<nav class="navbar navbar-expand-' . $this->escape($fallbackBreakpoint)
    . ' navbar-dark bg-dark hph-primary-navigation"><div class="' . $this->escape($fallbackContainer) . '">'
    . $fallbackBrandHtml
    . '<button class="navbar-toggler ms-auto" type="button" '
    . 'data-bs-toggle="offcanvas" data-bs-target="#navbarNav" aria-controls="navbarNav" '
    . 'aria-label="' . $this->escape($fallbackMobileLabel) . '">'
    . $fallbackMobileLabelHtml . '<span class="navbar-toggler-icon"></span></button>'
    . '<div class="offcanvas offcanvas-end hph-primary-navigation__drawer" tabindex="-1" '
    . 'id="navbarNav" aria-labelledby="navbarNavLabel">'
    . '<div class="offcanvas-header"><h2 class="offcanvas-title h5" id="navbarNavLabel">'
    . $this->escape($fallbackMobileLabel)
    . '</h2><button type="button" class="btn-close btn-close-white" data-bs-dismiss="offcanvas" '
    . 'aria-label="' . $this->escape(__('Close')) . '"></button></div>'
    . '<div class="offcanvas-body"><ul class="navbar-nav">'
    . '<li class="nav-item"><a class="nav-link" href="' . $this->escape($homePath) . '">'
    . $this->escape(__('Home')) . '</a></li>'
    . '<li class="nav-item"><a class="nav-link" href="'
    . $this->escape($this->urlGenerator->getPathFor('about')) . '">'
    . $this->escape(__('About')) . '</a></li>'
    . $fallbackCalendarHtml
    . '</ul>' . $fallbackAccountHtml . '</div></div></div></nav>';
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
$layoutInnerTitlePlacement = 'content-card';
$layoutInnerPageTitle = '';
$layoutHeroHasVisibleText = true;
$layoutTitleContext = isset($themeTitleContext) && is_scalar($themeTitleContext)
? strtolower(trim((string)$themeTitleContext))
: 'application';
if (!in_array($layoutTitleContext, ['application', 'integrated'], true)) {
    $layoutTitleContext = 'application';
}
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
$layoutHeroHasContext = $layoutHeroContext !== [];
if ($layoutHeroHasContext) {
    $layoutHeroContext['title_context'] = $layoutTitleContext;
}
if (
    $layoutThemeEnabled
    && method_exists($themeLayoutRenderer, 'titlePlacement')
) {
    $candidate = $themeLayoutRenderer->titlePlacement($layoutTitleContext);
    $layoutInnerTitlePlacement = is_string($candidate) ? $candidate : 'hero';
} elseif (
    $layoutThemeEnabled
    && method_exists($themeLayoutRenderer, 'innerTitlePlacement')
) {
    $candidate = $themeLayoutRenderer->innerTitlePlacement();
    $layoutInnerTitlePlacement = is_string($candidate) ? $candidate : 'hero';
}
if (
    !(bool)($layoutHeroContext['is_home'] ?? false)
    && is_scalar($layoutHeroContext['title'] ?? null)
) {
    $layoutInnerPageTitle = trim((string)$layoutHeroContext['title']);
}
if (
    $layoutThemeEnabled
    && $layoutHeroHasContext
    && method_exists($themeLayoutRenderer, 'heroHasVisibleText')
) {
    $candidate = $themeLayoutRenderer->heroHasVisibleText($layoutHeroContext);
    $layoutHeroHasVisibleText = is_bool($candidate) ? $candidate : true;
}
if (
    $layoutThemeEnabled
    && !$layoutHeroSuppressed
    && $layoutHeroHasContext
    && method_exists($themeLayoutRenderer, 'renderHero')
) {
    $heroNavigation = $layoutNavigationPlacement === 'hero' ? $renderedTopMenu : '';
    $candidate = $themeLayoutRenderer->renderHero($layoutHeroContext, $heroNavigation);
    $layoutHeroHtml = is_string($candidate) ? $candidate : '';
}
$layoutStandaloneNavigationHtml = $layoutNavigationPlacement === 'hero' && $layoutHeroHtml !== ''
? ''
: $renderedTopMenu;
$layoutDuplicateInnerTitle = in_array($layoutInnerTitlePlacement, ['content', 'content-card'], true)
? $layoutInnerPageTitle
: $layoutAutomaticInnerHeroTitle;
$layoutDuplicateInnerTitleJson = json_encode(
    $layoutDuplicateInnerTitle,
    JSON_HEX_TAG | JSON_HEX_AMP | JSON_HEX_APOS | JSON_HEX_QUOT,
);
if (!is_string($layoutDuplicateInnerTitleJson)) {
    $layoutDuplicateInnerTitleJson = '""';
}

/*
 * HR: Kada runtime tema nije aktivna, osnovni layout sam osigurava razmak između
 *     navigacije i sadržaja. Uključena tema razmak oblikuje kroz Hero i vlastiti CSS.
 * EN: When the runtime theme is inactive, the base layout provides spacing between
 *     navigation and content. An enabled theme controls that spacing through Hero and its CSS.
 */
$layoutMainClasses = 'container-fluid px-4' . ($layoutThemeEnabled ? '' : ' pt-3');
$layoutMainSurface = 'plain';
$layoutPageStageClasses = 'hph-page-stage';
if (
    $layoutThemeEnabled
    && method_exists($themeLayoutRenderer, 'mainContentPresentation')
) {
    $mainPresentation = $themeLayoutRenderer->mainContentPresentation(
        (bool)($layoutHeroContext['is_home'] ?? false),
        $layoutHeroHtml !== '',
        $layoutHeroHasVisibleText,
        $layoutTitleContext,
    );
    if (is_array($mainPresentation) && is_string($mainPresentation['classes'] ?? null)) {
        $layoutMainClasses = $mainPresentation['classes'];
        $layoutMainSurface = is_string($mainPresentation['surface'] ?? null)
        ? $mainPresentation['surface']
        : 'plain';
        $layoutPageStageClasses = is_string($mainPresentation['stage_classes'] ?? null)
        ? $mainPresentation['stage_classes']
        : 'hph-page-stage';
    }
}
?>

<!DOCTYPE html>
<html lang="<?= $this->translator->getLocale() ?>">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= isset($title) ? $this->escape(__($title)) : $this->escape($appName) ?></title>
    <link rel="icon"
          href="<?= $this->urlGenerator->getBasePath() ?>/theme/assets/library/simbioza/icon-natural-light.png"
          type="image/png">

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
        /*
         * HR: Posebni lijevi meni zauzima samo širinu koju traži njegov sadržaj.
         *     Glavni sadržaj preuzima sav preostali prostor i širi se kada se meni sakrije.
         * EN: The special left menu uses only the width required by its content.
         *     Main content receives the remaining space and expands when the menu is hidden.
         */
        .hph-route-left-layout {
            align-items: start;
            display: grid;
            gap: 1.5rem;
            grid-template-columns: fit-content(18rem) minmax(0, 1fr);
        }
        .hph-route-left-layout--menu-hidden {
            grid-template-columns: minmax(0, 1fr);
        }
        /*
         * HR: Tijekom zatvaranja stupac ostaje rezerviran, a uklanja se tek
         *     kada Bootstrap završi isti collapse prijelaz koji koristi stablo.
         * EN: During closing, the column remains reserved and is removed only
         *     after Bootstrap finishes the same collapse transition as the tree.
         */
        .hph-route-left-layout:has(.hph-route-left-layout__menu.collapse:not(.show)) {
            grid-template-columns: minmax(0, 1fr);
        }
        .hph-route-left-layout__menu {
            max-width: min(18rem, 32vw);
            min-width: 10rem;
            position: sticky;
            top: 1rem;
            width: max-content;
        }
        .hph-route-left-layout__menu .menu-route-sidebar {
            width: 100%;
        }
        .hph-route-left-layout__content {
            min-width: 0;
        }
        .hph-route-left-toggle-host {
            display: flex;
            margin-bottom: .75rem;
        }
        .hph-route-left-toggle {
            align-items: center;
            display: inline-flex;
            flex: 0 0 auto;
            height: 2rem;
            justify-content: center;
            margin-right: auto;
            opacity: .68;
            padding: 0;
            width: 2rem;
        }
        .hph-route-left-toggle:hover,
        .hph-route-left-toggle:focus-visible {
            opacity: 1;
        }
        .hph-route-left-toggle__icon {
            fill: none;
            height: 1rem;
            stroke: currentColor;
            stroke-linecap: round;
            stroke-linejoin: round;
            stroke-width: 2;
            width: 1rem;
        }
        .hph-route-left-mobile-header,
        .hph-route-left-backdrop {
            display: none;
        }
        .hph-route-left-layout:not(.hph-route-left-layout--ready) #workspace-page-tree {
            display: none;
        }
        @media (max-width: 991.98px) {
            .hph-route-left-layout {
                display: block;
            }
            .hph-route-left-toggle-host {
                display: none;
            }
            .hph-route-left-layout__menu {
                bottom: 0;
                display: block !important;
                left: 0;
                margin: 0;
                max-height: 100dvh;
                max-width: none;
                overflow: visible;
                padding: .75rem;
                position: fixed;
                top: 0;
                transform: translateX(-105%);
                transition: transform .2s ease, visibility .2s step-end;
                visibility: hidden;
                width: min(22rem, calc(100vw - 3rem));
                z-index: 1090;
            }
            .hph-route-left-layout__menu .menu-route-sidebar {
                box-shadow: 0 .75rem 2rem rgba(15, 23, 42, .28) !important;
                height: calc(100vh - 1.5rem);
                height: calc(100dvh - 1.5rem);
                overflow: hidden;
            }
            .hph-route-left-layout__menu .menu-route-sidebar > .card-body {
                height: 100%;
                overflow-y: auto;
                overscroll-behavior: contain;
            }
            .hph-route-left-layout__menu.hph-route-left-layout__menu--open {
                transform: translateX(0);
                transition: transform .2s ease, visibility 0s;
                visibility: visible;
            }
            .hph-route-left-mobile-header {
                align-items: center;
                display: flex;
                gap: 1rem;
                justify-content: space-between;
                margin-bottom: 1rem;
                min-height: 2.5rem;
            }
            .hph-route-left-desktop-title {
                display: none;
            }
            .hph-route-left-mobile-close {
                align-items: center;
                background: transparent;
                border: 0;
                color: inherit;
                display: inline-flex;
                font-size: 1.75rem;
                height: 2.5rem;
                justify-content: center;
                line-height: 1;
                padding: 0;
                width: 2.5rem;
            }
            .hph-route-left-toggle {
                background: var(--hph-card-bg, var(--bs-body-bg, #fff));
                border: 1px solid var(--hph-card-border, var(--bs-border-color));
                border-left: 0;
                border-radius: 0 .5rem .5rem 0;
                box-shadow: 0 .25rem .75rem rgba(15, 23, 42, .14);
                color: var(--hph-link, var(--hph-primary, var(--bs-primary)));
                height: 3rem;
                left: 0;
                margin: 0;
                opacity: .82;
                position: fixed;
                top: 43%;
                width: 2rem;
                z-index: 1040;
            }
            .hph-route-left-backdrop {
                background: rgba(15, 23, 42, .45);
                inset: 0;
                position: fixed;
                z-index: 1085;
            }
            .hph-route-left-backdrop:not([hidden]) {
                display: block;
            }
            body.hph-route-left-mobile-open {
                overflow: hidden;
            }
        }
        <?php if (!$layoutThemeEnabled) : ?>
        /*
         * HR: Osnovni layout čuva naslov i razmak kada Theme modul nije aktivan.
         *     Naslov premješten u stvarnu karticu sadržaja ne dobiva dodatni okvir.
         * EN: The base layout preserves the title and spacing when the Theme module
         *     is inactive. A title moved into the real content card gets no extra frame.
         */
        .hph-main-content__title {
            margin-bottom: 1.5rem;
        }
        .hph-main-content__title h1 {
            margin: 0;
        }
        .hph-main-content__title--inside-card {
            background: transparent;
            border: 0;
            box-shadow: none;
            margin: 0 0 1.25rem;
            padding: 0;
        }
        <?php endif; ?>
    </style>

    <?php // Render additional head content, if any. ?>
    <?= $this->renderPlaceholder('head') ?>
</head>
<body>
    <?= $layoutSkipLinkHtml ?>
    <?= $layoutHeaderHtml ?>
    <?= $layoutStandaloneNavigationHtml ?>
    <?php if ($layoutHeroHtml !== '') : ?>
        <?php
        /*
         * HR: Hero i sadržaj ostaju u zajedničkom sloju kako bi tema mogla napraviti
         *     stabilno preklapanje bez ovisnosti o visini sadržaja pojedine rute.
         * EN: Hero and content share one stage so the theme can provide stable overlap
         *     without depending on the content height of an individual route.
         */
        ?>
        <div class="<?= $this->escape($layoutPageStageClasses) ?>">
        <?= $layoutHeroHtml ?>
    <?php endif; ?>

    <main
        id="main-content"
        class="<?= $this->escape($layoutMainClasses) ?>"
        <?= $layoutTitleContext === 'application' && $renderedRouteLeftMenu === ''
        ? 'data-hph-content-title-scope'
        : '' ?>
    >
        <?php if (
            in_array($layoutInnerTitlePlacement, ['content', 'content-card'], true)
            && $layoutInnerPageTitle !== ''
) : ?>
            <header
                class="hph-main-content__title hph-title-card"
                data-hph-layout-title-container
                data-hph-layout-title-placement="<?= $this->escape($layoutInnerTitlePlacement) ?>"
                data-hph-main-surface="<?= $this->escape($layoutMainSurface) ?>"
            >
                <h1 data-hph-layout-title><?= $this->escape($layoutInnerPageTitle) ?></h1>
            </header>
        <?php endif; ?>

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
            <div class="hph-route-left-layout" data-hph-route-left-layout>
                <aside
                    id="hph-route-left-menu"
                    class="hph-route-left-layout__menu collapse show"
                    data-hph-route-left-panel
                >
            <?= $renderedRouteLeftMenu ?>
                </aside>
                <div
                    class="hph-route-left-layout__content"
                    data-hph-route-left-content
            <?= $layoutTitleContext === 'application' ? 'data-hph-content-title-scope' : '' ?>
                >
                    <div class="hph-route-left-toggle-host" data-hph-route-left-toggle-host>
                        <button
                            class="btn btn-outline-secondary btn-sm hph-route-left-toggle"
                            type="button"
                            data-hph-route-left-toggle
                            aria-controls="hph-route-left-menu"
                            aria-expanded="true"
                            title="<?= $this->escape(__('Show or hide special left menu')) ?>"
                            aria-label="<?= $this->escape(__('Show or hide special left menu')) ?>"
                        >
                            <svg
                                class="hph-route-left-toggle__icon"
                                viewBox="0 0 24 24"
                                aria-hidden="true"
                                focusable="false"
                            >
                                <rect x="3" y="4" width="18" height="16" rx="2"/>
                                <path d="M9 4v16M6 10l-2 2 2 2"/>
                            </svg>
                        </button>
                    </div>
            <?= $content ?>
                </div>
                <div class="hph-route-left-backdrop" data-hph-route-left-backdrop hidden></div>
            </div>
        <?php else : ?>
            <?= $content ?>
        <?php endif; ?>
    </main>

    <?php if ($layoutHeroHtml !== '') : ?>
        </div>
    <?php endif; ?>

    <?php if ($renderedRouteLeftMenu !== '') : ?>
        <script>
            (() => {
                const layout = document.querySelector('[data-hph-route-left-layout]');
                if (!(layout instanceof HTMLElement)) {
                    return;
                }

                const panel = layout.querySelector('[data-hph-route-left-panel]');
                const content = layout.querySelector('[data-hph-route-left-content]');
                const toggle = layout.querySelector('[data-hph-route-left-toggle]');
                const toggleHost = layout.querySelector('[data-hph-route-left-toggle-host]');
                const close = panel instanceof HTMLElement
                    ? panel.querySelector('[data-hph-route-left-close]')
                    : null;
                const backdrop = layout.querySelector('[data-hph-route-left-backdrop]');
                const mobileQuery = window.matchMedia('(max-width: 991.98px)');

                if (!(panel instanceof HTMLElement) || !(toggle instanceof HTMLButtonElement)) {
                    return;
                }

                const originalPanelParent = panel.parentElement;
                const originalPanelNextSibling = panel.nextSibling;
                const originalBackdropParent = backdrop instanceof HTMLElement
                    ? backdrop.parentElement
                    : null;

                /**
                 * HR: Na mobilnom prikazu premješta panel i njegovu pozadinu pod `body`
                 *     kako ih hero ili drugi tematski stacking context ne bi odrezao.
                 * EN: On mobile, portals the panel and its backdrop under `body` so the
                 *     Hero or another themed stacking context cannot clip them.
                 */
                const synchronizePortal = () => {
                    if (!(originalPanelParent instanceof HTMLElement)) {
                        return;
                    }

                    if (mobileQuery.matches) {
                        toggle.classList.remove('editor-html-view-action');
                        document.body.appendChild(toggle);
                        if (backdrop instanceof HTMLElement) {
                            document.body.appendChild(backdrop);
                        }
                        document.body.appendChild(panel);
                        return;
                    }

                    if (desktopToggleParent instanceof HTMLElement) {
                        if (actionRow instanceof HTMLElement) {
                            toggle.classList.add('editor-html-view-action');
                        }
                        desktopToggleParent.prepend(toggle);
                    }

                    if (
                        originalPanelNextSibling instanceof Node
                        && originalPanelNextSibling.parentNode === originalPanelParent
                    ) {
                        originalPanelParent.insertBefore(panel, originalPanelNextSibling);
                    } else {
                        originalPanelParent.prepend(panel);
                    }
                    if (
                        backdrop instanceof HTMLElement
                        && originalBackdropParent instanceof HTMLElement
                    ) {
                        originalBackdropParent.appendChild(backdrop);
                    }
                };

                /*
                 * HR: Sklopku premještamo na početak postojećeg reda akcija dokumenta.
                 *     Ako prikaz nema takav red, ostaje u vlastitom diskretnom retku.
                 * EN: Move the toggle to the start of the existing document action row.
                 *     If the view has no such row, it remains in its own discreet row.
                 */
                const actionRow = content instanceof HTMLElement
                    ? content.querySelector('.editor-html-view-actions')
                    : null;
                const desktopToggleParent = actionRow instanceof HTMLElement
                    ? actionRow
                    : toggleHost;
                if (actionRow instanceof HTMLElement) {
                    toggle.classList.add('editor-html-view-action');
                    actionRow.prepend(toggle);
                    if (toggleHost instanceof HTMLElement) {
                        toggleHost.hidden = true;
                    }
                }

                const workspaceTree = content instanceof HTMLElement
                    ? content.querySelector('#workspace-page-tree')
                    : null;
                const queryTree = new URLSearchParams(window.location.search).get('tree');
                const explicitlyShownTree = ['1', 'true', 'on', 'shown'].includes(
                    (queryTree || '').toLowerCase(),
                );

                /*
                 * HR: Posebni lijevi meni ima prednost nad zadanom postavkom područja,
                 *     ali izričit `tree=1` i postojeća ikona i dalje mogu otvoriti stablo.
                 * EN: The special left menu takes precedence over the Workspace default,
                 *     while explicit `tree=1` and the existing icon can still open the tree.
                 */
                if (workspaceTree instanceof HTMLElement) {
                    workspaceTree.classList.toggle('show', explicitlyShownTree);
                    content.querySelectorAll('[aria-controls="workspace-page-tree"]').forEach((control) => {
                        control.setAttribute('aria-expanded', explicitlyShownTree ? 'true' : 'false');
                    });
                }

                let desktopOpen = true;
                let mobileOpen = false;
                let desktopCollapse = null;

                /**
                 * HR: Bootstrap komponentu dohvaćamo tek kada korisnik klikne jer
                 *     se zajednički bundle učitava nakon ovog prikaza.
                 * EN: Resolve the Bootstrap component only when the user clicks
                 *     because the shared bundle loads after this view.
                 */
                const getDesktopCollapse = () => {
                    if (
                        !window.bootstrap
                        || typeof window.bootstrap.Collapse !== 'function'
                    ) {
                        return null;
                    }

                    if (!desktopCollapse) {
                        desktopCollapse = window.bootstrap.Collapse.getOrCreateInstance(
                            panel,
                            { toggle: false },
                        );
                    }

                    return desktopCollapse;
                };

                /*
                 * HR: Mobilni drawer zadržava bočni prijelaz. Desktop koristi
                 *     Bootstrapov vertikalni collapse od 350 ms kao stablo stranica.
                 * EN: The mobile drawer keeps its side transition. Desktop uses
                 *     Bootstrap's 350 ms vertical collapse like the page tree.
                 */
                const synchronizePanel = (focusPanel = false, animateDesktop = false) => {
                    synchronizePortal();
                    const open = mobileQuery.matches ? mobileOpen : desktopOpen;
                    panel.classList.toggle('hph-route-left-layout__menu--open', mobileQuery.matches && open);
                    toggle.setAttribute('aria-expanded', open ? 'true' : 'false');
                    if (backdrop instanceof HTMLElement) {
                        backdrop.hidden = !mobileQuery.matches || !open;
                    }
                    document.body.classList.toggle(
                        'hph-route-left-mobile-open',
                        mobileQuery.matches && open,
                    );

                    if (mobileQuery.matches) {
                        panel.hidden = false;
                        panel.inert = !open;
                        panel.toggleAttribute('aria-hidden', !open);
                    } else {
                        panel.hidden = false;
                        panel.classList.add('collapse');
                        if (animateDesktop) {
                            const collapse = getDesktopCollapse();
                            if (open) {
                                layout.classList.remove('hph-route-left-layout--menu-hidden');
                                panel.inert = false;
                                panel.removeAttribute('aria-hidden');
                                if (collapse) {
                                    collapse.show();
                                } else {
                                    panel.classList.add('show');
                                }
                            } else if (collapse) {
                                collapse.hide();
                            } else {
                                panel.classList.remove('show');
                                layout.classList.add('hph-route-left-layout--menu-hidden');
                                panel.inert = true;
                                panel.setAttribute('aria-hidden', 'true');
                            }
                        } else {
                            panel.classList.remove('collapsing');
                            panel.style.removeProperty('height');
                            panel.classList.toggle('show', open);
                            layout.classList.toggle('hph-route-left-layout--menu-hidden', !open);
                            panel.inert = !open;
                            panel.toggleAttribute('aria-hidden', !open);
                        }
                    }

                    if (focusPanel && mobileQuery.matches && open) {
                        const closeButton = panel.querySelector('[data-hph-route-left-close]');
                        if (closeButton instanceof HTMLElement) {
                            closeButton.focus({ preventScroll: true });
                        }
                    }
                };

                panel.addEventListener('show.bs.collapse', () => {
                    if (!mobileQuery.matches) {
                        desktopOpen = true;
                        layout.classList.remove('hph-route-left-layout--menu-hidden');
                        panel.inert = false;
                        panel.removeAttribute('aria-hidden');
                    }
                });
                panel.addEventListener('hidden.bs.collapse', () => {
                    if (!mobileQuery.matches) {
                        desktopOpen = false;
                        layout.classList.add('hph-route-left-layout--menu-hidden');
                        panel.inert = true;
                        panel.setAttribute('aria-hidden', 'true');
                    }
                });

                toggle.addEventListener('click', () => {
                    if (mobileQuery.matches) {
                        mobileOpen = !mobileOpen;
                    } else {
                        desktopOpen = !desktopOpen;
                    }
                    synchronizePanel(true, !mobileQuery.matches);
                });
                if (close instanceof HTMLElement) {
                    close.addEventListener('click', () => {
                        mobileOpen = false;
                        synchronizePanel();
                        toggle.focus({ preventScroll: true });
                    });
                }
                if (backdrop instanceof HTMLElement) {
                    backdrop.addEventListener('click', () => {
                        mobileOpen = false;
                        synchronizePanel();
                    });
                }
                document.addEventListener('keydown', (event) => {
                    if (event.key === 'Escape' && mobileOpen) {
                        mobileOpen = false;
                        synchronizePanel();
                        toggle.focus({ preventScroll: true });
                    }
                });
                mobileQuery.addEventListener('change', () => {
                    mobileOpen = false;
                    synchronizePanel();
                });

                layout.classList.add('hph-route-left-layout--ready');
                synchronizePanel();
            })();
        </script>
    <?php endif; ?>

    <?php if ($layoutInnerTitlePlacement === 'content-card' && $layoutInnerPageTitle !== '') : ?>
        <script>
            (() => {
                /*
                 * HR: Integrirani prikazi označavaju ciljnu karticu, dok obične aplikacijske
                 *     stranice cilj pronalaze preko izvornog H1 naslova. Ako cilj ne postoji,
                 *     naslov ostaje vidljiv kao sigurna zasebna kartica.
                 * EN: Integrated views mark their target card, while ordinary application
                 *     pages locate it through the original H1 heading. If no target exists,
                 *     the title remains visible as a safe standalone card.
                 */
                const titleContainer = document.querySelector('[data-hph-layout-title-container]');
                const explicitTarget = document.querySelector('[data-hph-content-title-target]');
                const applicationScope = document.querySelector('[data-hph-content-title-scope]');

                if (!(titleContainer instanceof HTMLElement)) {
                    return;
                }

                let contentTarget = explicitTarget instanceof HTMLElement ? explicitTarget : null;
                if (contentTarget === null && applicationScope instanceof HTMLElement) {
                    const expectedTitle = (
                        titleContainer.querySelector('[data-hph-layout-title]')?.textContent || ''
                    ).trim();
                    const headings = applicationScope.querySelectorAll(
                        'h1:not([data-hph-layout-title])',
                    );

                    for (const heading of headings) {
                        if ((heading.textContent || '').trim() !== expectedTitle) {
                            continue;
                        }

                        const cardBody = heading.closest('.card-body');
                        if (cardBody instanceof HTMLElement) {
                            contentTarget = cardBody;
                        }
                        break;
                    }
                }

                if (!(contentTarget instanceof HTMLElement)) {
                    return;
                }

                titleContainer.classList.add('hph-main-content__title--inside-card');
                contentTarget.prepend(titleContainer);
            })();
        </script>
    <?php endif; ?>

    <?php if ($layoutDuplicateInnerTitle !== '') : ?>
        <script>
            (() => {
                /*
                 * HR: Uklanjamo samo prvi sadržajni H1 čiji se tekst potpuno podudara
                 *     s automatskim hero naslovom; ostala dokumentna zaglavlja ostaju vidljiva.
                 * EN: Hide only the first content H1 whose text exactly matches the automatic
                 *     hero title; all other document headings remain visible.
                 */
                <?php // phpcs:ignore ?>
                const heroTitle = <?= $layoutDuplicateInnerTitleJson ?>;
                const headings = document.querySelectorAll(
                    '#main-content h1:not([data-hph-layout-title])',
                );

                for (const heading of headings) {
                    if ((heading.textContent || '').trim() !== heroTitle.trim()) {
                        continue;
                    }

                    if (heading.parentElement instanceof HTMLElement) {
                        heading.parentElement.classList.add('hph-page-heading-support');
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
            <p>&copy; <?= date('Y') ?> <?= __('Simbioza by HeartPhrame. All rights reserved.') ?></p>
        </div>
    </footer>

    <script>
        (() => {
            /*
             * HR: Bootstrap umeće pozadinu modala izravno u `body`. Svaki
             *     modal zato prije otvaranja premještamo na istu razinu, izvan
             *     hero i tematskih stacking contexta koji bi ga inače mogli
             *     ostaviti ispod vlastite pozadine i učiniti neklikabilnim.
             *
             * EN: Bootstrap inserts modal backdrops directly under `body`.
             *     Before a modal opens, move it to the same level and outside
             *     Hero or Theme stacking contexts that could otherwise leave
             *     it below its own backdrop and make it unclickable.
             */
            document.addEventListener('show.bs.modal', (event) => {
                const modal = event.target;
                if (modal instanceof HTMLElement && modal.parentElement !== document.body) {
                    document.body.appendChild(modal);
                }
            });
        })();
    </script>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.2.3/dist/js/bootstrap.bundle.min.js"></script>

    <?php // Render additional HTML content at the end, if any. ?>
    <?= $this->renderPlaceholder('tail') ?>
</body>
</html>
