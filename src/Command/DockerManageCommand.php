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
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Process\Exception\ProcessFailedException;
use Symfony\Component\Process\Process;

class DockerManageCommand extends Command
{
    const LFPHPDEFAULTVERSION = 'asclinux/linuxforphp-8.1';

    const PHPDEFAULTVERSION = 'master';

    protected $phpCurrentVersions = array(
        '7.3.0dev',
        '7.2.5',
        '7.1.16',
        '7.0.29',
        '5.6.35',
    );

    protected static $defaultName = 'docker:manage';

    public function __construct()
    {
        // you *must* call the parent constructor
        parent::__construct();
    }

    protected function configure()
    {
        $this
            ->setName('docker:manage')
            ->setDescription('Run Docker management commands.');
        $this
            // configure an argument
            ->addArgument('execute', InputArgument::REQUIRED, 'The Docker command to execute.')
            // configure options
            ->addOption('interactive', 'i')
            ->addOption('tty', 't')
            ->addOption('detached', 'd')
            ->addOption('phpversion', null, InputOption::VALUE_REQUIRED, 'The version of PHP you want to run.')
            ->addOption('threadsafe', null, InputOption::VALUE_REQUIRED, 'Enable (zts) or disable (nts) thread-safety.')
            ->addOption('port', null, InputOption::VALUE_OPTIONAL | InputOption::VALUE_IS_ARRAY)
            ->addOption('volume', null, InputOption::VALUE_OPTIONAL | InputOption::VALUE_IS_ARRAY)
            ->addOption('script', null, InputOption::VALUE_OPTIONAL);
    }

    protected function checkImage($phpversionFull, $threadsafe, $script)
    {
        $phpversionFull = (string) $phpversionFull;
        $threadsafe = (string) $threadsafe;
        $script = (string) $script;

        echo PHP_EOL;

        echo 'Checking for image availability and downloading if necessary.' . PHP_EOL;

        echo 'This may take a few minutes...' . PHP_EOL;

        $dockerPullCommand = 'docker pull ' . DockerManageCommand::LFPHPDEFAULTVERSION . ':' . $phpversionFull;

        if (strtoupper(substr(PHP_OS, 0, 3)) === 'WIN') {
            if (strstr(php_uname('v'), 'Windows 10') !== false && php_uname('r') == '10.0') {
                $dockerPullCommand = 'start /wait PowerShell -Command "'
                    . $dockerPullCommand
                    . '"';
            } else {
                $dockerPullCommand = 'start /wait bash -c "'
                    . $dockerPullCommand
                    .'"';
            }
        }

        $checkImage = new Process($dockerPullCommand);

        $checkImage->setTimeout(null);

        if (strtoupper((substr(PHP_OS, 0, 3))) !== 'WIN') {
            $checkImage->setTty(true);
        }

        $checkImage->start();

        $checkImage->wait();

        $processStdout = $checkImage->getOutput();

        $processStderr = $checkImage->getErrorOutput();

        if (!empty($processStdout)) {
            echo $processStdout . PHP_EOL;
        }

        if (!empty($processStderr)) {
            echo $processStderr . PHP_EOL;
        }

        $checkLocalExitCode = $checkImage->getExitCode();

        echo 'Done!' . PHP_EOL . PHP_EOL;

        $imageString = '';

        $phpversionFullArray = explode('-', $phpversionFull);

        $phpversion = $phpversionFullArray[0];

        // The use of in_array() is a Windows workaround
        if ($checkLocalExitCode === 1 || !in_array($phpversion, $this->phpCurrentVersions)) {
            $imageString .= ' ' . DockerManageCommand::LFPHPDEFAULTVERSION . ':src ';
            $imageString .=
                '/bin/bash -c \'cd ; wget -O tmp http://bit.ly/2jheBrr ; /bin/bash ./tmp '
                . $phpversion . ' ' . $threadsafe
                . ' ; '. $script .'\'';
        } else {
            $imageString .= ' ' . DockerManageCommand::LFPHPDEFAULTVERSION . ':' . $phpversionFull . ' ';
            $imageString .= $script;
        }

        return $imageString;
    }

    protected function formatInput(InputInterface $input)
    {
        $dockerRunCommand = '';
        $dockerRunCommand .= 'docker run --restart=always';
        $dockerRunCommand .= ($input->getOption('interactive')) ? ' -i' : null;
        $dockerRunCommand .= ($input->getOption('tty')) ? ' -t' : null;
        $dockerRunCommand .= ($input->getOption('detached')) ? ' -d' : null;

        $ports = $input->getOption('port');

        if (isset($ports) && is_array($ports)) {
            if (!empty($ports) && !in_array('', $ports)) {
                foreach ($ports as $portMap) {
                    if (!empty($portMap)) {
                        $dockerRunCommand .= ' -p ' . $portMap;
                    }
                }
            }
        } else {
            if (!empty($ports)) {
                $dockerRunCommand .= ' -p ' . $ports;
            }
        }

        $volumes = $input->getOption('volume');

        if (isset($volumes) && is_array($volumes)) {
            if (!empty($volumes) && !in_array('', $volumes)) {
                foreach ($volumes as $volumeMap) {
                    if (!empty($volumeMap)) {
                        $dockerRunCommand .= ' -v ' . $volumeMap;
                    }
                }
            }
        } else {
            if (!empty($volumes)) {
                $dockerRunCommand .= ' -v ' . $volumes;
            }
        }

        $threadsafe = $input->getOption('threadsafe');

        $phpversion =
            !empty($input->getOption('phpversion'))
                ? $input->getOption('phpversion')
                : DockerManageCommand::PHPDEFAULTVERSION;

        $phpversionFull = $phpversion . '-'. $threadsafe;

        $script = ($input->getOption('script')) ?: 'lfphp';

        $dockerRunCommand .= $this->checkImage($phpversionFull, $threadsafe, $script);

        return $dockerRunCommand;
    }

