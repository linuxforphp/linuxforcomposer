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
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Question\ConfirmationQuestion;
use Symfony\Component\Console\Question\Question;
use Linuxforcomposer\Helper\LinuxForComposerProcess;

//use Symfony\Component\Process\Exception\ProcessFailedException;

class DockerManageCommand extends Command
{
    const LFPHPDEFAULTVERSION = 'asclinux/linuxforphp-8.1-ultimate';

    const PHPDEFAULTVERSION = 'master';

    protected static $defaultName = 'docker:manage';

    protected $dockerPullCommand = 'docker pull ';

    protected $dockerRunCommand = 'docker run --restart=always ';

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

        echo PHP_EOL . 'Checking for image availability and downloading if necessary.' . PHP_EOL;

        echo PHP_EOL . 'This may take a few minutes...' . PHP_EOL . PHP_EOL;

        $this->dockerPullCommand .= DockerManageCommand::LFPHPDEFAULTVERSION . ':' . $phpversionFull;

        $temp_filename = tempnam(sys_get_temp_dir(), 'lfcprv');

        $checkImageProcess = new LinuxForComposerProcess($this->dockerPullCommand);

        if (strtoupper(substr(PHP_OS, 0, 3)) === 'WIN') {
            // @codeCoverageIgnoreStart
            if (strstr(php_uname('v'), 'Windows 10') !== false && php_uname('r') == '10.0') {
                $checkImageProcess->setDecorateWindowsWithReturnCode(true, $temp_filename);
            } else {
                $temp_filename = $this->win8NormalizePath($temp_filename);
                $checkImageProcess->setDecorateWindowsLegacyWithReturnCode(true, $temp_filename);
            }
            // @codeCoverageIgnoreEnd
        }

        $checkImageProcess->setTty($checkImageProcess->isTtySupported());

        $checkImageProcess->setTimeout(null);

        $checkImageProcess->prepareProcess();

        $checkImageProcess->start();

        $checkImageProcess->wait();

        $processStdout = $checkImageProcess->getOutput();

        $processStderr = $checkImageProcess->getErrorOutput();

        if (!empty($processStdout)) {
            echo $processStdout . PHP_EOL;
        }

        if (!empty($processStderr)) {
            echo $processStderr . PHP_EOL;
        }

        if (strtoupper(substr(PHP_OS, 0, 3)) === 'WIN') {
            // @codeCoverageIgnoreStart
            $checkLocalExitCode = (int) trim(file_get_contents($temp_filename));
            // @codeCoverageIgnoreEnd
        } else {
            $checkLocalExitCode = (int) trim($checkImageProcess->getExitCode());
        }

        echo 'Done!' . PHP_EOL . PHP_EOL;

        $imageName = '';

        $phpversionFullArray = explode('-', $phpversionFull);

        $phpversion = $phpversionFullArray[0];

        if ($checkLocalExitCode !== 0) {
            $imageName .= DockerManageCommand::LFPHPDEFAULTVERSION . ':src ';
            $imageName .=
                '/bin/bash -c "lfphp-compile '
                . $phpversion . ' ' . $threadsafe
                . ' ; '. $script . '"';
        } else {
            $imageName .= DockerManageCommand::LFPHPDEFAULTVERSION . ':' . $phpversionFull . ' ';
            $imageName .= '/bin/bash -c "' . $script . '"';
        }

