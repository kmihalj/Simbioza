<?php

declare(strict_types=1);

/**
 * @var \HeartPhrame\View\View $this
 * @var ?string $title
 * @var string $content
 * @var ?\AaiEduHr\HeartPhrameModuleMenu\Service\MenuRenderer $menuRenderer
 * @var ?\AaiEduHr\HeartPhrameModuleTheme\Service\ThemeRenderer $themeRenderer
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
        .navbar {
            margin-bottom: 2rem;
        }
    </style>

    <?php // Render additional head content, if any. ?>
    <?= $this->renderPlaceholder('head') ?>
</head>
<body>
    <?php
    $renderedTopMenu = '';
    $renderedRouteLeftMenu = '';
    $menuConfigurationError = null;
    if (
        isset($menuRenderer)
        && is_object($menuRenderer)
        && method_exists($menuRenderer, 'isEnabled')
        && method_exists($menuRenderer, 'renderTopMenu')
        && $menuRenderer->isEnabled()
    ) {
        try {
            $topMenuCandidate = $menuRenderer->renderTopMenu();
            $renderedTopMenu = is_string($topMenuCandidate) ? $topMenuCandidate : '';
            if (method_exists($menuRenderer, 'renderRouteLeftMenu')) {
                $routeLeftMenuCandidate = $menuRenderer->renderRouteLeftMenu();
                $renderedRouteLeftMenu = is_string($routeLeftMenuCandidate) ? $routeLeftMenuCandidate : '';
            }
        } catch (\AaiEduHr\HeartPhrameModuleMenu\Exception\MenuConfigurationException $exception) {
            $menuConfigurationError = $exception->getMessage();
        }
    }
    ?>
    <?php if ($renderedTopMenu !== '') : ?>
        <?= $renderedTopMenu ?>
    <?php else : ?>
        <nav class="navbar navbar-expand-lg navbar-dark bg-dark">
        <div class="container-fluid">
            <a class="navbar-brand" href="<?= $this->escape($homePath) ?>"><?= $this->escape($appName) ?></a>
            <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#navbarNav">
                <span class="navbar-toggler-icon"></span>
            </button>
            <div class="collapse navbar-collapse" id="navbarNav">
                <ul class="navbar-nav">
                    <li class="nav-item">
                        <a class="nav-link" href="<?= $this->urlGenerator->getPathFor('home') ?>">
        <?= $this->escape(__('Home')) ?>
                        </a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link" href="<?= $this->urlGenerator->getPathFor('about') ?>">
        <?= $this->escape(__('About')) ?>
                        </a>
                    </li>
        <?php if ($this->urlGenerator->namedRouteExists('calendar.index')) : ?>
                    <li class="nav-item">
                        <a class="nav-link" href="<?= $this->urlGenerator->getPathFor('calendar.index') ?>">
            <?= $this->escape(__('Calendars')) ?>
                        </a>
                    </li>
        <?php endif; ?>
                </ul>
        <?php if ($fallbackAuthMenuHtml !== '') : ?>
                    <ul class="navbar-nav ms-auto"><?= $fallbackAuthMenuHtml ?></ul>
        <?php endif; ?>
            </div>
        </div>
        </nav>
    <?php endif; ?>

    <div class="container-fluid px-4">
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
    </div>

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
