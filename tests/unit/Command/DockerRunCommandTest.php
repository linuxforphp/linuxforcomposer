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

namespace LinuxforcomposerTest\Command;

use Linuxforcomposer\Command\DockerParsejsonCommand;
use Linuxforcomposer\Command\DockerRunCommand;
use Symfony\Bundle\FrameworkBundle\Console\Application;
use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;
use Symfony\Component\Console\Tester\CommandTester;

/**
 * @runTestsInSeparateProcesses
 * @preserveGlobalState disabled
 */
class DockerRunCommandTest extends KernelTestCase
{
    protected $dockerLfcProcessMock;

    public static function setUpBeforeClass(): void
    {
        if (!defined('PHARFILENAME')) {
            define(
                'PHARFILENAME',
                dirname(__DIR__)
                . DIRECTORY_SEPARATOR
                . 'app'
                . DIRECTORY_SEPARATOR
                . 'app.php'
            );
        }

        if (!defined('JSONFILEDIST')) {
            define(
                'JSONFILEDIST',
                dirname(__DIR__)
                . DIRECTORY_SEPARATOR
                . 'app'
                . DIRECTORY_SEPARATOR
                . 'linuxforcomposer.test.dist.json'
            );
        }

        if (!defined('JSONFILE')) {
            define(
                'JSONFILE',
                dirname(__DIR__)
                . DIRECTORY_SEPARATOR
                . 'app'
                . DIRECTORY_SEPARATOR
                . 'linuxforcomposer.test.json'
            );
        }
    }

    public function tearDown(): void
    {
        \Mockery::close();

        parent::tearDown();
    }

    public function createMocksForUnixEnv()
    {
        $this->dockerLfcProcessMock = \Mockery::mock('overload:Linuxforcomposer\Helper\LinuxForComposerProcess');
        $this->dockerLfcProcessMock
            ->shouldReceive('isTtySupported')
            ->withAnyArgs();
        $this->dockerLfcProcessMock
            ->shouldReceive('setTty')
            ->withAnyArgs();
        $this->dockerLfcProcessMock
            ->shouldReceive('setTimeout')
            ->once()
            ->with(null);
        $this->dockerLfcProcessMock
            ->shouldReceive('prepareProcess')
            ->once();
        $this->dockerLfcProcessMock
            ->shouldReceive('start')
            ->once();
        $this->dockerLfcProcessMock
            ->shouldReceive('wait')
            ->once();
    }

    public function testExecuteWithWrongCommand()
    {
        // Redirect output to command output
        $this->setOutputCallback(function () {
        });

        $kernel = self::bootKernel();

        $application = new Application($kernel);
        $application->add(new DockerRunCommand());
        $application->add(new DockerParsejsonCommand());

        $command = $application->find('docker:run');
        $commandTester = new CommandTester($command);
        $commandTester->execute([
            'command'  => $command->getName(),
            'execute'  => 'bad',
        ]);

        // the output of the command in the console
        //$output = $commandTester->getDisplay();
        $this->assertStringContainsString(
            PHP_EOL
            . 'Wrong command given!'
            . PHP_EOL,
            $this->getActualOutput()
        );
    }

    public function testExecuteWithWrongCommandAndCustomJsonFile()
    {
        // Redirect output to command output
        $this->setOutputCallback(function () {
        });

        $kernel = self::bootKernel();

        $application = new Application($kernel);
        $application->add(new DockerRunCommand());
        $application->add(new DockerParsejsonCommand());

        $command = $application->find('docker:run');
        $commandTester = new CommandTester($command);
        $commandTester->execute([
            'command'  => $command->getName(),
            'execute'  => 'bad',
            '--jsonfile' => dirname(__DIR__)
                . DIRECTORY_SEPARATOR
                . 'app'
                . DIRECTORY_SEPARATOR
                . 'linuxforcomposer.test.custom.json',
        ]);

        // the output of the command in the console
        //$output = $commandTester->getDisplay();
        $this->assertStringContainsString(
            PHP_EOL
            . 'Wrong command given!'
            . PHP_EOL,
            $this->getActualOutput()
        );
    }

