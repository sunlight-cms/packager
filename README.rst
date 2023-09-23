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

    bin/make-package -r <root-dir> [options]

      -r        path to the sunlight root directory (required)
      -o        output .zip output path (default name: sunlight-cms-%version%.zip)

    bin/make-patch -r <root-dir> [options]

      -r        path to the sunlight root directory (required, must be a GIT repo)
      -o        output .zip path (default name: %from-%to%.zip)
      --since   starting tag or commit (defaults to newest version tag)
      --from    version this patch is for (defaults to --since tag if possible)
      --to      version this patch updates to (defaults to core version)
      --db      path to a .sql patch file (optional)
      --script  path to a .php patch script (optional)
