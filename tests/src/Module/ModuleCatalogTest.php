<?php

declare(strict_types=1);

namespace Tests\Module;

use App\Module\ModuleCatalog;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\TestCase;

/**
 * EN: Product profiles select only the common core and genuine feature dependencies.
 *
 * HR: Profili proizvoda biraju samo zajedničku jezgru i stvarne ovisnosti funkcije.
 */
#[CoversClass(ModuleCatalog::class)]
final class ModuleCatalogTest extends TestCase
{
    public function testSixCoreModulesAreRequiredAndComposerContainsNoOptionalFeatures(): void
    {
        $catalog = new ModuleCatalog();
        $this->assertSame(['orm', 'menu', 'theme', 'auth', 'audit', 'backup'], $catalog->requiredSlugs());
        $manifest = json_decode(
            (string)file_get_contents(dirname(__DIR__, 3) . '/composer.json'),
            true,
            512,
            JSON_THROW_ON_ERROR,
        );
        foreach ($catalog->definitions() as $slug => $definition) {
            if ($definition['optional']) {
                $this->assertArrayNotHasKey($definition['package'], $manifest['require'], $slug);
                $this->assertArrayHasKey(
                    $definition['package'],
                    $manifest['extra']['simbioza']['optional-modules'],
                    $slug,
                );
            } else {
                $this->assertArrayHasKey($definition['package'], $manifest['require'], $slug);
            }
        }
    }

    public function testProductSelectionsContainOnlyTheirNecessaryDependencies(): void
    {
        $catalog = new ModuleCatalog();
        $cases = [
            'web' => [['editor-html'], ['editor-html']],
            'calendar' => [['calendar'], ['calendar']],
            'wiki' => [
                ['workspace', 'workspace-search', 'simbioza-user'],
                ['notification', 'editor-html', 'workspace', 'workspace-search', 'simbioza-user'],
            ],
            'surveys' => [['survey'], ['editor-html', 'survey']],
            'web-tasks' => [['editor-html', 'task'], ['editor-html', 'task']],
            'web-comments' => [['editor-html', 'comment'], ['notification', 'editor-html', 'comment']],
            'web-calendar' => [['editor-html', 'calendar'], ['editor-html', 'calendar']],
            'wiki-surveys' => [
                ['workspace', 'workspace-search', 'simbioza-user', 'survey'],
                ['notification', 'editor-html', 'workspace', 'workspace-search', 'survey', 'simbioza-user'],
            ],
        ];
        foreach ($cases as $name => [$selection, $features]) {
            $slugs = array_map($catalog->slugForPackage(...), $catalog->packagesForSelection($selection));
            $optional = array_values(array_diff($slugs, $catalog->requiredSlugs()));
            $this->assertSame($features, $optional, $name);
            $this->assertSame($slugs, array_values(array_unique($slugs)), $name);
            foreach ($slugs as $slug) {
                foreach ($catalog->definitionFor($slug)['dependencies'] as $dependency) {
                    $this->assertContains($dependency, $slugs, $name . ':' . $slug);
                }
            }
        }
    }

    public function testInstallerPresetsMatchTheirDependencyResolvedSelections(): void
    {
        $catalog = new ModuleCatalog();
        foreach ($catalog->installationProfiles() as $key => $profile) {
            $features = $catalog->optionalSlugsForSelection($profile['modules']);
            $this->assertSame($features, $catalog->optionalSlugsForSelection($features), $key);
            $this->assertSame(
                $catalog->packagesForSelection($profile['modules']),
                $catalog->packagesForSelection($features),
            );
        }

        $this->assertSame(['editor-html', 'survey'], $catalog->optionalSlugsForSelection(['survey']));
    }
}
