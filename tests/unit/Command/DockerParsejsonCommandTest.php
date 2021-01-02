<?php
/**
 * Linux for PHP/Linux for Composer
 *
 * Copyright 2017 - 2020 Foreach Code Factory <lfphp@asclinux.net>
 * Version 2.0.9
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
use Symfony\Bundle\FrameworkBundle\Console\Application;
use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;
use Symfony\Component\Console\Tester\CommandTester;

/**
 * @runTestsInSeparateProcesses
 * @preserveGlobalState disabled
 */
class DockerParsejsonCommandTest extends KernelTestCase
{
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

    public function testExecuteWithNoOption()
    {
        $kernel = self::bootKernel();

        $application = new Application($kernel);
        $application->add(new DockerParsejsonCommand());

        $command = $application->find('docker:parsejson');
        $commandTester = new CommandTester($command);
        $commandTester->execute([
            'command'  => $command->getName(),
        ]);

        // the output of the command in the console
        $output = $commandTester->getDisplay();
        $this->assertSame(
            'php '. dirname(__DIR__) . '/app/app.php docker:manage '
            . '--detached --interactive --tty --phpversion 7.4 --threadsafe nts '
            . '--port 7474:80 --port 13306:3306 '
            . '--script \'lfphp \' run'
            . PHP_EOL
            . 'php '. dirname(__DIR__) . '/app/app.php docker:manage '
            . '--detached --interactive --tty --phpversion 7.3 --threadsafe nts '
            . '--port 7373:80 --port 13307:3306 '
            . '--script \'lfphp \' run'
            . PHP_EOL
            . 'php '. dirname(__DIR__) . '/app/app.php docker:manage '
            . '--detached --interactive --tty --phpversion 7.2 --threadsafe nts '
            . '--port 7272:80 --port 13308:3306 '
            . '--script \'lfphp \' run'
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
        $commandTester->execute([
            'command'  => $command->getName(),
            '--jsonfile' => dirname(__DIR__)
                . DIRECTORY_SEPARATOR
                . 'app'
                . DIRECTORY_SEPARATOR
                . 'linuxforcomposer.test.custom.json',
        ]);

        // the output of the command in the console
        $output = $commandTester->getDisplay();
        $this->assertSame(
            'php '. dirname(__DIR__) . '/app/app.php docker:manage '
            . '--detached --interactive --tty --phpversion 5.6 --threadsafe nts --port 7474:80 '
            . '--volume ${PWD}/:/srv/www --script \'lfphp \' run'
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
        $commandTester->execute([
            'command'  => $command->getName(),
            '--jsonfile' => dirname(__DIR__)
                . DIRECTORY_SEPARATOR
                . 'app'
                . DIRECTORY_SEPARATOR
                . 'linuxforcomposer.test.custom.wrong.json',
        ]);

        // the output of the command in the console
        $output = $commandTester->getDisplay();
        $this->assertSame(
            'php '. dirname(__DIR__) . '/app/app.php docker:manage '
            . '--detached --interactive --tty --phpversion 7.4 --threadsafe nts '
            . '--port 7474:80 --port 13306:3306 '
            . "--script 'lfphp ' run"
            . PHP_EOL
            . 'php '. dirname(__DIR__) . '/app/app.php docker:manage '
            . '--detached --interactive --tty --phpversion 7.3 --threadsafe nts '
            . '--port 7373:80 --port 13307:3306 '
            . "--script 'lfphp ' run"
            . PHP_EOL
            . 'php '. dirname(__DIR__) . '/app/app.php docker:manage '
            . '--detached --interactive --tty --phpversion 7.2 --threadsafe nts '
            . '--port 7272:80 --port 13308:3306 '
            . "--script 'lfphp ' run"
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
        $commandTester->execute([
            'command'  => $command->getName(),
            '--jsonfile' => dirname(__DIR__)
                . DIRECTORY_SEPARATOR
                . 'app'
                . DIRECTORY_SEPARATOR
                . 'linuxforcomposer.test.custom.wrong.json',
        ]);

        copy(JSONFILE . '.wrong', JSONFILE);
        unlink(JSONFILE . '.wrong');

        // the output of the command in the console
        $output = $commandTester->getDisplay();
        $this->assertSame(
            'php '. dirname(__DIR__) . '/app/app.php docker:manage '
            . '--detached --interactive --tty --phpversion 7.4 --threadsafe nts '
            . '--port 7474:80 --port 13306:3306 '
            . "--script 'echo '\''<?php phpinfo();'\'' > /srv/www/index.php ,,,lfphp --mysql --phpfpm --apache ' run"
            . PHP_EOL
            . 'php '. dirname(__DIR__) . '/app/app.php docker:manage '
            . '--detached --interactive --tty --phpversion 7.3 --threadsafe nts '
            . '--port 7373:80 --port 13307:3306 '
            . "--script 'echo '\''<?php phpinfo();'\'' > /srv/www/index.php ,,,lfphp --mysql --phpfpm --apache ' run"
            . PHP_EOL
            . 'php '. dirname(__DIR__) . '/app/app.php docker:manage '
            . '--detached --interactive --tty --phpversion 7.2 --threadsafe nts '
            . '--port 7272:80 --port 13308:3306 '
            . "--script 'echo '\''<?php phpinfo();'\'' > /srv/www/index.php ,,,lfphp --mysql --phpfpm --apache ' run"
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
        $commandTester->execute([
            'command'  => $command->getName(),
            '--jsonfile' => dirname(__DIR__)
                . DIRECTORY_SEPARATOR
                . 'app'
                . DIRECTORY_SEPARATOR
                . 'linuxforcomposer.test.invalid.json',
        ]);

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
        $commandTester->execute([
            'command'  => $command->getName(),
            '--jsonfile' => dirname(__DIR__)
                . DIRECTORY_SEPARATOR
                . 'app'
                . DIRECTORY_SEPARATOR
                . 'linuxforcomposer.test.empty.json',
        ]);

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
        $commandTester->execute([
            'command'  => $command->getName(),
            '--jsonfile' => dirname(__DIR__)
                . DIRECTORY_SEPARATOR
                . 'app'
                . DIRECTORY_SEPARATOR
                . 'linuxforcomposer.test.missing.php.json',
        ]);

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
        $commandTester->execute([
            'command'  => $command->getName(),
            '--jsonfile' => dirname(__DIR__)
                . DIRECTORY_SEPARATOR
                . 'app'
                . DIRECTORY_SEPARATOR
                . 'linuxforcomposer.test.minimum.config.json',
        ]);

        // the output of the command in the console
        $output = $commandTester->getDisplay();
        $this->assertSame(
            'php '. dirname(__DIR__) . '/app/app.php docker:manage '
            . '--detached --phpversion 5.6 --threadsafe nts --script \'lfphp \' '
            . 'run'
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
        $commandTester->execute([
            'command'  => $command->getName(),
            '--jsonfile' => dirname(__DIR__)
                . DIRECTORY_SEPARATOR
                . 'app'
                . DIRECTORY_SEPARATOR
                . 'linuxforcomposer.test.missing.detached.json',
        ]);

        // the output of the command in the console
        $output = $commandTester->getDisplay();
        $this->assertSame(
            'php '. dirname(__DIR__) . '/app/app.php docker:manage '
            . '--detached --interactive --tty --phpversion 7.4 --threadsafe nts --port 7474:80 '
            . '--volume ${PWD}/:/srv/www --script \'lfphp \' run'
            . PHP_EOL
            . 'php '. dirname(__DIR__) . '/app/app.php docker:manage '
            . '--detached --interactive --tty --phpversion 7.3 --threadsafe nts --port 7373:80 '
            . '--volume ${PWD}/:/srv/www --script \'lfphp \' '
            . 'run'
            . PHP_EOL,
            $output
        );
    }

    public function testExecuteWithDockerfile()
    {
        $kernel = self::bootKernel();

        $application = new Application($kernel);
        $application->add(new DockerParsejsonCommand());

        $command = $application->find('docker:parsejson');
        $commandTester = new CommandTester($command);
        $commandTester->execute([
            'command'  => $command->getName(),
            '--jsonfile' => dirname(__DIR__)
                . DIRECTORY_SEPARATOR
                . 'app'
                . DIRECTORY_SEPARATOR
                . 'linuxforcomposer.test.dockerfile.json',
        ]);

        // the output of the command in the console
        $output = $commandTester->getDisplay();
        $this->assertSame(
            'php '. dirname(__DIR__) . '/app/app.php docker:manage '
            . '--detached --interactive --tty '
            . '--port 7474:80 --port 13306:3306 '
            . "--script dockerfile,,,Dockerfile,,,dockerfiletest "
            . 'build'
            . PHP_EOL,
            $output
        );
    }

    public function testExecuteWithDockerfileAndWithAuthentication()
    {
        $kernel = self::bootKernel();

        $application = new Application($kernel);
        $application->add(new DockerParsejsonCommand());

        $command = $application->find('docker:parsejson');
        $commandTester = new CommandTester($command);
        $commandTester->execute([
            'command'  => $command->getName(),
            '--jsonfile' => dirname(__DIR__)
                . DIRECTORY_SEPARATOR
                . 'app'
                . DIRECTORY_SEPARATOR
                . 'linuxforcomposer.test.dockerfile.auth.json',
        ]);

        // the output of the command in the console
        $output = $commandTester->getDisplay();
        $this->assertSame(
            'php '. dirname(__DIR__) . '/app/app.php docker:manage '
            . '--detached --interactive --tty '
            . '--port 7474:80 --port 13306:3306 '
            . "--script dockerfile,,,Dockerfile,,,user1:secret,,,dockerfiletest "
            . 'build'
            . PHP_EOL,
            $output
        );
    }

    public function testExecuteWithDockerCompose()
    {
        $kernel = self::bootKernel();

        $application = new Application($kernel);
        $application->add(new DockerParsejsonCommand());

        $command = $application->find('docker:parsejson');
        $commandTester = new CommandTester($command);
        $commandTester->execute([
            'command'  => $command->getName(),
            '--jsonfile' => dirname(__DIR__)
                . DIRECTORY_SEPARATOR
                . 'app'
                . DIRECTORY_SEPARATOR
                . 'linuxforcomposer.test.docker-compose.json',
        ]);

        // the output of the command in the console
        $output = $commandTester->getDisplay();
        $this->assertSame(
            'php '. dirname(__DIR__) . '/app/app.php docker:manage '
            . "--script docker-compose,,,https://example.com/fakerepo "
            . 'build'
            . PHP_EOL,
            $output
        );
    }

    public function testExecuteWithDockerComposeAndWithAuthentication()
    {
        $kernel = self::bootKernel();

        $application = new Application($kernel);
        $application->add(new DockerParsejsonCommand());

        $command = $application->find('docker:parsejson');
        $commandTester = new CommandTester($command);
        $commandTester->execute([
            'command'  => $command->getName(),
            '--jsonfile' => dirname(__DIR__)
                . DIRECTORY_SEPARATOR
                . 'app'
                . DIRECTORY_SEPARATOR
                . 'linuxforcomposer.test.docker-compose.auth.json',
        ]);

        // the output of the command in the console
        $output = $commandTester->getDisplay();
        $this->assertSame(
            'php '. dirname(__DIR__) . '/app/app.php docker:manage '
            . "--script docker-compose,,,https://example.com/fakerepo,,,user1:secret "
            . 'build'
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
        $commandTester->execute([
            'command'  => $command->getName(),
            '--jsonfile' => dirname(__DIR__)
                . DIRECTORY_SEPARATOR
                . 'app'
                . DIRECTORY_SEPARATOR
                . 'linuxforcomposer.test.one.port.noarray.json',
        ]);

        // the output of the command in the console
        $output = $commandTester->getDisplay();
        $this->assertSame(
            'php '. dirname(__DIR__) . '/app/app.php docker:manage '
            . '--detached --interactive --tty --phpversion 7.4 --threadsafe nts '
            . '--port 7474:80 '
            . '--volume ${PWD}/:/srv/www --script \'lfphp \' '
            . 'run'
            . PHP_EOL
            . 'php '. dirname(__DIR__) . '/app/app.php docker:manage '
            . '--detached --interactive --tty --phpversion 7.3 --threadsafe nts '
            . '--volume ${PWD}/:/srv/www --script \'lfphp \' '
            . 'run'
            . PHP_EOL,
            $output
        );
    }

    public function testExecuteWithStartCommandWithPortArrayNotAnArray()
    {
        $kernel = self::bootKernel();

        $application = new Application($kernel);
        $application->add(new DockerParsejsonCommand());

        $command = $application->find('docker:parsejson');
        $commandTester = new CommandTester($command);
        $commandTester->execute([
            'command'  => $command->getName(),
            '--jsonfile' => dirname(__DIR__)
                . DIRECTORY_SEPARATOR
                . 'app'
                . DIRECTORY_SEPARATOR
                . 'linuxforcomposer.test.one.port.array.noarray.json',
        ]);

        // the output of the command in the console
        $output = $commandTester->getDisplay();
        $this->assertSame(
            'php '. dirname(__DIR__) . '/app/app.php docker:manage '
            . '--detached --interactive --tty --phpversion 7.4 --threadsafe nts '
            . '--port 7474:80 '
            . '--volume ${PWD}/:/srv/www --script \'lfphp \' '
            . 'run'
            . PHP_EOL
            . 'php '. dirname(__DIR__) . '/app/app.php docker:manage '
            . '--detached --interactive --tty --phpversion 7.3 --threadsafe nts '
            . '--volume ${PWD}/:/srv/www --script \'lfphp \' '
            . 'run'
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
        $commandTester->execute([
            'command'  => $command->getName(),
            '--jsonfile' => dirname(__DIR__)
                . DIRECTORY_SEPARATOR
                . 'app'
                . DIRECTORY_SEPARATOR
                . 'linuxforcomposer.test.one.port.json',
        ]);

        // the output of the command in the console
        $output = $commandTester->getDisplay();
        $this->assertSame(
            'php '. dirname(__DIR__) . '/app/app.php docker:manage '
            . '--detached --interactive --tty --phpversion 7.4 --threadsafe nts --port 7474:80 '
            . '--volume ${PWD}/:/srv/www --script \'lfphp \' '
            . 'run'
            . PHP_EOL
            . 'php '. dirname(__DIR__) . '/app/app.php docker:manage '
            . '--detached --interactive --tty --phpversion 7.3 --threadsafe nts '
            . '--volume ${PWD}/:/srv/www --script \'lfphp \' '
            . 'run'
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
        $commandTester->execute([
            'command'  => $command->getName(),
            '--jsonfile' => dirname(__DIR__)
                . DIRECTORY_SEPARATOR
                . 'app'
                . DIRECTORY_SEPARATOR
                . 'linuxforcomposer.test.multiple.ports.json',
        ]);

        // the output of the command in the console
        $output = $commandTester->getDisplay();
        $this->assertSame(
            'php '. dirname(__DIR__) . '/app/app.php docker:manage '
            . '--detached --interactive --tty --phpversion 7.4 --threadsafe nts --port 7474:80 '
            . '--port 3306:3306 --volume ${PWD}/:/srv/www --script \'lfphp \' '
            . 'run'
            . PHP_EOL
            . 'php '. dirname(__DIR__) . '/app/app.php docker:manage '
            . '--detached --interactive --tty --phpversion 7.3 --threadsafe nts --port 7373:80 '
            . '--port 3307:3306 --volume ${PWD}/:/srv/www --script \'lfphp \' '
            . 'run'
            . PHP_EOL,
            $output
        );
    }

    public function testExecuteWithStartCommandWithScriptNotAnArray()
    {
        $kernel = self::bootKernel();

        $application = new Application($kernel);
        $application->add(new DockerParsejsonCommand());

        $command = $application->find('docker:parsejson');
        $commandTester = new CommandTester($command);
        $commandTester->execute([
            'command'  => $command->getName(),
            '--jsonfile' => dirname(__DIR__)
                . DIRECTORY_SEPARATOR
                . 'app'
                . DIRECTORY_SEPARATOR
                . 'linuxforcomposer.test.one.script.noarray.json',
        ]);

        // the output of the command in the console
        $output = $commandTester->getDisplay();
        $this->assertSame(
            'php '. dirname(__DIR__) . '/app/app.php docker:manage '
            . '--detached --interactive --tty --phpversion 7.4 --threadsafe nts '
            . '--port 7474:80 '
            . '--script \'lfphp \' '
            . 'run'
            . PHP_EOL
            . 'php '. dirname(__DIR__) . '/app/app.php docker:manage '
            . '--detached --interactive --tty --phpversion 7.3 --threadsafe nts '
            . '--port 7373:80 '
            . '--script \'lfphp \' '
            . 'run'
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
        $commandTester->execute([
            'command'  => $command->getName(),
            '--jsonfile' => dirname(__DIR__)
                . DIRECTORY_SEPARATOR
                . 'app'
                . DIRECTORY_SEPARATOR
                . 'linuxforcomposer.test.one.volume.noarray.json',
        ]);

        // the output of the command in the console
        $output = $commandTester->getDisplay();
        $this->assertSame(
            'php '. dirname(__DIR__) . '/app/app.php docker:manage '
            . '--detached --interactive --tty --phpversion 7.4 --threadsafe nts --port 7474:80 '
            . '--script \'lfphp \' '
            . 'run'
            . PHP_EOL
            . 'php '. dirname(__DIR__) . '/app/app.php docker:manage '
            . '--detached --interactive --tty --phpversion 7.3 --threadsafe nts --port 7373:80 '
            . '--script \'lfphp \' '
            . 'run'
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
        $commandTester->execute([
            'command'  => $command->getName(),
            '--jsonfile' => dirname(__DIR__)
                . DIRECTORY_SEPARATOR
                . 'app'
                . DIRECTORY_SEPARATOR
                . 'linuxforcomposer.test.one.volume.json',
        ]);

        // the output of the command in the console
        $output = $commandTester->getDisplay();
        $this->assertSame(
            'php '. dirname(__DIR__) . '/app/app.php docker:manage '
            . '--detached --interactive --tty --phpversion 7.4 --threadsafe nts --port 7474:80 '
            . '--volume ${PWD}/:/srv/www --script \'lfphp \' '
            . 'run'
            . PHP_EOL
            . 'php '. dirname(__DIR__) . '/app/app.php docker:manage '
            . '--detached --interactive --tty --phpversion 7.3 --threadsafe nts --port 7373:80 '
            . '--volume ${PWD}/:/srv/www --script \'lfphp \' '
            . 'run'
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
        $commandTester->execute([
            'command'  => $command->getName(),
            '--jsonfile' => dirname(__DIR__)
                . DIRECTORY_SEPARATOR
                . 'app'
                . DIRECTORY_SEPARATOR
                . 'linuxforcomposer.test.multiple.volumes.json',
        ]);

        // the output of the command in the console
        $output = $commandTester->getDisplay();
        $this->assertSame(
            'php '. dirname(__DIR__) . '/app/app.php docker:manage '
            . '--detached --interactive --tty --phpversion 7.4 --threadsafe nts --port 7474:80 '
            . '--volume ${PWD}/:/srv/www --volume ${PWD}/:/srv/test --script \'lfphp \' '
            . 'run'
            . PHP_EOL
            . 'php '. dirname(__DIR__) . '/app/app.php docker:manage '
            . '--detached --interactive --tty --phpversion 7.3 --threadsafe nts --port 7373:80 '
            . '--volume ${PWD}/:/srv/www --volume ${PWD}/:/srv/test --script \'lfphp \' '
            . 'run'
            . PHP_EOL,
            $output
        );
    }

    public function testExecuteWithStartCommandWithOneMountOnly()
    {
        $kernel = self::bootKernel();

        $application = new Application($kernel);
        $application->add(new DockerParsejsonCommand());

        $command = $application->find('docker:parsejson');
        $commandTester = new CommandTester($command);
        $commandTester->execute([
            'command'  => $command->getName(),
            '--jsonfile' => dirname(__DIR__)
                . DIRECTORY_SEPARATOR
                . 'app'
                . DIRECTORY_SEPARATOR
                . 'linuxforcomposer.test.one.mount.json',
        ]);

        // the output of the command in the console
        $output = $commandTester->getDisplay();
        $this->assertSame(
            'php '. dirname(__DIR__) . '/app/app.php docker:manage '
            . '--detached --interactive --tty --phpversion 7.4 --threadsafe nts --port 7474:80 '
            . '--mount source=unittest_srv,target=/srv,,,unittest_srv,,,, '
            . '--script \'lfphp \' '
            . 'run'
            . PHP_EOL
            . 'php '. dirname(__DIR__) . '/app/app.php docker:manage '
            . '--detached --interactive --tty --phpversion 7.3 --threadsafe nts --port 7373:80 '
            . '--mount source=unittest_srv,target=/srv,,,unittest_srv,,,, '
            . '--script \'lfphp \' '
            . 'run'
            . PHP_EOL,
            $output
        );
    }

    public function testExecuteWithStartCommandWithOneMountOnlyToRemove()
    {
        $kernel = self::bootKernel();

        $application = new Application($kernel);
        $application->add(new DockerParsejsonCommand());

        $command = $application->find('docker:parsejson');
        $commandTester = new CommandTester($command);
        $commandTester->execute([
            'command'  => $command->getName(),
            '--jsonfile' => dirname(__DIR__)
                . DIRECTORY_SEPARATOR
                . 'app'
                . DIRECTORY_SEPARATOR
                . 'linuxforcomposer.test.one.mount.remove.json',
        ]);

        // the output of the command in the console
        $output = $commandTester->getDisplay();
        $this->assertSame(
            'php '. dirname(__DIR__) . '/app/app.php docker:manage '
            . '--detached --interactive --tty --phpversion 7.4 --threadsafe nts --port 7474:80 '
            . '--mount :unittest_srv,,,, '
            . '--script \'lfphp \' '
            . 'run'
            . PHP_EOL
            . 'php '. dirname(__DIR__) . '/app/app.php docker:manage '
            . '--detached --interactive --tty --phpversion 7.3 --threadsafe nts --port 7373:80 '
            . '--mount :unittest_srv,,,, '
            . '--script \'lfphp \' '
            . 'run'
            . PHP_EOL,
            $output
        );
    }

    public function testExecuteWithStartCommandWithOneMountOnlyWithSubdirectoryInName()
    {
        $kernel = self::bootKernel();

        $application = new Application($kernel);
        $application->add(new DockerParsejsonCommand());

        $command = $application->find('docker:parsejson');
        $commandTester = new CommandTester($command);
        $commandTester->execute([
            'command'  => $command->getName(),
            '--jsonfile' => dirname(__DIR__)
                . DIRECTORY_SEPARATOR
                . 'app'
                . DIRECTORY_SEPARATOR
                . 'linuxforcomposer.test.one.mount.subdir.json',
        ]);

        // the output of the command in the console
        $output = $commandTester->getDisplay();
        $this->assertSame(
            'php '. dirname(__DIR__) . '/app/app.php docker:manage '
            . '--detached --interactive --tty --phpversion 7.4 --threadsafe nts --port 7474:80 '
            . '--mount source=unittest_srv_mysql,target=/srv/mysql,,,unittest_srv_mysql,,,, '
            . '--script \'lfphp \' '
            . 'run'
            . PHP_EOL
            . 'php '. dirname(__DIR__) . '/app/app.php docker:manage '
            . '--detached --interactive --tty --phpversion 7.3 --threadsafe nts --port 7373:80 '
            . '--mount source=unittest_srv_mysql,target=/srv/mysql,,,unittest_srv_mysql,,,, '
            . '--script \'lfphp \' '
            . 'run'
            . PHP_EOL,
            $output
        );
    }

    public function testExecuteWithStartCommandWithMultipleMounts()
    {
        $kernel = self::bootKernel();

        $application = new Application($kernel);
        $application->add(new DockerParsejsonCommand());

        $command = $application->find('docker:parsejson');
        $commandTester = new CommandTester($command);
        $commandTester->execute([
            'command'  => $command->getName(),
            '--jsonfile' => dirname(__DIR__)
                . DIRECTORY_SEPARATOR
                . 'app'
                . DIRECTORY_SEPARATOR
                . 'linuxforcomposer.test.multiple.mounts.json',
        ]);

        // the output of the command in the console
        $output = $commandTester->getDisplay();
        $this->assertSame(
            'php '. dirname(__DIR__) . '/app/app.php docker:manage '
            . '--detached --interactive --tty --phpversion 7.4 --threadsafe nts --port 7474:80 '
            . '--mount source=unittest_srv,target=/srv,,,unittest_srv,,,,source=unittest_home,target=/home,,,unittest_home,,,, '
            . '--script \'lfphp \' '
            . 'run'
            . PHP_EOL
            . 'php '. dirname(__DIR__) . '/app/app.php docker:manage '
            . '--detached --interactive --tty --phpversion 7.3 --threadsafe nts --port 7373:80 '
            . '--mount source=unittest_srv,target=/srv,,,unittest_srv,,,,source=unittest_home,target=/home,,,unittest_home,,,, '
            . '--script \'lfphp \' '
            . 'run'
            . PHP_EOL,
            $output
        );
    }

    public function testExecuteWithStartCommandWithMultipleMountsToRemove()
    {
        $kernel = self::bootKernel();

        $application = new Application($kernel);
        $application->add(new DockerParsejsonCommand());

        $command = $application->find('docker:parsejson');
        $commandTester = new CommandTester($command);
        $commandTester->execute([
            'command'  => $command->getName(),
            '--jsonfile' => dirname(__DIR__)
                . DIRECTORY_SEPARATOR
                . 'app'
                . DIRECTORY_SEPARATOR
                . 'linuxforcomposer.test.multiple.mounts.remove.json',
        ]);

        // the output of the command in the console
        $output = $commandTester->getDisplay();
        $this->assertSame(
            'php '. dirname(__DIR__) . '/app/app.php docker:manage '
            . '--detached --interactive --tty --phpversion 7.4 --threadsafe nts --port 7474:80 '
            . '--mount :unittest_srv,,,,:unittest_home,,,, '
            . '--script \'lfphp \' '
            . 'run'
            . PHP_EOL
            . 'php '. dirname(__DIR__) . '/app/app.php docker:manage '
            . '--detached --interactive --tty --phpversion 7.3 --threadsafe nts --port 7373:80 '
            . '--mount :unittest_srv,,,,:unittest_home,,,, '
            . '--script \'lfphp \' '
            . 'run'
            . PHP_EOL,
            $output
        );
    }

    public function testExecuteWithStartCommandWithMissingMountDirectory()
    {
        $kernel = self::bootKernel();

        $application = new Application($kernel);
        $application->add(new DockerParsejsonCommand());

        $command = $application->find('docker:parsejson');
        $commandTester = new CommandTester($command);
        $commandTester->execute([
            'command'  => $command->getName(),
            '--jsonfile' => dirname(__DIR__)
                . DIRECTORY_SEPARATOR
                . 'app'
                . DIRECTORY_SEPARATOR
                . 'linuxforcomposer.test.missing.mount.json',
        ]);

        // the output of the command in the console
        $output = $commandTester->getDisplay();
        $this->assertSame(
            'php '. dirname(__DIR__) . '/app/app.php docker:manage '
            . '--detached --interactive --tty --phpversion 7.4 --threadsafe nts --port 7474:80 '
            . '--script \'lfphp \' '
            . 'run'
            . PHP_EOL
            . 'php '. dirname(__DIR__) . '/app/app.php docker:manage '
            . '--detached --interactive --tty --phpversion 7.3 --threadsafe nts --port 7373:80 '
            . '--script \'lfphp \' '
            . 'run'
            . PHP_EOL,
            $output
        );
    }

    public function testExecuteWithStartCommandWithMissingMountRootName()
    {
        $kernel = self::bootKernel();

        $application = new Application($kernel);
        $application->add(new DockerParsejsonCommand());

        $command = $application->find('docker:parsejson');
        $commandTester = new CommandTester($command);
        $commandTester->execute([
            'command'  => $command->getName(),
            '--jsonfile' => dirname(__DIR__)
                . DIRECTORY_SEPARATOR
                . 'app'
                . DIRECTORY_SEPARATOR
                . 'linuxforcomposer.test.missing.mount.rootname.json',
        ]);

        // the output of the command in the console
        $output = $commandTester->getDisplay();
        $this->assertSame(
            'php '. dirname(__DIR__) . '/app/app.php docker:manage '
            . '--detached --interactive --tty --phpversion 7.4 --threadsafe nts --port 7474:80 '
            . '--script \'lfphp \' '
            . 'run'
            . PHP_EOL
            . 'php '. dirname(__DIR__) . '/app/app.php docker:manage '
            . '--detached --interactive --tty --phpversion 7.3 --threadsafe nts --port 7373:80 '
            . '--script \'lfphp \' '
            . 'run'
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
        $commandTester->execute([
            'command'  => $command->getName(),
            '--jsonfile' => dirname(__DIR__)
                . DIRECTORY_SEPARATOR
                . 'app'
                . DIRECTORY_SEPARATOR
                . 'linuxforcomposer.test.missing.script.json',
        ]);

        // the output of the command in the console
        $output = $commandTester->getDisplay();
        $this->assertSame(
            'php '. dirname(__DIR__) . '/app/app.php docker:manage '
            . '--detached --interactive --tty --phpversion 7.4 --threadsafe nts --port 7474:80 '
            . '--volume ${PWD}/:/srv/www --script \'lfphp \' '
            . 'run'
            . PHP_EOL
            . 'php '. dirname(__DIR__) . '/app/app.php docker:manage '
            . '--detached --interactive --tty --phpversion 7.3 --threadsafe nts --port 7373:80 '
            . '--volume ${PWD}/:/srv/www --script \'lfphp \' '
            . 'run'
            . PHP_EOL,
            $output
        );
    }

    public function testExecuteWithStartCommandWithMultipleScripts()
    {
        $kernel = self::bootKernel();

        $application = new Application($kernel);
        $application->add(new DockerParsejsonCommand());

        $command = $application->find('docker:parsejson');
        $commandTester = new CommandTester($command);
        $commandTester->execute([
            'command'  => $command->getName(),
            '--jsonfile' => dirname(__DIR__)
                . DIRECTORY_SEPARATOR
                . 'app'
                . DIRECTORY_SEPARATOR
                . 'linuxforcomposer.test.multiple.scripts.json',
        ]);

        // the output of the command in the console
        $output = $commandTester->getDisplay();
        $this->assertSame(
            "php " . dirname(__DIR__) . "/app/app.php docker:manage "
            . "--detached --interactive --tty --phpversion 7.4 --threadsafe nts --port 7474:80 "
            . "--volume \${PWD}/:/srv/www --script 'echo '\''<?php phpinfo();'\'' > /srv/www/index.php ,,,lfphp ' "
            . 'run'
            . PHP_EOL
            . "php " . dirname(__DIR__) . "/app/app.php docker:manage "
            . "--detached --interactive --tty --phpversion 7.3 --threadsafe nts --port 7373:80 "
            . "--volume \${PWD}/:/srv/www --script 'echo '\''<?php phpinfo();'\'' > /srv/www/index.php ,,,lfphp ' "
            . 'run'
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
        $commandTester->execute([
            'command'  => $command->getName(),
            '--jsonfile' => dirname(__DIR__)
                . DIRECTORY_SEPARATOR
                . 'app'
                . DIRECTORY_SEPARATOR
                . 'linuxforcomposer.test.missing.ts.json',
        ]);

        // the output of the command in the console
        $output = $commandTester->getDisplay();
        $this->assertSame(
            'php '. dirname(__DIR__) . '/app/app.php docker:manage '
            . '--detached --interactive --tty --phpversion 7.4 --threadsafe nts --port 7474:80 '
            . '--volume ${PWD}/:/srv/www --script \'lfphp \' '
            . 'run'
            . PHP_EOL
            . 'php '. dirname(__DIR__) . '/app/app.php docker:manage '
            . '--detached --interactive --tty --phpversion 7.3 --threadsafe nts --port 7373:80 '
            . '--volume ${PWD}/:/srv/www --script \'lfphp \' '
            . 'run'
            . PHP_EOL,
            $output
        );
    }
}
