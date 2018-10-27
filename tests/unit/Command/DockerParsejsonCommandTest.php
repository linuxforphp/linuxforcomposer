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

use Linuxforcomposer\Command\DockerParsejsonCommand;
use Symfony\Bundle\FrameworkBundle\Console\Application;
use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;
use Symfony\Component\Console\Tester\CommandTester;

class DockerParsejsonCommandTest extends KernelTestCase
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
    }

    public function testExecuteWithNoOption()
    {
        $kernel = self::bootKernel();

        $application = new Application($kernel);
        $application->add(new DockerParsejsonCommand());

        $command = $application->find('docker:parsejson');
        $commandTester = new CommandTester($command);
        $commandTester->execute(array(
            'command'  => $command->getName(),
        ));

        // the output of the command in the console
        $output = $commandTester->getDisplay();
        $this->assertSame(
            'php '. dirname(__DIR__) . '/app/app.php docker:manage '
            .'--detached --interactive --tty --phpversion 7.2.5 --threadsafe nts --port 8181:80 '
            .'--volume ${PWD}/:/srv/www --script lfphp run'
            . PHP_EOL
            . 'php '. dirname(__DIR__) . '/app/app.php docker:manage '
            .'--detached --interactive --tty --phpversion 7.1.16 --threadsafe nts --port 8282:80 '
            .'--volume ${PWD}/:/srv/www --script lfphp run'
            . PHP_EOL
            . 'php '. dirname(__DIR__) . '/app/app.php docker:manage '
            .'--detached --interactive --tty --phpversion 7.0.29 --threadsafe nts --port 8383:80 '
            .'--volume ${PWD}/:/srv/www --script lfphp run'
            . PHP_EOL,
            $output
        );
    }

    public function testExecuteWithCustomJsonFile()
    {
        $kernel = self::bootKernel();

        $application = new Application($kernel);
        $application->add(new DockerParsejsonCommand());

        $command = $application->find('docker:parsejson');
        $commandTester = new CommandTester($command);
        $commandTester->execute(array(
            'command'  => $command->getName(),
            '--jsonfile' => dirname(__DIR__)
                . DIRECTORY_SEPARATOR
                . 'app'
                . DIRECTORY_SEPARATOR
                . 'linuxforcomposer.test.custom.json',
        ));

        // the output of the command in the console
        $output = $commandTester->getDisplay();
        $this->assertSame(
            'php '. dirname(__DIR__) . '/app/app.php docker:manage '
            .'--detached --interactive --tty --phpversion 5.6.35 --threadsafe nts --port 8181:80 '
            .'--volume ${PWD}/:/srv/www --script lfphp run'
            . PHP_EOL,
            $output
        );
    }

    public function testExecuteWithNonexistentCustomJsonFile()
    {
        $kernel = self::bootKernel();

        $application = new Application($kernel);
        $application->add(new DockerParsejsonCommand());

        $command = $application->find('docker:parsejson');
        $commandTester = new CommandTester($command);
        $commandTester->execute(array(
            'command'  => $command->getName(),
            '--jsonfile' => dirname(__DIR__)
                . DIRECTORY_SEPARATOR
                . 'app'
                . DIRECTORY_SEPARATOR
                . 'linuxforcomposer.test.custom.wrong.json',
        ));

        // the output of the command in the console
        $output = $commandTester->getDisplay();
        $this->assertSame(
            'php '. dirname(__DIR__) . '/app/app.php docker:manage '
            .'--detached --interactive --tty --phpversion 7.2.5 --threadsafe nts --port 8181:80 '
            .'--volume ${PWD}/:/srv/www --script lfphp run'
            . PHP_EOL
            . 'php '. dirname(__DIR__) . '/app/app.php docker:manage '
            .'--detached --interactive --tty --phpversion 7.1.16 --threadsafe nts --port 8282:80 '
            .'--volume ${PWD}/:/srv/www --script lfphp run'
            . PHP_EOL
            . 'php '. dirname(__DIR__) . '/app/app.php docker:manage '
            .'--detached --interactive --tty --phpversion 7.0.29 --threadsafe nts --port 8383:80 '
            .'--volume ${PWD}/:/srv/www --script lfphp run'
            . PHP_EOL,
            $output
        );
    }

    public function testExecuteWithNonexistentJsonFiles()
    {
        copy(JSONFILE, JSONFILE . '.wrong');
        unlink(JSONFILE);

        $kernel = self::bootKernel();

        $application = new Application($kernel);
        $application->add(new DockerParsejsonCommand());

        $command = $application->find('docker:parsejson');
        $commandTester = new CommandTester($command);
        $commandTester->execute(array(
            'command'  => $command->getName(),
            '--jsonfile' => dirname(__DIR__)
                . DIRECTORY_SEPARATOR
                . 'app'
                . DIRECTORY_SEPARATOR
                . 'linuxforcomposer.test.custom.wrong.json',
        ));

        copy(JSONFILE . '.wrong', JSONFILE);
        unlink(JSONFILE . '.wrong');

        // the output of the command in the console
        $output = $commandTester->getDisplay();
        $this->assertSame(
            'php '. dirname(__DIR__) . '/app/app.php docker:manage '
            .'--detached --interactive --tty --phpversion 7.2.5 --threadsafe nts --port 8181:80 '
            .'--volume ${PWD}/:/srv/www --script lfphp run'
            . PHP_EOL
            . 'php '. dirname(__DIR__) . '/app/app.php docker:manage '
            .'--detached --interactive --tty --phpversion 7.1.16 --threadsafe nts --port 8282:80 '
            .'--volume ${PWD}/:/srv/www --script lfphp run'
            . PHP_EOL
            . 'php '. dirname(__DIR__) . '/app/app.php docker:manage '
            .'--detached --interactive --tty --phpversion 7.0.29 --threadsafe nts --port 8383:80 '
            .'--volume ${PWD}/:/srv/www --script lfphp run'
            . PHP_EOL
            . 'php '. dirname(__DIR__) . '/app/app.php docker:manage '
            .'--detached --interactive --tty --phpversion 5.6.35 --threadsafe nts --port 8484:80 '
            .'--volume ${PWD}/:/srv/www --script lfphp run'
            . PHP_EOL,
            $output
        );
    }

    public function testExecuteWithInvalidJsonFile()
    {
        $kernel = self::bootKernel();

        $application = new Application($kernel);
        $application->add(new DockerParsejsonCommand());

        $command = $application->find('docker:parsejson');
        $commandTester = new CommandTester($command);
        $commandTester->execute(array(
            'command'  => $command->getName(),
            '--jsonfile' => dirname(__DIR__)
                . DIRECTORY_SEPARATOR
                . 'app'
                . DIRECTORY_SEPARATOR
                . 'linuxforcomposer.test.invalid.json',
        ));

        $this->assertEquals(1, $commandTester->getStatusCode());

        // the output of the command in the console
        $output = $commandTester->getDisplay();
        $this->assertEmpty($output);
    }

    public function testExecuteWithEmptyJsonFile()
    {
        $kernel = self::bootKernel();

        $application = new Application($kernel);
        $application->add(new DockerParsejsonCommand());

        $command = $application->find('docker:parsejson');
        $commandTester = new CommandTester($command);
        $commandTester->execute(array(
            'command'  => $command->getName(),
            '--jsonfile' => dirname(__DIR__)
                . DIRECTORY_SEPARATOR
                . 'app'
                . DIRECTORY_SEPARATOR
                . 'linuxforcomposer.test.empty.json',
        ));

        $this->assertEquals(2, $commandTester->getStatusCode());

        // the output of the command in the console
        $output = $commandTester->getDisplay();
        $this->assertEmpty($output);
    }

    public function testExecuteWithMissingPHPVersionsProperty()
    {
        $kernel = self::bootKernel();

        $application = new Application($kernel);
        $application->add(new DockerParsejsonCommand());

        $command = $application->find('docker:parsejson');
        $commandTester = new CommandTester($command);
        $commandTester->execute(array(
            'command'  => $command->getName(),
            '--jsonfile' => dirname(__DIR__)
                . DIRECTORY_SEPARATOR
                . 'app'
                . DIRECTORY_SEPARATOR
                . 'linuxforcomposer.test.missing.php.json',
        ));

        $this->assertEquals(2, $commandTester->getStatusCode());

        // the output of the command in the console
        $output = $commandTester->getDisplay();
        $this->assertEmpty($output);
    }

    public function testExecuteWithMinimumConfiguration()
    {
        $kernel = self::bootKernel();

        $application = new Application($kernel);
        $application->add(new DockerParsejsonCommand());

        $command = $application->find('docker:parsejson');
        $commandTester = new CommandTester($command);
        $commandTester->execute(array(
            'command'  => $command->getName(),
            '--jsonfile' => dirname(__DIR__)
                . DIRECTORY_SEPARATOR
                . 'app'
                . DIRECTORY_SEPARATOR
                . 'linuxforcomposer.test.minimum.config.json',
        ));

        // the output of the command in the console
        $output = $commandTester->getDisplay();
        $this->assertSame(
            'php '. dirname(__DIR__) . '/app/app.php docker:manage '
            .'--detached --phpversion 5.6.35 --threadsafe nts --script lfphp run'
            . PHP_EOL,
            $output
        );
    }

    public function testExecuteWithStartCommandWithMissingDetachedMode()
    {
        $kernel = self::bootKernel();

        $application = new Application($kernel);
        $application->add(new DockerParsejsonCommand());

        $command = $application->find('docker:parsejson');
        $commandTester = new CommandTester($command);
        $commandTester->execute(array(
            'command'  => $command->getName(),
            '--jsonfile' => dirname(__DIR__)
                . DIRECTORY_SEPARATOR
                . 'app'
                . DIRECTORY_SEPARATOR
                . 'linuxforcomposer.test.missing.detached.json',
        ));

        // the output of the command in the console
        $output = $commandTester->getDisplay();
        $this->assertSame(
            'php '. dirname(__DIR__) . '/app/app.php docker:manage '
            .'--detached --interactive --tty --phpversion 7.0.29 --threadsafe nts --port 8181:80 '
            .'--volume ${PWD}/:/srv/www --script lfphp run'
            . PHP_EOL
            . 'php '. dirname(__DIR__) . '/app/app.php docker:manage '
            .'--detached --interactive --tty --phpversion 5.6.35 --threadsafe nts --port 8282:80 '
            .'--volume ${PWD}/:/srv/www --script lfphp run'
            . PHP_EOL,
            $output
        );
    }

    public function testExecuteWithStartCommandWithPortNotAnArray()
    {
        $kernel = self::bootKernel();

        $application = new Application($kernel);
        $application->add(new DockerParsejsonCommand());

        $command = $application->find('docker:parsejson');
        $commandTester = new CommandTester($command);
        $commandTester->execute(array(
            'command'  => $command->getName(),
            '--jsonfile' => dirname(__DIR__)
                . DIRECTORY_SEPARATOR
                . 'app'
                . DIRECTORY_SEPARATOR
                . 'linuxforcomposer.test.one.port.noarray.json',
        ));

        // the output of the command in the console
        $output = $commandTester->getDisplay();
        $this->assertSame(
            'php '. dirname(__DIR__) . '/app/app.php docker:manage '
            .'--detached --interactive --tty --phpversion 7.0.29 --threadsafe nts '
            .'--volume ${PWD}/:/srv/www --script lfphp run'
            . PHP_EOL
            . 'php '. dirname(__DIR__) . '/app/app.php docker:manage '
            .'--detached --interactive --tty --phpversion 5.6.35 --threadsafe nts '
            .'--volume ${PWD}/:/srv/www --script lfphp run'
            . PHP_EOL,
            $output
        );
    }

    public function testExecuteWithStartCommandWithOnePortOnly()
    {
        $kernel = self::bootKernel();

        $application = new Application($kernel);
        $application->add(new DockerParsejsonCommand());

        $command = $application->find('docker:parsejson');
        $commandTester = new CommandTester($command);
        $commandTester->execute(array(
            'command'  => $command->getName(),
            '--jsonfile' => dirname(__DIR__)
                . DIRECTORY_SEPARATOR
                . 'app'
                . DIRECTORY_SEPARATOR
                . 'linuxforcomposer.test.one.port.json',
        ));

        // the output of the command in the console
        $output = $commandTester->getDisplay();
        $this->assertSame(
            'php '. dirname(__DIR__) . '/app/app.php docker:manage '
            .'--detached --interactive --tty --phpversion 7.0.29 --threadsafe nts --port 8484:80 '
            .'--volume ${PWD}/:/srv/www --script lfphp run'
            . PHP_EOL
            . 'php '. dirname(__DIR__) . '/app/app.php docker:manage '
            .'--detached --interactive --tty --phpversion 5.6.35 --threadsafe nts '
            .'--volume ${PWD}/:/srv/www --script lfphp run'
            . PHP_EOL,
            $output
        );
    }

    public function testExecuteWithStartCommandWithMultiplePorts()
    {
        $kernel = self::bootKernel();

        $application = new Application($kernel);
        $application->add(new DockerParsejsonCommand());

        $command = $application->find('docker:parsejson');
        $commandTester = new CommandTester($command);
        $commandTester->execute(array(
            'command'  => $command->getName(),
            '--jsonfile' => dirname(__DIR__)
                . DIRECTORY_SEPARATOR
                . 'app'
                . DIRECTORY_SEPARATOR
                . 'linuxforcomposer.test.multiple.ports.json',
        ));

        // the output of the command in the console
        $output = $commandTester->getDisplay();
        $this->assertSame(
            'php '. dirname(__DIR__) . '/app/app.php docker:manage '
            .'--detached --interactive --tty --phpversion 7.0.29 --threadsafe nts --port 8181:80 '
            .'--port 3306:3306 --volume ${PWD}/:/srv/www --script lfphp run'
            . PHP_EOL
            . 'php '. dirname(__DIR__) . '/app/app.php docker:manage '
            .'--detached --interactive --tty --phpversion 5.6.35 --threadsafe nts --port 8282:80 '
            .'--port 3307:3306 --volume ${PWD}/:/srv/www --script lfphp run'
            . PHP_EOL,
            $output
        );
    }

    public function testExecuteWithStartCommandWithVolumeNotAnArray()
    {
        $kernel = self::bootKernel();

        $application = new Application($kernel);
        $application->add(new DockerParsejsonCommand());

        $command = $application->find('docker:parsejson');
        $commandTester = new CommandTester($command);
        $commandTester->execute(array(
            'command'  => $command->getName(),
            '--jsonfile' => dirname(__DIR__)
                . DIRECTORY_SEPARATOR
                . 'app'
                . DIRECTORY_SEPARATOR
                . 'linuxforcomposer.test.one.volume.noarray.json',
        ));

        // the output of the command in the console
        $output = $commandTester->getDisplay();
        $this->assertSame(
            'php '. dirname(__DIR__) . '/app/app.php docker:manage '
            .'--detached --interactive --tty --phpversion 7.0.29 --threadsafe nts --port 8181:80 '
            .'--script lfphp run'
            . PHP_EOL
            . 'php '. dirname(__DIR__) . '/app/app.php docker:manage '
            .'--detached --interactive --tty --phpversion 5.6.35 --threadsafe nts --port 8282:80 '
            .'--script lfphp run'
            . PHP_EOL,
            $output
        );
    }

    public function testExecuteWithStartCommandWithOneVolumeOnly()
    {
        $kernel = self::bootKernel();

        $application = new Application($kernel);
        $application->add(new DockerParsejsonCommand());

        $command = $application->find('docker:parsejson');
        $commandTester = new CommandTester($command);
        $commandTester->execute(array(
            'command'  => $command->getName(),
            '--jsonfile' => dirname(__DIR__)
                . DIRECTORY_SEPARATOR
                . 'app'
                . DIRECTORY_SEPARATOR
                . 'linuxforcomposer.test.one.volume.json',
        ));

        // the output of the command in the console
        $output = $commandTester->getDisplay();
        $this->assertSame(
            'php '. dirname(__DIR__) . '/app/app.php docker:manage '
            .'--detached --interactive --tty --phpversion 7.0.29 --threadsafe nts --port 8181:80 '
            .'--volume ${PWD}/:/srv/www --script lfphp run'
            . PHP_EOL
            . 'php '. dirname(__DIR__) . '/app/app.php docker:manage '
            .'--detached --interactive --tty --phpversion 5.6.35 --threadsafe nts --port 8282:80 '
            .'--volume ${PWD}/:/srv/www --script lfphp run'
            . PHP_EOL,
            $output
        );
    }

    public function testExecuteWithStartCommandWithMultipleVolumes()
    {
        $kernel = self::bootKernel();

        $application = new Application($kernel);
        $application->add(new DockerParsejsonCommand());

        $command = $application->find('docker:parsejson');
        $commandTester = new CommandTester($command);
        $commandTester->execute(array(
            'command'  => $command->getName(),
            '--jsonfile' => dirname(__DIR__)
                . DIRECTORY_SEPARATOR
                . 'app'
                . DIRECTORY_SEPARATOR
                . 'linuxforcomposer.test.multiple.volumes.json',
        ));

        // the output of the command in the console
        $output = $commandTester->getDisplay();
        $this->assertSame(
            'php '. dirname(__DIR__) . '/app/app.php docker:manage '
            .'--detached --interactive --tty --phpversion 7.0.29 --threadsafe nts --port 8181:80 '
            .'--volume ${PWD}/:/srv/www --volume ${PWD}/:/srv/test --script lfphp run'
            . PHP_EOL
            . 'php '. dirname(__DIR__) . '/app/app.php docker:manage '
            .'--detached --interactive --tty --phpversion 5.6.35 --threadsafe nts --port 8282:80 '
            .'--volume ${PWD}/:/srv/www --volume ${PWD}/:/srv/test --script lfphp run'
            . PHP_EOL,
            $output
        );
    }

    public function testExecuteWithMissingScriptProperty()
    {
        $kernel = self::bootKernel();

        $application = new Application($kernel);
        $application->add(new DockerParsejsonCommand());

        $command = $application->find('docker:parsejson');
        $commandTester = new CommandTester($command);
        $commandTester->execute(array(
            'command'  => $command->getName(),
            '--jsonfile' => dirname(__DIR__)
                . DIRECTORY_SEPARATOR
                . 'app'
                . DIRECTORY_SEPARATOR
                . 'linuxforcomposer.test.missing.script.json',
        ));

        // the output of the command in the console
        $output = $commandTester->getDisplay();
        $this->assertSame(
            'php '. dirname(__DIR__) . '/app/app.php docker:manage '
            .'--detached --interactive --tty --phpversion 7.0.29 --threadsafe nts --port 8181:80 '
            .'--volume ${PWD}/:/srv/www --script lfphp run'
            . PHP_EOL
            . 'php '. dirname(__DIR__) . '/app/app.php docker:manage '
            .'--detached --interactive --tty --phpversion 5.6.35 --threadsafe nts --port 8282:80 '
            .'--volume ${PWD}/:/srv/www --script lfphp run'
            . PHP_EOL,
            $output
        );
    }

    public function testExecuteWithStartCommandWithMissingTSProperty()
    {
        $kernel = self::bootKernel();

        $application = new Application($kernel);
        $application->add(new DockerParsejsonCommand());

        $command = $application->find('docker:parsejson');
        $commandTester = new CommandTester($command);
        $commandTester->execute(array(
            'command'  => $command->getName(),
            '--jsonfile' => dirname(__DIR__)
                . DIRECTORY_SEPARATOR
                . 'app'
                . DIRECTORY_SEPARATOR
                . 'linuxforcomposer.test.missing.ts.json',
        ));

        // the output of the command in the console
        $output = $commandTester->getDisplay();
        $this->assertSame(
            'php '. dirname(__DIR__) . '/app/app.php docker:manage '
            .'--detached --interactive --tty --phpversion 7.0.29 --threadsafe nts --port 8181:80 '
            .'--volume ${PWD}/:/srv/www --script lfphp run'
            . PHP_EOL
            . 'php '. dirname(__DIR__) . '/app/app.php docker:manage '
            .'--detached --interactive --tty --phpversion 5.6.35 --threadsafe nts --port 8282:80 '
            .'--volume ${PWD}/:/srv/www --script lfphp run'
            . PHP_EOL,
            $output
        );
    }
}