    public function testExecuteWithWrongCommandAndNonexistentCustomJsonFile()
    {
        // Redirect output to command output
        $this->setOutputCallback(function () {
        });

        $kernel = self::bootKernel();

        $application = new Application($kernel);
        $application->add(new DockerRunCommand());
        $application->add(new DockerParsejsonCommand());

        $command = $application->find('docker:run');
        $commandTester = new CommandTester($command);
        $commandTester->execute([
            'command'  => $command->getName(),
            'execute'  => 'bad',
            '--jsonfile' => dirname(__DIR__)
                . DIRECTORY_SEPARATOR
                . 'app'
                . DIRECTORY_SEPARATOR
                . 'linuxforcomposer.test.custom.wrong.json',
        ]);

        // the output of the command in the console
        //$output = $commandTester->getDisplay();
        $this->assertStringContainsString(
            PHP_EOL
            . 'Wrong command given!'
            . PHP_EOL,
            $this->getActualOutput()
        );
    }

    public function testExecuteWithStartCommand()
    {
        // Redirect output to command output
        $this->setOutputCallback(function () {
        });

        $this->createMocksForUnixEnv();

        $this->dockerLfcProcessMock
            ->shouldReceive('getOutput')
            ->once()
            ->andReturn('Fake Docker is running!');
        $this->dockerLfcProcessMock
            ->shouldReceive('getErrorOutput')
            ->once()
            ->andReturn('');
        $this->dockerLfcProcessMock
            ->shouldReceive('getExitCode')
            ->andReturn(0);

        $kernel = self::bootKernel();

        $application = new Application($kernel);
        $application->add(new DockerRunCommand());
        $application->add(new DockerParsejsonCommand());

        $command = $application->find('docker:run');
        $commandTester = new CommandTester($command);
        $commandTester->execute([
            'command'  => $command->getName(),
            'execute'  => 'start',
        ]);

        $this->assertSame(
            'Fake Docker is running!'
            . PHP_EOL
            . 'Fake Docker is running!'
            . PHP_EOL
            . 'Fake Docker is running!'
            . PHP_EOL,
            $this->getActualOutput()
        );
    }

    public function testExecuteWithStartCommandAndCustomJsonFile()
    {
        // Redirect output to command output
        $this->setOutputCallback(function () {
        });

        $this->createMocksForUnixEnv();

        $this->dockerLfcProcessMock
            ->shouldReceive('getOutput')
            ->once()
            ->andReturn('Fake Docker is running!');
        $this->dockerLfcProcessMock
            ->shouldReceive('getErrorOutput')
            ->once()
            ->andReturn('');
        $this->dockerLfcProcessMock
            ->shouldReceive('getExitCode')
            ->andReturn(0);

        $kernel = self::bootKernel();

        $application = new Application($kernel);
        $application->add(new DockerRunCommand());
        $application->add(new DockerParsejsonCommand());

        $command = $application->find('docker:run');
        $commandTester = new CommandTester($command);
        $commandTester->execute([
            'command'  => $command->getName(),
            'execute'  => 'start',
            '--jsonfile' => dirname(__DIR__)
                . DIRECTORY_SEPARATOR
                . 'app'
                . DIRECTORY_SEPARATOR
                . 'linuxforcomposer.test.custom.json',
        ]);

        $this->assertSame(
            'Fake Docker is running!'
            . PHP_EOL,
            $this->getActualOutput()
        );
    }

    public function testExecuteWithStartCommandAndNonexistentCustomJsonFile()
    {
        // Redirect output to command output
        $this->setOutputCallback(function () {
        });

        $this->createMocksForUnixEnv();

        $this->dockerLfcProcessMock
            ->shouldReceive('getOutput')
            ->once()
            ->andReturn('Fake Docker is running!');
        $this->dockerLfcProcessMock
            ->shouldReceive('getErrorOutput')
            ->once()
            ->andReturn('');
        $this->dockerLfcProcessMock
            ->shouldReceive('getExitCode')
            ->andReturn(0);

        $kernel = self::bootKernel();

        $application = new Application($kernel);
        $application->add(new DockerRunCommand());
        $application->add(new DockerParsejsonCommand());

        $command = $application->find('docker:run');
        $commandTester = new CommandTester($command);
        $commandTester->execute([
            'command'  => $command->getName(),
            'execute'  => 'start',
            '--jsonfile' => dirname(__DIR__)
                . DIRECTORY_SEPARATOR
                . 'app'
                . DIRECTORY_SEPARATOR
                . 'linuxforcomposer.test.custom.wrong.json',
        ]);

        $this->assertSame(
            'Fake Docker is running!'
            . PHP_EOL
            . 'Fake Docker is running!'
            . PHP_EOL
            . 'Fake Docker is running!'
            . PHP_EOL,
            $this->getActualOutput()
        );
    }

