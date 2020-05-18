<?php
/**
 * Linux for PHP/Linux for Composer
 *
 * Copyright 2017 - 2020 Foreach Code Factory <lfphp@asclinux.net>
 * Version 2.0.7
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
    const LFPHPDEFAULTVERSION = 'asclinux/linuxforphp-8.2-ultimate';

    const PHPDEFAULTVERSION = 'master';

    protected static $defaultName = 'docker:manage';

    protected $dockerPullCommand = 'docker pull ';

    protected $dockerRunCommand = 'docker run --restart=always ';

    protected $tempScriptFile = '';

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
            ->addOption('detached', 'd')
            ->addOption('interactive', 'i')
            ->addOption('tty', 't')
            ->addOption('phpversion', null, InputOption::VALUE_REQUIRED, 'The version of PHP you want to run.')
            ->addOption('threadsafe', null, InputOption::VALUE_REQUIRED, 'Enable (zts) or disable (nts) thread-safety.')
            ->addOption('port', null, InputOption::VALUE_OPTIONAL | InputOption::VALUE_IS_ARRAY)
            ->addOption('mount', null, InputOption::VALUE_OPTIONAL | InputOption::VALUE_IS_ARRAY)
            ->addOption('volume', null, InputOption::VALUE_OPTIONAL | InputOption::VALUE_IS_ARRAY)
            ->addOption('script', null, InputOption::VALUE_OPTIONAL);
    }

    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $command = $input->getArgument('execute');

        // @codeCoverageIgnoreStart
        $this->dockerRunCommand .=
            ($command == 'build' || $command == 'run')
            && LFPHP
                ? '--memory=' . LFPHP_MEM . ' '
                    . '--memory-swap=' . LFPHP_SWAP . ' '
                    . '--cpu-shares=' . LFPHP_SHARES . ' '
                    . '--cpu-period=' . LFPHP_PERIOD . ' '
                    . '--cpu-quota=' . LFPHP_QUOTA . ' '
                : '';
        // @codeCoverageIgnoreEnd

        $mount = $input->getOption('mount');

        $modeOptions = '';
        $modeOptions .= ($input->getOption('detached')) ? '-d ' : null;
        $modeOptions .= ($input->getOption('interactive')) ? '-i ' : null;
        $modeOptions .= ($input->getOption('tty')) ? '-t ' : null;

        $ports = $input->getOption('port');

        $portOptions = $this->getPortOptions($ports);

        $volumes = $input->getOption('volume');

        $volumeOptions = $this->getVolumeOptions($volumes);

        $script = $input->getOption('script');

        $scriptArray = $this->getScriptOptions($script);

        $mountNames = '';

        $mountOptions = '';

        switch ($command) {
            case 'build':
                $engine = $scriptArray['engine'];

                $url = $scriptArray['url'];

                $auth = $scriptArray['auth'];

                $imageName = $scriptArray['image_name'];

                $urlArray = parse_url($url);

                $pathArray = explode('/', $urlArray['path']);

                if (isset($urlArray['host']) && strpos($urlArray['scheme'], 'http') !== false) {
                    if ($engine === 'dockerfile') {
                        // @codeCoverageIgnoreStart
                        $filename = array_pop($pathArray);

                        $path = BASEDIR . DIRECTORY_SEPARATOR . $filename;

                        if (!file_exists($path)) {
                            set_time_limit(0);
                            $fp = fopen($path, 'w+');
                            $ch = \curl_init($url);
                            \curl_setopt($ch, CURLOPT_TIMEOUT, 50);
                            \curl_setopt($ch, CURLOPT_FILE, $fp);
                            \curl_setopt($ch, CURLOPT_FOLLOWLOCATION, true);
                            if (!empty($auth)) {
                                \curl_setopt($ch, CURLOPT_USERPWD, $auth);
                            }
                            \curl_exec($ch);

                            $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);

                            if ($httpCode != 200) {
                                echo PHP_EOL
                                    . 'URL is invalid!'
                                    . PHP_EOL
                                    . PHP_EOL
                                    . 'Please make sure that the URL is allowed and valid,'
                                    . PHP_EOL
                                    . 'and that cURL and Git are available on your system.'
                                    . PHP_EOL
                                    . PHP_EOL;

                                \curl_close($ch);
                                fclose($fp);
                                unlink($path);
                                return 4;
                            }

                            \curl_close($ch);
                            fclose($fp);
                        }
                    // @codeCoverageIgnoreEnd
                    } else {
                        $path = array_pop($pathArray);

                        // @codeCoverageIgnoreStart
                        if (!file_exists($path)) {
                            $processGit = new LinuxForComposerProcess('git clone ' . $url);
                            $processGit->setTimeout(null);
                            $processGit->prepareProcess();
                            $processGit->start();
                            $processGit->wait();
                        }
                        // @codeCoverageIgnoreEnd
                    }
                } elseif (!isset($urlArray['host'])) {
                    $path = $urlArray['path'];

                    // @codeCoverageIgnoreStart
                    if (strtoupper(substr(PHP_OS, 0, 3)) === 'WIN') {
                        if (strstr(php_uname('v'), 'Windows 10') !== false && php_uname('r') == '10.0') {
                            $path = $this->winNormalizePath($path);
                        }
                    }
                    // @codeCoverageIgnoreEnd
                } else {
                    echo PHP_EOL
                        . 'URL is invalid!'
                        . PHP_EOL
                        . PHP_EOL
                        . 'Please make sure that the URL is allowed and valid,'
                        . PHP_EOL
                        . 'and that cURL and Git are available on your system.'
                        . PHP_EOL
                        . PHP_EOL;

                    return 4;
                }

                if (file_exists($path)) {
                    if ($engine === 'docker-compose') {
                        chdir($path);
                    }
                } else {
                    echo PHP_EOL
                        . 'URL is invalid!'
                        . PHP_EOL
                        . PHP_EOL
                        . 'Please make sure that the URL is allowed and valid,'
                        . PHP_EOL
                        . 'and that cURL and Git are available on your system.'
                        . PHP_EOL
                        . PHP_EOL;

                    return 4;
                }

                if (!empty($imageName)) {
                    $path = $path . ' -t ' . $imageName;
                }

                $dockerBuildCommand =
                    $engine === 'dockerfile'
                        ? 'docker build . -f ' . $path
                        : 'docker-compose up -d --build';

                $buildContainerProcess = new LinuxForComposerProcess($dockerBuildCommand);

                echo PHP_EOL . 'Building all containers...' . PHP_EOL;

                // @codeCoverageIgnoreStart
                if (strtoupper(substr(PHP_OS, 0, 3)) === 'WIN') {
                    if (strstr(php_uname('v'), 'Windows 10') !== false && php_uname('r') == '10.0') {
                        $buildContainerProcess->setDecorateWindows(true);
                    } else {
                        $buildContainerProcess->setDecorateWindowsLegacy(true);
                    }
                }
                // @codeCoverageIgnoreEnd

                $buildContainerProcess->setTty($buildContainerProcess->isTtySupported());

                $buildContainerProcess->setTimeout(null);

                $buildContainerProcess->prepareProcess();

                $buildContainerProcess->start();

                // @codeCoverageIgnoreStart
                $buildContainerProcess->wait(
                    function ($type, $data) {
                        echo $data;
                    }
                );
                // @codeCoverageIgnoreEnd

                $returnCode = $buildContainerProcess->getExitCode();

                // @codeCoverageIgnoreStart
                //$output->writeln($process->getErrorOutput());
                if ($returnCode > 0) {
                    return 5;
                }
                // @codeCoverageIgnoreEnd

                if ($engine === 'dockerfile'
                    && isset($mount)
                    && !empty($mount)
                    && strpos($mount[0], ':') === false
                ) {
                    $mountNames = $this->getMountNames($mount);

                    $mountOptions = $this->getMountOptions($mount);

                    $mountOptionsArray = explode('=', $mountOptions);

                    $directory = trim(array_pop($mountOptionsArray));

                    foreach ($mountNames as $mountName => $mountOption) {
                        $checkVolumeCommand = 'docker volume ls -q --filter name=' . $mountName;

                        $checkVolumeProcess =
                            new LinuxForComposerProcess($checkVolumeCommand);

                        $temp_filename = tempnam(sys_get_temp_dir(), 'lfcprvcheck');

                        // @codeCoverageIgnoreStart
                        if (strtoupper(substr(PHP_OS, 0, 3)) === 'WIN') {
                            if (strstr(php_uname('v'), 'Windows 10') !== false && php_uname('r') == '10.0') {
                                $checkVolumeProcess->setDecorateWindowsWithStdout(true, $temp_filename);
                            } else {
                                $temp_filename = $this->winNormalizePath($temp_filename);
                                $checkVolumeProcess->setDecorateWindowsLegacyWithStdout(true, $temp_filename);
                            }
                        // @codeCoverageIgnoreEnd
                        } else {
                            $checkVolumeProcess->setTempFilename($temp_filename);

                            $checkVolumeProcess->setDockerCommand('/bin/bash & '
                                . $checkVolumeCommand
                                . ' > '
                                . $temp_filename);
                        }

                        $checkVolumeProcess->setTty($checkVolumeProcess->isTtySupported());

                        $checkVolumeProcess->setTimeout(null);

                        $checkVolumeProcess->prepareProcess();

                        $checkVolumeProcess->start();

                        $checkVolumeProcess->wait();

                        $processStdout = trim(file_get_contents(($temp_filename)));

                        //$output->writeln($process->getErrorOutput());
                        if (empty($processStdout)) {
                            if (LFPHP) {
                                // @codeCoverageIgnoreStart
                                //$volumeUnit = '';
                                //preg_match('/[a-zA-Z]+/', LFPHP_VOLSIZE, $volumeUnit);
                                //$numberVolumes = count($mountNames);
                                //$totalVolSize = (int) LFPHP_VOLSIZE;
                                //$volumeSize = floor($totalVolSize / $numberVolumes) . strtoupper($volumeUnit[0]);

                                // docker volume create -d flocker -o size=
                                // docker volume create -d local -o o=size=
                                // docker volume create -d local -o type=tmpfs -o device=tmpfs -o o=size=100m,uid=1000
                                $createVolumeCommand = 'docker volume create -d local -o type=ext4 -o device=/dev/vg00/' . LFPHP_ACCOUNT . ' ' . $mountName;
                                // @codeCoverageIgnoreEnd
                            } else {
                                $createVolumeCommand = 'docker volume create ' . $mountName;
                            }

                            $createVolumeProcess =
                                new LinuxForComposerProcess($createVolumeCommand);

                            $createVolumeProcess->setTty($createVolumeProcess->isTtySupported());

                            $createVolumeProcess->setTimeout(null);

                            $createVolumeProcess->prepareProcess();

                            $createVolumeProcess->start();

                            $createVolumeProcess->wait();

                            $processStderr = $createVolumeProcess->getErrorOutput();

                            $returnCode = $createVolumeProcess->getExitCode();

                            // @codeCoverageIgnoreStart
                            //$output->writeln($process->getErrorOutput());
                            if (!empty($processStderr) || $returnCode > 0) {
                                echo $processStderr . PHP_EOL;

                                return 5;
                            }
                            // @codeCoverageIgnoreEnd

                            $initializeVolumeCommand =
                                'docker run --rm --mount source='
                                . $mountName
                                . ',target=/tmp/tempo '
                                . $scriptArray['image_name'] . ' '
                                . 'sh -c "cp -rfp '
                                . $directory
                                . '/* /tmp/tempo/ >/dev/null"';

                            $initializeVolumeProcess =
                                new LinuxForComposerProcess($initializeVolumeCommand);

                            echo PHP_EOL . 'Building mount point...' . PHP_EOL;

                            $initializeVolumeProcess->setTty($initializeVolumeProcess->isTtySupported());

                            $initializeVolumeProcess->setTimeout(null);

                            $initializeVolumeProcess->prepareProcess();

                            $initializeVolumeProcess->start();

                            $initializeVolumeProcess->wait();

                            $processStderr = $initializeVolumeProcess->getErrorOutput();

                            $returnCode = $initializeVolumeProcess->getExitCode();

                            // @codeCoverageIgnoreStart
                            //$output->writeln($process->getErrorOutput());
                            if (!empty($processStderr) || $returnCode > 0) {
                                echo $processStderr . PHP_EOL;

                                return 5;
                            }
                            // @codeCoverageIgnoreEnd
                        }
                    }
                }

                if (!empty($imageName)) {
                    $containerName = $imageName . hash('sha256', 'lfphp' . time());

                    $buildContainerProcess = new LinuxForComposerProcess(
                        $this->dockerRunCommand
                        . $modeOptions
                        . ' --name '
                        . $containerName . ' '
                        . $portOptions
                        . $mountOptions
                        . $volumeOptions
                        . $imageName
                    );

                    echo PHP_EOL . 'Starting container...' . PHP_EOL;

                    // @codeCoverageIgnoreStart
                    if (strtoupper(substr(PHP_OS, 0, 3)) === 'WIN') {
                        if (strstr(php_uname('v'), 'Windows 10') !== false && php_uname('r') == '10.0') {
                            $buildContainerProcess->setDecorateWindows(true);
                        } else {
                            $buildContainerProcess->setDecorateWindowsLegacy(true);
                        }
                    }

                    $buildContainerProcess->setTty($buildContainerProcess->isTtySupported());

                    $buildContainerProcess->setTimeout(null);

                    $buildContainerProcess->prepareProcess();

                    $buildContainerProcess->start();

                    $buildContainerProcess->wait(
                        function ($type, $data) {
                            echo $data;
                        }
                    );

                    // executes after the command finishes
                    if ($buildContainerProcess->isSuccessful()) {
                        file_put_contents(
                            VENDORFOLDERPID
                            . DIRECTORY_SEPARATOR
                            . 'composer'
                            . DIRECTORY_SEPARATOR
                            . 'linuxforcomposer.pid',
                            $containerName . PHP_EOL,
                            FILE_APPEND
                        );
                    } else {
                        return 5;
                    }
                    // @codeCoverageIgnoreEnd
                }

                break;

            case 'run':
                if (isset($mount)
                    && !empty($mount)
                    && strpos($mount[0], ':') === false
                ) {
                    $mountNames = $this->getMountNames($mount);

                    $mountOptions = $this->getMountOptions($mount);

                    $mountOptionsArray = explode('=', $mountOptions);

                    $directory = trim(array_pop($mountOptionsArray));

                    foreach ($mountNames as $mountName => $mountOption) {
                        $checkVolumeCommand = 'docker volume ls -q --filter name=' . $mountName;

                        $checkVolumeProcess =
                            new LinuxForComposerProcess($checkVolumeCommand);

                        $temp_filename = tempnam(sys_get_temp_dir(), 'lfcprvcheck');

                        // @codeCoverageIgnoreStart
                        if (strtoupper(substr(PHP_OS, 0, 3)) === 'WIN') {
                            if (strstr(php_uname('v'), 'Windows 10') !== false && php_uname('r') == '10.0') {
                                $checkVolumeProcess->setDecorateWindowsWithStdout(true, $temp_filename);
                            } else {
                                $temp_filename = $this->winNormalizePath($temp_filename);
                                $checkVolumeProcess->setDecorateWindowsLegacyWithStdout(true, $temp_filename);
                            }
                        // @codeCoverageIgnoreEnd
                        } else {
                            $checkVolumeProcess->setTempFilename($temp_filename);

                            $checkVolumeProcess->setDockerCommand('/bin/bash & '
                                . $checkVolumeCommand
                                . ' > '
                                . $temp_filename);
                        }

                        $checkVolumeProcess->setTty($checkVolumeProcess->isTtySupported());

                        $checkVolumeProcess->setTimeout(null);

                        $checkVolumeProcess->prepareProcess();

                        $checkVolumeProcess->start();

                        $checkVolumeProcess->wait();

                        $processStdout = trim(file_get_contents(($temp_filename)));

                        //$output->writeln($process->getErrorOutput());
                        if (empty($processStdout)) {
                            if (LFPHP) {
                                // @codeCoverageIgnoreStart
                                //$volumeUnit = '';
                                //preg_match('/[a-zA-Z]+/', LFPHP_VOLSIZE, $volumeUnit);
                                //$numberVolumes = count($mountNames);
                                //$totalVolSize = (int) LFPHP_VOLSIZE;
                                //$volumeSize = floor($totalVolSize / $numberVolumes) . strtoupper($volumeUnit[0]);

                                // docker volume create -d flocker -o size=
                                // docker volume create -d local -o o=size=
                                // docker volume create -d local -o type=tmpfs -o device=tmpfs -o o=size=100m,uid=1000
                                $createVolumeCommand = 'docker volume create -d local -o type=ext4 -o device=/dev/vg00/' . LFPHP_ACCOUNT . ' ' . $mountName;
                                // @codeCoverageIgnoreEnd
                            } else {
                                $createVolumeCommand = 'docker volume create ' . $mountName;
                            }

                            $createVolumeProcess =
                                new LinuxForComposerProcess($createVolumeCommand);

                            $createVolumeProcess->setTty($createVolumeProcess->isTtySupported());

                            $createVolumeProcess->setTimeout(null);

                            $createVolumeProcess->prepareProcess();

                            $createVolumeProcess->start();

                            $createVolumeProcess->wait();

                            $processStderr = $createVolumeProcess->getErrorOutput();

                            $returnCode = $createVolumeProcess->getExitCode();

                            // @codeCoverageIgnoreStart
                            //$output->writeln($process->getErrorOutput());
                            if (!empty($processStderr) || $returnCode > 0) {
                                echo $processStderr . PHP_EOL;

                                return 5;
                            }
                            // @codeCoverageIgnoreEnd

                            $initializeVolumeCommand =
                                'docker run --rm --mount source='
                                . $mountName
                                . ',target=/tmp/tempo '
                                . 'asclinux/linuxforphp-8.2-ultimate:src '
                                . 'bash -c "rsync -avP --delete-before '
                                . $directory
                                . '/ /tmp/tempo/ >/dev/null"';

                            $initializeVolumeProcess =
                                new LinuxForComposerProcess($initializeVolumeCommand);

                            echo PHP_EOL . 'Building mount point...' . PHP_EOL;

                            $initializeVolumeProcess->setTty($initializeVolumeProcess->isTtySupported());

                            $initializeVolumeProcess->setTimeout(null);

                            $initializeVolumeProcess->prepareProcess();

                            $initializeVolumeProcess->start();

                            $initializeVolumeProcess->wait();

                            $processStderr = $initializeVolumeProcess->getErrorOutput();

                            $returnCode = $initializeVolumeProcess->getExitCode();

                            // @codeCoverageIgnoreStart
                            //$output->writeln($process->getErrorOutput());
                            if (!empty($processStderr) || $returnCode > 0) {
                                echo $processStderr . PHP_EOL;

                                return 5;
                            }
                            // @codeCoverageIgnoreEnd
                        }
                    }
                }

                $this->formatInput($input);

                $temp_filename = tempnam(sys_get_temp_dir(), 'lfcprv');

                $runContainerProcess = new LinuxForComposerProcess($this->dockerRunCommand);

                echo PHP_EOL . 'Starting container...' . PHP_EOL;

                // @codeCoverageIgnoreStart
                if ($input->getOption('detached') !== false) {
                    if (strtoupper(substr(PHP_OS, 0, 3)) === 'WIN') {
                        if (strstr(php_uname('v'), 'Windows 10') !== false && php_uname('r') == '10.0') {
                            $runContainerProcess->setDecorateWindowsWithStdout(true, $temp_filename);
                        } else {
                            $temp_filename = $this->winNormalizePath($temp_filename);
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

                $runContainerProcess->setTty($runContainerProcess->isTtySupported());

                $runContainerProcess->setTimeout(null);

                $runContainerProcess->prepareProcess();

                $runContainerProcess->start();

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
                // @codeCoverageIgnoreStart
                } else {
                    return 5;
                }
                // @codeCoverageIgnoreEnd

                // @codeCoverageIgnoreStart
                if (!empty($this->tempScriptFile)) {
                    // Allow for a clean restart with Docker on Cloud servers using a Linux/Unix OS.
                    if (LFPHP && strtoupper(substr(PHP_OS, 0, 3)) !== 'WIN') {
                        unlink($this->tempScriptFile);

                        file_put_contents($this->tempScriptFile, "#!/usr/bin/env bash\nlfphp");

                        chmod($this->tempScriptFile, 777); // Must be world-writable for Mac computers.
                    }
                }
                // @codeCoverageIgnoreEnd

                //throw new ProcessFailedException($process);

                break;

            case 'stop-force':
                $stopForce = true;

                // break; Fall through. Deliberately not breaking here.

            case 'stop':
                $stopForce = isset($stopForce) ?: false;

                if (isset($scriptArray['engine'])) {
                    $engine = $scriptArray['engine'];

                    $url = $scriptArray['url'];

                    $urlArray = parse_url($url);

                    $pathArray = explode('/', $urlArray['path']);

                    if (isset($urlArray['host']) && strpos($urlArray['scheme'], 'http') !== false) {
                        $path = array_pop($pathArray);
                    } elseif (!isset($urlArray['host'])) {
                        $path = $urlArray['path'];

                        // @codeCoverageIgnoreStart
                        if (strtoupper(substr(PHP_OS, 0, 3)) === 'WIN') {
                            if (strstr(php_uname('v'), 'Windows 10') !== false && php_uname('r') == '10.0') {
                                $path = $this->winNormalizePath($path);
                            }
                        }
                        // @codeCoverageIgnoreEnd
                    }
                } else {
                    $engine = '';

                    $url = '';

                    $urlArray = [];

                    $pathArray = [];

                    $path = '';
                }

                if ($engine === 'docker-compose') {
                    if (file_exists($path)) {
                        chdir($path);

                        $dockerStopCommand = 'docker-compose down -v';

                        $buildContainerProcess = new LinuxForComposerProcess($dockerStopCommand);

                        echo PHP_EOL . 'Stopping all containers...' . PHP_EOL;

                        $buildContainerProcess->setTty($buildContainerProcess->isTtySupported());

                        $buildContainerProcess->setTimeout(null);

                        $buildContainerProcess->prepareProcess();

                        $buildContainerProcess->start();

                        // @codeCoverageIgnoreStart
                        $buildContainerProcess->wait(
                            function ($type, $data) {
                                echo $data;
                            }
                        );
                        // @codeCoverageIgnoreEnd

                        $processStderr = $buildContainerProcess->getErrorOutput();

                        $returnCode = $buildContainerProcess->getExitCode();

                        // @codeCoverageIgnoreStart
                        //$output->writeln($process->getErrorOutput());
                        if (!empty($processStderr) || $returnCode > 0) {
                            echo $processStderr . PHP_EOL;

                            return 5;
                        }
                        // @codeCoverageIgnoreEnd

                        break;
                    } else {
                        echo PHP_EOL
                            . 'URL is invalid!'
                            . PHP_EOL
                            . PHP_EOL
                            . 'Please make sure that the URL is allowed and valid,'
                            . PHP_EOL
                            . 'and that cURL and Git are available on your system.'
                            . PHP_EOL
                            . PHP_EOL;

                        return 4;
                    }
                }

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

                            $subvalue = substr($value, 0, strlen($value));

                            if ($stopForce === false) {
                                // @codeCoverageIgnoreStart
                                if (strtoupper(substr(PHP_OS, 0, 3)) === 'WIN') {
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

                                        $temp_filename = $this->winNormalizePath($temp_filename);

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

                                    $processStderr = $containerCommitInfoProcess->getErrorOutput();

                                    $returnCode = $containerCommitInfoProcess->getExitCode();

                                    //$output->writeln($process->getErrorOutput());
                                    if (!empty($processStderr) || $returnCode > 0) {
                                        echo $processStderr . PHP_EOL;

                                        return 5;
                                    }

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

                                        $returnCode = $commitContainerProcess->getExitCode();

                                        if (!empty($processStdout)) {
                                            echo $processStdout . PHP_EOL;
                                        }

                                        //$output->writeln($process->getErrorOutput());
                                        if (!empty($processStderr) || $returnCode > 0) {
                                            echo $processStderr . PHP_EOL;

                                            return 5;
                                        }
                                    }
                                // @codeCoverageIgnoreEnd
                                } else {
                                    $containerInfoProcess =
                                        new LinuxForComposerProcess('docker ps -a --filter "id=' . $subvalue . '"');
                                    $containerInfoProcess->setTty($containerInfoProcess->isTtySupported());
                                    $containerInfoProcess->setTimeout(null);
                                    $containerInfoProcess->prepareProcess();
                                    $containerInfoProcess->start();
                                    $containerInfoProcess->wait();

                                    $processStderr = $containerInfoProcess->getErrorOutput();

                                    $returnCode = $containerInfoProcess->getExitCode();

                                    //$output->writeln($process->getErrorOutput());
                                    if (!empty($processStderr) || $returnCode > 0) {
                                        echo $processStderr . PHP_EOL;

                                        return 5;
                                    }

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

                                        $returnCode = $commitContainerProcess->getExitCode();

                                        if (!empty($processStdout)) {
                                            echo $processStdout . PHP_EOL;
                                        }

                                        //$output->writeln($process->getErrorOutput());
                                        if (!empty($processStderr) || $returnCode > 0) {
                                            echo $processStderr . PHP_EOL;

                                            return 5;
                                        }
                                    }
                                    // @codeCoverageIgnoreEnd
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

                            $returnCode = $stopContainerProcess->getExitCode();

                            if (!empty($processStdout)) {
                                echo $processStdout . PHP_EOL;
                            }

                            // @codeCoverageIgnoreStart
                            //$output->writeln($process->getErrorOutput());
                            if (!empty($processStderr) || $returnCode > 0) {
                                echo $processStderr . PHP_EOL;

                                return 5;
                            }

                            if (isset($dockerRemoveCommand)) {
                                $removeContainerProcess =
                                    new LinuxForComposerProcess($dockerRemoveCommand);

                                $removeContainerProcess->setTty($removeContainerProcess->isTtySupported());

                                $removeContainerProcess->setTimeout(null);

                                $removeContainerProcess->prepareProcess();

                                $removeContainerProcess->start();

                                $removeContainerProcess->wait();

                                $processStdout = $removeContainerProcess->getOutput();

                                $processStderr = $removeContainerProcess->getErrorOutput();

                                $returnCode = $removeContainerProcess->getExitCode();

                                if (!empty($processStdout)) {
                                    echo $processStdout . PHP_EOL;
                                }

                                //$output->writeln($process->getErrorOutput());
                                if (!empty($processStderr) || $returnCode > 0) {
                                    echo $processStderr . PHP_EOL;

                                    return 5;
                                }
                            }
                            // @codeCoverageIgnoreEnd

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

                if (isset($mount)
                    && !empty($mount)
                    && strpos($mount[0], ':') !== false
                ) {
                    $mountNames = $this->getMountNames($mount);

                    foreach ($mountNames as $mountName => $mountOption) {
                        $checkVolumeCommand = 'docker volume ls -q --filter name=' . $mountName;

                        $checkVolumeProcess =
                            new LinuxForComposerProcess($checkVolumeCommand);

                        $temp_filename = tempnam(sys_get_temp_dir(), 'lfcprvcheck');

                        // @codeCoverageIgnoreStart
                        if (strtoupper(substr(PHP_OS, 0, 3)) === 'WIN') {
                            if (strstr(php_uname('v'), 'Windows 10') !== false && php_uname('r') == '10.0') {
                                $checkVolumeProcess->setDecorateWindowsWithStdout(true, $temp_filename);
                            } else {
                                $temp_filename = $this->winNormalizePath($temp_filename);
                                $checkVolumeProcess->setDecorateWindowsLegacyWithStdout(true, $temp_filename);
                            }
                        // @codeCoverageIgnoreEnd
                        } else {
                            $checkVolumeProcess->setTempFilename($temp_filename);

                            $checkVolumeProcess->setDockerCommand('/bin/bash & '
                                . $checkVolumeCommand
                                . ' > '
                                . $temp_filename);
                        }

                        $checkVolumeProcess->setTty($checkVolumeProcess->isTtySupported());

                        $checkVolumeProcess->setTimeout(null);

                        $checkVolumeProcess->prepareProcess();

                        $checkVolumeProcess->start();

                        $checkVolumeProcess->wait();

                        $processStdout = trim(file_get_contents(($temp_filename)));

                        // @codeCoverageIgnoreStart
                        //$output->writeln($process->getErrorOutput());
                        if (!empty($processStdout)) {
                            $createVolumeCommand = 'docker volume rm ' . $mountName;

                            $createVolumeProcess =
                                new LinuxForComposerProcess($createVolumeCommand);

                            $createVolumeProcess->setTty($createVolumeProcess->isTtySupported());

                            $createVolumeProcess->setTimeout(null);

                            $createVolumeProcess->prepareProcess();

                            $createVolumeProcess->start();

                            $createVolumeProcess->wait();

                            $processStderr = $createVolumeProcess->getErrorOutput();

                            $returnCode = $createVolumeProcess->getExitCode();

                            //$output->writeln($process->getErrorOutput());
                            if (!empty($processStderr) || $returnCode > 0) {
                                echo $processStderr . PHP_EOL;

                                return 5;
                            }
                        }
                        // @codeCoverageIgnoreEnd
                    }
                }

                break;

            default:
                echo PHP_EOL . 'Wrong command given!' . PHP_EOL . PHP_EOL;

                return 1;

                break;
        }

        return 0;
    }

    protected function checkImage($phpversionFull, $threadsafe)
    {
        $phpversionFull = (string) $phpversionFull;
        $threadsafe = (string) $threadsafe;

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
                $temp_filename = $this->winNormalizePath($temp_filename);
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

        echo PHP_EOL . 'Done!' . PHP_EOL . PHP_EOL;

        $imageName = '';

        if ($checkLocalExitCode !== 0) {
            echo 'Compiling from source...' . PHP_EOL . PHP_EOL;

            $imageName .= DockerManageCommand::LFPHPDEFAULTVERSION . ':src ';
        }

        return $imageName;
    }

    protected function formatInput(InputInterface $input)
    {
        $this->dockerRunCommand .= ($input->getOption('detached')) ? '-d ' : null;
        $this->dockerRunCommand .= ($input->getOption('interactive')) ? '-i ' : null;
        $this->dockerRunCommand .= ($input->getOption('tty')) ? '-t ' : null;

        $ports = $input->getOption('port');

        $this->dockerRunCommand .= $this->getPortOptions($ports);

        $mount = $input->getOption('mount');

        if (isset($mount)
            && !empty($mount)
            && strpos($mount[0], ':') === false
        ) {
            $this->dockerRunCommand .= $this->getMountOptions($mount);
        }

        $volumes = $input->getOption('volume');

        $this->dockerRunCommand .= $this->getVolumeOptions($volumes);

        $threadsafe = $input->getOption('threadsafe');

        $phpversion =
            !empty($input->getOption('phpversion'))
                ? $input->getOption('phpversion')
                : DockerManageCommand::PHPDEFAULTVERSION;

        $phpversionFull = $phpversion . '-'. $threadsafe;

        $checkImageName = '';

        if (strpos($phpversionFull, 'custom') === false) {
            $checkImageName = $this->checkImage($phpversionFull, $threadsafe);
        }

        $script = ($input->getOption('script')) ?: 'lfphp';

        $tempScriptFile = '';

        if (strpos($script, ',,,') === false) {
            if (!empty($checkImageName)) {
                $this->dockerRunCommand .= $checkImageName;

                $this->dockerRunCommand .=
                    '/bin/bash -c "lfphp-compile '
                    . $phpversion . ' ' . $threadsafe
                    . ' ; '. $script . '"';
            } else {
                $this->dockerRunCommand .= DockerManageCommand::LFPHPDEFAULTVERSION . ':' . $phpversionFull . ' ';

                $this->dockerRunCommand .= '/bin/bash -c "' . $script . '"';
            }
        } else {
            $scriptArray = explode(',,,', $script);

            if (!empty($checkImageName)) {
                array_unshift($scriptArray, 'lfphp-compile ' . $phpversion . ' ' . $threadsafe);
                array_push($scriptArray, 'lfphp');
            }

            $script = implode("\n", $scriptArray);

            $tempScriptFile = tempnam(VENDORFOLDERPID . DIRECTORY_SEPARATOR . 'composer', 'entryscript');

            $tempScriptFilePath = '';

            $handle = fopen($tempScriptFile, 'w+');
            fwrite($handle, '#!/usr/bin/env bash' . "\n");
            fwrite($handle, $script);
            fclose($handle);
            chmod($tempScriptFile, 777); // Must be world-writable for Mac computers.

            if (strtoupper(substr(PHP_OS, 0, 3)) === 'WIN') {
                // @codeCoverageIgnoreStart
                if (strstr(php_uname('v'), 'Windows 10') === false && php_uname('r') != '10.0') {
                    $tempScriptFilePath = $this->winNormalizePath($tempScriptFile);
                    $tempScriptFilePath = lcfirst($tempScriptFilePath);
                    $tempScriptFilePath = str_replace(':/', '/', $tempScriptFilePath);
                    $tempScriptFilePath = '/' . $tempScriptFilePath;
                }
                // @codeCoverageIgnoreEnd
            }

            if (empty($tempScriptFilePath)) {
                $tempScriptFilePath = $tempScriptFile;
            }

            $this->dockerRunCommand .= '-v ' . $tempScriptFilePath . ':/tmp/script.bash --entrypoint /tmp/script.bash ';

            if (!empty($checkImageName)) {
                $this->dockerRunCommand .= trim($checkImageName);
            } else {
                $this->dockerRunCommand .= DockerManageCommand::LFPHPDEFAULTVERSION . ':' . $phpversionFull;
            }
        }

        $this->tempScriptFile = $tempScriptFile;

        return $this->dockerRunCommand;
    }

    protected function getPortOptions($ports)
    {
        $portOptions = '';

        if (is_array($ports)) {
            if (!empty($ports) && !in_array('', $ports)) {
                foreach ($ports as $portMap) {
                    if (!empty($portMap)) {
                        $portOptions .= '-p ' . $portMap . ' ';
                    }
                }
            }
        } else {
            if (!empty($ports)) {
                $portOptions .= '-p ' . $ports . ' ';
            }
        }

        return $portOptions;
    }

    protected function getMountNames($mount)
    {
        $mountNames = [];

        if (is_array($mount)) {
            $mountArray = explode(',,,,', $mount[0]);

            foreach ($mountArray as $singleMount) {
                if (!empty($singleMount)
                    && strpos($singleMount, ',,,') !== false
                    && strpos($singleMount, ':') === false
                ) {
                    $singleMountArray = explode(',,,', $singleMount);

                    $mountNames[$singleMountArray[1]] = $singleMountArray[0];
                } elseif (!empty($singleMount)
                    && strpos($singleMount, ':') !== false
                ) {
                    $singleMount = str_replace(':', '', $singleMount);

                    $mountNames[$singleMount] = $singleMount;
                }
            }
        }

        return $mountNames;
    }

    protected function getMountOptions($mount)
    {
        $mountNames = $this->getMountNames($mount);

        $mountOptions = '';

        foreach ($mountNames as $singleMount) {
            if (strpos($singleMount, 'source=') !== false) {
                $mountOptions .= '--mount ';

                $mountOptions .= $singleMount;

                $mountOptions .= ' ';
            }
        }

        return $mountOptions;
    }

    protected function getVolumeOptions($volumes)
    {
        $volumeOptions = '';

        if (is_array($volumes)) {
            if (!empty($volumes) && !in_array('', $volumes)) {
                foreach ($volumes as $volumeMap) {
                    if (!empty($volumeMap)) {
                        if (strtoupper(substr(PHP_OS, 0, 3)) === 'WIN') {
                            // @codeCoverageIgnoreStart
                            if (strstr(php_uname('v'), 'Windows 10') !== false && php_uname('r') == '10.0') {
                                $volumeMap = $this->winNormalizePath($volumeMap);
                            } else {
                                $volumeMap = $this->winNormalizePath($volumeMap);
                                $volumeMap = lcfirst($volumeMap);
                                if (strpos($volumeMap, ':') === 1) {
                                    $volumeMap = substr_replace($volumeMap, '', 1, 1);
                                    $volumeMap = '/' . $volumeMap;
                                }
                            }
                            // @codeCoverageIgnoreEnd
                        }

                        $volumeOptions .= '-v ' . $volumeMap . ' ';
                    }
                }
            }
        } else {
            if (!empty($volumes)) {
                if (strtoupper(substr(PHP_OS, 0, 3)) === 'WIN') {
                    // @codeCoverageIgnoreStart
                    if (strstr(php_uname('v'), 'Windows 10') === false && php_uname('r') != '10.0') {
                        $volumes = $this->winNormalizePath($volumes);
                        $volumes = lcfirst($volumes);
                        $volumes = str_replace(':/', '/', $volumes);
                        $volumes = '/' . $volumes;
                    }
                    // @codeCoverageIgnoreEnd
                }

                $volumeOptions .= '-v ' . $volumes . ' ';
            }
        }

        return $volumeOptions;
    }

    protected function getScriptOptions($script)
    {
        $scriptArray = explode(',,,', $script);

        $scriptOptions = [];

        if (strpos($scriptArray[0], 'dockerfile') !== false
            || strpos($scriptArray[0], 'docker-compose') !== false
        ) {
            $scriptOptions['engine'] = $scriptArray[0];

            $scriptOptions['url'] = $scriptArray[1];

            if (isset($scriptArray[2]) && strpos($scriptArray[2], ':') !== false) {
                $scriptOptions['auth'] = $scriptArray[2];
            } elseif (isset($scriptArray[2]) && strpos($scriptArray[2], ':') === false) {
                $scriptOptions['auth'] = '';
                $scriptOptions['image_name'] = $scriptArray[2];
            } else {
                $scriptOptions['auth'] = '';
            }

            if (!isset($scriptOptions['image_name']) && isset($scriptArray[3])) {
                $scriptOptions['image_name'] = $scriptArray[3];
            } elseif (!isset($scriptOptions['image_name']) && !isset($scriptArray[3])) {
                $scriptOptions['image_name'] = '';
            }
        } else {
            $scriptOptions = $scriptArray;
        }

        return $scriptOptions;
    }

    // @codeCoverageIgnoreStart
    protected function winNormalizePath($path)
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
