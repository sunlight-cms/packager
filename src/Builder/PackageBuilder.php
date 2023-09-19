<?php

namespace SunlightPackager\Builder;

use Sunlight\Backup\Backup;
use Sunlight\Core;
use SunlightPackager\Builder\Helper\CoreClassHelper;

use function SunlightPackager\render_template;

class PackageBuilder extends Builder
{
    /** @var string */
    private $distType;

    function __construct(string $distType)
    {
        parent::__construct();

        $this->distType = $distType;

        // remove dynamic paths
        foreach ($this->getDynamicPathNames() as $name) {
            $this->removeDynamicPath($name);
        }

        // exclude Core.php (added manually with modifications)
        $this->excludePath('system/class/Core.php');
    }

    protected function write(Backup $backup): void
    {
        // write configured contents
        $backup->setDataPath('cms');
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
            '@@@version@@@' => Core::VERSION . ($this->distType !== 'STABLE' ? " ({$this->distType})" : ''),
            '@@@build_date@@@' => date('Y-m-d'),
        ];

        $zip->addFromString('README.html', render_template('package/README.html', $readmeParams));
        $zip->addFromString('CTIMNE.html', render_template('package/CTIMNE.html', $readmeParams));

        // add core class
        (new CoreClassHelper())->addCoreClass($backup, $this->distType);
    }
}
