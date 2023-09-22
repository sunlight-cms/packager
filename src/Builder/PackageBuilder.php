<?php

namespace SunlightPackager\Builder;

use Sunlight\Backup\Backup;
use Sunlight\Core;

use function SunlightPackager\render_template;

class PackageBuilder extends Builder
{
    function __construct()
    {
        parent::__construct();

        // remove dynamic paths
        foreach ($this->getDynamicPathNames() as $name) {
            $this->removeDynamicPath($name);
        }
    }

    protected function write(Backup $backup): void
    {
        // write configured contents
        $backup->setDataPath('cms');
        $backup->setMetadataPath(null); // no backup.json

        parent::write($backup);
        $zip = $backup->getArchive();

        // add extra directories
        $directories = [
            'install',
            'plugins/extend/codemirror',
            'plugins/extend/devkit',
            'plugins/extend/lightbox',
            'plugins/languages/cs',
            'plugins/languages/en',
            'plugins/templates/default',
            'plugins/templates/blank',
            'images/groupicons',
        ];

        foreach ($directories as $directory) {
            $backup->addDirectory($directory);
        }

        // remove auto-generated config.php
        $zip->deleteName("{$backup->getDataPath()}/config.php");

        // add READMEs
        $readmeParams = [
            '@@@version@@@' => Core::VERSION,
            '@@@build_date@@@' => date('Y-m-d'),
        ];

        $zip->addFromString('README.html', render_template('package/README.html', $readmeParams));
        $zip->addFromString('CTIMNE.html', render_template('package/CTIMNE.html', $readmeParams));
    }
}