    public function testExecuteWithStartCommandAndNonexistentJsonFiles()
    {
        copy(JSONFILE, JSONFILE . '.wrong');
        unlink(JSONFILE);

        // Redirect output to command output
        //$this->setOutputCallback(function () {
        //});

        ob_start();

        $this->createMocksForUnixEnv();

        $this->dockerLfcProcessMock
            ->shouldReceive('getOutput')
            ->once()
            ->andReturn('Fake Docker is running!');
        $this->dockerLfcProcessMock
            ->shouldReceive('getErrorOutput')
            ->once()
            ->andReturn('');
        $this->dockerLfcProcessMock
            ->shouldReceive('getExitCode')
            ->andReturn(0);

        $kernel = self::bootKernel();

        $application = new Application($kernel);
        $application->add(new DockerRunCommand());
        $application->add(new DockerParsejsonCommand());

        $command = $application->find('docker:run');
        $commandTester = new CommandTester($command);
        $commandTester->execute([
            'command'  => $command->getName(),
            'execute'  => 'start',
            '--jsonfile' => dirname(__DIR__)
                . DIRECTORY_SEPARATOR
                . 'app'
                . DIRECTORY_SEPARATOR
                . 'linuxforcomposer.test.custom.wrong.json',
        ]);

        copy(JSONFILE . '.wrong', JSONFILE);
        unlink(JSONFILE . '.wrong');

        $this->assertSame(
            'Fake Docker is running!'
            . PHP_EOL
            . 'Fake Docker is running!'
            . PHP_EOL
            . 'Fake Docker is running!'
            . PHP_EOL,
            $this->getActualOutput()
        );

        ob_end_clean();
    }

    public function testExecuteWithStartCommandWithInvalidJsonFile()
    {
        // Redirect output to command output
        //$this->setOutputCallback(function () {
        //});

        ob_start();

        $kernel = self::bootKernel();

        $application = new Application($kernel);
        $application->add(new DockerRunCommand());
        $application->add(new DockerParsejsonCommand());

        $command = $application->find('docker:run');
        $commandTester = new CommandTester($command);
        $commandTester->execute([
            'command'  => $command->getName(),
            'execute'  => 'start',
            '--jsonfile' => dirname(__DIR__)
                . DIRECTORY_SEPARATOR
                . 'app'
                . DIRECTORY_SEPARATOR
                . 'linuxforcomposer.test.invalid.json',
        ]);

        $this->assertSame(
            PHP_EOL
            . "The 'Linux for Composer' JSON file is invalid."
            . PHP_EOL
            . PHP_EOL,
            $this->getActualOutput()
        );

        ob_end_clean();
    }

    // Deactivating successful test due to an issue with Travis CI
    /*public function testExecuteWithStartCommandWithInvalidScriptJsonFile()
    {
        // Redirect output to command output
        //$this->setOutputCallback(function () {
        //});

        ob_start();

        $kernel = self::bootKernel();

        $application = new Application($kernel);
        $application->add(new DockerRunCommand());
        $application->add(new DockerParsejsonCommand());

        $command = $application->find('docker:run');
        $commandTester = new CommandTester($command);
        $commandTester->execute([
            'command'  => $command->getName(),
            'execute'  => 'start',
            '--jsonfile' => dirname(__DIR__)
                . DIRECTORY_SEPARATOR
                . 'app'
                . DIRECTORY_SEPARATOR
                . 'linuxforcomposer.test.invalid.script.json',
        ]);

        $this->assertSame(
            PHP_EOL
            . "The 'Linux for Composer' JSON file is invalid."
            . PHP_EOL
            . PHP_EOL,
            $this->getActualOutput()
        );

        ob_end_clean();
    }*/

