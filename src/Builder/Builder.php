<?php

namespace SunlightPackager\Builder;

use Sunlight\Backup\BackupBuilder;

abstract class Builder extends BackupBuilder
{
    function __construct()
    {
        $this->setDatabaseDumpEnabled(false);
        $this->setConfigFileEnabled(false);
    }
}