    protected function execute(InputInterface $input, OutputInterface $output)
    {
        switch ($input->getArgument('execute')) {
            case 'run':
                $dockerRunCommand = $this->formatInput($input);

                echo 'Starting container...' . PHP_EOL;

                if (strtoupper(substr(PHP_OS, 0, 3)) === 'WIN') {
                    if (strstr(php_uname('v'), 'Windows 10') !== false && php_uname('r') == '10.0') {
                        $dockerRunCommand = 'start /wait PowerShell -Command "'
                            . $dockerRunCommand
                            . '"';
                    } else {
                        $dockerRunCommand = 'start /wait bash -i -c "'
                            . $dockerRunCommand
                            . '"';
                    }
                }

                $process = new Process($dockerRunCommand);

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

                // executes after the command finishes
                if ($process->isSuccessful()) {
                    $processPID = new Process('docker ps -l -q');

                    $processPID->setTimeout(null);

                    $processPID->start();

                    $processPID->wait();

                    $pid = $processPID->getOutput();

                    //throw new ProcessFailedException($process);

                    file_put_contents(
                        VENDORFOLDERPID
                        . DIRECTORY_SEPARATOR
                        . 'composer'
                        . DIRECTORY_SEPARATOR
                        . 'linuxforcomposer.pid',
                        $pid,
                        FILE_APPEND
                    );
                }

                break;

            case 'stop':
                if (!file_exists(
                    VENDORFOLDERPID
                    . DIRECTORY_SEPARATOR
                    . 'composer'
                    . DIRECTORY_SEPARATOR
                    . 'linuxforcomposer.pid'
                )
                ) {
                    echo PHP_EOL
                        . 'Could not find the PID file!'
                        . PHP_EOL
                        . 'Please make sure the file exists or stop the containers manually.'
                        . PHP_EOL
                        . PHP_EOL;
                } else {
                    $fileContents = file_get_contents(
                        VENDORFOLDERPID
                        . DIRECTORY_SEPARATOR
                        . 'composer'
                        . DIRECTORY_SEPARATOR
                        . 'linuxforcomposer.pid'
                    );

                    $pids = explode(PHP_EOL, $fileContents);

                    foreach ($pids as $key => $value) {
                        if (empty($value)) {
                            unset($pids[$key]);
                        }
                    }

                    if (!empty($pids)) {
                        foreach ($pids as $key => $pid) {
                            if (strtoupper(substr(PHP_OS, 0, 3)) === 'WIN') {
                                echo 'Stopping containers...' . PHP_EOL;

                                if (strstr(php_uname('v'), 'Windows 10') !== false && php_uname('r') == '10.0') {
                                    $dockerStopCommand = 'docker stop ' . $pid;
                                    $dockerStopCommand = 'PowerShell -Command "'
                                        . $dockerStopCommand
                                        . '"';
                                    $dockerStopCommand2 = 'docker rm ' . $pid;
                                    $dockerStopCommand2 = 'PowerShell -Command "'
                                        . $dockerStopCommand2
                                        . '"';
                                } else {
                                    $dockerStopCommand = 'docker stop ' . $pid . '&& docker rm ' . $pid;
                                    $dockerStopCommand = 'bash -c "'
                                        . $dockerStopCommand
                                        . '"';
                                }
                            } else {
                                echo 'Stopping container...' . PHP_EOL;

                                $dockerStopCommand = 'docker stop ' . $pid . ' && docker rm ' . $pid;
                            }

                            $process = new Process($dockerStopCommand);

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

                            if (isset($dockerStopCommand2)) {
                                $process = new Process($dockerStopCommand2);

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
                            }
                        }
                    } else {
                        echo PHP_EOL . 'PID file was empty!' . PHP_EOL . PHP_EOL;
                    }

                    unlink(
                        VENDORFOLDERPID
                        . DIRECTORY_SEPARATOR
                        . 'composer'
                        . DIRECTORY_SEPARATOR
                        . 'linuxforcomposer.pid'
                    );
                }

                break;

            default:
                echo PHP_EOL . 'Wrong command given!' . PHP_EOL . PHP_EOL;
                break;
        }
    }
}
