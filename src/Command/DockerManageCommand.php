<?php

/**
 * Linux for PHP/Linux for Composer
 *
 * Copyright 2010 - 2018 Foreach Code Factory <lfphp@asclinux.net>
 * Version 1.0.0-dev
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
 * @copyright  Copyright 2010 - 2018 Foreach Code Factory <lfphp@asclinux.net>
 * @link       http://linuxforphp.net/
 * @license    Apache License, Version 2.0, see above
 * @license    http://www.apache.org/licenses/LICENSE-2.0
 * @since 0.9.8
 */

namespace Linuxforcomposer\Command;

use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Question\ConfirmationQuestion;
use Symfony\Component\Console\Question\Question;
use Symfony\Component\Process\Process;
use Symfony\Component\Process\Exception\ProcessFailedException;

class DockerManageCommand extends Command
{
    const LFPHPDEFAULTVERSION = 'asclinux/linuxforphp-8.1';

    const PHPDEFAULTVERSION = 'master';

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

        $temp_filename = tempnam(sys_get_temp_dir(), 'lfcprv');

        if (strtoupper(substr(PHP_OS, 0, 3)) === 'WIN') {
            if (strstr(php_uname('v'), 'Windows 10') !== false && php_uname('r') == '10.0') {
                $dockerPullCommand = 'start /wait PowerShell -Command "'
                    . $dockerPullCommand
                    . ' ; $LASTEXITCODE | Out-File '
                    . $temp_filename
                    . ' -encoding ASCII"';
            } else {
                $dockerPullCommand = 'start /wait bash -c "'
                    . $dockerPullCommand
                    . ' ; echo $? > '
                    . $temp_filename
                    . '"';
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

        if (strtoupper(substr(PHP_OS, 0, 3)) === 'WIN') {
            $checkLocalExitCode = (int) trim(file_get_contents($temp_filename));
        } else {
            $checkLocalExitCode = (int) trim($checkImage->getExitCode());
        }

        echo 'Done!' . PHP_EOL . PHP_EOL;

        $imageString = '';

        $phpversionFullArray = explode('-', $phpversionFull);

        $phpversion = $phpversionFullArray[0];

        if ($checkLocalExitCode !== 0) {
            $imageString .= ' ' . DockerManageCommand::LFPHPDEFAULTVERSION . ':src ';
            $imageString .=
                '/bin/bash -c \'lfphp-compile '
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

        if (strpos($phpversionFull, 'custom') !== false) {
            $dockerRunCommand .= ' '
                . DockerManageCommand::LFPHPDEFAULTVERSION
                . ':' . $phpversionFull
                . ' '
                . $script;
        } else {
            $dockerRunCommand .= $this->checkImage($phpversionFull, $threadsafe, $script);
        }

        return $dockerRunCommand;
    }

    protected function execute(InputInterface $input, OutputInterface $output)
    {
        switch ($input->getArgument('execute')) {
            case 'run':
                $dockerRunCommand = $this->formatInput($input);

                $temp_filename = tempnam(sys_get_temp_dir(), 'lfcprv');

                echo 'Starting container...' . PHP_EOL;

                if ($input->getOption('detached') !== false) {
                    if (strtoupper(substr(PHP_OS, 0, 3)) === 'WIN') {
                        if (strstr(php_uname('v'), 'Windows 10') !== false && php_uname('r') == '10.0') {
                            $dockerRunCommand = 'start /wait PowerShell -Command "$Env:AppReturnValue =  & '
                                . $dockerRunCommand
                                . ' ; $Env:AppReturnValue | Out-File '
                                . $temp_filename
                                . ' -encoding ASCII"';
                        } else {
                            $dockerRunCommand = 'start /wait bash -i -c "'
                                . $dockerRunCommand
                                . ' > '
                                . $temp_filename
                                . '"';
                        }
                    } else {
                        $dockerRunCommand = '/bin/bash & '
                            . $dockerRunCommand
                            . ' > '
                            . $temp_filename;
                    }
                } else {
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
                    if ($input->getOption('detached') !== false) {
                        $pid = trim(file_get_contents($temp_filename));
                    } else {
                        $processPID = new Process('docker ps -l -q');
                        $processPID->setTimeout(null);
                        $processPID->start();
                        $processPID->wait();
                        $pid = trim($processPID->getOutput());
                    }

                    file_put_contents(
                        VENDORFOLDERPID
                        . DIRECTORY_SEPARATOR
                        . 'composer'
                        . DIRECTORY_SEPARATOR
                        . 'linuxforcomposer.pid',
                        $pid . PHP_EOL,
                        FILE_APPEND
                    );
                }

                //throw new ProcessFailedException($process);

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

                    if (empty(trim($fileContents))) {
                        echo PHP_EOL . 'PID file was empty!' . PHP_EOL . PHP_EOL;
                    } else {
                        $pids = explode(PHP_EOL, $fileContents);

                        $position = 0;

                        foreach ($pids as $key => $value) {
                            if (empty($value)) {
                                unset($pids[$key]);

                                break;
                            }

                            $subvalue = substr($value, 0, 12);
                            $helper1 = $this->getHelper('question');
                            $question1 = new ConfirmationQuestion(
                                'Commit container '
                                . $subvalue
                                . '? (y/N)',
                                false
                            );

                            if ($helper1->ask($input, $output, $question1)) {
                                $helper2 = $this->getHelper('question');
                                $question2 = new Question(
                                    'Please enter the name of the new commit: ',
                                    'test' . sha1(microtime())
                                );

                                $name = $helper2->ask($input, $output, $question2);

                                $helper3 = $this->getHelper('question');
                                $question3 = new ConfirmationQuestion(
                                    'Save to linuxforcomposer.json file? (y/N)',
                                    false
                                );

                                if ($helper3->ask($input, $output, $question3)) {
                                    $dockerCommitCommand = 'php '
                                        . PHARFILENAME
                                        . ' docker:commit ' . $value . ' ' . $name . ' -s ' . $position;
                                } else {
                                    $dockerCommitCommand = 'php '
                                        . PHARFILENAME
                                        . ' docker:commit ' . $value . ' ' . $name;
                                }

                                $process = new Process($dockerCommitCommand);

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

                            $position++;
                        }

                        if (!empty($pids)) {
                            echo 'Stopping containers...' . PHP_EOL;

                            foreach ($pids as $key => $pid) {
                                $pid = substr(trim($pid), 0, 12);

                                if (strtoupper(substr(PHP_OS, 0, 3)) === 'WIN') {
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
                        }
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
