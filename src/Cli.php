<?php

namespace Sunlight\Packager;

use Sunlight\Core;

class Cli
{
    function run(): int
    {
        // parse options
        $options = getopt('r:o:d:');

        if ($options === false || !isset($options['r'])) {
            $this->printUsage();

            return 1;
        }

        $distType = $options['d'] ?? 'STABLE';

        if (!in_array($distType, ['GIT', 'STABLE', 'BETA'], true)) {
            $this->fail('Invalid dist type');
        }

        // handle directories
        $sunlightRootDirectory = $options['r'];
        $outputDirectory = $options['o'] ?? getcwd();

        if (!is_dir($sunlightRootDirectory)) {
            $this->fail("SunLight root directory \"{$sunlightRootDirectory}\" does not exist or is not a directory");
        }

        if (!is_dir($outputDirectory) && !@mkdir($outputDirectory, 0777, true)) {
            $this->fail("Output directory \"{$outputDirectory}\" does not exist and could not be created");
        }

        $sunlightRootDirectory = realpath($sunlightRootDirectory) or $this->fail('Failed to resolve SunLight root directory');
        $outputDirectory = realpath($outputDirectory) or $this->fail('Failed to resolve output directory');

        // initialize SunLight core
        require $sunlightRootDirectory . '/system/bootstrap.php';
        Core::init(['minimal_mode' => true]);

        // create package
        $outputPath = sprintf(
            '%s/sunlight_cms_%s%s.zip',
            $outputDirectory,
            str_replace('.', '', Core::VERSION),
            $distType !== 'STABLE' ? '_' . strtolower($distType) : ''
        );

        echo "Creating package\n";
        $package = (new PackageBuilder($distType))->build();

        echo "Moving package to {$outputPath}\n";
        $package->move($outputPath);

        echo "Done\n";

        return 0;
    }

    private function printUsage()
    {
        echo <<<USAGE
Usage: {$_SERVER['PHP_SELF']} [-od] -r <sunlight-root-dir>

  -r    path to the sunlight root directory (required)
  -o    path to an output directory (defaults to current)
  -d    dist type (GIT / STABLE / BETA, defaults to STABLE)

USAGE;
    }

    /**
     * @throws \RuntimeException
     */
    private function fail(string $message)
    {
        throw new \RuntimeException($message);
    }
}
