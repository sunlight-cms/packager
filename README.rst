Packager
########

Builds ZIP archives from the SunLight CMS 8.x repository intended for distribution.


Requirements
************

- PHP 7.1+
- SunLight CMS codebase (version 8.x)
- `Composer <https://getcomposer.org/>`_


Installation
************

1. download (or clone) this repository
2. run ``composer install``


Usage
*****

::

    bin/make [-od] -r <sunlight-root-dir>

      -r    path to the sunlight root directory (required)
      -o    path to an output directory (defaults to current)
      -d    dist type (GIT / STABLE / BETA, defaults to STABLE)
