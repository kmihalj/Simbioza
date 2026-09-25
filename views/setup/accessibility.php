<?php

declare(strict_types=1);

/**
 * @var \HeartPhrame\View\View $this
 * @var string $title
 * @var array<string,mixed>|null $module
 * @var bool $stateChangesAllowed
 * @var string $actionPath
 * @var string $settingsMenuActiveSection
 * @var object|null $menuRenderer
 */

$settingsMenu = '';
if (isset($menuRenderer) && is_object($menuRenderer) && is_callable([$menuRenderer, 'renderSettingsMenu'])) {
    $rendered = $menuRenderer->renderSettingsMenu($settingsMenuActiveSection);
    $settingsMenu = is_string($rendered) ? $rendered : '';
}
$installed = $module !== null && $module['package_installed'];
$enabled = $installed && $module['enabled'];
?>
<div class="row g-4">
    <?php if ($settingsMenu !== '') : ?>
        <aside class="col-12 col-xl-3"><?= $settingsMenu ?></aside>
    <?php endif; ?>
    <div class="col-12 <?= $settingsMenu !== '' ? 'col-xl-9' : '' ?>">
        <section class="card shadow-sm">
            <div class="card-body">
                <h1 class="h3 mb-3"><?= $this->escape($title) ?></h1>
                <p class="mb-3">
                    <?php if (!$installed) : ?>
                        <?= $this->escape(__('Nije instaliran')) ?>
                    <?php elseif ($enabled) : ?>
                        <?= $this->escape(__('Modul je uključen.')) ?>
                    <?php else : ?>
                        <?= $this->escape(__('Modul je isključen; podaci su sačuvani.')) ?>
                    <?php endif; ?>
                </p>
                <?php if ($installed && $stateChangesAllowed) : ?>
                    <form method="post" action="<?= $this->escape($actionPath) ?>">
                    <?= $this->csrfHandler->generateCsrfTokenInputField() ?>
                        <input type="hidden" name="action" value="<?= $enabled ? 'disable' : 'enable' ?>">
                        <button type="submit" class="btn <?= $enabled ? 'btn-outline-danger' : 'btn-primary' ?>">
                    <?= $this->escape($enabled ? __('Isključi') : __('Uključi')) ?>
                        </button>
                    </form>
                <?php elseif ($installed) : ?>
                    <div class="alert alert-warning" role="status">
                    <?= $this->escape(__('Datoteka stanja modula nije zapisiva za web proces.')) ?>
                    </div>
                <?php endif; ?>
            </div>
        </section>
    </div>
</div>
