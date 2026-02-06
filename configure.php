<?php

declare(strict_types=1);

// ============================================================================
// Helper Functions
// ============================================================================

function isInteractive(): bool
{
    return function_exists('posix_isatty') && posix_isatty(STDIN);
}

function shellExec(string $command): ?string
{
    $output = @shell_exec("$command 2>/dev/null");

    if ($output === null || $output === false) {
        return null;
    }

    $output = trim($output);

    return $output !== '' ? $output : null;
}

function gitConfig(string $key): ?string
{
    return shellExec("git config $key");
}

function githubUsername(): ?string
{
    return shellExec('gh api user --jq .login');
}

function studly(string $value): string
{
    return str_replace(' ', '', ucwords(str_replace(['-', '_'], ' ', $value)));
}

function loadComposer(string $path): array
{
    $contents = file_get_contents($path);

    if ($contents === false) {
        fwrite(STDERR, "Unable to read composer.json at $path.\n");
        exit(1);
    }

    try {
        return json_decode($contents, true, 512, JSON_THROW_ON_ERROR);
    } catch (JsonException $exception) {
        fwrite(STDERR, 'Invalid composer.json: '.$exception->getMessage()."\n");
        exit(1);
    }
}

function saveComposer(string $path, array $composer): void
{
    file_put_contents(
        $path,
        json_encode($composer, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES).PHP_EOL
    );
}

function replaceInFile(string $path, array $replacements): void
{
    if (! file_exists($path)) {
        return;
    }

    $contents = file_get_contents($path);

    if ($contents === false) {
        return;
    }

    $updated = str_replace(array_keys($replacements), array_values($replacements), $contents);

    if ($updated !== $contents) {
        file_put_contents($path, $updated);
    }
}

function prompt(string $label, string $default): string
{
    $hint = $default !== '' ? " [$default]" : '';
    $value = trim(readline("$label$hint: "));

    return $value !== '' ? $value : $default;
}

function promptRequired(string $label, string $default): string
{
    while (true) {
        $value = prompt($label, $default);

        if ($value !== '') {
            return $value;
        }

        fwrite(STDERR, "$label is required.\n");
    }
}

function promptLicense(string $currentLicense): string
{
    $defaultChoice = strtolower(trim($currentLicense)) === 'proprietary' ? '2' : '1';

    while (true) {
        $choice = trim(readline("License [1=MIT, 2=proprietary] ($defaultChoice): "));

        if ($choice === '') {
            $choice = $defaultChoice;
        }

        $license = match ($choice) {
            '1' => 'MIT',
            '2' => 'proprietary',
            default => null,
        };

        if ($license !== null) {
            return $license;
        }

        fwrite(STDERR, "Please enter 1 or 2.\n");
    }
}

// ============================================================================
// Workflow Functions
// ============================================================================

function gatherInputs(array $composer): array
{
    $currentPackage = $composer['name'] ?? 'vendor-slug/package-slug';
    $currentDescription = $composer['description'] ?? '';
    $currentLicense = is_string($composer['license'] ?? null) ? $composer['license'] : 'MIT';

    $defaultAuthorName = gitConfig('user.name') ?? '';

    $vendor = strtolower(promptRequired('Vendor', githubUsername() ?? ''));
    $package = strtolower(promptRequired('Package', basename(__DIR__)));

    $derivedNamespace = studly($vendor).'\\'.studly($package);
    $namespace = trim(prompt('Namespace', $derivedNamespace), '\\');

    $description = prompt('Description', $currentDescription);
    $authorName = prompt('Author Name', $defaultAuthorName);
    $authorEmail = prompt('Author Email', gitConfig('user.email') ?? '');
    $copyright = prompt('Copyright Holder', $defaultAuthorName);
    $license = promptLicense($currentLicense);

    return [
        'currentPackage' => $currentPackage,
        'vendor' => $vendor,
        'package' => $package,
        'newPackage' => "$vendor/$package",
        'namespace' => $namespace,
        'description' => $description,
        'authorName' => $authorName,
        'authorEmail' => $authorEmail,
        'copyright' => $copyright,
        'license' => $license,
    ];
}

