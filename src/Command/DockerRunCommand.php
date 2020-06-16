<?php
/**
 * Linux for PHP/Linux for Composer
 *
 * Copyright 2017 - 2020 Foreach Code Factory <lfphp@asclinux.net>
 * Version 2.0.8
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
use Symfony\Component\Console\Input\ArrayInput;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\BufferedOutput;
use Symfony\Component\Console\Output\OutputInterface;
use Linuxforcomposer\Helper\LinuxForComposerProcess;

class DockerRunCommand extends Command
{
    const LFPHPCLOUDSERVER = 'https://linuxforphp.com/api/v1/deployments';

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
        $arguments = $input->getArguments();

        // @codeCoverageIgnoreStart
        if ($arguments['command'] === 'docker:run'
            && $arguments['execute'] === 'start'
            && file_exists(
                VENDORFOLDERPID
                . DIRECTORY_SEPARATOR
                . 'composer'
                . DIRECTORY_SEPARATOR
                . 'linuxforcomposer.pid'
            )) {
            echo PHP_EOL
                . "Attention: before starting new containers, please enter the 'stop' command"
                . PHP_EOL
                . "in order to shut down the current containers properly."
                . PHP_EOL
                . PHP_EOL;
            exit;
        }
        // @codeCoverageIgnoreEnd

        $dockerManageCommandsArray = $this->getParsedJsonFile($input);

        if (is_int($dockerManageCommandsArray)) {
            return $dockerManageCommandsArray;
        }

        foreach ($dockerManageCommandsArray as $key => $value) {
            if (empty($value)) {
                unset($dockerManageCommandsArray[$key]);
                continue;
            }

            // @codeCoverageIgnoreStart
            if (($position = strrpos($value, 'build')) === false
                && ($position = strrpos($value, 'run')) === false
            ) {
                echo PHP_EOL . "The 'Linux for Composer' JSON file is invalid." . PHP_EOL . PHP_EOL;
                return 1;
            }
            // @codeCoverageIgnoreEnd
        }

        switch ($input->getArgument('execute')) {
            case 'start':
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

                    $returnCode = $process->getExitCode();

                    //$output->writeln($process->getOutput());
                    if (!empty($processStdout)) {
                        echo $processStdout . PHP_EOL;
                    }

                    //$output->writeln($process->getErrorOutput());
                    if (!empty($processStderr) || $returnCode > 0) {
                        echo $processStderr . PHP_EOL;
                    }
                }

                break;

            case 'stop-force':
                $stopForce = true;

                // break; Fall through. Deliberately not breaking here.

            case 'stop':
                $stopForce = isset($stopForce) ?: false;

                $stopCommand = $stopForce ? 'stop-force' : 'stop';

                if (($position = strrpos($dockerManageCommandsArray[0], 'build')) !== false) {
                    $searchLength = strlen('build');
                    $dockerManageCommand = substr_replace(
                        $dockerManageCommandsArray[0],
                        $stopCommand,
                        $position,
                        $searchLength
                    );
                } elseif (($position = strrpos($dockerManageCommandsArray[0], 'run')) !== false) {
                    $searchLength = strlen('run');
                    $dockerManageCommand = substr_replace(
                        $dockerManageCommandsArray[0],
                        $stopCommand,
                        $position,
                        $searchLength
                    );
                }

                $process = new LinuxForComposerProcess($dockerManageCommand);

                $process->setTty($process->isTtySupported());

                $process->setTimeout(null);

                $process->prepareProcess();

                $process->start();

                $process->wait();

                $processStdout = $process->getOutput();

                $processStderr = $process->getErrorOutput();

                $returnCode = $process->getExitCode();

                //$output->writeln($process->getOutput());
                if (!empty($processStdout)) {
                    echo $processStdout . PHP_EOL;
                }

                //$output->writeln($process->getErrorOutput());
                if (!empty($processStderr) || $returnCode > 0) {
                    echo $processStderr . PHP_EOL;

                    return $returnCode;
                }

                break;

            // @codeCoverageIgnoreStart
            case 'deploy':
                set_time_limit(0);

                $jsonFile = ($input->getOption('jsonfile')) ?: null;

                if (($jsonFile === null || !file_exists($jsonFile)) && file_exists(JSONFILE)) {
                    $jsonFile = JSONFILE;
                } elseif (($jsonFile === null || !file_exists($jsonFile)) && !file_exists(JSONFILE)) {
                    $jsonFile = JSONFILEDIST;
                }

                $fileContentsJson = file_get_contents($jsonFile);

                $fileContentsArray = json_decode($fileContentsJson, true);

                if ($fileContentsArray === null) {
                    echo PHP_EOL . "The 'Linux for Composer' JSON file is invalid." . PHP_EOL . PHP_EOL;
                    return 1;
                }

                $account = $fileContentsArray['lfphp-cloud']['account'];

                $username = $fileContentsArray['lfphp-cloud']['username'];

                $token = $fileContentsArray['lfphp-cloud']['token'];

                if (empty($account) || empty($username) || empty($token)) {
                    echo PHP_EOL
                        . PHP_EOL
                        . "Insufficient information in order to deploy to the Cloud."
                        . PHP_EOL
                        . PHP_EOL;
                    return 7;
                }

                $cloudServerUrl = DockerRunCommand::LFPHPCLOUDSERVER . '/' . $account;

                $ch = \curl_init($cloudServerUrl);
                \curl_setopt($ch, CURLOPT_TIMEOUT, 50);
                \curl_setopt($ch, CURLOPT_FOLLOWLOCATION, true);
                \curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
                \curl_setopt($ch, CURLOPT_POST, true);

                $postData = [
                    'account' => $account,
                    'username' => $username,
                    'token' => $token,
                    'json' => $fileContentsJson,
                ];

                if (isset($fileContentsArray['single']['image']['dockerfile'])
                    && !empty($fileContentsArray['single']['image']['dockerfile']['url'])
                ) {
                    $url = $fileContentsArray['single']['image']['dockerfile']['url'];

                    $urlArray = parse_url($url);

                    if (isset($urlArray['host']) && isset($urlArray['scheme'])) {
                        $pathArray = explode('/', $urlArray['path']);

                        $filename = array_pop($pathArray);
                    } else {
                        $filename = $urlArray['path'];
                    }

                    $path = BASEDIR . DIRECTORY_SEPARATOR . $filename;

                    if (file_exists($path)) {
                        $curlFile = curl_file_create($path);
                        $postData['file'] = $curlFile;
                    }
                }

                \curl_setopt($ch, CURLOPT_POSTFIELDS, $postData);

                $headers = [
                    //'X-Apple-Tz: 0',
                    //'X-Apple-Store-Front: 143444,12',
                    //'Accept: text/html,application/xhtml+xml,application/xml;q=0.9,*/*;q=0.8',
                    'Accept: application/json',
                    'Accept-Encoding: gzip, deflate',
                    'Accept-Language: en-US,en;q=0.5',
                    'Cache-Control: no-cache',
                    //'Content-Type: application/x-www-form-urlencoded; charset=utf-8',
                    //'Host: www.example.com',
                    //'Referer: http://www.example.com/index.php', //Your referrer address
                    //'User-Agent: Mozilla/5.0 (X11; Ubuntu; Linux i686; rv:28.0) Gecko/20100101 Firefox/28.0',
                    //'X-MicrosoftAjax: Delta=true'
                    'User-Agent: Linux for PHP Deployment Client',
                ];

                curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);
                //curl_setopt($ch, CURLOPT_USERPWD, "user:pass");

                $response = \curl_exec($ch);
                $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
                \curl_close($ch);

                if ($httpCode === 0 && $response === false) {
                    echo PHP_EOL
                        . 'The Linux for PHP Cloud Services are currently unavailable.'
                        . PHP_EOL
                        . 'Please try again later.'
                        . PHP_EOL
                        . PHP_EOL;
                } else {
                    echo PHP_EOL . $httpCode . PHP_EOL . $response . PHP_EOL;

                    switch ($httpCode) {
                        case 200:
                        case 201:
                            echo 'Payload sent and deployed to the LfPHP Cloud.'
                                . PHP_EOL
                                . PHP_EOL;
                            break;
                        case 400:
                            echo 'The request is invalid.'
                                . PHP_EOL
                                . PHP_EOL;
                            break;
                        case 401:
                            echo 'Valid credentials are required.'
                                . PHP_EOL
                                . PHP_EOL;
                            break;
                        case 403:
                            echo 'Access is forbidden.'
                                . PHP_EOL
                                . PHP_EOL;
                            break;
                        default:
                            echo 'Unable to complete the deployment. '
                                . 'Please contact support.'
                                . PHP_EOL
                                . PHP_EOL;
                            break;
                    }
                }

                break;
            // @codeCoverageIgnoreEnd

            default:
                echo PHP_EOL . 'Wrong command given!' . PHP_EOL . PHP_EOL;

                return 1; //break;
        }

        return 0;
    }

    protected function getParsedJsonFile(InputInterface $input)
    {
        $parseCommand = $this->getApplication()->find('docker:parsejson');

        $jsonFile = ($input->getOption('jsonfile')) ?: null;

        if ($jsonFile !== null) {
            $arguments = [
                '--jsonfile' => $jsonFile,
            ];
        } else {
            $arguments = [];
        }

        $parseInput = new ArrayInput($arguments);

        $parseOutput = new BufferedOutput();

        $returnCode = (int) $parseCommand->run($parseInput, $parseOutput);

        if ($returnCode > 1) {
            echo PHP_EOL . "Please check your 'Linux for Composer' JSON file for misconfigurations." . PHP_EOL . PHP_EOL;
            return $returnCode;
        } elseif ($returnCode === 1) {
            echo PHP_EOL . "The 'Linux for Composer' JSON file is invalid." . PHP_EOL . PHP_EOL;
            return 1;
        }

        return explode("\n", $parseOutput->fetch());
    }
}
