<?php
/**
 * Linux for PHP/Linux for Composer
 *
 * Copyright 2017 - 2020 Foreach Code Factory <lfphp@asclinux.net>
 * Version 2.0.8
 *
 * Licensed under the Apache License, Version 2.0 (the "License");
 * you may not use this file except in compliance with the License.
 * You may obtain a copy of the License at
 *
 * http://www.apache.org/licenses/LICENSE-2.0
 *
 * Unless required by applicable law or agreed to in writing, software
 * distributed under the License is distributed on an "AS IS" BASIS,
 * WITHOUT WARRANTIES OR CONDITIONS OF ANY KIND, either express or implied.
 * See the License for the specific language governing permissions and
 * limitations under the License.
 *
 * @package    Linux for PHP/Linux for Composer
 * @copyright  Copyright 2017 - 2020 Foreach Code Factory <lfphp@asclinux.net>
 * @link       https://linuxforphp.net/
 * @license    Apache License, Version 2.0, see above
 * @license    http://www.apache.org/licenses/LICENSE-2.0
 * @since 0.9.8
 */

if (PHP_SAPI !== 'cli') {
    die('This is a CLI-based application only. Aborting...');
}

define('LFC_VERSION', '2.0.8');

$lfphpEnv = (bool) getenv('LFPHP') ?: false;

define('LFPHP', $lfphpEnv);

if (LFPHP) {
    $lfphpEnvVolsize = (string) getenv('LFPHP_VOLSIZE') ?: '10g';
    define('LFPHP_VOLSIZE', $lfphpEnvVolsize);
    $lfphpEnvMem = (string) getenv('LFPHP_MEM') ?: '1g';
    define('LFPHP_MEM', $lfphpEnvMem);
    $lfphpEnvSwap = (string) getenv('LFPHP_SWAP') ?: '2g';
    define('LFPHP_SWAP', $lfphpEnvSwap);
    $lfphpEnvShares = (int) getenv('LFPHP_SHARES') ?: '1024';
    define('LFPHP_SHARES', $lfphpEnvShares);
    $lfphpEnvPeriod = (int) getenv('LFPHP_PERIOD') ?: '100000';
    define('LFPHP_PERIOD', $lfphpEnvPeriod);
    $lfphpEnvQuota = (int) getenv('LFPHP_QUOTA') ?: '100000';
    define('LFPHP_QUOTA', $lfphpEnvQuota);
    $lfphpEnvAccount = (string) getenv('LFPHP_ACCOUNT') ?: '';
    define('LFPHP_ACCOUNT', $lfphpEnvAccount);
}

define('BASEDIR', getcwd());

$path = dirname(\Phar::running(false));

if (strlen($path) > 0) {
    define('PHARBASEDIR', $path);

    define('PHARFILENAMERET', \Phar::running());

    define('PHARFILENAME', $path . DIRECTORY_SEPARATOR . basename(PHARFILENAMERET));

    define(
        'COMPOSERJSONFILE',
        BASEDIR
        . DIRECTORY_SEPARATOR
        . 'composer.json'
    );

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
        'COMPOSERFOLDER',
        BASEDIR
        . DIRECTORY_SEPARATOR
        . 'vendor'
        . DIRECTORY_SEPARATOR
        . 'composer'
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

    define('PHARFILENAMERET', PHARBASEDIR);

    define('PHARFILENAME', PHARBASEDIR . DIRECTORY_SEPARATOR . basename(__FILE__));

    define(
        'COMPOSERJSONFILE',
        BASEDIR
        . DIRECTORY_SEPARATOR
        . 'composer.json'
    );

    define(
        'VENDORFOLDER',
        BASEDIR
        . DIRECTORY_SEPARATOR
        . 'vendor'
    );

    define('VENDORFOLDERPID', VENDORFOLDER);

    define(
        'COMPOSERFOLDER',
        BASEDIR
        . DIRECTORY_SEPARATOR
        . 'vendor'
        . DIRECTORY_SEPARATOR
        . 'composer'
    );

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

if (!file_exists(COMPOSERJSONFILE) && !file_exists(COMPOSERJSONFILE)) {
    echo PHP_EOL
        . 'We could not find the composer.json file!'
        . PHP_EOL
        . "Please change to the project's working directory and/or\ninstall Linux for Composer using Composer."
        . PHP_EOL
        . PHP_EOL;
    exit;
}

if (!file_exists(VENDORFOLDER) && !file_exists(VENDORFOLDERPID)) {
    echo PHP_EOL
        . 'We could not find the vendor folder!'
        . PHP_EOL
        . "Please change to the project's working directory and/or\ninstall Linux for Composer using Composer."
        . PHP_EOL
        . PHP_EOL;
    exit;
}

if (!file_exists(COMPOSERFOLDER) && !file_exists(COMPOSERFOLDER)) {
    echo PHP_EOL
        . 'We could not find the Composer folder!'
        . PHP_EOL
        . "Please change to the project's working directory and/or\ninstall Linux for Composer using Composer."
        . PHP_EOL
        . PHP_EOL;
    exit;
}

if (count($argv) === 1) {
    echo PHP_EOL
        . 'Missing command argument!'
        . PHP_EOL
        . 'To get help, please enter the "help" or "list" commands.'
        . PHP_EOL
        . PHP_EOL;
    exit;
}

require VENDORFOLDER
    . DIRECTORY_SEPARATOR
    .'autoload.php';

use Symfony\Component\Console\Application;
use Linuxforcomposer\Command\DockerParsejsonCommand;
use Linuxforcomposer\Command\DockerManageCommand;
use Linuxforcomposer\Command\DockerCommitCommand;
use Linuxforcomposer\Command\DockerRunCommand;

if (!file_exists(JSONFILE)) {
    if (copy(JSONFILEDIST, JSONFILE)) {
        require_once
            PHARFILENAMERET
            . DIRECTORY_SEPARATOR
            . 'bin'
            . DIRECTORY_SEPARATOR
            . 'lfcomposer-post-install.php';

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
            . 'Could not create the linuxforcomposer.json file!'
            . PHP_EOL
            . "Please verify your working directory's permissions."
            . PHP_EOL
            . PHP_EOL;
    }
}

$application = new Application('Linux for Composer', LFC_VERSION);

$application->add(new DockerParsejsonCommand());

$application->add(new DockerManageCommand());

$application->add(new DockerCommitCommand());

$dockerRunner = new DockerRunCommand();

$application->add($dockerRunner);

$application->setDefaultCommand($dockerRunner->getName());

$application->run();
