<?php
/**
 * Linux for PHP/Linux for Composer
 *
 * Copyright 2017 - 2020 Foreach Code Factory <lfphp@asclinux.net>
 * Version 2.0.6
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
 * @since 1.0.0
 */

namespace Linuxforcomposer\Command;

use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Linuxforcomposer\Helper\LinuxForComposerProcess;

class DockerCommitCommand extends Command
{
    const LFPHPDEFAULTVERSION = DockerManageCommand::LFPHPDEFAULTVERSION;

    protected static $defaultName = 'docker:commit';

    protected $dockerCommitCommand = 'docker commit ';

    public function __construct()
    {
        // you *must* call the parent constructor
        parent::__construct();
    }

    protected function configure()
    {
        $this
            ->setName('docker:commit')
            ->setDescription('Docker commit commands.');
        $this
            // configure an argument
            ->addArgument('pid', InputArgument::REQUIRED, 'The Docker PID to commit.')
            // configure an argument
            ->addArgument('name', InputArgument::REQUIRED, 'Committed version\'s name.')
            // configure options
            ->addOption(
                'savetojsonfile',
                's',
                InputOption::VALUE_REQUIRED,
                'Save the version\'s name to the linuxforcomposer.json file at the specified position.'
            )
        ;
    }

    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $jsonFile = JSONFILE;

        $fileContentsJson = file_get_contents($jsonFile);

        $fileContentsArray = json_decode($fileContentsJson, true);

        if ($fileContentsArray === null) {
            echo "WARNING: The linuxforcomposer.json file is empty or invalid! The file is unchanged." . PHP_EOL;
            return;
        }

        if (!isset($fileContentsArray['single']['image']['linuxforcomposer']['php-versions']) || empty($fileContentsArray['single']['image']['linuxforcomposer']['php-versions'])) {
            echo "WARNING: No versions of PHP found in the linuxforcomposer.json file! The file is unchanged." . PHP_EOL;
            return;
        }

        // @codeCoverageIgnoreStart
        if ($fileContentsArray['single']['image']['linuxforcomposer']['thread-safe'] === 'true') {
            $threadsafe = '-zts';
        } else {
            $threadsafe = '-nts';
        }
        // @codeCoverageIgnoreEnd

        $pid = (string) $input->getArgument('pid');

        $versionName = 'custom-' . $input->getArgument('name');

        $versionNameTS = 'custom-' . $input->getArgument('name') . $threadsafe;

        $name = (string) DockerCommitCommand::LFPHPDEFAULTVERSION . ':' . $versionNameTS;

        $this->dockerCommitCommand .= $pid . ' ' . $name;

        $commitImageProcess = new LinuxForComposerProcess($this->dockerCommitCommand);

        if (strtoupper(substr(PHP_OS, 0, 3)) === 'WIN') {
            // @codeCoverageIgnoreStart
            if (strstr(php_uname('v'), 'Windows 10') !== false && php_uname('r') == '10.0') {
                $commitImageProcess->setDecorateWindows(true);
            } else {
                $commitImageProcess->setDecorateWindowsLegacy(true);
            }
            // @codeCoverageIgnoreEnd
        }

        $commitImageProcess->setTty($commitImageProcess->isTtySupported());

        $commitImageProcess->setTimeout(null);

        $commitImageProcess->prepareProcess();

        $commitImageProcess->start();

        $commitImageProcess->wait();

        $processStdout = $commitImageProcess->getOutput();

        $processStderr = $commitImageProcess->getErrorOutput();

        if (!empty($processStdout)) {
            echo $processStdout . PHP_EOL;
        }

        if (!empty($processStderr)) {
            echo $processStderr . PHP_EOL;
        }

        $position = $input->getOption('savetojsonfile');

        if ($position !== null) {
            $fileContentsArray['single']['image']['linuxforcomposer']['php-versions'][$position] = $versionName;

            $fileContentsJson = json_encode($fileContentsArray, JSON_UNESCAPED_SLASHES | JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE);

            file_put_contents($jsonFile, $fileContentsJson);
        }
    }
}
