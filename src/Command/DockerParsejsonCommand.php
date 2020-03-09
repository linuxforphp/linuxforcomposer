<?php
/**
 * Linux for PHP/Linux for Composer
 *
 * Copyright 2017 - 2020 Foreach Code Factory <lfphp@asclinux.net>
 * Version 2.0.1
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
            ->setDescription('Parse JSON file for instructions for Docker.');
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

        if (!isset($fileContentsArray['single']['image']['linuxforcomposer']['php-versions'])
                || empty($fileContentsArray['single']['image']['linuxforcomposer']['php-versions'])
        ) {
            if ((!isset($fileContentsArray['single']['image']['dockerfile']['url'])
                    || empty($fileContentsArray['single']['image']['dockerfile']['url'])
                )
                && (!isset($fileContentsArray['single']['image']['dockerfile']['container-name'])
                    || empty($fileContentsArray['single']['image']['dockerfile']['container-name'])
                )
                && (!isset($fileContentsArray['docker-compose']['url'])
                    || empty($fileContentsArray['docker-compose']['url'])
                )
            ) {
                return 2;
            }
        }

        if (is_array($fileContentsArray['single']['image']['linuxforcomposer']['php-versions'])
            && count($fileContentsArray['single']['image']['linuxforcomposer']['php-versions']) > 1
        ) {
            if (!in_array('detached', $fileContentsArray['single']['containers']['modes'])) {
                $fileContentsArray['single']['containers']['modes'][] = 'detached';
            }
        }

        if (!isset($fileContentsArray['single']['containers']['modes'])
            || !in_array('detached', $fileContentsArray['single']['containers']['modes'])
            && !in_array('interactive', $fileContentsArray['single']['containers']['modes'])
            && !in_array('tty', $fileContentsArray['single']['containers']['modes'])
        ) {
            $fileContentsArray['single']['containers']['modes'][] = 'detached';
        }

        if (isset($fileContentsArray['single']['image']['dockerfile'])
            && !empty($fileContentsArray['single']['image']['dockerfile']['url'])
            && !empty($fileContentsArray['single']['image']['dockerfile']['container-name'])
        ) {
            $dockerManageCommand = 'php '
                . PHARFILENAME
                . ' docker:manage ';

            $dockerManageCommand .= $this->getModes($fileContentsArray);

            $dockerManageCommand .= $this->getPorts($fileContentsArray, 0);

            $dockerManageCommand .= $this->getMount($fileContentsArray);

            $dockerManageCommand .= $this->getVolumes($fileContentsArray);

            $dockerManageCommand .=
                '--script dockerfile,,,'
                . $fileContentsArray['single']['image']['dockerfile']['url'];

            if (isset($fileContentsArray['single']['image']['dockerfile']['username'])
                && isset($fileContentsArray['single']['image']['dockerfile']['token'])
                && !empty($fileContentsArray['single']['image']['dockerfile']['username'])
                && !empty($fileContentsArray['single']['image']['dockerfile']['token'])
            ) {
                $dockerManageCommand .=
                    ',,,'
                    . $fileContentsArray['single']['image']['dockerfile']['username']
                    . ':'
                    . $fileContentsArray['single']['image']['dockerfile']['token'];
            }

            if (isset($fileContentsArray['single']['image']['dockerfile']['container-name'])) {
                $dockerManageCommand .=
                    ',,,'
                    . $fileContentsArray['single']['image']['dockerfile']['container-name'];
            }

            $dockerManageCommand .=
                ' build';

            // outputs a message followed by a newline ("\n")
            $output->writeln($dockerManageCommand);

            $dockerManageCommand = '';

            return 0;
        }

        if (isset($fileContentsArray['docker-compose']) && !empty($fileContentsArray['docker-compose']['url'])) {
            $dockerManageCommand = 'php '
                . PHARFILENAME
                . ' docker:manage ';

            $dockerManageCommand .=
                '--script docker-compose,,,'
                . $fileContentsArray['docker-compose']['url'];

            if (isset($fileContentsArray['docker-compose']['username'])
                && isset($fileContentsArray['docker-compose']['token'])
                && !empty($fileContentsArray['docker-compose']['username'])
                && !empty($fileContentsArray['docker-compose']['token'])
            ) {
                $dockerManageCommand .=
                    ',,,'
                    . $fileContentsArray['docker-compose']['username']
                    . ':'
                    . $fileContentsArray['docker-compose']['token'];
            }

            $dockerManageCommand .=
                ' build';

            // outputs a message followed by a newline ("\n")
            $output->writeln($dockerManageCommand);

            $dockerManageCommand = '';

            return 0;
        }

        for ($i = 0; $i < count($fileContentsArray['single']['image']['linuxforcomposer']['php-versions']); $i++) {
            $dockerManageCommand = 'php '
                . PHARFILENAME
                . ' docker:manage ';

            $dockerManageCommand .= $this->getModes($fileContentsArray);

            $dockerManageCommand .= '--phpversion ';

            $dockerManageCommand .= $fileContentsArray['single']['image']['linuxforcomposer']['php-versions'][$i] . ' ';

            // @codeCoverageIgnoreStart
            $threadsafe =
                isset($fileContentsArray['single']['image']['linuxforcomposer']['thread-safe'])
                && $fileContentsArray['single']['image']['linuxforcomposer']['thread-safe'] === 'true'
                    ? 'zts'
                    : 'nts';
            // @codeCoverageIgnoreEnd

            $dockerManageCommand .= '--threadsafe ';

            $dockerManageCommand .= $threadsafe . ' ';

            $dockerManageCommand .= $this->getPorts($fileContentsArray, $i);

            $dockerManageCommand .= $this->getMount($fileContentsArray);

            $dockerManageCommand .= $this->getVolumes($fileContentsArray);

            $script = '';

            if (isset($fileContentsArray['single']['image']['linuxforcomposer']['script'])
                && !empty($fileContentsArray['single']['image']['linuxforcomposer']['script'])
                && is_array($fileContentsArray['single']['image']['linuxforcomposer']['script'])
            ) {
                foreach ($fileContentsArray['single']['image']['linuxforcomposer']['script'] as $command) {
                    if (!empty($script)) {
                        $script .= ',,,';
                    }

                    $script .= $command . ' ';
                }
            } elseif (isset($fileContentsArray['single']['image']['linuxforcomposer']['script'])
                && !empty($fileContentsArray['single']['image']['linuxforcomposer']['script'])
                && !is_array($fileContentsArray['single']['image']['linuxforcomposer']['script'])
            ) {
                $script .= $fileContentsArray['single']['image']['linuxforcomposer']['script'] . ' ';
            } else {
                $script .= 'lfphp ';
            }

            $dockerManageCommand .= '--script ';

            $dockerManageCommand .= escapeshellarg($script) . ' ';

            $dockerManageCommand .= 'run';

            // outputs a message followed by a newline ("\n")
            $output->writeln($dockerManageCommand);

            $dockerManageCommand = '';
        }

        return 0;
    }

    protected function getModes(array $fileContentsArray)
    {
        $dockerManageCommand = '';

        if (in_array('detached', $fileContentsArray['single']['containers']['modes'])) {
            $dockerManageCommand .= '--detached ';
        }

        if (in_array('interactive', $fileContentsArray['single']['containers']['modes'])) {
            $dockerManageCommand .= '--interactive ';
        }

        if (in_array('tty', $fileContentsArray['single']['containers']['modes'])) {
            $dockerManageCommand .= '--tty ';
        }

        return $dockerManageCommand;
    }

    protected function getPorts(array $fileContentsArray, int $i)
    {
        $dockerManageCommand = '';

        if (isset($fileContentsArray['single']['containers']['ports'])
            && is_array($fileContentsArray['single']['containers']['ports'])
            && !empty($fileContentsArray['single']['containers']['ports'])
        ) {
            foreach ($fileContentsArray['single']['containers']['ports'] as $port) {
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
                    $dockerManageCommand .= '--port ';

                    $dockerManageCommand .= $portNumber . ' ';
                }
            }
        } else {
            $portNumber =
                isset($fileContentsArray['single']['containers']['ports'])
                && !empty($fileContentsArray['single']['containers']['ports'])
                    ? $fileContentsArray['single']['containers']['ports']
                    : '';

            if (!empty($portNumber) && $i === 0) {
                $dockerManageCommand .= '--port ';

                $dockerManageCommand .= $portNumber . ' ';
            }
        }

        return $dockerManageCommand;
    }

    protected function getMount(array $fileContentsArray)
    {
        $dockerManageCommand = '';

        if (isset($fileContentsArray['single']['containers']['persist-data']['mount'])
            && isset($fileContentsArray['single']['containers']['persist-data']['directories'])
            && is_array($fileContentsArray['single']['containers']['persist-data']['directories'])
            && isset($fileContentsArray['single']['containers']['persist-data']['directories']['directory1'])
            && !empty($fileContentsArray['single']['containers']['persist-data']['directories']['directory1'])
            && isset($fileContentsArray['single']['containers']['persist-data']['root-name'])
            && !empty($fileContentsArray['single']['containers']['persist-data']['root-name'])
            ) {
            $dockerManageCommand .= '--mount ';

            if ($fileContentsArray['single']['containers']['persist-data']['mount'] == 'true') {
                foreach ($fileContentsArray['single']['containers']['persist-data']['directories'] as $directory) {
                    if (!empty($directory)) {
                        $search = strpos($directory, DIRECTORY_SEPARATOR) !== false ? DIRECTORY_SEPARATOR : '/' ;
                        $dockerManageCommand .=
                            'source='
                            . $fileContentsArray['single']['containers']['persist-data']['root-name']
                            . str_replace($search, '_', $directory)
                            . ',target='
                            . $directory
                            . ',,,'
                            . $fileContentsArray['single']['containers']['persist-data']['root-name']
                            . str_replace($search, '_', $directory)
                            . ',,,,';
                    }
                }
            } else {
                foreach ($fileContentsArray['single']['containers']['persist-data']['directories'] as $directory) {
                    if (!empty($directory)) {
                        $search = strpos($directory, DIRECTORY_SEPARATOR) !== false ? DIRECTORY_SEPARATOR : '/' ;
                        $dockerManageCommand .=
                            ':'
                            . $fileContentsArray['single']['containers']['persist-data']['root-name']
                            . str_replace($search, '_', $directory)
                            . ',,,,';
                    }
                }
            }
        } else {
            $dockerManageCommand = '';

            return $dockerManageCommand;
        }

        $dockerManageCommand .= ' ';

        return $dockerManageCommand;
    }

    protected function getVolumes(array $fileContentsArray)
    {
        $dockerManageCommand = '';

        if (isset($fileContentsArray['single']['containers']['volumes'])
            && is_array($fileContentsArray['single']['containers']['volumes'])
            && !empty($fileContentsArray['single']['containers']['volumes'])
        ) {
            foreach ($fileContentsArray['single']['containers']['volumes'] as $volume) {
                if (!empty($volume)) {
                    $dockerManageCommand .= '--volume ';

                    $dockerManageCommand .= $volume . ' ';
                }
            }
        }

        return $dockerManageCommand;
    }
}
