<?php

namespace SunlightPackager\Command;

use function SunlightPackager\fail;

abstract class ArgumentParser
{
    /**
     * @param string[] $longOptions
     */
    static function parse(string $shortOptions, array $longOptions = []): array
    {
        global $argc;

        $opts = getopt($shortOptions, $longOptions, $rest);

        is_array($opts) or fail('Invalid arguments');
        $rest === $argc or fail('Too many arguments');

        return $opts;
    }
}
