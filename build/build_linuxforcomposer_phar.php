<?php

/**
 * Linux for PHP/Linux for Composer
 *
 * Copyright 2010 - 2018 A. Caya <andrewscaya@yahoo.ca>
 * Version 0.9.8
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

define('BASEDIR', dirname(dirname(__FILE__)));
define('TARGET', BASEDIR . DIRECTORY_SEPARATOR . 'bin');
define('BUILDDIR', BASEDIR . DIRECTORY_SEPARATOR . 'build');
define('PHARFILE', 'linuxforcomposer.phar');
define('JSONFILE', 'linuxforcomposer.json');

// Will exclude everything under these directories
$exclude = [
    PHARFILE,
    'bin',
    'build',
    'data',
    'docs',
    'docs-api',
    'tests',
    '.codeclimate.yml',
    '.gitattributes',
    '.gitignore',
    '.travis.yml',
    'composer.json',
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