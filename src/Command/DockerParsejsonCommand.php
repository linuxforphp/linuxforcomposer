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

namespace Linuxforcomposer\Command;

use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;

class DockerParsejsonCommand extends Command
{
    protected static $defaultName = 'docker:parsejson';

    public function __construct()
    {
        // you *must* call the parent constructor
        parent::__construct();
    }

    protected function configure()
    {
        $this
            ->setName('docker:parsejson')
            ->setDescription('Parse JSON file for Docker instructions.');
        $this
            // configure options
            ->addOption('jsonfile', null, InputOption::VALUE_REQUIRED, 'Use a custom JSON configuration file.');
    }

    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $jsonFile = ($input->getOption('jsonfile')) ?: null;

        if (($jsonFile === null || !file_exists($jsonFile)) && file_exists(JSONFILE)) {
            $jsonFile = JSONFILE;
        } elseif (($jsonFile === null || !file_exists($jsonFile)) && !file_exists(JSONFILE)) {
            $jsonFile = JSONFILEDIST;
        }

        $fileContentsJson = file_get_contents($jsonFile);

        $fileContentsArray = json_decode($fileContentsJson, true);

        if ($fileContentsArray === null) {
            return 1;
        }

        if (!isset($fileContentsArray['php-versions']) || empty($fileContentsArray['php-versions'])) {
            return 2;
        }

        if (is_array($fileContentsArray['php-versions'])
            && count($fileContentsArray['php-versions']) > 1
        ) {
            if (!in_array('detached', $fileContentsArray['modes'])) {
                $fileContentsArray['modes'][] = 'detached';
            }
        }

        if (!isset($fileContentsArray['modes'])
            || !in_array('detached', $fileContentsArray['modes'])
                && !in_array('interactive', $fileContentsArray['modes'])
                && !in_array('tty', $fileContentsArray['modes'])
        ) {
            $fileContentsArray['modes'][] = 'detached';
        }

        $i = 0;

        foreach ($fileContentsArray['php-versions'] as $phpversion) {
            $dockerManageCommand = 'php '
                . PHARFILENAME
                . ' docker:manage';

            if (in_array('detached', $fileContentsArray['modes'])) {
                $dockerManageCommand .= ' --detached';
            }

            if (in_array('interactive', $fileContentsArray['modes'])) {
                $dockerManageCommand .= ' --interactive';
            }

            if (in_array('tty', $fileContentsArray['modes'])) {
                $dockerManageCommand .= ' --tty';
            }

            $dockerManageCommand .= ' --phpversion ';

            $dockerManageCommand .= $phpversion;

            $threadsafe =
                isset($fileContentsArray['thread-safe']) && $fileContentsArray['thread-safe'] === 'true'
                    ? 'zts'
                    : 'nts';

            $dockerManageCommand .= ' --threadsafe ';

            $dockerManageCommand .= $threadsafe;

            if (isset($fileContentsArray['ports'])
                && is_array($fileContentsArray['ports'])
                && !empty($fileContentsArray['ports'])
            ) {
                foreach ($fileContentsArray['ports'] as $port) {
                    if (is_array($port) && count($port) >= 1) {
                        $portNumber =  isset($port[$i]) && !empty($port[$i]) ? $port[$i] : '';
                    } else {
                        if ($i === 0) {
                            $portNumber =  isset($port) && !empty($port) ? $port : '';
                        } else {
                            $portNumber = '';
                        }
                    }

                    if (!empty($portNumber)) {
                        $dockerManageCommand .= ' --port ';

                        $dockerManageCommand .= $portNumber;
                    }
                }
            }

            if (isset($fileContentsArray['volumes'])
                && is_array($fileContentsArray['volumes'])
                && !empty($fileContentsArray['volumes'])
            ) {
                foreach ($fileContentsArray['volumes'] as $volume) {
                    if (!empty($volume)) {
                        $dockerManageCommand .= ' --volume ';

                        $dockerManageCommand .= $volume;
                    }
                }
            }

            $script =
                isset($fileContentsArray['script']) && !empty($fileContentsArray['script'])
                    ? $fileContentsArray['script']
                    : 'lfphp';

            $dockerManageCommand .= ' --script ';

            $dockerManageCommand .= $script;

            $dockerManageCommand .= ' run';

            // outputs a message followed by a newline ("\n")
            $output->writeln($dockerManageCommand);

            $dockerManageCommand = '';

            $i++;
        }

        return 0;
    }
}