    public function testExecuteWithStartCommandWithEmptyJsonFile()
    {
        // Redirect output to command output
        //$this->setOutputCallback(function () {
        //});

        ob_start();

        $kernel = self::bootKernel();

        $application = new Application($kernel);
        $application->add(new DockerRunCommand());
        $application->add(new DockerParsejsonCommand());

        $command = $application->find('docker:run');
        $commandTester = new CommandTester($command);
        $commandTester->execute([
            'command'  => $command->getName(),
            'execute'  => 'start',
            '--jsonfile' => dirname(__DIR__)
                . DIRECTORY_SEPARATOR
                . 'app'
                . DIRECTORY_SEPARATOR
                . 'linuxforcomposer.test.empty.json',
        ]);

        $this->assertSame(
            PHP_EOL
            . "Please check your 'Linux for Composer' JSON file for misconfigurations."
            . PHP_EOL
            . PHP_EOL,
            $this->getActualOutput()
        );

        ob_end_clean();
    }

    public function testExecuteWithStartCommandWithMissingPHPVersionsProperty()
    {
        // Redirect output to command output
        //$this->setOutputCallback(function () {
        //});

        ob_start();

        $kernel = self::bootKernel();

        $application = new Application($kernel);
        $application->add(new DockerRunCommand());
        $application->add(new DockerParsejsonCommand());

        $command = $application->find('docker:run');
        $commandTester = new CommandTester($command);
        $commandTester->execute([
            'command'  => $command->getName(),
            'execute'  => 'start',
            '--jsonfile' => dirname(__DIR__)
                . DIRECTORY_SEPARATOR
                . 'app'
                . DIRECTORY_SEPARATOR
                . 'linuxforcomposer.test.missing.php.json',
        ]);

        $this->assertSame(
            PHP_EOL
            . "Please check your 'Linux for Composer' JSON file for misconfigurations."
            . PHP_EOL
            . PHP_EOL,
            $this->getActualOutput()
        );

        ob_end_clean();
    }

    public function testExecuteWithStartCommandWithMinimumConfiguration()
    {
        // Redirect output to command output
        $this->setOutputCallback(function () {
        });

        $this->createMocksForUnixEnv();

        $this->dockerLfcProcessMock
            ->shouldReceive('getOutput')
            ->once()
            ->andReturn('Fake Docker is running!');
        $this->dockerLfcProcessMock
            ->shouldReceive('getErrorOutput')
            ->once()
            ->andReturn('');
        $this->dockerLfcProcessMock
            ->shouldReceive('getExitCode')
            ->andReturn(0);

        $kernel = self::bootKernel();

        $application = new Application($kernel);
        $application->add(new DockerRunCommand());
        $application->add(new DockerParsejsonCommand());

        $command = $application->find('docker:run');
        $commandTester = new CommandTester($command);
        $commandTester->execute([
            'command'  => $command->getName(),
            'execute'  => 'start',
            '--jsonfile' => dirname(__DIR__)
                . DIRECTORY_SEPARATOR
                . 'app'
                . DIRECTORY_SEPARATOR
                . 'linuxforcomposer.test.minimum.config.json',
        ]);

        $this->assertSame(
            'Fake Docker is running!'
            . PHP_EOL,
            $this->getActualOutput()
        );
    }

    public function testExecuteWithStartCommandWithMissingDetachedMode()
    {
        // Redirect output to command output
        $this->setOutputCallback(function () {
        });

        $this->createMocksForUnixEnv();

        $this->dockerLfcProcessMock
            ->shouldReceive('getOutput')
            ->once()
            ->andReturn('Fake Docker is running!');
        $this->dockerLfcProcessMock
            ->shouldReceive('getErrorOutput')
            ->once()
            ->andReturn('');
        $this->dockerLfcProcessMock
            ->shouldReceive('getExitCode')
            ->andReturn(0);

        $kernel = self::bootKernel();

        $application = new Application($kernel);
        $application->add(new DockerRunCommand());
        $application->add(new DockerParsejsonCommand());

        $command = $application->find('docker:run');
        $commandTester = new CommandTester($command);
        $commandTester->execute([
            'command'  => $command->getName(),
            'execute'  => 'start',
            '--jsonfile' => dirname(__DIR__)
                . DIRECTORY_SEPARATOR
                . 'app'
                . DIRECTORY_SEPARATOR
                . 'linuxforcomposer.test.missing.detached.json',
        ]);

        $this->assertSame(
            'Fake Docker is running!'
            . PHP_EOL
            . 'Fake Docker is running!'
            . PHP_EOL,
            $this->getActualOutput()
        );
    }