function buildReplacements(array $inputs, array $composer): array
{
    $currentNamespace = array_key_first($composer['autoload']['psr-4'] ?? ['VendorName\\PackageName\\' => 'src/']);
    $currentTestNamespace = array_key_first($composer['autoload-dev']['psr-4'] ?? ['VendorName\\PackageName\\Tests\\' => 'tests/']);
    $currentProviderClass = $composer['extra']['laravel']['providers'][0] ?? $currentNamespace.'SkeletonLaravelServiceProvider';
    $currentProviderBase = substr($currentProviderClass, strrpos($currentProviderClass, '\\') + 1);

    $configFiles = glob(__DIR__.'/config/*.php');
    $currentConfigFile = $configFiles[0] ?? __DIR__.'/config/skeleton-laravel.php';
    $currentConfigKey = pathinfo($currentConfigFile, PATHINFO_FILENAME);

    $newBaseName = studly($inputs['package']);
    $newProviderBase = $newBaseName.'ServiceProvider';
    $newProviderClass = $inputs['namespace'].'\\'.$newProviderBase;
    $newConfigKey = $inputs['package'];
    $newConfigFile = __DIR__.'/config/'.$newConfigKey.'.php';

    $currentNamespaceBase = rtrim($currentNamespace, '\\');
    $currentTestNamespaceBase = rtrim($currentTestNamespace, '\\');
    $newNamespaceBase = rtrim($inputs['namespace'], '\\');

    $replacements = [
        $inputs['currentPackage'] => $inputs['newPackage'],
        $currentNamespaceBase.'\\' => $newNamespaceBase.'\\',
        $currentNamespaceBase => $newNamespaceBase,
        $currentTestNamespaceBase.'\\' => $newNamespaceBase.'\\Tests\\',
        $currentTestNamespaceBase => $newNamespaceBase.'\\Tests',
        $currentProviderClass => $newProviderClass,
        $currentProviderBase => $newProviderBase,
        $currentConfigKey.'-config' => $newConfigKey.'-config',
        "'$currentConfigKey'" => "'$newConfigKey'",
        $currentConfigKey.'.php' => $newConfigKey.'.php',
    ];

    return [
        'replacements' => $replacements,
        'currentProviderBase' => $currentProviderBase,
        'newProviderBase' => $newProviderBase,
        'newProviderClass' => $newProviderClass,
        'currentConfigFile' => $currentConfigFile,
        'newConfigFile' => $newConfigFile,
        'currentNamespace' => $currentNamespace,
        'currentTestNamespace' => $currentTestNamespace,
        'newNamespaceBase' => $newNamespaceBase,
    ];
}

function updateFiles(array $replacements, string $currentProviderBase, string $currentConfigFile): void
{
    $files = [
        __DIR__.'/src/'.$currentProviderBase.'.php',
        __DIR__.'/tests/TestCase.php',
        __DIR__.'/tests/Pest.php',
        $currentConfigFile,
        __DIR__.'/README.md',
    ];

    foreach ($files as $file) {
        replaceInFile($file, $replacements);
    }
}

function updateComposerJson(string $path, array $inputs, array $composer, array $replacementData): void
{
    $composer['name'] = $inputs['newPackage'];

    if ($inputs['description'] !== '') {
        $composer['description'] = $inputs['description'];
    }

    $composer['license'] = $inputs['license'];

    $author = array_filter([
        'name' => $inputs['authorName'],
        'email' => $inputs['authorEmail'],
    ], fn (string $value): bool => $value !== '');

    if ($author !== []) {
        $composer['authors'] = [$author];
    }

    unset($composer['autoload']['psr-4'][$replacementData['currentNamespace']]);
    $composer['autoload']['psr-4'][$replacementData['newNamespaceBase'].'\\'] = 'src/';

    unset($composer['autoload-dev']['psr-4'][$replacementData['currentTestNamespace']]);
    $composer['autoload-dev']['psr-4'][$replacementData['newNamespaceBase'].'\\Tests\\'] = 'tests/';

    $composer['extra']['laravel']['providers'] = [$replacementData['newProviderClass']];
    unset($composer['scripts']['configure']);

    saveComposer($path, $composer);
}

