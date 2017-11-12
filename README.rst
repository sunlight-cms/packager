Packager
########

Builds ZIP archives from the SunLight CMS 8.x repository intended for distribution.


Requirements
************

- PHP 5.4+
- SunLight CMS codebase (version 8.x)
- `Composer <https://getcomposer.org/>`_


Installation
************

1. download (or clone) this repository
2. run ``composer install``


Usage
*****

Building a package in the current directory
===========================================

.. code:: bash

   bin/make path/to/sunlight/cms/source


Building a package in a custom directory
========================================

.. code:: bash

   bin/make path/to/sunlight/cms/source path/to/output/directory