    public function testExecuteWithStartCommandWithOnePortNumberOnly()
    {
        // Redirect output to command output
        $this->setOutputCallback(function () {
        });

        $this->createMocksForUnixEnv();

        $this->dockerLfcProcessMock
            ->shouldReceive('getOutput')
            ->once()
            ->andReturn('Fake Docker is running!');
        $this->dockerLfcProcessMock
            ->shouldReceive('getErrorOutput')
            ->once()
            ->andReturn('');
        $this->dockerLfcProcessMock
            ->shouldReceive('getExitCode')
            ->andReturn(0);

        $kernel = self::bootKernel();

        $application = new Application($kernel);
        $application->add(new DockerRunCommand());
        $application->add(new DockerParsejsonCommand());

        $command = $application->find('docker:run');
        $commandTester = new CommandTester($command);
        $commandTester->execute([
            'command'  => $command->getName(),
            'execute'  => 'start',
            '--jsonfile' => dirname(__DIR__)
                . DIRECTORY_SEPARATOR
                . 'app'
                . DIRECTORY_SEPARATOR
                . 'linuxforcomposer.test.one.port.json',
        ]);

        $this->assertSame(
            'Fake Docker is running!'
            . PHP_EOL
            . 'Fake Docker is running!'
            . PHP_EOL,
            $this->getActualOutput()
        );
    }

    public function testExecuteWithStartCommandWithMissingScriptProperty()
    {
        // Redirect output to command output
        $this->setOutputCallback(function () {
        });

        $this->createMocksForUnixEnv();

        $this->dockerLfcProcessMock
            ->shouldReceive('getOutput')
            ->once()
            ->andReturn('Fake Docker is running!');
        $this->dockerLfcProcessMock
            ->shouldReceive('getErrorOutput')
            ->once()
            ->andReturn('');
        $this->dockerLfcProcessMock
            ->shouldReceive('getExitCode')
            ->andReturn(0);

        $kernel = self::bootKernel();

        $application = new Application($kernel);
        $application->add(new DockerRunCommand());
        $application->add(new DockerParsejsonCommand());

        $command = $application->find('docker:run');
        $commandTester = new CommandTester($command);
        $commandTester->execute([
            'command'  => $command->getName(),
            'execute'  => 'start',
            '--jsonfile' => dirname(__DIR__)
                . DIRECTORY_SEPARATOR
                . 'app'
                . DIRECTORY_SEPARATOR
                . 'linuxforcomposer.test.missing.script.json',
        ]);

        $this->assertSame(
            'Fake Docker is running!'
            . PHP_EOL
            . 'Fake Docker is running!'
            . PHP_EOL,
            $this->getActualOutput()
        );
    }

    public function testExecuteWithStartCommandWithMissingTSProperty()
    {
        // Redirect output to command output
        $this->setOutputCallback(function () {
        });

        $this->createMocksForUnixEnv();

        $this->dockerLfcProcessMock
            ->shouldReceive('getOutput')
            ->once()
            ->andReturn('Fake Docker is running!');
        $this->dockerLfcProcessMock
            ->shouldReceive('getErrorOutput')
            ->once()
            ->andReturn('');
        $this->dockerLfcProcessMock
            ->shouldReceive('getExitCode')
            ->andReturn(0);

        $kernel = self::bootKernel();

        $application = new Application($kernel);
        $application->add(new DockerRunCommand());
        $application->add(new DockerParsejsonCommand());

        $command = $application->find('docker:run');
        $commandTester = new CommandTester($command);
        $commandTester->execute([
            'command'  => $command->getName(),
            'execute'  => 'start',
            '--jsonfile' => dirname(__DIR__)
                . DIRECTORY_SEPARATOR
                . 'app'
                . DIRECTORY_SEPARATOR
                . 'linuxforcomposer.test.missing.ts.json',
        ]);

        $this->assertSame(
            'Fake Docker is running!'
            . PHP_EOL
            . 'Fake Docker is running!'
            . PHP_EOL,
            $this->getActualOutput()
        );
    }

