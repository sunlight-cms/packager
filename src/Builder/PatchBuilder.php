<?php

namespace SunlightPackager\Builder;

use Sunlight\Backup\Backup;
use Sunlight\Core;
use Sunlight\Util\Json;
use SunlightPackager\Builder\Helper\CoreClassHelper;

use function SunlightPackager\render_template;

class PatchBuilder extends Builder
{
    /** @var string */
    private $from;
    /** @var string */
    private $to;
    /** @var string */
    private $distType;
    /** @var string[] */
    private $files;
    /** @var string[] */
    private $removedFiles;
    /** @var string|null */
    private $databasePatchPath;
    /** @var string|null */
    private $patchScriptPath;

    function __construct(
        string $from,
        string $to,
        string $distType,
        array $files,
        array $removedFiles,
        ?string $databasePatchPath,
        ?string $patchScriptPath
    ) {
        parent::__construct();

        $this->from = $from;
        $this->to = $to;
        $this->distType = $distType;
        $this->files = $files;
        $this->removedFiles = $removedFiles;
        $this->databasePatchPath = $databasePatchPath;
        $this->patchScriptPath = $patchScriptPath;

        // remove default content
        $this->setFullBackup(false);

        foreach ($this->getDynamicPathNames() as $name) {
            $this->removeDynamicPath($name);
        }

        // add files
        $this->addDynamicPath('patch_files', $files);

        // add vendor if composer.json has changed
        if (in_array('composer.json', $files, true)) {
            $this->addDynamicPath('patch_vendor', ['vendor']);
        }
    }

    protected function write(Backup $backup): void
    {
        // write configured contents
        parent::write($backup);
        $zip = $backup->getArchive();

        // define metadata
        $metadata = [
            'system_version' => $this->from,
            'created_at' => time(),
            'directory_list' => [],
            'file_list' => $this->files,
            'db_prefix' => null,
            'patch' => [
                'new_system_version' => $this->to,
            ],
        ];

        // add vendor to metadata
        if ($this->hasDynamicPath('patch_vendor')) {
            $metadata['directory_list'][] = 'vendor';
        }

        // add removed files
        if (!empty($this->removedFiles)) {
            $metadata['patch']['files_to_remove'] = $this->removedFiles;
        }

        // add database patch
        if ($this->databasePatchPath !== null) {
            $metadata['db_prefix'] = 'sunlight_';
            $zip->addFile($this->databasePatchPath, $backup->getDbDumpPath());
        }

        // add patch script
        if ($this->patchScriptPath !== null) {
            $metadata['patch']['patch_scripts'] = ['patch_script.php'];
            $zip->addFile($this->patchScriptPath, 'patch_script.php');
        }

        // write metadata
        $zip->addFromString('backup.json', Json::encode($metadata));

        // add READMEs
        // TODO

        // add core class
        (new CoreClassHelper())->addCoreClass($backup, $this->distType);
    }
}
