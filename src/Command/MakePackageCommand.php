<?php

namespace SunlightPackager\Command;

use Sunlight\Core;
use SunlightPackager\Builder\PackageBuilder;
use SunlightPackager\CmsFacade;

use function SunlightPackager\{fail,log};

class MakePackageCommand
{
    function run(): int
    {
        // parse arguments
        $opts = ArgumentParser::parse('r:o:');

        isset($opts['r']) or fail('Missing -r');

        $root = $opts['r'];
        $output = $opts['o'] ?? null;

        is_dir($root) or fail('Invalid -r: dir does not exist');

        // init cms
        (new CmsFacade())->init($root);

        // normalize output
        if ($output === null || ($output[-1] ?? null) === '/') {
            $output = ($output ?? '.') . sprintf(
                '/sunlight_cms_%s.zip',
                Core::getStability() === 'stable' ? 'latest' : Core::VERSION
            );
        }

        // create package
        log('Creating package');
        $package = (new PackageBuilder())->build();
        log('Total size: %dkB', intdiv($package->getSize(), 1000));

        // move package
        log('Moving package to "%s"', $output);
        $package->move($output) or fail('Could not move file');;

        log('Done');

        return 0;
    }
}