    public function testExecuteWithStartCommandWillReadDockerStderr()
    {
        // Redirect output to command output
        //$this->setOutputCallback(function () {
        //});

        ob_start();

        $this->createMocksForUnixEnv();

        $this->dockerLfcProcessMock
            ->shouldReceive('getOutput')
            ->once()
            ->andReturn('');
        $this->dockerLfcProcessMock
            ->shouldReceive('getErrorOutput')
            ->once()
            ->andReturn('Error! Docker is not running.');
        $this->dockerLfcProcessMock
            ->shouldReceive('getExitCode')
            ->andReturn(1);

        $kernel = self::bootKernel();

        $application = new Application($kernel);
        $application->add(new DockerRunCommand());
        $application->add(new DockerParsejsonCommand());

        $command = $application->find('docker:run');
        $commandTester = new CommandTester($command);
        $commandTester->execute([
            'command'  => $command->getName(),
            'execute'  => 'start',
            '--jsonfile' => dirname(__DIR__)
                . DIRECTORY_SEPARATOR
                . 'app'
                . DIRECTORY_SEPARATOR
                . 'linuxforcomposer.test.json',
        ]);

        $this->assertSame(
            'Error! Docker is not running.'
            . PHP_EOL
            . 'Error! Docker is not running.'
            . PHP_EOL
            . 'Error! Docker is not running.'
            . PHP_EOL,
            $this->getActualOutput()
        );

        ob_end_clean();
    }

    public function testExecuteWithStartCommandWithDockerfile()
    {
        // Redirect output to command output
        //$this->setOutputCallback(function () {
        //});

        ob_start();

        $this->createMocksForUnixEnv();

        $this->dockerLfcProcessMock
            ->shouldReceive('getOutput')
            ->once()
            ->andReturn('Fake Docker is running!');
        $this->dockerLfcProcessMock
            ->shouldReceive('getErrorOutput')
            ->once()
            ->andReturn('');
        $this->dockerLfcProcessMock
            ->shouldReceive('getExitCode')
            ->andReturn(0);

        $kernel = self::bootKernel();

        $application = new Application($kernel);
        $application->add(new DockerRunCommand());
        $application->add(new DockerParsejsonCommand());

        $command = $application->find('docker:run');
        $commandTester = new CommandTester($command);
        $commandTester->execute([
            'command'  => $command->getName(),
            'execute'  => 'start',
            '--jsonfile' => dirname(__DIR__)
                . DIRECTORY_SEPARATOR
                . 'app'
                . DIRECTORY_SEPARATOR
                . 'linuxforcomposer.test.dockerfile.json',
        ]);

        $this->assertSame(
            'Fake Docker is running!'
            . PHP_EOL,
            $this->getActualOutput()
        );

        ob_end_clean();
    }

    public function testExecuteWithStopCommand()
    {
        // Redirect output to command output
        $this->setOutputCallback(function () {
        });

        $this->createMocksForUnixEnv();

        $this->dockerLfcProcessMock
            ->shouldReceive('getOutput')
            ->once()
            ->andReturn('Fake Docker stopped!');
        $this->dockerLfcProcessMock
            ->shouldReceive('getErrorOutput')
            ->once()
            ->andReturn('');
        $this->dockerLfcProcessMock
            ->shouldReceive('getExitCode')
            ->andReturn(0);

        $kernel = self::bootKernel();

        $application = new Application($kernel);
        $application->add(new DockerRunCommand());
        $application->add(new DockerParsejsonCommand());

        $command = $application->find('docker:run');
        $commandTester = new CommandTester($command);
        $commandTester->execute([
            'command'  => $command->getName(),
            'execute'  => 'stop',
        ]);

        $this->assertSame(
            'Fake Docker stopped!'
            . PHP_EOL,
            $this->getActualOutput()
        );
    }

    public function testExecuteWithStopForceCommand()
    {
        // Redirect output to command output
        $this->setOutputCallback(function () {
        });

        $this->createMocksForUnixEnv();

        $this->dockerLfcProcessMock
            ->shouldReceive('getOutput')
            ->once()
            ->andReturn('Fake Docker stopped!');
        $this->dockerLfcProcessMock
            ->shouldReceive('getErrorOutput')
            ->once()
            ->andReturn('');
        $this->dockerLfcProcessMock
            ->shouldReceive('getExitCode')
            ->andReturn(0);

        $kernel = self::bootKernel();

        $application = new Application($kernel);
        $application->add(new DockerRunCommand());
        $application->add(new DockerParsejsonCommand());

        $command = $application->find('docker:run');
        $commandTester = new CommandTester($command);
        $commandTester->execute([
            'command'  => $command->getName(),
            'execute'  => 'stop-force',
        ]);

        $this->assertSame(
            'Fake Docker stopped!'
            . PHP_EOL,
            $this->getActualOutput()
        );
    }

