<?php

namespace Sunlight\Packager;

use Composer\Autoload\ClassLoader;
use Sunlight\Core;

class Cli
{
    /** @var ClassLoader */
    private $classLoader;

    public function __construct(ClassLoader $classLoader)
    {
        $this->classLoader = $classLoader;
    }

    /**
     * @return int
     */
    public function run()
    {
        global $argc, $argv;

        // handle arguments
        if ($argc < 2 || $argc > 3) {
            $this->printUsage();

            return 1;
        }

        $sunlightRootDirectory = $argv[1];
        $outputDirectory = isset($argv[2]) ? $argv[2] : getcwd();

        if (!is_dir($sunlightRootDirectory)) {
            return $this->fail("SunLight root directory \"{$sunlightRootDirectory}\" does not exist or is not a directory");
        }

        if (!is_dir($outputDirectory) && !@mkdir($outputDirectory, 0777, true)) {
            return $this->fail("Output directory \"{$outputDirectory}\" does not exist and could not be created");
        }

        $sunlightRootDirectory = realpath($sunlightRootDirectory) or $this->fail('Failed to resolve SunLight root directory');
        $outputDirectory = realpath($outputDirectory) or $this->fail('Failed to resolve output directory');

        // initialize SunLight core
        require $sunlightRootDirectory . '/vendor/autoload.php';
        Core::init($sunlightRootDirectory . '/', ['minimal_mode' => true]);

        // create package
        $outputPath = $outputDirectory . '/sunlight_cms_' . str_replace('.', '', Core::VERSION) . '.zip';

        echo "Creating package\n";
        $package = (new PackageBuilder())->buildPackage();

        echo "Moving package to {$outputPath}\n";
        $package->move($outputPath);

        echo "Done\n";

        return 0;
    }

    private function printUsage()
    {
        echo "Usage: make <path-to-sunlight-root-directory> [path-to-output-directory]\n";
    }

    /**
     * @param string $message
     * @return int
     */
    private function fail($message)
    {
        fwrite(STDERR, "ERROR: {$message}\n");

        return 1;
    }
}
