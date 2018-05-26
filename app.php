<?php

/**
 * Linux for PHP/Linux for Composer
 *
 * Copyright 2010 - 2018 A. Caya <andrewscaya@yahoo.ca>
 * Version 0.9.9
 *
 * This program is free software; you can redistribute it and/or modify
 * it under the terms of the GNU General Public License as published by
 * the Free Software Foundation; either version 2 of the License, or
 * (at your option) any later version.
 *
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU General Public License for more details.
 *
 * You should have received a copy of the GNU General Public License
 * along with this program; if not, write to the Free Software
 * Foundation, Inc., 51 Franklin Street, Fifth Floor, Boston,
 * MA 02110-1301, USA.
 *
 * @package    Linux for PHP/Linux for Composer
 * @copyright  Copyright 2010 - 2018 A. Caya <andrewscaya@yahoo.ca>
 * @link       http://linuxforphp.net/
 * @license    GNU/GPLv2, see above
 * @since 0.9.8
 */

if (PHP_SAPI !== 'cli') {
    die('This is a CLI-based application only. Aborting...');
}

define('BASEDIR', getcwd());

$path = dirname(\Phar::running(false));

if (strlen($path) > 0) {
    define('PHARBASEDIR', $path);

    define('PHARFILENAMERET', \Phar::running());

    define('PHARFILENAME', $path . DIRECTORY_SEPARATOR . basename(PHARFILENAMERET));

    define(
        'VENDORFOLDER',
        PHARFILENAMERET
        . DIRECTORY_SEPARATOR
        . 'vendor'
    );

    define(
        'VENDORFOLDERPID',
        BASEDIR
        . DIRECTORY_SEPARATOR
        . 'vendor'
    );

    define(
        'JSONFILEDIST',
        PHARFILENAMERET
        . DIRECTORY_SEPARATOR
        . 'linuxforcomposer.json'
    );

    define(
        'JSONFILE',
        BASEDIR
        . DIRECTORY_SEPARATOR
        . 'linuxforcomposer.json'
    );
} else {
    define('PHARBASEDIR', dirname(__FILE__));

    define('PHARFILENAME', PHARBASEDIR . DIRECTORY_SEPARATOR . basename(__FILE__));

    define(
        'VENDORFOLDER',
        BASEDIR
        . DIRECTORY_SEPARATOR
        . 'vendor'
    );

    define('VENDORFOLDERPID', VENDORFOLDER);

    define(
        'JSONFILEDIST',
        PHARBASEDIR
        . DIRECTORY_SEPARATOR
        . 'linuxforcomposer.json'
    );

    define(
        'JSONFILE',
        BASEDIR
        . DIRECTORY_SEPARATOR
        . 'linuxforcomposer.json'
    );
}

if (!file_exists(VENDORFOLDER) && !file_exists(VENDORFOLDERPID)) {
    echo 'Could not find the vendor folder!'
        . PHP_EOL
        . 'Please change to the project\'s working directory or install Linux for Composer using Composer.'
        . PHP_EOL
        . PHP_EOL;
    exit;
}

require VENDORFOLDER
    . DIRECTORY_SEPARATOR
    .'autoload.php';

use Symfony\Component\Console\Application;
use Linuxforcomposer\Command\DockerManageCommand;
use Linuxforcomposer\Command\DockerParsejsonCommand;
use Linuxforcomposer\Command\DockerRunCommand;

if (!file_exists(JSONFILE)) {
    if (copy(JSONFILEDIST, JSONFILE)) {
        echo PHP_EOL
            .'SUCCESS!'
            . PHP_EOL
            .'Linux for Composer has been initialized!'
            . PHP_EOL
            .'Please modify the linuxforcomposer.json file according to your needs.'
            . PHP_EOL
            . PHP_EOL;
        exit;
    } else {
        echo PHP_EOL
            . "Could not create the linuxforcomposer.json file! Please verify your working directory's permissions."
            . PHP_EOL
            . PHP_EOL;
    }
}

if ($argv[1] === 'docker:run'
    && $argv[2] === 'start'
    && file_exists(
        VENDORFOLDERPID
        . DIRECTORY_SEPARATOR
        . 'composer'
        . DIRECTORY_SEPARATOR
        . 'linuxforcomposer.pid'
)) {
    echo PHP_EOL
        . "Attention: before starting new containers, please enter the 'stop' command "
        . "in order to shut down the current containers properly."
        . PHP_EOL
        . PHP_EOL;
    exit;
}

$application = new Application();

$application->add(new DockerParsejsonCommand());

$application->add(new DockerManageCommand());

$dockerRunner = new DockerRunCommand();

$application->add($dockerRunner);

$application->setDefaultCommand($dockerRunner->getName());

$application->run();