    public function testExecuteWithStopCommandWillReadDockerStderr()
    {
        // Redirect output to command output
        $this->setOutputCallback(function () {
        });

        $this->createMocksForUnixEnv();

        $this->dockerLfcProcessMock
            ->shouldReceive('getOutput')
            ->once()
            ->andReturn('');
        $this->dockerLfcProcessMock
            ->shouldReceive('getErrorOutput')
            ->once()
            ->andReturn('Error! Docker is not stopped.');
        $this->dockerLfcProcessMock
            ->shouldReceive('getExitCode')
            ->andReturn(0);

        $kernel = self::bootKernel();

        $application = new Application($kernel);
        $application->add(new DockerRunCommand());
        $application->add(new DockerParsejsonCommand());

        $command = $application->find('docker:run');
        $commandTester = new CommandTester($command);
        $commandTester->execute([
            'command'  => $command->getName(),
            'execute'  => 'stop',
        ]);

        $this->assertSame(
            'Error! Docker is not stopped.'
            . PHP_EOL,
            $this->getActualOutput()
        );
    }

    public function testExecuteWithStopCommandWithDockerfile()
    {
        // Redirect output to command output
        //$this->setOutputCallback(function () {
        //});

        ob_start();

        $this->createMocksForUnixEnv();

        $this->dockerLfcProcessMock
            ->shouldReceive('getOutput')
            ->once()
            ->andReturn('Fake Docker stopped!');
        $this->dockerLfcProcessMock
            ->shouldReceive('getErrorOutput')
            ->once()
            ->andReturn('');
        $this->dockerLfcProcessMock
            ->shouldReceive('getExitCode')
            ->andReturn(0);

        $kernel = self::bootKernel();

        $application = new Application($kernel);
        $application->add(new DockerRunCommand());
        $application->add(new DockerParsejsonCommand());

        $command = $application->find('docker:run');
        $commandTester = new CommandTester($command);
        $commandTester->execute([
            'command'  => $command->getName(),
            'execute'  => 'stop',
            '--jsonfile' => dirname(__DIR__)
                . DIRECTORY_SEPARATOR
                . 'app'
                . DIRECTORY_SEPARATOR
                . 'linuxforcomposer.test.dockerfile.json',
        ]);

        $this->assertSame(
            'Fake Docker stopped!'
            . PHP_EOL,
            $this->getActualOutput()
        );

        ob_end_clean();
    }

    public function testExecuteWithStopCommandWithInvalidJsonFile()
    {
        // Redirect output to command output
        $this->setOutputCallback(function () {
        });

        $kernel = self::bootKernel();

        $application = new Application($kernel);
        $application->add(new DockerRunCommand());
        $application->add(new DockerParsejsonCommand());

        $command = $application->find('docker:run');
        $commandTester = new CommandTester($command);
        $commandTester->execute([
            'command'  => $command->getName(),
            'execute'  => 'stop',
            '--jsonfile' => dirname(__DIR__)
                . DIRECTORY_SEPARATOR
                . 'app'
                . DIRECTORY_SEPARATOR
                . 'linuxforcomposer.test.invalid.json',
        ]);

        $this->assertSame(
            PHP_EOL
            . "The 'Linux for Composer' JSON file is invalid."
            . PHP_EOL
            . PHP_EOL,
            $this->getActualOutput()
        );
    }

    // Deactivating successful test due to an issue with Travis CI
    /*public function testExecuteWithStopCommandWithInvalidScriptJsonFile()
    {
        // Redirect output to command output
        //$this->setOutputCallback(function () {
        //});

        ob_start();

        $kernel = self::bootKernel();

        $application = new Application($kernel);
        $application->add(new DockerRunCommand());
        $application->add(new DockerParsejsonCommand());

        $command = $application->find('docker:run');
        $commandTester = new CommandTester($command);
        $commandTester->execute([
            'command'  => $command->getName(),
            'execute'  => 'stop',
            '--jsonfile' => dirname(__DIR__)
                . DIRECTORY_SEPARATOR
                . 'app'
                . DIRECTORY_SEPARATOR
                . 'linuxforcomposer.test.invalid.script.json',
        ]);

        $this->assertSame(
            PHP_EOL
            . "The 'Linux for Composer' JSON file is invalid."
            . PHP_EOL
            . PHP_EOL,
            $this->getActualOutput()
        );

        ob_end_clean();
    }*/
}
