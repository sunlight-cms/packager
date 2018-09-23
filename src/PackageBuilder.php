<?php

namespace Sunlight\Packager;

use Kuria\Cache\Util\TemporaryFile;
use Sunlight\Backup\Backup;
use Sunlight\Backup\BackupBuilder;
use Sunlight\Core;

class PackageBuilder extends BackupBuilder
{
    /** @var string */
    private $distType;

    /**
     * @param string $distType
     */
    function __construct($distType)
    {
        $this->distType = $distType;
    }

    /**
     * @return TemporaryFile
     */
    function buildPackage()
    {
        // exclude useless files from vendors
        $excludedVendorPatterns = [
            'bin/*',
            'test/*',
            'tests/*',
            'ext/*',
            '_exts/*',
            'doc/*',
            'docs/*',
            '*.md',
            '*.markdown',
            '*.rst',
            '*CHANGELOG',
            '*UPGRADE',
            'composer.lock',
            'phpunit.xml.*',
            'build.properties',
            'build.xml',
            '*.yml*',
            '*.git*',
            '*.dist',
            '.*',
            '*/.*',
        ];

        $this->setDatabaseDumpEnabled(false);
        foreach ($excludedVendorPatterns as $vendorPattern) {
            $this->excludePath('vendor/*/' . $vendorPattern);
        }

        $this->excludePath('vendor/bin/*');

        // disable all dynamic paths
        foreach ($this->getDynamicPathNames() as $dynamicPathName) {
            $this->makeDynamicPathOptional($dynamicPathName);
            $this->disableDynamicPath($dynamicPathName);
        }

        // exclude generated paths
        $this->excludePath('system/class/Core.php');

        return $this->build(PackageBuilder::TYPE_FULL);
    }

    protected function writeFull(Backup $package)
    {
        parent::writeFull($package);

        $zip = $package->getArchive();

        $directories = [
            'install',
            'plugins/extend/codemirror',
            'plugins/extend/devkit',
            'plugins/extend/fancybox',
            'plugins/languages/cs',
            'plugins/languages/en',
            'plugins/templates/default',
            'plugins/templates/classic',
            'images/groupicons',
        ];

        $files = [
            'images/avatars/no-avatar.jpg',
            'images/avatars/no-avatar-dark.jpg',
        ];

        foreach ($directories as $directory) {
            $package->addDirectory($directory);
        }

        foreach ($files as $file) {
            $package->addFile($file, _root . $file);
        }

        $zip->deleteName($package::DATA_PATH . '/config.php');
        $zip->addFromString('README.html', $this->processReadmeTemplate(__DIR__ . '/Resource/README.tpl.html'));
        $zip->addFromString('CTIMNE.html', $this->processReadmeTemplate(__DIR__ . '/Resource/CTIMNE.tpl.html'));
        $zip->addFromString($package::DATA_PATH . '/system/class/Core.php', $this->getCoreClassSource());
    }

    protected function createBackup($path)
    {
        return new Package($path);
    }

    /**
     * @param string $templatePath
     * @return string
     */
    private function processReadmeTemplate($templatePath)
    {
        $version = Core::VERSION;

        if ($this->distType !== 'STABLE') {
            $version .= " ({$this->distType})";
        }

        return strtr(
            file_get_contents($templatePath),
            [
                '@@@version@@@' => $version,
                '@@@year@@@' => date('Y'),
            ]
        );
    }

    /**
     * @return string
     */
    private function getCoreClassSource()
    {
        return strtr(
            file_get_contents(_root . 'system/class/Core.php'),
            ["const DIST = 'GIT';" => sprintf('const DIST = %s;', var_export($this->distType, true))]
        );
    }
}
