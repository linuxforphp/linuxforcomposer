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

namespace LinuxforcomposerTest\Command;

use Linuxforcomposer\Command\DockerCommitCommand;
use Symfony\Bundle\FrameworkBundle\Console\Application;
use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;
use Symfony\Component\Console\Tester\CommandTester;

class DockerCommitCommandTest extends KernelTestCase
{
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

        if (!defined('VENDORFOLDERPID')) {
            define(
                'VENDORFOLDERPID',
                dirname(__DIR__)
                . DIRECTORY_SEPARATOR
                . 'app'
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
        $this->dockerCommandMock = \Mockery::mock('overload:Symfony\Component\Process\Process');
        $this->dockerCommandMock
            ->shouldReceive('setTimeout')
            ->once()
            ->with(null);
        $this->dockerCommandMock
            ->shouldReceive('setTty')
            ->once()
            ->with(true);
        $this->dockerCommandMock
            ->shouldReceive('start')
            ->once();
        $this->dockerCommandMock
            ->shouldReceive('wait')
            ->once();
    }

    public function testExecuteWithRequiredArgumentsAndOptionsOnly()
    {
        file_put_contents(
            VENDORFOLDERPID
            . DIRECTORY_SEPARATOR
            . 'composer'
            . DIRECTORY_SEPARATOR
            . 'linuxforcomposer.pid',
            'a1a1' . PHP_EOL
        );

        $this->createMocksForUnixEnv();

        $this->dockerCommandMock
            ->shouldReceive('getOutput')
            ->once()
            ->andReturn('We committed the image!');
        $this->dockerCommandMock
            ->shouldReceive('getErrorOutput')
            ->once()
            ->andReturn('One commit failed');

        // Redirect output to command output
        $this->setOutputCallback(function () {
        });

        $kernel = self::bootKernel();

        $application = new Application($kernel);
        $application->add(new DockerCommitCommand());

        $command = $application->find('docker:commit');
        $commandTester = new CommandTester($command);
        $commandTester->execute(array(
            'command'  => $command->getName(),
            'pid'  => 'a1a1',
            'name'  => 'myversion',
        ));

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
}
