<?php
/**
 * Linux for PHP/Linux for Composer
 *
 * Copyright 2017 - 2021 Foreach Code Factory <lfphp@asclinux.net>
 * Version 2.0.9.2
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
 * @copyright  Copyright 2017 - 2021 Foreach Code Factory <lfphp@asclinux.net>
 * @link       https://linuxforphp.net/
 * @license    Apache License, Version 2.0, see above
 * @license    http://www.apache.org/licenses/LICENSE-2.0
 * @since 0.9.8
 */

namespace LinuxforcomposerTest\Command;

use Linuxforcomposer\Command\DockerCommitCommand;
use LinuxforcomposerTest\Mock\InputMock;
use Symfony\Bundle\FrameworkBundle\Console\Application;
use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;
use Symfony\Component\Console\Tester\CommandTester;

/**
 * @runTestsInSeparateProcesses
 * @preserveGlobalState disabled
 */
class DockerCommitCommandTest extends KernelTestCase
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

        if (!defined('VENDORFOLDERPID')) {
            define(
                'VENDORFOLDERPID',
                dirname(__DIR__)
                . DIRECTORY_SEPARATOR
                . 'app'
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

    public function testExecuteWithRequiredArgumentsAndOptionsOnly()
    {
        // Redirect output to command output
        $this->setOutputCallback(function () {
        });

        file_put_contents(
            VENDORFOLDERPID
            . DIRECTORY_SEPARATOR
            . 'composer'
            . DIRECTORY_SEPARATOR
            . 'linuxforcomposer.pid',
            'a1a1' . PHP_EOL
        );

        $this->createMocksForUnixEnv();

        $this->dockerLfcProcessMock
            ->shouldReceive('getOutput')
            ->once()
            ->andReturn('We committed the image!');
        $this->dockerLfcProcessMock
            ->shouldReceive('getErrorOutput')
            ->once()
            ->andReturn('One commit failed');

        $kernel = self::bootKernel();

        $application = new Application($kernel);
        $application->add(new DockerCommitCommand());

        $command = $application->find('docker:commit');
        $commandTester = new CommandTester($command);
        $commandTester->execute([
            'command'  => $command->getName(),
            'pid'  => 'a1a1',
            'name'  => 'myversion',
        ]);

        // the output of the command in the console
        $output = $commandTester->getDisplay();
        $this->assertSame(
            'We committed the image!'
            . PHP_EOL
            . 'One commit failed'
            . PHP_EOL,
            $this->getActualOutput()
        );
    }

    public function testExecuteOutputToJsonFile()
    {
        // Redirect output to command output
        //$this->setOutputCallback(function () {
        //});

        ob_start();

        $jsonFile = JSONFILE;

        $fileContentsJsonOriginal = file_get_contents($jsonFile);

        file_put_contents(
            VENDORFOLDERPID
            . DIRECTORY_SEPARATOR
            . 'composer'
            . DIRECTORY_SEPARATOR
            . 'linuxforcomposer.pid',
            'a1a1' . PHP_EOL
        );

        $this->createMocksForUnixEnv();

        $this->dockerLfcProcessMock
            ->shouldReceive('getOutput')
            ->once()
            ->andReturn('We committed the image!');
        $this->dockerLfcProcessMock
            ->shouldReceive('getErrorOutput')
            ->once()
            ->andReturn('');

        $kernel = self::bootKernel();

        $application = new Application($kernel);
        $application->add(new DockerCommitCommand());

        $command = $application->find('docker:commit');
        $commandTester = new CommandTester($command);
        $commandTester->execute([
            'command'  => $command->getName(),
            'pid'  => 'a1a1',
            'name'  => '7.4-myversion',
            '--savetojsonfile' => '0',
        ]);

        // the output of the command in the console
        //$output = $commandTester->getDisplay();
        $this->assertSame(
            'We committed the image!'
            . PHP_EOL,
            $this->getActualOutput()
        );

        $fileContentsJson = file_get_contents($jsonFile);

        $fileContentsArray = json_decode($fileContentsJson, true);

        $actual = (string) $fileContentsArray['single']['image']['linuxforcomposer']['php-versions'][0];

        $this->assertSame('custom-7.4-myversion', $actual);

        file_put_contents($jsonFile, $fileContentsJsonOriginal);

        ob_end_clean();
    }

    public function testExecuteWithEmptyJsonFile()
    {
        // Redirect output to command output
        $this->setOutputCallback(function () {
        });

        $jsonFile = JSONFILE;

        $fileContentsJsonOriginal = file_get_contents($jsonFile);

        file_put_contents(
            VENDORFOLDERPID
            . DIRECTORY_SEPARATOR
            . 'composer'
            . DIRECTORY_SEPARATOR
            . 'linuxforcomposer.pid',
            'a1a1' . PHP_EOL
        );

        file_put_contents(
            $jsonFile,
            ''
        );

        $this->createMocksForUnixEnv();

        $this->dockerLfcProcessMock
            ->shouldReceive('getOutput')
            ->once()
            ->andReturn('');
        $this->dockerLfcProcessMock
            ->shouldReceive('getErrorOutput')
            ->once()
            ->andReturn('');

        $kernel = self::bootKernel();

        $application = new Application($kernel);
        $application->add(new DockerCommitCommand());

        $command = $application->find('docker:commit');
        $commandTester = new CommandTester($command);
        $commandTester->execute([
            'command'  => $command->getName(),
            'pid'  => 'a1a1',
            'name'  => '7.2-myversion',
            '--savetojsonfile' => '0',
        ]);

        file_put_contents($jsonFile, $fileContentsJsonOriginal);

        // the output of the command in the console
        //$output = $commandTester->getDisplay();
        $this->assertSame(
            'WARNING: The linuxforcomposer.json file is empty or invalid! The file is unchanged.'
            . PHP_EOL,
            $this->getActualOutput()
        );
    }

    public function testExecuteWithInvalidJsonFile()
    {
        // Redirect output to command output
        $this->setOutputCallback(function () {
        });

        $jsonFile = JSONFILE;

        $fileContentsJsonOriginal = file_get_contents($jsonFile);

        file_put_contents(
            VENDORFOLDERPID
            . DIRECTORY_SEPARATOR
            . 'composer'
            . DIRECTORY_SEPARATOR
            . 'linuxforcomposer.pid',
            'a1a1' . PHP_EOL
        );

        file_put_contents(
            $jsonFile,
            '{"name": "linuxforphp/linuxforcomposer",}'
        );

        $this->createMocksForUnixEnv();

        $this->dockerLfcProcessMock
            ->shouldReceive('getOutput')
            ->once()
            ->andReturn('');
        $this->dockerLfcProcessMock
            ->shouldReceive('getErrorOutput')
            ->once()
            ->andReturn('');

        $kernel = self::bootKernel();

        $application = new Application($kernel);
        $application->add(new DockerCommitCommand());

        $command = $application->find('docker:commit');
        $commandTester = new CommandTester($command);
        $commandTester->execute([
            'command'  => $command->getName(),
            'pid'  => 'a1a1',
            'name'  => '7.2-myversion',
            '--savetojsonfile' => '0',
        ]);

        file_put_contents($jsonFile, $fileContentsJsonOriginal);

        // the output of the command in the console
        //$output = $commandTester->getDisplay();
        $this->assertSame(
            'WARNING: The linuxforcomposer.json file is empty or invalid! The file is unchanged.'
            . PHP_EOL,
            $this->getActualOutput()
        );
    }

    public function testExecuteWithIncompleteJsonFile()
    {
        // Redirect output to command output
        $this->setOutputCallback(function () {
        });

        $jsonFile = JSONFILE;

        $fileContentsJsonOriginal = file_get_contents($jsonFile);

        file_put_contents(
            VENDORFOLDERPID
            . DIRECTORY_SEPARATOR
            . 'composer'
            . DIRECTORY_SEPARATOR
            . 'linuxforcomposer.pid',
            'a1a1' . PHP_EOL
        );

        file_put_contents(
            $jsonFile,
            '{"name": "linuxforphp/linuxforcomposer"}'
        );

        $this->createMocksForUnixEnv();

        $this->dockerLfcProcessMock
            ->shouldReceive('getOutput')
            ->once()
            ->andReturn('');
        $this->dockerLfcProcessMock
            ->shouldReceive('getErrorOutput')
            ->once()
            ->andReturn('');

        $kernel = self::bootKernel();

        $application = new Application($kernel);
        $application->add(new DockerCommitCommand());

        $command = $application->find('docker:commit');
        $commandTester = new CommandTester($command);
        $commandTester->execute([
            'command'  => $command->getName(),
            'pid'  => 'a1a1',
            'name'  => '7.2-myversion',
            '--savetojsonfile' => '0',
        ]);

        file_put_contents($jsonFile, $fileContentsJsonOriginal);

        // the output of the command in the console
        //$output = $commandTester->getDisplay();
        $this->assertSame(
            'WARNING: No versions of PHP found in the linuxforcomposer.json file! The file is unchanged.'
            . PHP_EOL,
            $this->getActualOutput()
        );
    }
}
