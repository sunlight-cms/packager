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
        // parse opts
        $opts = getopt('r:o:d:');
        $opts !== false or fail('Invalid opts');

        isset($opts['r']) or fail('Missing -r');

        $root = $opts['r'];
        $output = $opts['o'] ?? null;
        $distType = $opts['d'] ?? 'STABLE';

        is_dir($root) or fail('Invalid -r: dir does not exist');
        in_array($distType, ['GIT', 'STABLE', 'BETA'], true) or fail('Invalid dist type');

        // init cms
        (new CmsFacade())->init($root);

        // normalize output
        if ($output === null || ($output[-1] ?? null) === '/') {
            $output = ($output ?? '.') . sprintf(
                '/sunlight_cms_%s.zip',
                $distType === 'STABLE' ? 'latest' : sprintf('%s_%s', str_replace('.', '', Core::VERSION), $distType)
            );
        }

        // create package
        log('Creating package');
        $package = (new PackageBuilder($distType))->build();

        log('Moving package to "%s"', $output);
        $package->move($output) or fail('Could not move file');;

        log('Done');

        return 0;
    }
}
