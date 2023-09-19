<?php

namespace SunlightPackager\Command;

use Sunlight\Core;
use SunlightPackager\Builder\PatchBuilder;
use SunlightPackager\CmsFacade;

use function SunlightPackager\{fail,log,exec_formatted};

class MakePatchCommand
{
    function run(): int
    {
        // parse options
        $opts = getopt('r:o:d:', ['since:', 'until:', 'from:', 'to:', 'db-patch:', 'script:']);
        $opts !== false or fail('Invalid options');

        isset($opts['r']) or fail('Missing -r');

        $root = $opts['r'];
        $output = $opts['o'] ?? null;
        $distType = $options['d'] ?? 'STABLE';
        $since = $opts['since'] ?? null;
        $until = $opts['until'] ?? null;
        $from = $opts['from'] ?? null;
        $to = $opts['to'] ?? null;
        $databasePatchPath = $opts['db-patch'] ?? null;
        $patchScriptPath = $opts['script'] ?? null;

        is_dir($root) or fail('Invalid -r: dir does not exist');
        $databasePatchPath === null || is_file($databasePatchPath) or fail('Invalid --db-patch: file not found');
        $patchScriptPath === null || is_file($patchScriptPath) or fail('Invalid --script: file not found');

        // init cms
        (new CmsFacade())->init($root);

        // normalize since
        if ($since === null) {
            $tags = exec_formatted('cd %s && git tag --sort=%s', $root, '-v:refname');
            !empty($tags) or fail('No tags');
            $since = $tags[0];
        }

        // normalize until
        if ($until === null) {
            $until = 'HEAD';
        }

        // normalize from
        if ($from === null) {
            preg_match('{v\d+\.\d+\.\d+$}AD', $since) or fail('Cannot parse version from "%s", specify --from', $since);
            $from = substr($since, 1);
        }

        // normalize to
        if ($to === null) {
            $to = Core::VERSION;
        }

        // normalize output
        if ($output === null || ($output[-1] ?? null) === '/') {
            $output = ($output ?? './') . sprintf(
                '%s-%s%s.zip',
                $from,
                $to,
                $distType !== 'STABLE' ? "-{$distType}" : ''
            );
        }

        // get files
        $changedFiles = exec_formatted('cd %s && git diff --name-only %s %s', $root, $since, $until)
            or fail('No files changed');

        $files = [];
        $removedFiles = [];

        foreach ($changedFiles as $file) {
            if (is_file(SL_ROOT . '/' . $file)) {
                $files[] = $file;
            } else {
                $removedFiles[] = $file;
            }
        }

        log('Changed files: %d', count($files));
        log('Removed files: %d', count($removedFiles));

        // create patch
        log('Creating patch %s > %s', $from, $to);

        $builder = new PatchBuilder(
            $from,
            $to,
            $distType,
            $files,
            $removedFiles,
            $databasePatchPath,
            $patchScriptPath
        );

        $package = $builder->build();

        log('Moving patch to "%s"', $output);
        $package->move($output) or fail('Could not move file');

        log('Done');

        return 0;
    }
}
