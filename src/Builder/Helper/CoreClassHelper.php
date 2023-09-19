<?php

namespace SunlightPackager\Builder\Helper;

use Sunlight\Backup\Backup;

class CoreClassHelper
{
    function addCoreClass(Backup $backup, string $distType): void
    {
        $source = strtr(
            file_get_contents(SL_ROOT . 'system/class/Core.php'),
            ["const DIST = 'GIT';" => sprintf('const DIST = %s;', var_export($distType, true))]
        );

        $backup->getArchive()->addFromString("{$backup->getDataPath()}/system/class/Core.php", $source);
    }
}
