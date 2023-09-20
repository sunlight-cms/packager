<?php

namespace SunlightPackager\Command;

use Sunlight\Core;
use Sunlight\Util\TemporaryFile;
use SunlightPackager\Builder\PatchBuilder;
use SunlightPackager\CmsFacade;

use function SunlightPackager\{fail,log,exec_formatted};

class MakePatchCommand
{
    function run(): int
    {
        // parse arguments
        $opts = ArgumentParser::parse('r:o:d:', ['since:', 'until:', 'from:', 'to:', 'db:', 'script:']);

        isset($opts['r']) or fail('Missing -r');

        $root = $opts['r'];
        $output = $opts['o'] ?? null;
        $distType = mb_strtoupper($opts['d'] ?? 'STABLE');
        $since = $opts['since'] ?? null;
        $until = $opts['until'] ?? null;
        $from = $opts['from'] ?? null;
        $to = $opts['to'] ?? null;
        $databasePatchPath = $opts['db'] ?? null;
        $patchScriptPath = $opts['script'] ?? null;

        is_dir($root) or fail('Invalid -r: dir does not exist');
        in_array($distType, ['GIT', 'STABLE', 'BETA'], true) or fail('Invalid dist type');
        $databasePatchPath === null || is_file($databasePatchPath) or fail('Invalid --db: file not found');
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

        log('Changed files: %d', count($changedFiles));

        $files = [];
        $removedFiles = [];

        foreach ($changedFiles as $file) {
            if (is_file(SL_ROOT . '/' . $file)) {
                $files[] = $file;
            } else {
                $removedFiles[] = $file;
            }
        }

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

        $patch = $builder->build();

        $this->printPatchContents($patch);
        log('Total size: %dkB', intdiv($patch->getSize(), 1000));

        // move patch
        log('Moving patch to "%s"', $output);
        $patch->move($output) or fail('Could not move file');

        log('Done');

        return 0;
    }

    private function printPatchContents(TemporaryFile $patch): void
    {
        $zip = new \ZipArchive();
        $zip->open($patch->getPathname());

        $foundVendor = false;

        for ($i = 0; $i < $zip->numFiles; ++$i) {
            $stat = $zip->statIndex($i);

            if (strncmp($stat['name'], 'data/vendor/', 7) === 0) {
                if ($foundVendor) {
                    continue;
                }

                $foundVendor = true;
                log('    data/vendor/...');
                continue;
            }

            log('    %s', $stat['name']);
        }
    }
}