        return $imageName;
    }

    protected function formatInput(InputInterface $input)
    {
        $this->dockerRunCommand .= ($input->getOption('interactive')) ? '-i ' : null;
        $this->dockerRunCommand .= ($input->getOption('tty')) ? '-t ' : null;
        $this->dockerRunCommand .= ($input->getOption('detached')) ? '-d ' : null;

        $ports = $input->getOption('port');

        if (isset($ports) && is_array($ports)) {
            if (!empty($ports) && !in_array('', $ports)) {
                foreach ($ports as $portMap) {
                    if (!empty($portMap)) {
                        $this->dockerRunCommand .= '-p ' . $portMap . ' ';
                    }
                }
            }
        } else {
            if (!empty($ports)) {
                $this->dockerRunCommand .= '-p ' . $ports . ' ';
            }
        }

        $volumes = $input->getOption('volume');

        if (isset($volumes) && is_array($volumes)) {
            if (!empty($volumes) && !in_array('', $volumes)) {
                foreach ($volumes as $volumeMap) {
                    if (!empty($volumeMap)) {
                        $this->dockerRunCommand .= '-v ' . $volumeMap . ' ';
                    }
                }
            }
        } else {
            if (!empty($volumes)) {
                $this->dockerRunCommand .= '-v ' . $volumes . ' ';
            }
        }

        $threadsafe = $input->getOption('threadsafe');

        $phpversion =
            !empty($input->getOption('phpversion'))
                ? $input->getOption('phpversion')
                : DockerManageCommand::PHPDEFAULTVERSION;

        $phpversionFull = $phpversion . '-'. $threadsafe;

        $script = ($input->getOption('script')) ?: 'lfphp';

        $script = str_replace(',,,', ' ; ', $script);

        if (strpos($phpversionFull, 'custom') !== false) {
            $this->dockerRunCommand .= DockerManageCommand::LFPHPDEFAULTVERSION
                . ':' . $phpversionFull
                . ' '
                . '/bin/bash -c "' . $script . '"';
        } else {
            $this->dockerRunCommand .= $this->checkImage($phpversionFull, $threadsafe, $script);
        }

        return $this->dockerRunCommand;
    }

    protected function execute(InputInterface $input, OutputInterface $output)
    {
        switch ($input->getArgument('execute')) {
            case 'run':
                $this->dockerRunCommand = $this->formatInput($input);

                $temp_filename = tempnam(sys_get_temp_dir(), 'lfcprv');

                $runContainerProcess = new LinuxForComposerProcess($this->dockerRunCommand);

                echo 'Starting container...' . PHP_EOL;

                // @codeCoverageIgnoreStart
                if ($input->getOption('detached') !== false) {
                    if (strtoupper(substr(PHP_OS, 0, 3)) === 'WIN') {
                        if (strstr(php_uname('v'), 'Windows 10') !== false && php_uname('r') == '10.0') {
                            $runContainerProcess->setDecorateWindowsWithStdout(true, $temp_filename);
                        } else {
                            $temp_filename = $this->win8NormalizePath($temp_filename);
                            $runContainerProcess->setDecorateWindowsLegacyWithStdout(true, $temp_filename);
                        }
                    } else {
                        $runContainerProcess->setTempFilename($temp_filename);

                        $runContainerProcess->setDockerCommand('/bin/bash & '
                            . $this->dockerRunCommand
                            . ' > '
                            . $temp_filename);
                    }
                } else {
                    if (strtoupper(substr(PHP_OS, 0, 3)) === 'WIN') {
                        if (strstr(php_uname('v'), 'Windows 10') !== false && php_uname('r') == '10.0') {
                            $runContainerProcess->setDecorateWindows(true);
                        } else {
                            $runContainerProcess->setDecorateWindowsLegacy(true);
                        }
                    }
                }
                // @codeCoverageIgnoreEnd

                $runContainerProcess->setTty($runContainerProcess->isTtySupported());

                $runContainerProcess->setTimeout(null);

                $runContainerProcess->prepareProcess();

                $runContainerProcess->start();

                // @codeCoverageIgnoreStart
                $runContainerProcess->wait(
                    function ($type, $data) {
                        echo $data;
                    }
                );
                // @codeCoverageIgnoreEnd

                // executes after the command finishes
                if ($runContainerProcess->isSuccessful()) {
                    if ($input->getOption('detached') !== false) {
                        // @codeCoverageIgnoreStart
                        $pid = trim(file_get_contents($temp_filename));
                        // @codeCoverageIgnoreEnd
                    } else {
                        $processPID = new LinuxForComposerProcess('docker ps -l -q');
                        $processPID->setTimeout(null);
                        $processPID->prepareProcess();
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

                            if (strtoupper(substr(PHP_OS, 0, 3)) === 'WIN') {
                                // @codeCoverageIgnoreStart
                                if (strstr(php_uname('v'), 'Windows 10') !== false && php_uname('r') == '10.0') {
                                    if (!file_exists(VENDORFOLDERPID . DIRECTORY_SEPARATOR . 'bin' . DIRECTORY_SEPARATOR . 'linuxforcomposer-commit-info.bat')) {
                                        if (!copy(
                                            PHARFILENAMERET . DIRECTORY_SEPARATOR . 'bin' . DIRECTORY_SEPARATOR . 'linuxforcomposer-commit-info.bat',
                                            VENDORFOLDERPID . DIRECTORY_SEPARATOR . 'bin' . DIRECTORY_SEPARATOR . 'linuxforcomposer-commit-info.bat'
                                        )
                                        ) {
                                            echo PHP_EOL
                                                . "Could not create the linuxforcomposer-commit-info.bat file! No commits possible."
                                                . PHP_EOL
                                                . PHP_EOL;
                                        }
                                    }

                                    $containerCommitInfoProcess =
                                    new LinuxForComposerProcess(
                                        VENDORFOLDERPID
                                        . DIRECTORY_SEPARATOR
                                        . 'bin'
                                        . DIRECTORY_SEPARATOR
                                        . 'linuxforcomposer-commit-info.bat '
                                        . $subvalue
                                        . ' '
                                        . VENDORFOLDERPID
                                        . DIRECTORY_SEPARATOR
                                        . 'composer'
                                        . DIRECTORY_SEPARATOR
                                    );
                                } else {
                                    if (!file_exists(VENDORFOLDERPID . DIRECTORY_SEPARATOR . 'bin' . DIRECTORY_SEPARATOR . 'linuxforcomposer-commit-info.bash')) {
                                        if (!copy(
                                            PHARFILENAMERET . DIRECTORY_SEPARATOR . 'bin' . DIRECTORY_SEPARATOR . 'linuxforcomposer-commit-info.bash',
                                            VENDORFOLDERPID . DIRECTORY_SEPARATOR . 'bin' . DIRECTORY_SEPARATOR . 'linuxforcomposer-commit-info.bash'
                                        )
                                        ) {
                                            echo PHP_EOL
                                                . "Could not create the linuxforcomposer-commit-info.bat file! No commits possible."
                                                . PHP_EOL
                                                . PHP_EOL;
                                        }
                                    }

                                    $temp_filename = tempnam(sys_get_temp_dir(), 'lfcprv');

                                    $temp_filename = $this->win8NormalizePath($temp_filename);

                                    $containerCommitInfoProcess =
                                    new LinuxForComposerProcess(
                                        'start /wait bash '
                                        . VENDORFOLDERPID
                                        . DIRECTORY_SEPARATOR
                                        . 'bin'
                                        . DIRECTORY_SEPARATOR
                                        . 'linuxforcomposer-commit-info.bash '
                                        . $subvalue
                                        . ' '
                                        . $temp_filename
                                    );
                                }


                                $containerCommitInfoProcess->setTty($containerCommitInfoProcess->isTtySupported());
                                $containerCommitInfoProcess->setTimeout(null);
                                $containerCommitInfoProcess->prepareProcess();
                                $containerCommitInfoProcess->start();
                                $containerCommitInfoProcess->wait();

                                if (strstr(php_uname('v'), 'Windows 10') !== false && php_uname('r') == '10.0') {
                                    $answerArray = explode(';', $containerCommitInfoProcess->getOutput());
                                } else {
                                    $answerArray = explode(';', file_get_contents($temp_filename));
                                }

                                if (count($answerArray) < 3) {
                                    $answerValue1 = '';
                                    $answerValue2 = '';
                                    $name = $answerValue2;
                                    $answerValue3 = '';
                                } else {
                                    $answerValue1 = trim($answerArray[0]);
                                    $answerValue2 = trim($answerArray[1]);
                                    $name = $answerValue2;
                                    $answerValue3 = trim($answerArray[2]);
                                }

                                if ($answerValue1 === 'y'
                                    || $answerValue1 === 'Y'
                                    || $answerValue1 === 'yes'
                                    || $answerValue1 === 'YES'
                                ) {
                                    if (empty(trim($name))) {
                                        $name = 'test' . sha1(microtime());
                                    }

                                    if ($answerValue3 === 'y'
                                        || $answerValue3 === 'Y'
                                        || $answerValue3 === 'yes'
                                        || $answerValue3 === 'YES'
                                    ) {
                                        $dockerCommitCommand = 'php '
                                            . PHARFILENAME
                                            . ' docker:commit ' . $subvalue . ' ' . $name . ' -s ' . $position;
                                    } else {
                                        $dockerCommitCommand = 'php '
                                            . PHARFILENAME
                                            . ' docker:commit ' . $subvalue . ' ' . $name;
                                    }

                                    $commitContainerProcess = new LinuxForComposerProcess($dockerCommitCommand);

                                    $commitContainerProcess->setTty($commitContainerProcess->isTtySupported());

                                    $commitContainerProcess->setTimeout(null);

                                    $commitContainerProcess->prepareProcess();

                                    $commitContainerProcess->start();

                                    $commitContainerProcess->wait();

                                    $processStdout = $commitContainerProcess->getOutput();

                                    $processStderr = $commitContainerProcess->getErrorOutput();

                                    if (!empty($processStdout)) {
                                        echo $processStdout . PHP_EOL;
                                    }

                                    if (!empty($processStderr)) {
                                        echo $processStderr . PHP_EOL;
                                    }
                                }
                                // @codeCoverageIgnoreEnd
                            } else {
                                $containerInfoProcess =
                                    new LinuxForComposerProcess('docker ps --filter "id=' . $subvalue . '"');
                                $containerInfoProcess->setTty($containerInfoProcess->isTtySupported());
                                $containerInfoProcess->setTimeout(null);
                                $containerInfoProcess->prepareProcess();
                                $containerInfoProcess->start();
                                $containerInfoProcess->wait();
                                echo $containerInfoProcess->getOutput();

                                $helper1 = $this->getHelper('question');
                                $question1 = new ConfirmationQuestion(
                                    'Commit container '
                                    . $subvalue
                                    . '? (y/N)',
                                    false
                                );

                                // @codeCoverageIgnoreStart
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
                                            . ' docker:commit ' . $subvalue . ' ' . $name . ' -s ' . $position;
                                    } else {
                                        $dockerCommitCommand = 'php '
                                            . PHARFILENAME
                                            . ' docker:commit ' . $subvalue . ' ' . $name;
                                    }

                                    $commitContainerProcess = new LinuxForComposerProcess($dockerCommitCommand);

                                    $commitContainerProcess->setTty($commitContainerProcess->isTtySupported());

                                    $commitContainerProcess->setTimeout(null);

                                    $commitContainerProcess->prepareProcess();

                                    $commitContainerProcess->start();

                                    $commitContainerProcess->wait();

                                    $processStdout = $commitContainerProcess->getOutput();

                                    $processStderr = $commitContainerProcess->getErrorOutput();

                                    if (!empty($processStdout)) {
                                        echo $processStdout . PHP_EOL;
                                    }

                                    if (!empty($processStderr)) {
                                        echo $processStderr . PHP_EOL;
                                    }
                                }
                            }

                            echo PHP_EOL . 'Stopping container...' . PHP_EOL;

                            // Not declared and defined at the class level because of potential for multiple containers.
                            $dockerStopCommand = 'docker stop ';

                            $dockerStopCommand .= $subvalue;

                            if (strtoupper(substr(PHP_OS, 0, 3)) === 'WIN') {
                                // @codeCoverageIgnoreStart
                                if (strstr(php_uname('v'), 'Windows 10') !== false && php_uname('r') == '10.0') {
                                    // Not declared and defined at the class level because of possibly multiple containers.
                                    $dockerRemoveCommand = 'docker rm ' . $subvalue;
                                } else {
                                    $dockerStopCommand .= ' && docker rm ' . $subvalue;
                                }
                                // @codeCoverageIgnoreEnd
                            } else {
                                $dockerStopCommand .= ' && docker rm ' . $subvalue;
                            }

                            $stopContainerProcess = new LinuxForComposerProcess($dockerStopCommand);

                            if (strtoupper(substr(PHP_OS, 0, 3)) === 'WIN') {
                                // @codeCoverageIgnoreStart
                                if (strstr(php_uname('v'), 'Windows 10') !== false && php_uname('r') == '10.0') {
                                    $stopContainerProcess->setDecorateWindows(true);
                                } else {
                                    $stopContainerProcess->setDecorateWindowsLegacy(true);
                                }
                                // @codeCoverageIgnoreEnd
                            }

                            $stopContainerProcess->setTty($stopContainerProcess->isTtySupported());

                            $stopContainerProcess->setTimeout(null);

                            $stopContainerProcess->prepareProcess();

                            $stopContainerProcess->start();

                            $stopContainerProcess->wait();

                            $processStdout = $stopContainerProcess->getOutput();

                            $processStderr = $stopContainerProcess->getErrorOutput();

                            if (!empty($processStdout)) {
                                echo $processStdout . PHP_EOL;
                            }

                            if (!empty($processStderr)) {
                                echo $processStderr . PHP_EOL;
                            }

                            if (isset($dockerRemoveCommand)) {
                                // @codeCoverageIgnoreStart
                                $removeContainerProcess =
                                    new LinuxForComposerProcess($dockerRemoveCommand);

                                $removeContainerProcess->setTty($removeContainerProcess->isTtySupported());

                                $removeContainerProcess->setTimeout(null);

                                $removeContainerProcess->prepareProcess();

                                $removeContainerProcess->start();

                                $removeContainerProcess->wait();

                                $processStdout = $removeContainerProcess->getOutput();

                                $processStderr = $removeContainerProcess->getErrorOutput();

                                if (!empty($processStdout)) {
                                    echo $processStdout . PHP_EOL;
                                }

                                if (!empty($processStderr)) {
                                    echo $processStderr . PHP_EOL;
                                }
                                // @codeCoverageIgnoreEnd
                            }

                            $position++;
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

    // @codeCoverageIgnoreStart
    protected function win8NormalizePath($path)
    {
        $path = str_replace('\\', '/', $path);
        $path = preg_replace('|(?<=.)/+|', '/', $path);
        if (':' === substr($path, 1, 1)) {
            $path = ucfirst($path);
        }
        return $path;
    }
    // @codeCoverageIgnoreEnd
}
