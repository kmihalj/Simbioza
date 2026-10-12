<?php

declare(strict_types=1);

namespace Tests\Tools;

use PHPUnit\Framework\Attributes\CoversNothing;
use PHPUnit\Framework\TestCase;

#[CoversNothing]
final class LocalMatrixRepositoriesTest extends TestCase
{
    /**
     * EN: Minimal local sets must admit transitive development aliases without
     *     changing the production manifest or the framework repository.
     *
     * HR: Minimalni lokalni skupovi moraju dopustiti posredne razvojne aliase bez
     *     promjene produkcijskog manifesta ili frameworkova repozitorija.
     */
    public function testLocalModeAdmitsTransitiveModulesAndRetainsStablePreference(): void
    {
        require_once dirname(__DIR__, 3) . '/src/Tools/CleanInstallMatrix.php';
        $original = [
            'minimum-stability' => 'stable',
            'repositories' => [
                ['type' => 'vcs', 'url' => 'https://example.test/heartphrame-framework'],
                ['type' => 'vcs', 'url' => 'https://example.test/heartphrame-module-auth'],
            ],
        ];
        $workspace = sys_get_temp_dir() . '/simbioza-matrix-sources-' . bin2hex(random_bytes(8));
        mkdir($workspace, 0700);
        try {
            foreach (\HFClean\Tools\MATRIX_LOCAL_PACKAGE_DIRECTORIES as $package => $directory) {
                mkdir($workspace . '/' . $directory, 0700);
                file_put_contents($workspace . '/' . $directory . '/composer.json', json_encode(['name' => $package]));
            }

            $configured = \HFClean\Tools\withLocalMatrixRepositories($original, $workspace);
            $this->assertSame('stable', $original['minimum-stability']);
            $this->assertSame('dev', $configured['minimum-stability']);
            $this->assertTrue($configured['prefer-stable']);
            $this->assertCount(count(\HFClean\Tools\MATRIX_LOCAL_PACKAGE_DIRECTORIES) + 1, $configured['repositories']);
            $this->assertSame(
                $original['repositories'][0],
                $configured['repositories'][array_key_last($configured['repositories'])],
            );
            foreach (array_slice($configured['repositories'], 0, -1) as $repository) {
                $this->assertSame('path', $repository['type']);
                $this->assertTrue($repository['canonical']);
                $this->assertFileExists($repository['url'] . '/composer.json');
            }
        } finally {
            foreach (\HFClean\Tools\MATRIX_LOCAL_PACKAGE_DIRECTORIES as $directory) {
                unlink($workspace . '/' . $directory . '/composer.json');
                rmdir($workspace . '/' . $directory);
            }

            rmdir($workspace);
        }
    }
}
