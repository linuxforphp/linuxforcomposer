<?php

/**
 * Linux for PHP/Linux for Composer
 *
 * Copyright 2010 - 2018 A. Caya <andrewscaya@yahoo.ca>
 * Version 0.9.8
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

namespace LinuxforcomposerTest\Command;

use Linuxforcomposer\Command\DockerParsejsonCommand;
use Linuxforcomposer\Command\DockerRunCommand;
use Symfony\Bundle\FrameworkBundle\Console\Application;
use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;
use Symfony\Component\Console\Tester\CommandTester;

class DockerRunCommandTest extends KernelTestCase
{
    protected $dockerManageCommandMock;

    public static function setUpBeforeClass()
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

    public function tearDown()
    {
        \Mockery::close();

        parent::tearDown();
    }

    public function createMocksForUnixEnv()
    {
        $this->dockerManageCommandMock = \Mockery::mock('overload:Symfony\Component\Process\Process');
        $this->dockerManageCommandMock
            ->shouldReceive('setTimeout')
            ->once()
            ->with(null);
        $this->dockerManageCommandMock
            ->shouldReceive('setTty')
            ->once()
            ->with(true);
        $this->dockerManageCommandMock
            ->shouldReceive('start')
            ->once();
        $this->dockerManageCommandMock
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

        $command = $application->find('docker:run');
        $commandTester = new CommandTester($command);
        $commandTester->execute(array(
            'command'  => $command->getName(),
            'execute'  => 'bad',
        ));

        // the output of the command in the console
        //$output = $commandTester->getDisplay();
        $this->assertContains(
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

        $command = $application->find('docker:run');
        $commandTester = new CommandTester($command);
        $commandTester->execute(array(
            'command'  => $command->getName(),
            'execute'  => 'bad',
            '--jsonfile' => dirname(__DIR__)
                . DIRECTORY_SEPARATOR
                . 'app'
                . DIRECTORY_SEPARATOR
                . 'linuxforcomposer.test.custom.json',
        ));

        // the output of the command in the console
        //$output = $commandTester->getDisplay();
        $this->assertContains(
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

        $command = $application->find('docker:run');
        $commandTester = new CommandTester($command);
        $commandTester->execute(array(
            'command'  => $command->getName(),
            'execute'  => 'bad',
            '--jsonfile' => dirname(__DIR__)
                . DIRECTORY_SEPARATOR
                . 'app'
                . DIRECTORY_SEPARATOR
                . 'linuxforcomposer.test.custom.wrong.json',
        ));

        // the output of the command in the console
        //$output = $commandTester->getDisplay();
        $this->assertContains(
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

        $this->dockerManageCommandMock
            ->shouldReceive('getOutput')
            ->once()
            ->andReturn('Fake Docker is running!');
        $this->dockerManageCommandMock
            ->shouldReceive('getErrorOutput')
            ->once()
            ->andReturn('');

        $kernel = self::bootKernel();

        $application = new Application($kernel);
        $application->add(new DockerRunCommand());
        $application->add(new DockerParsejsonCommand());

        $command = $application->find('docker:run');
        $commandTester = new CommandTester($command);
        $commandTester->execute(array(
            'command'  => $command->getName(),
            'execute'  => 'start',
        ));

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

        $this->dockerManageCommandMock
            ->shouldReceive('getOutput')
            ->once()
            ->andReturn('Fake Docker is running!');
        $this->dockerManageCommandMock
            ->shouldReceive('getErrorOutput')
            ->once()
            ->andReturn('');

        $kernel = self::bootKernel();

        $application = new Application($kernel);
        $application->add(new DockerRunCommand());
        $application->add(new DockerParsejsonCommand());

        $command = $application->find('docker:run');
        $commandTester = new CommandTester($command);
        $commandTester->execute(array(
            'command'  => $command->getName(),
            'execute'  => 'start',
            '--jsonfile' => dirname(__DIR__)
                . DIRECTORY_SEPARATOR
                . 'app'
                . DIRECTORY_SEPARATOR
                . 'linuxforcomposer.test.custom.json',
        ));

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

        $this->dockerManageCommandMock
            ->shouldReceive('getOutput')
            ->once()
            ->andReturn('Fake Docker is running!');
        $this->dockerManageCommandMock
            ->shouldReceive('getErrorOutput')
            ->once()
            ->andReturn('');

        $kernel = self::bootKernel();

        $application = new Application($kernel);
        $application->add(new DockerRunCommand());
        $application->add(new DockerParsejsonCommand());

        $command = $application->find('docker:run');
        $commandTester = new CommandTester($command);
        $commandTester->execute(array(
            'command'  => $command->getName(),
            'execute'  => 'start',
            '--jsonfile' => dirname(__DIR__)
                . DIRECTORY_SEPARATOR
                . 'app'
                . DIRECTORY_SEPARATOR
                . 'linuxforcomposer.test.custom.wrong.json',
        ));

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
        $this->setOutputCallback(function () {
        });

        $this->createMocksForUnixEnv();

        $this->dockerManageCommandMock
            ->shouldReceive('getOutput')
            ->once()
            ->andReturn('Fake Docker is running!');
        $this->dockerManageCommandMock
            ->shouldReceive('getErrorOutput')
            ->once()
            ->andReturn('');

        $kernel = self::bootKernel();

        $application = new Application($kernel);
        $application->add(new DockerRunCommand());
        $application->add(new DockerParsejsonCommand());

        $command = $application->find('docker:run');
        $commandTester = new CommandTester($command);
        $commandTester->execute(array(
            'command'  => $command->getName(),
            'execute'  => 'start',
            '--jsonfile' => dirname(__DIR__)
                . DIRECTORY_SEPARATOR
                . 'app'
                . DIRECTORY_SEPARATOR
                . 'linuxforcomposer.test.custom.wrong.json',
        ));

        copy(JSONFILE . '.wrong', JSONFILE);
        unlink(JSONFILE . '.wrong');

        $this->assertSame(
            'Fake Docker is running!'
            . PHP_EOL
            . 'Fake Docker is running!'
            . PHP_EOL
            . 'Fake Docker is running!'
            . PHP_EOL
            . 'Fake Docker is running!'
            . PHP_EOL,
            $this->getActualOutput()
        );
    }

    public function testExecuteWithStartCommandWithInvalidJsonFile()
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
        $commandTester->execute(array(
            'command'  => $command->getName(),
            'execute'  => 'start',
            '--jsonfile' => dirname(__DIR__)
                . DIRECTORY_SEPARATOR
                . 'app'
                . DIRECTORY_SEPARATOR
                . 'linuxforcomposer.test.invalid.json',
        ));

        $this->assertSame(
            PHP_EOL
            . "The 'Linux for Composer' JSON file is invalid."
            . PHP_EOL
            . PHP_EOL,
            $this->getActualOutput()
        );
    }

    public function testExecuteWithStartCommandWithEmptyJsonFile()
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
        $commandTester->execute(array(
            'command'  => $command->getName(),
            'execute'  => 'start',
            '--jsonfile' => dirname(__DIR__)
                . DIRECTORY_SEPARATOR
                . 'app'
                . DIRECTORY_SEPARATOR
                . 'linuxforcomposer.test.empty.json',
        ));

        $this->assertSame(
            PHP_EOL
            . 'You must choose at least one PHP version to run.'
            . PHP_EOL
            . PHP_EOL,
            $this->getActualOutput()
        );
    }

    public function testExecuteWithStartCommandWithMissingPHPVersionsProperty()
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
        $commandTester->execute(array(
            'command'  => $command->getName(),
            'execute'  => 'start',
            '--jsonfile' => dirname(__DIR__)
                . DIRECTORY_SEPARATOR
                . 'app'
                . DIRECTORY_SEPARATOR
                . 'linuxforcomposer.test.missing.php.json',
        ));

        $this->assertSame(
            PHP_EOL
            . 'You must choose at least one PHP version to run.'
            . PHP_EOL
            . PHP_EOL,
            $this->getActualOutput()
        );
    }

    public function testExecuteWithStartCommandWithMinimumConfiguration()
    {
        // Redirect output to command output
        $this->setOutputCallback(function () {
        });

        $this->createMocksForUnixEnv();

        $this->dockerManageCommandMock
            ->shouldReceive('getOutput')
            ->once()
            ->andReturn('Fake Docker is running!');
        $this->dockerManageCommandMock
            ->shouldReceive('getErrorOutput')
            ->once()
            ->andReturn('');

        $kernel = self::bootKernel();

        $application = new Application($kernel);
        $application->add(new DockerRunCommand());
        $application->add(new DockerParsejsonCommand());

        $command = $application->find('docker:run');
        $commandTester = new CommandTester($command);
        $commandTester->execute(array(
            'command'  => $command->getName(),
            'execute'  => 'start',
            '--jsonfile' => dirname(__DIR__)
                . DIRECTORY_SEPARATOR
                . 'app'
                . DIRECTORY_SEPARATOR
                . 'linuxforcomposer.test.minimum.config.json',
        ));

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

        $this->dockerManageCommandMock
            ->shouldReceive('getOutput')
            ->once()
            ->andReturn('Fake Docker is running!');
        $this->dockerManageCommandMock
            ->shouldReceive('getErrorOutput')
            ->once()
            ->andReturn('');

        $kernel = self::bootKernel();

        $application = new Application($kernel);
        $application->add(new DockerRunCommand());
        $application->add(new DockerParsejsonCommand());

        $command = $application->find('docker:run');
        $commandTester = new CommandTester($command);
        $commandTester->execute(array(
            'command'  => $command->getName(),
            'execute'  => 'start',
            '--jsonfile' => dirname(__DIR__)
                . DIRECTORY_SEPARATOR
                . 'app'
                . DIRECTORY_SEPARATOR
                . 'linuxforcomposer.test.missing.detached.json',
        ));

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

        $this->dockerManageCommandMock
            ->shouldReceive('getOutput')
            ->once()
            ->andReturn('Fake Docker is running!');
        $this->dockerManageCommandMock
            ->shouldReceive('getErrorOutput')
            ->once()
            ->andReturn('');

        $kernel = self::bootKernel();

        $application = new Application($kernel);
        $application->add(new DockerRunCommand());
        $application->add(new DockerParsejsonCommand());

        $command = $application->find('docker:run');
        $commandTester = new CommandTester($command);
        $commandTester->execute(array(
            'command'  => $command->getName(),
            'execute'  => 'start',
            '--jsonfile' => dirname(__DIR__)
                . DIRECTORY_SEPARATOR
                . 'app'
                . DIRECTORY_SEPARATOR
                . 'linuxforcomposer.test.one.port.json',
        ));

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

        $this->dockerManageCommandMock
            ->shouldReceive('getOutput')
            ->once()
            ->andReturn('Fake Docker is running!');
        $this->dockerManageCommandMock
            ->shouldReceive('getErrorOutput')
            ->once()
            ->andReturn('');

        $kernel = self::bootKernel();

        $application = new Application($kernel);
        $application->add(new DockerRunCommand());
        $application->add(new DockerParsejsonCommand());

        $command = $application->find('docker:run');
        $commandTester = new CommandTester($command);
        $commandTester->execute(array(
            'command'  => $command->getName(),
            'execute'  => 'start',
            '--jsonfile' => dirname(__DIR__)
                . DIRECTORY_SEPARATOR
                . 'app'
                . DIRECTORY_SEPARATOR
                . 'linuxforcomposer.test.missing.script.json',
        ));

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

        $this->dockerManageCommandMock
            ->shouldReceive('getOutput')
            ->once()
            ->andReturn('Fake Docker is running!');
        $this->dockerManageCommandMock
            ->shouldReceive('getErrorOutput')
            ->once()
            ->andReturn('');

        $kernel = self::bootKernel();

        $application = new Application($kernel);
        $application->add(new DockerRunCommand());
        $application->add(new DockerParsejsonCommand());

        $command = $application->find('docker:run');
        $commandTester = new CommandTester($command);
        $commandTester->execute(array(
            'command'  => $command->getName(),
            'execute'  => 'start',
            '--jsonfile' => dirname(__DIR__)
                . DIRECTORY_SEPARATOR
                . 'app'
                . DIRECTORY_SEPARATOR
                . 'linuxforcomposer.test.missing.ts.json',
        ));

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
        $this->setOutputCallback(function () {
        });

        $this->createMocksForUnixEnv();

        $this->dockerManageCommandMock
            ->shouldReceive('getOutput')
            ->once()
            ->andReturn('');
        $this->dockerManageCommandMock
            ->shouldReceive('getErrorOutput')
            ->once()
            ->andReturn('Error! Docker is not running.');

        $kernel = self::bootKernel();

        $application = new Application($kernel);
        $application->add(new DockerRunCommand());
        $application->add(new DockerParsejsonCommand());

        $command = $application->find('docker:run');
        $commandTester = new CommandTester($command);
        $commandTester->execute(array(
            'command'  => $command->getName(),
            'execute'  => 'start',
            '--jsonfile' => dirname(__DIR__)
                . DIRECTORY_SEPARATOR
                . 'app'
                . DIRECTORY_SEPARATOR
                . 'linuxforcomposer.test.json',
        ));

        $this->assertSame(
            'Error! Docker is not running.'
            . PHP_EOL
            . 'Error! Docker is not running.'
            . PHP_EOL
            . 'Error! Docker is not running.'
            . PHP_EOL,
            $this->getActualOutput()
        );
    }

    public function testExecuteWithStopCommand()
    {
        // Redirect output to command output
        $this->setOutputCallback(function () {
        });

        $this->createMocksForUnixEnv();

        $this->dockerManageCommandMock
            ->shouldReceive('getOutput')
            ->once()
            ->andReturn('Fake Docker stopped!');
        $this->dockerManageCommandMock
            ->shouldReceive('getErrorOutput')
            ->once()
            ->andReturn('');

        $kernel = self::bootKernel();

        $application = new Application($kernel);
        $application->add(new DockerRunCommand());
        $application->add(new DockerParsejsonCommand());

        $command = $application->find('docker:run');
        $commandTester = new CommandTester($command);
        $commandTester->execute(array(
            'command'  => $command->getName(),
            'execute'  => 'stop',
        ));

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

        $this->dockerManageCommandMock
            ->shouldReceive('getOutput')
            ->once()
            ->andReturn('');
        $this->dockerManageCommandMock
            ->shouldReceive('getErrorOutput')
            ->once()
            ->andReturn('Error! Docker is not stopped.');

        $kernel = self::bootKernel();

        $application = new Application($kernel);
        $application->add(new DockerRunCommand());
        $application->add(new DockerParsejsonCommand());

        $command = $application->find('docker:run');
        $commandTester = new CommandTester($command);
        $commandTester->execute(array(
            'command'  => $command->getName(),
            'execute'  => 'stop',
        ));

        $this->assertSame(
            'Error! Docker is not stopped.'
            . PHP_EOL,
            $this->getActualOutput()
        );
    }
}
