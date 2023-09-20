<?php

namespace SunlightPackager;

use Sunlight\Core;

class CmsFacade
{
    function init(string $root): void
    {
        require $root . '/system/bootstrap.php';
        Core::init(['minimal_mode' => true, 'error_handler' => false, 'debug' => true]);
    }
}