.. _InstallationAnchor:

Installation
============

.. index:: Installation

Prerequisites
-------------

**Linux for Composer** runs in the default Docker environment of each supported platform (operating system).

* All platforms:
    * Docker (https://www.docker.com/)
    * Composer (https://getcomposer.org/)
    * Git (https://git-scm.com/)
    * cURL (https://curl.haxx.se/)
* Unix/Mac/Linux: Bash,
* Windows 10: PowerShell,
* Windows 8: Bash for Windows (Docker Toolbox).

Installation using Composer
---------------------------

To install the **Linux for Composer** package, you can simply run the following commands::

    $ composer require --dev linuxforphp/linuxforcomposer
    $ php vendor/bin/linuxforcomposer.phar

.. note:: On Windows, please use the PHAR file in the ``vendor/linuxforphp/linuxforcomposer/bin`` folder.

You can install **Linux for Composer** for your entire system by copying the binary in a folder that is in your **PATH**::

    $ cp vendor/linuxforphp/linuxforcomposer/bin/linuxforcomposer.phar /usr/local/bin/linuxforcomposer

You would then be able to invoke the binary directly from within the working directories of your PHP projects::

    $ cd /my/favorite/php/project
    $ linuxforcomposer docker:run start

Once installed, you will now be able to configure the ``linuxforcomposer.json`` file according to the specific needs of your project.
