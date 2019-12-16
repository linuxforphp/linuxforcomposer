<?php
/**
 * Linux for PHP/Linux for Composer
 *
 * Copyright 2010 - 2019 Foreach Code Factory <lfphp@asclinux.net>
 * Version 1.0.2
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
 * @copyright  Copyright 2010 - 2019 Foreach Code Factory <lfphp@asclinux.net>
 * @link       http://linuxforphp.net/
 * @license    Apache License, Version 2.0, see above
 * @license    http://www.apache.org/licenses/LICENSE-2.0
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

        for ($i = 0; $i < count($fileContentsArray['php-versions']); $i++) {
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

            $dockerManageCommand .= $fileContentsArray['php-versions'][$i];

            // @codeCoverageIgnoreStart
            $threadsafe =
                isset($fileContentsArray['thread-safe']) && $fileContentsArray['thread-safe'] === 'true'
                    ? 'zts'
                    : 'nts';
            // @codeCoverageIgnoreEnd

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

            $script = '';

            if (isset($fileContentsArray['script'])
                && !empty($fileContentsArray['script'])
                && is_array($fileContentsArray['script'])
            ) {
                foreach ($fileContentsArray['script'] as $command) {
                    if (!empty($script)) {
                        $script .= ',,,';
                    }

                    $script .= $command;
                }
            } elseif (isset($fileContentsArray['script'])
                && !empty($fileContentsArray['script'])
                && !is_array($fileContentsArray['script'])
            ) {
                $script .= $fileContentsArray['script'];
            } else {
                $script .= 'lfphp';
            }

            $dockerManageCommand .= ' --script ';

            $dockerManageCommand .= escapeshellarg($script);

            $dockerManageCommand .= ' run';

            // outputs a message followed by a newline ("\n")
            $output->writeln($dockerManageCommand);

            $dockerManageCommand = '';
        }

        return 0;
    }
}
