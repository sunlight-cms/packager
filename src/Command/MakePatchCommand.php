<?php

namespace SunlightPackager\Command;

use Sunlight\Core;
use Sunlight\Util\Json;
use Sunlight\Util\TemporaryFile;
use SunlightPackager\Builder\PatchBuilder;
use SunlightPackager\CmsFacade;

use function SunlightPackager\{fail,log,exec_formatted};

class MakePatchCommand
{
    function run(): int
    {
        // parse arguments
        $opts = ArgumentParser::parse('r:o:', ['since:', 'from:', 'to:', 'db:', 'script:']);

        isset($opts['r']) or fail('Missing -r');

        $root = $opts['r'];
        $output = $opts['o'] ?? null;
        $since = $opts['since'] ?? null;
        $from = $opts['from'] ?? null;
        $to = $opts['to'] ?? null;
        $databasePatchPath = $opts['db'] ?? null;
        $patchScriptPath = $opts['script'] ?? null;

        is_dir($root) or fail('Invalid -r: dir does not exist');
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
            $output = ($output ?? './') . sprintf('%s-%s.zip', $from, $to);
        }

        // get files
        $diff = exec_formatted('cd %s && git diff --name-only %s %s', $root, $since, 'HEAD')
            or fail('No files changed');

        log('Diff files: %d', count($diff));

        $changedFiles = [];
        $removedFiles = [];

        foreach ($diff as $file) {
            if (is_file(SL_ROOT . '/' . $file)) {
                $changedFiles[] = $file;
            } else {
                $removedFiles[] = $file;
            }
        }

        // create patch
        log('Creating patch %s > %s', $from, $to);

        $builder = new PatchBuilder(
            $from,
            $to,
            $changedFiles,
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

        $dirs = Json::decode($zip->getFromName('backup.json'))['directory_list'];
        $printedDirs = [];

        for ($i = 0; $i < $zip->numFiles; ++$i) {
            $stat = $zip->statIndex($i);

            // only print dir names for added directories, not individual files
            foreach ($dirs as $path) {
                $archivePath = 'data/' . $path . '/';
                if (strncmp($stat['name'], $archivePath, strlen($archivePath)) === 0) {
                    if (!isset($printedDirs[$path])) {
                        log('    %s...', $archivePath);
                        $printedDirs[$path] = true;
                    }

                    continue 2;
                }
            }

            // print file
            log('    %s', $stat['name']);
        }
    }
}
