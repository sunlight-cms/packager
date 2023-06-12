<?php

namespace Sunlight\Packager;

use Sunlight\Backup\Backup;
use Sunlight\Backup\BackupBuilder;
use Sunlight\Core;

class PackageBuilder extends BackupBuilder
{
    /** @var string */
    private $distType;

    function __construct(string $distType)
    {
        $this->distType = $distType;

        // no database dump
        $this->setDatabaseDumpEnabled(false);

        // no config prefilling
        $this->setPrefillConfigFile(false);

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

        foreach ($excludedVendorPatterns as $vendorPattern) {
            $this->excludePath('vendor/*/' . $vendorPattern);
        }

        $this->excludePath('vendor/bin/*');

        // disable all dynamic paths
        foreach ($this->getDynamicPathNames() as $dynamicPathName) {
            $this->makeDynamicPathOptionalInFullBackup($dynamicPathName);
            $this->disableDynamicPath($dynamicPathName);
        }

        // exclude generated paths
        $this->excludePath('system/class/Core.php');
    }

    protected function write(Backup $backup): void
    {
        $backup->setDataPath('cms');
        $backup->setMetadataPath(null);

        parent::write($backup);

        $directories = [
            'install',
            'plugins/extend/codemirror',
            'plugins/extend/devkit',
            'plugins/extend/lightbox',
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
            $backup->addDirectory($directory);
        }

        foreach ($files as $file) {
            $backup->addFile($file, SL_ROOT . $file);
        }

        $zip = $backup->getArchive();
        $zip->deleteName("{$backup->getDataPath()}/config.php");
        $zip->addFromString('README.html', $this->processReadmeTemplate(__DIR__ . '/Resource/README.tpl.html'));
        $zip->addFromString('CTIMNE.html', $this->processReadmeTemplate(__DIR__ . '/Resource/CTIMNE.tpl.html'));
        $zip->addFromString("{$backup->getDataPath()}/system/class/Core.php", $this->getCoreClassSource());
    }

    private function processReadmeTemplate(string $templatePath): string
    {
        $version = Core::VERSION;

        if ($this->distType !== 'STABLE') {
            $version .= " ({$this->distType})";
        }

        return strtr(
            file_get_contents($templatePath),
            [
                '@@@version@@@' => $version,
                '@@@build_date@@@' => date('Y-m-d'),
            ]
        );
    }

    private function getCoreClassSource(): string
    {
        return strtr(
            file_get_contents(SL_ROOT . 'system/class/Core.php'),
            ["const DIST = 'GIT';" => sprintf('const DIST = %s;', var_export($this->distType, true))]
        );
    }
}
