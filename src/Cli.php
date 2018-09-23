<?php

namespace Sunlight\Packager;

use Sunlight\Core;

class Cli
{
    /**
     * @return int
     */
    function run()
    {
        // parse options
        $options = getopt('r:o:d:');

        if ($options === false || !isset($options['r'])) {
            $this->printUsage();

            return 1;
        }

        $distType = isset($options['d']) ? $options['d'] : 'STABLE';

        if (!in_array($distType, ['GIT', 'STABLE', 'BETA'], true)) {
            $this->fail('Invalid dist type');
        }

        // handle directories
        $sunlightRootDirectory = $options['r'];
        $outputDirectory = isset($options['o']) ? $options['o'] : getcwd();

        if (!is_dir($sunlightRootDirectory)) {
            $this->fail("SunLight root directory \"{$sunlightRootDirectory}\" does not exist or is not a directory");
        }

        if (!is_dir($outputDirectory) && !@mkdir($outputDirectory, 0777, true)) {
            $this->fail("Output directory \"{$outputDirectory}\" does not exist and could not be created");
        }

        $sunlightRootDirectory = realpath($sunlightRootDirectory) or $this->fail('Failed to resolve SunLight root directory');
        $outputDirectory = realpath($outputDirectory) or $this->fail('Failed to resolve output directory');

        // initialize SunLight core
        require $sunlightRootDirectory . '/vendor/autoload.php';
        Core::init($sunlightRootDirectory . '/', ['minimal_mode' => true]);

        // create package
        $outputPath = $outputDirectory . '/sunlight_cms_' . str_replace('.', '', Core::VERSION) . '.zip';

        echo "Creating package\n";
        $package = (new PackageBuilder($distType))->buildPackage();

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
     * @param string $message
     * @throws \RuntimeException
     */
    private function fail($message)
    {
        throw new \RuntimeException($message);
    }
}
