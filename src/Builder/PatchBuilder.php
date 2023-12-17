<?php

namespace SunlightPackager\Builder;

use Sunlight\Backup\Backup;
use Sunlight\Plugin\Plugin;

use function SunlightPackager\render_template;

class PatchBuilder extends Builder
{
    /** @var string */
    private $from;
    /** @var string */
    private $to;
    /** @var string[] */
    private $removedFiles;
    /** @var string|null */
    private $databasePatchPath;
    /** @var string|null */
    private $patchScriptPath;

    function __construct(
        string $from,
        string $to,
        array $changedFiles,
        array $removedFiles,
        ?string $databasePatchPath,
        ?string $patchScriptPath
    ) {
        parent::__construct();

        $this->from = $from;
        $this->to = $to;
        $this->removedFiles = $removedFiles;
        $this->databasePatchPath = $databasePatchPath;
        $this->patchScriptPath = $patchScriptPath;

        // remove default content
        $this->setFullBackup(false);

        foreach ($this->getDynamicPathNames() as $name) {
            $this->removeDynamicPath($name);
        }

        // filter out plugins from changed files
        $plugins = [];
        $changedFiles = array_filter($changedFiles, function (string $file) use (&$plugins) {
            if (preg_match('{plugins/(\w+)/(' . Plugin::NAME_PATTERN . ')/}A', $file, $match)) {
                if ($match[1] !== 'template') {
                    $plugins[$match[1]][$match[2]] = true;
                }

                return false;
            }

            return true;
        });

        // add changed files
        $this->addDynamicPath('patch_files', $changedFiles);

        // add plugins
        foreach ($plugins as $type => $plugins) {
            foreach ($plugins as $id => $_) {
                $this->addDynamicPath("plugin_{$type}_{$id}", ["plugins/{$type}/{$id}"]);
            }
        }

        // add vendor if composer.json has changed
        if (in_array('composer.json', $changedFiles, true)) {
            $this->addDynamicPath('patch_vendor', ['vendor']);
        }

        // exclude some paths
        $excludedPaths = [
            'images/*',
            'install/*',
            'plugins/*/DISABLED',
            'plugins/*/config.php',
            'upload/*',
            '.*', // .htaccess, .gitignore, etc.
            'config.php.dist',
            'robots.txt',
            'favicon.ico',
            'README.rst',
            'SECURITY.rst',
        ];

        foreach ($excludedPaths as $path) {
            $this->excludePath($path);
        }
    }

    protected function write(Backup $backup): void
    {
        // write configured contents
        parent::write($backup);
        $zip = $backup->getArchive();

        // prepare metadata
        $metadata = [
            'system_version' => $this->from,
            'patch' => [
                'new_system_version' => $this->to,
            ],
        ];

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

        // set metadata factory
        $backup->setMetadataFactory(function (array $defaultMetadata) use ($metadata) {
            return array_replace($defaultMetadata, $metadata);
        });

        // add READMEs
        $readmeParams = [
            '@@@from_version@@@' => $this->from,
            '@@@to_version@@@' => $this->to,
            '@@@build_date@@@' => date('Y-m-d'),
        ];

        $zip->addFromString('README.txt', render_template('patch/README.txt', $readmeParams));
        $zip->addFromString('CTIMNE.txt', render_template('patch/CTIMNE.txt', $readmeParams));
    }
}
