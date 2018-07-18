<?php

use Sami\RemoteRepository\GitHubRemoteRepository;
use Sami\Sami;
use Symfony\Component\Finder\Finder;
use Symfony\Component\Process\Process;

return (new class() {
    public function buildSami() {
        $iterator = $this->getSourceIterator();
        $themeSettings = $this->getThemeSettings();
        $title = $this->getTitle();
        $version = $this->getLatestStableVersion();
        $remoteUrl = $this->getRemoteUrl();

        $buildDir = __DIR__ . '/build/doc';
        $cacheDir = __DIR__ . '/build/cache';

        $this->clearDirectories([$buildDir, $cacheDir]);
        $this->checkout($version);

        register_shutdown_function(function () {
            $this->checkout('-');
        });

        return new Sami($iterator, $themeSettings + [
            'title'                => $title,
            'version'              => $version,
            'build_dir'            => $buildDir,
            'cache_dir'            => $cacheDir,
            'remote_repository'    => new GitHubRemoteRepository($remoteUrl, __DIR__),
            'default_opened_level' => 2,
        ]);
    }

    private function getSourceIterator(): Traversable {
        return Finder::create()
            ->files()
            ->name('*.php')
            ->in(__DIR__ . '/src');
    }

    private function getThemeSettings(): array {
        $theme = getenv('SAMI_THEME');
        $settings = [];

        if ($theme) {
            $settings['theme'] = basename($theme);
            $settings['template_dirs'] = [dirname($theme)];
        }

        return $settings;
    }

    private function getTitle(): string {
        $readme = file_get_contents(__DIR__ . '/README.md');

        if (!preg_match('/^#([^#\r\n]++)#?(\R|$)/', $readme, $match)) {
            throw new RuntimeException('Could not parse a title from the README.md');
        }

        return trim($match[1]). ' API';
    }

    private function getLatestStableVersion(): string {
        $process = new Process('git tag', __DIR__);
        $process->mustRun();

        $tags = [];

        foreach (preg_split('/\R/', $process->getOutput()) as $tag) {
            if (preg_match('/^v?\d+\.\d+\.\d+$/', $tag)) {
                $tags[] = $tag;
            }
        }

        if (empty($tags)) {
            throw new RuntimeException('No stable versions exist to create documentation');
        }

        usort($tags, function ($a, $b) {
            return version_compare($a, $b);
        });

        return array_pop($tags);
    }

    private function getRemoteUrl(): string {
        $process = new Process('git remote get-url origin', __DIR__);
        $process->mustRun();

        $url = trim($process->getOutput());

        if (!preg_match('#^https://github.com/([^/.]++/[^/.]++)\.git#', $url, $match)) {
            throw new RuntimeException("The remote url '$url' for origin is not a valid github url");
        }

        return $match[1];
    }

    private function clearDirectories(array $paths) {
        foreach ($paths as $path) {
            if (file_exists($path)) {
                if (!is_dir($path)) {
                    throw new RuntimeException("The directory path '$path' is not a directory");
                }

                $process = new Process(['rm', '-rf', $path], __DIR__);
                $process->mustRun();
            }
        }
    }

    private function checkout(string $name) {
        $process = new Process('git status --porcelain --untracked-files=no', __DIR__);
        $process->mustRun();

        if (trim($process->getOutput())) {
            throw new RuntimeException("Cannot checkout '$name', because the directory is not clean");
        }

        $process = new Process(['git', 'checkout', $name], __DIR__);
        $process->mustRun();
    }
})->buildSami();
