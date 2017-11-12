<?php

namespace Sunlight\Packager;

use Kuria\Cache\Util\TemporaryFile;
use Sunlight\Backup\Backup;
use Sunlight\Backup\BackupBuilder;
use Sunlight\Core;

class PackageBuilder extends BackupBuilder
{
    /**
     * @return TemporaryFile
     */
    public function buildPackage()
    {
        $excludedVendorRegexps = [
            'tests/',
            '.*\.(md|markdown|rst)$',
            'composer\.lock$',
            'phpunit\.xml\.dist$',
            'build\.properties$',
            'build\.xml$',
            '\.yml$',
            '\.git\w+$',
        ];

        $this->setDatabaseDumpEnabled(false);
        $this->excludePath('~^vendor/.*/(' . implode('|', $excludedVendorRegexps) . ')~i');
        $this->excludePath('~^vendor/bin/~');

        foreach ($this->getDynamicPathNames() as $dynamicPathName) {
            $this->makeDynamicPathOptional($dynamicPathName);
            $this->disableDynamicPath($dynamicPathName);
        }

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
        return strtr(
            file_get_contents($templatePath),
            [
                '@@@version@@@' => Core::VERSION,
                '@@year@@' => date('Y'),
            ]
        );
    }
}
