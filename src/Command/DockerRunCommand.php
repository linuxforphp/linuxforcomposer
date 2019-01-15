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
use Symfony\Component\Console\Input\ArrayInput;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\BufferedOutput;
use Symfony\Component\Console\Output\OutputInterface;
use Linuxforcomposer\Helper\LinuxForComposerProcess;

class DockerRunCommand extends Command
{
    protected static $defaultName = 'docker:run';

    public function __construct()
    {
        // you *must* call the parent constructor
        parent::__construct();
    }

    protected function configure()
    {
        $this
            ->setName('docker:run')
            ->setDescription("Run 'Linux for PHP' containers.");
        $this
            // configure arguments
            ->addArgument('execute', InputArgument::REQUIRED, '[start] or [stop] the containers.')
            // configure options
            ->addOption('jsonfile', null, InputOption::VALUE_REQUIRED, 'Use a custom JSON configuration file.');
    }

    protected function execute(InputInterface $input, OutputInterface $output)
    {
        switch ($input->getArgument('execute')) {
            case 'start':
                $parseCommand = $this->getApplication()->find('docker:parsejson');

                $jsonFile = ($input->getOption('jsonfile')) ?: null;

                if ($jsonFile !== null) {
                    $arguments = array(
                        '--jsonfile' => $jsonFile,
                    );
                } else {
                    $arguments = array();
                }

                $parseInput = new ArrayInput($arguments);

                $parseOutput = new BufferedOutput();

                $returnCode = (int) $parseCommand->run($parseInput, $parseOutput);

                if ($returnCode > 1) {
                    echo PHP_EOL . 'You must choose at least one PHP version to run.' . PHP_EOL . PHP_EOL;
                    break;
                } elseif ($returnCode === 1) {
                    echo PHP_EOL . "The 'Linux for Composer' JSON file is invalid." . PHP_EOL . PHP_EOL;
                    break;
                }

                $dockerManageCommandsArray = explode("\n", $parseOutput->fetch());

                foreach ($dockerManageCommandsArray as $key => $value) {
                    if (empty($value)) {
                        unset($dockerManageCommandsArray[$key]);
                    }
                }

                foreach ($dockerManageCommandsArray as $key => $dockerManageCommand) {
                    $process = new LinuxForComposerProcess($dockerManageCommand);

                    $process->setTty($process->isTtySupported());

                    $process->setTimeout(null);

                    $process->prepareProcess();

                    $process->start();

                    //$process->run();

                    /*while ($process->isRunning()) {
                        // waiting for process to finish
                        ;
                    }*/

                    $process->wait();

                    $processStdout = $process->getOutput();

                    $processStderr = $process->getErrorOutput();

                    //$output->writeln($process->getOutput());
                    if (!empty($processStdout)) {
                        echo $processStdout . PHP_EOL;
                    }

                    //$output->writeln($process->getErrorOutput());
                    if (!empty($processStderr)) {
                        echo $processStderr . PHP_EOL;
                    }
                }

                break;

            case 'stop':
                $dockerManageCommand = 'php '
                    . PHARFILENAME
                    . ' docker:manage stop';

                $process = new LinuxForComposerProcess($dockerManageCommand);

                $process->setTty($process->isTtySupported());

                $process->setTimeout(null);

                $process->prepareProcess();

                $process->start();

                $process->wait();

                $processStdout = $process->getOutput();

                $processStderr = $process->getErrorOutput();

                if (!empty($processStdout)) {
                    echo $processStdout . PHP_EOL;
                }

                if (!empty($processStderr)) {
                    echo $processStderr . PHP_EOL;
                }

                break;

            default:
                echo PHP_EOL . 'Wrong command given!' . PHP_EOL . PHP_EOL;
                break;
        }
    }
}
