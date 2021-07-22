# [![Linux for PHP Banner](docs/images/logo.png)](https://linuxforphp.net/)
# Linux for Composer

https://linuxforphp.net

Composer package that helps to easily configure and run Linux for PHP containers for any PHP project.

[![codecov](https://codecov.io/gh/linuxforphp/linuxforcomposer/branch/master/graph/badge.svg?token=SD9QOT2AJG)](https://codecov.io/gh/linuxforphp/linuxforcomposer)
[![Documentation Status](https://readthedocs.org/projects/linux-for-composer/badge/?version=latest)](https://linux-for-composer.readthedocs.io/en/latest/?badge=latest)
[![PDS Skeleton](https://img.shields.io/badge/pds-skeleton-blue.svg?style=flat-square)](https://github.com/php-pds/skeleton)

To install this library, please enter the following command:
```bash
composer require linuxforphp/linuxforcomposer
```

Once the dependencies are installed, you can create the linuxforcomposer.json file using:
```bash
php vendor/bin/linuxforcomposer.phar
```

NOTE: On Windows, please use the Linux for Composer PHAR file in the 'vendor/linuxforphp/linuxforcomposer/bin' folder.

Then, you only have to enter the following command to run the Linux for PHP containers that you have configured in the JSON file.
```bash
php vendor/bin/linuxforcomposer.phar docker:run start
```

If you wish to install the Linux for Composer binary for your entire system, please copy the PHAR file to a folder included in your PATH:
```bash
cp vendor/linuxforphp/linuxforcomposer/bin/linuxforcomposer.phar /usr/local/bin/linuxforcomposer
```

You will then be able to invoke the binary directly:
```bash
cd /folder/of/my/favorite/project
linuxforcomposer docker:run start
```

For more information on configuring and using Linux for Composer, please visit this page:

https://linuxforphp.net/documentation

Have a lot of fun! :)

PLEASE NOTE: As long as you have [Docker](https://www.docker.com/), [Composer](https://getcomposer.org/), [Git](https://git-scm.com/) and [cURL](https://curl.haxx.se/) installed on your computer, this library should work fine.
If on Windows, make sure you are using the linuxforcomposer.phar file contained in the 'vendor/linuxforphp/linuxforcomposer/bin' folder.

Enjoy!
