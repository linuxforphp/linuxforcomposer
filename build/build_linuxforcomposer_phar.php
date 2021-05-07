<?php
/**
 * Linux for PHP/Linux for Composer
 *
 * Copyright 2017 - 2021 Foreach Code Factory <lfphp@asclinux.net>
 * Version 2.0.9.2
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
 * @copyright  Copyright 2017 - 2021 Foreach Code Factory <lfphp@asclinux.net>
 * @link       https://linuxforphp.net/
 * @license    Apache License, Version 2.0, see above
 * @license    http://www.apache.org/licenses/LICENSE-2.0
 * @since 0.9.8
 */

define('BASEDIR', dirname(dirname(__FILE__)));
define('TARGET', BASEDIR . DIRECTORY_SEPARATOR . 'bin');
define('BUILDDIR', BASEDIR . DIRECTORY_SEPARATOR . 'build');
define('PHARFILE', 'linuxforcomposer.phar');
define('JSONFILE', 'linuxforcomposer.json');

// Will exclude everything under these directories
$exclude = [
    PHARFILE,
    'bin/linuxforcomposer.phar',
    'build',
    'data',
    'docs',
    'docs-api',
    'tests',
    '.gitattributes',
    '.gitignore',
    '.travis.yml',
    'composer.json',
    'composer.json.dev',
    'composer.lock',
    'composer.phar',
    'phpcs.xml',
    'phpdoc.xml',
    'phpunit.xml.dist',
    'README.md',
    '.git',
    '.idea',
];

/**
 * @param SplFileInfo $file
 * @param mixed $key
 * @param RecursiveCallbackFilterIterator $iterator
 * @return bool True if you need to recurse or if the item is acceptable
 */
$filter = function ($file, $key, $iterator) use ($exclude) {
    if ($iterator->hasChildren() && !in_array($file->getFilename(), $exclude)) {
        return true;
    }
    return $file->isFile() && !in_array($file->getFilename(), $exclude);
};

$innerIterator = new RecursiveDirectoryIterator(
    BASEDIR,
    RecursiveDirectoryIterator::SKIP_DOTS
);

$iterator = new RecursiveIteratorIterator(
    new RecursiveCallbackFilterIterator($innerIterator, $filter)
);

$phar = new \Phar(PHARFILE, 0, PHARFILE);
$phar->setSignatureAlgorithm(\Phar::SHA1);
$phar->startBuffering();
$phar->buildFromIterator($iterator, BASEDIR);
//$phar->setStub($phar->createDefaultStub('app.php'));
$phar->setStub(file_get_contents(BUILDDIR . DIRECTORY_SEPARATOR . 'stub.php'));
$phar->stopBuffering();

rename(BASEDIR . DIRECTORY_SEPARATOR . PHARFILE, TARGET . DIRECTORY_SEPARATOR . PHARFILE);