function renameFiles(array $replacementData): void
{
    $renames = [
        __DIR__.'/src/'.$replacementData['currentProviderBase'].'.php' => __DIR__.'/src/'.$replacementData['newProviderBase'].'.php',
        $replacementData['currentConfigFile'] => $replacementData['newConfigFile'],
    ];

    foreach ($renames as $from => $to) {
        if ($from !== $to && file_exists($from)) {
            rename($from, $to);
        }
    }
}

const MIT_LICENSE_TEMPLATE = <<<'TEXT'
The MIT License (MIT)

Copyright (c) <Year> <Copyright Holder>

Permission is hereby granted, free of charge, to any person obtaining a copy
of this software and associated documentation files (the "Software"), to deal
in the Software without restriction, including without limitation the rights
to use, copy, modify, merge, publish, distribute, sublicense, and/or sell
copies of the Software, and to permit persons to whom the Software is
furnished to do so, subject to the following conditions:

The above copyright notice and this permission notice shall be included in
all copies or substantial portions of the Software.

THE SOFTWARE IS PROVIDED "AS IS", WITHOUT WARRANTY OF ANY KIND, EXPRESS OR
IMPLIED, INCLUDING BUT NOT LIMITED TO THE WARRANTIES OF MERCHANTABILITY,
FITNESS FOR A PARTICULAR PURPOSE AND NONINFRINGEMENT. IN NO EVENT SHALL THE
AUTHORS OR COPYRIGHT HOLDERS BE LIABLE FOR ANY CLAIM, DAMAGES OR OTHER
LIABILITY, WHETHER IN AN ACTION OF CONTRACT, TORT OR OTHERWISE, ARISING FROM,
OUT OF OR IN CONNECTION WITH THE SOFTWARE OR THE USE OR OTHER DEALINGS IN
THE SOFTWARE.
TEXT;

const PROPRIETARY_LICENSE_TEMPLATE = <<<'TEXT'
Copyright (c) <Year> <Copyright Holder>
All rights reserved.

This software and associated documentation files (the "Software") are proprietary
and confidential.

You are granted a limited, non-exclusive, non-transferable license to use the
Software for your own internal purposes only.

You may not copy, modify, distribute, sublicense, sell, or make the Software
available to any third party.

Any unauthorized use terminates this license.
TEXT;

const AGENTS_TEMPLATE = <<<'TEXT'
# AI Guide

---

@AGENTS.local.md
TEXT;

function licenseTemplate(string $license): string
{
    return $license === 'MIT' ? MIT_LICENSE_TEMPLATE : PROPRIETARY_LICENSE_TEMPLATE;
}

function updateLicense(string $copyright, string $license): void
{
    $licensePath = __DIR__.'/LICENSE.md';

    if (! file_exists($licensePath)) {
        return;
    }

    $year = date('Y');
    $holder = $copyright ?: 'Copyright Holder';
    $template = licenseTemplate($license);
    $contents = str_replace(['<Year>', '<Copyright Holder>'], [$year, $holder], $template);

    file_put_contents($licensePath, $contents.PHP_EOL);
}

function updateAgentsGuide(): void
{
    $agentsPath = __DIR__.'/AGENTS.md';

    if (! file_exists($agentsPath)) {
        return;
    }

    file_put_contents($agentsPath, AGENTS_TEMPLATE.PHP_EOL);
}

function main(): void
{
    if (! isInteractive()) {
        fwrite(STDERR, "This script must be run interactively.\n");
        exit(1);
    }

    $composerPath = __DIR__.'/composer.json';
    $composer = loadComposer($composerPath);

    $inputs = gatherInputs($composer);
    $replacementData = buildReplacements($inputs, $composer);

    updateFiles($replacementData['replacements'], $replacementData['currentProviderBase'], $replacementData['currentConfigFile']);
    updateComposerJson($composerPath, $inputs, $composer, $replacementData);
    renameFiles($replacementData);
    updateLicense($inputs['copyright'], $inputs['license']);
    updateAgentsGuide();

    fwrite(STDOUT, 'Configured package '.$inputs['newPackage'].".\n");

    fwrite(STDOUT, "Deleting configure.php...\n");

    @unlink(__FILE__);
}

main();
