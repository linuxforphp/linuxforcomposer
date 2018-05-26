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

namespace Linuxforcomposer\Command;

use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\ArrayInput;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\BufferedOutput;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Process\Process;

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
                    $process = new Process($dockerManageCommand);

                    $process->setTimeout(null);

                    if (strtoupper((substr(PHP_OS, 0, 3))) !== 'WIN') {
                        $process->setTty(true);
                    }

                    //$process->run();

                    $process->start();

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

                $process = new Process($dockerManageCommand);

                $process->setTimeout(null);

                if (strtoupper((substr(PHP_OS, 0, 3))) !== 'WIN') {
                    $process->setTty(true);
                }

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
