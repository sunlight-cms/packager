<?php

namespace SunlightPackager\Builder;

use Sunlight\Backup\Backup;
use Sunlight\Backup\BackupBuilder;

abstract class Builder extends BackupBuilder
{
    function __construct()
    {
        // no database dump
        $this->setDatabaseDumpEnabled(false);

        // no config prefilling
        $this->setPrefillConfigFile(false);
    }

    protected function write(Backup $backup): void
    {
        $backup->setMetadataPath(null);

        parent::write($backup);
    }
}
