<?php
/**
 * Linux for PHP/Linux for Composer
 *
 * Copyright 2017 - 2020 Foreach Code Factory <lfphp@asclinux.net>
 * Version 2.0.0
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

use Linuxforcomposer\Command\DockerManageCommand;
use LinuxforcomposerTest\Mock\InputMock;
use Symfony\Bundle\FrameworkBundle\Console\Application;
use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;
use Symfony\Component\Console\Tester\CommandTester;

/**
 * @runTestsInSeparateProcesses
 * @preserveGlobalState disabled
 */
class DockerManageCommandTest extends KernelTestCase
{
    protected $dockerLfcProcessMock;

    protected $progressBarMock;

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

        if (!defined('LFPHP')) {
            define('LFPHP', false);
        }
    }

    public function tearDown()
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
            ->with(null);
        $this->dockerLfcProcessMock
            ->shouldReceive('prepareProcess');
        $this->dockerLfcProcessMock
            ->shouldReceive('start');
        $this->dockerLfcProcessMock
            ->shouldReceive('wait')
            ->withAnyArgs();

        $this->progressBarMock = \Mockery::mock('overload:Symfony\Component\Console\Helper\ProgressBar');
        $this->progressBarMock
            ->shouldReceive('setFormatDefinition')
            ->once()
            ->with('normal_nomax_nocurrent', ' Working on it... [%bar%]');
        $this->progressBarMock
            ->shouldReceive('setFormat')
            ->once()
            ->with('normal_nomax_nocurrent');
        $this->progressBarMock
            ->shouldReceive('start')
            ->once();
        $this->progressBarMock
            ->shouldReceive('finish')
            ->once();
    }

    public function testCheckImageWithImageAvailabilitySuccess()
    {
        // Redirect output to command output
        //$this->setOutputCallback(function () {
        //});

        ob_start();

        $this->createMocksForUnixEnv();

        $this->dockerLfcProcessMock
            ->shouldReceive('isSuccessful')
            ->andReturn(true);
        $this->dockerLfcProcessMock
            ->shouldReceive('getOutput')
            ->once()
            ->andReturn('We downloaded the image!');
        $this->dockerLfcProcessMock
            ->shouldReceive('getErrorOutput')
            ->once()
            ->andReturn('One download failed');
        $this->dockerLfcProcessMock
            ->shouldReceive('getExitCode')
            ->once()
            ->andReturn(0);

        $kernel = self::bootKernel();

        $application = new Application($kernel);
        $application->add(new DockerManageCommand());

        $command = $application->find('docker:manage');
        $commandReflection = new \ReflectionClass($command);

        $methodsList = $commandReflection->getMethods();

        for ($i = 0; $i < count($methodsList); $i++) {
            $key = $methodsList[$i]->name;
            $commandMethods[$key] = $methodsList[$i];
            $commandMethods[$key]->setAccessible(true);
        }

        $output = $commandMethods['checkImage']->invokeArgs(
            $command,
            ['7.2-nts', 'nts']
        );

        $this->assertSame(
            '',
            $output
        );

        $this->assertSame(
            PHP_EOL
            . 'Checking for image availability and downloading if necessary.'
            . PHP_EOL
            . PHP_EOL
            . 'This may take a few minutes...'
            . PHP_EOL
            . PHP_EOL
            . 'We downloaded the image!'
            . PHP_EOL
            . 'One download failed'
            . PHP_EOL
            . 'Done!'
            . PHP_EOL
            . PHP_EOL,
            $this->getActualOutput()
        );

        $output2 = $commandMethods['checkImage']->invokeArgs(
            $command,
            ['7.1-zts', 'zts']
        );

        $this->assertSame(
            '',
            $output2
        );

        $output3 = $commandMethods['checkImage']->invokeArgs(
            $command,
            ['7.0-nts', 'nts']
        );

        $this->assertSame(
            '',
            $output3
        );

        ob_end_clean();
    }

    public function testCheckImageWithImageAvailabilityFailure()
    {
        // Redirect output to command output
        //$this->setOutputCallback(function () {
        //});

        ob_start();

        $this->createMocksForUnixEnv();

        $this->dockerLfcProcessMock
            ->shouldReceive('isSuccessful')
            ->andReturn(true);
        $this->dockerLfcProcessMock
            ->shouldReceive('getOutput')
            ->once()
            ->andReturn('We downloaded the image!');
        $this->dockerLfcProcessMock
            ->shouldReceive('getErrorOutput')
            ->once()
            ->andReturn('One download failed');
        $this->dockerLfcProcessMock
            ->shouldReceive('getExitCode')
            ->once()
            ->andReturn(1);

        $kernel = self::bootKernel();

        $application = new Application($kernel);
        $application->add(new DockerManageCommand());

        $command = $application->find('docker:manage');
        $commandReflection = new \ReflectionClass($command);

        $methodsList = $commandReflection->getMethods();

        for ($i = 0; $i < count($methodsList); $i++) {
            $key = $methodsList[$i]->name;
            $commandMethods[$key] = $methodsList[$i];
            $commandMethods[$key]->setAccessible(true);
        }

        $output = $commandMethods['checkImage']->invokeArgs(
            $command,
            ['7.3.5-nts', 'nts']
        );

        $this->assertSame(
            'asclinux/linuxforphp-8.2-ultimate:src ',
            $output
        );

        $this->assertSame(
            PHP_EOL
            . 'Checking for image availability and downloading if necessary.'
            . PHP_EOL
            . PHP_EOL
            . 'This may take a few minutes...'
            . PHP_EOL
            . PHP_EOL
            . 'We downloaded the image!'
            . PHP_EOL
            . 'One download failed'
            . PHP_EOL
            . 'Done!'
            . PHP_EOL
            . PHP_EOL,
            $this->getActualOutput()
        );

        ob_end_clean();
    }

    public function testFormatInputWithExistingPHPVersion()
    {
        // Redirect output to command output
        //$this->setOutputCallback(function () {
        //});

        ob_start();

        $this->createMocksForUnixEnv();

        $this->dockerLfcProcessMock
            ->shouldReceive('isSuccessful')
            ->andReturn(true);
        $this->dockerLfcProcessMock
            ->shouldReceive('getOutput')
            ->once()
            ->andReturn('We downloaded the image!');
        $this->dockerLfcProcessMock
            ->shouldReceive('getErrorOutput')
            ->once()
            ->andReturn('One download failed');
        $this->dockerLfcProcessMock
            ->shouldReceive('getExitCode')
            ->once()
            ->andReturn(0);

        $dockerManageCommandFake = new DockerManageCommand();
        $commandReflection = new \ReflectionClass($dockerManageCommandFake);

        $methodsList = $commandReflection->getMethods();

        for ($i = 0; $i < count($methodsList); $i++) {
            $key = $methodsList[$i]->name;
            $commandMethods[$key] = $methodsList[$i];
            $commandMethods[$key]->setAccessible(true);
        }

        $arguments = [
            'command' => 'docker:manage',
            'interactive' => true,
            'tty'  => true,
            'detached'  => true,
            'phpversion' => '7.2',
            'threadsafe' => 'nts',
            'port' => '7474:80',
            'mount' => null,
            'volume' => '${PWD}/:/srv/www',
            'script' => 'lfphp',
            'execute' => 'run',
        ];

        $arrayInputFake = new InputMock();
        $arrayInputFake->setArguments($arguments);

        $output = $commandMethods['formatInput']->invokeArgs(
            $dockerManageCommandFake,
            [$arrayInputFake]
        );

        $this->assertSame(
            'docker run --restart=always -d -i -t -p 7474:80 '
            . '-v ${PWD}/:/srv/www asclinux/linuxforphp-8.2-ultimate:7.2-nts /bin/bash -c "lfphp"',
            $output
        );

        $this->assertSame(
            PHP_EOL
            . 'Checking for image availability and downloading if necessary.'
            . PHP_EOL
            . PHP_EOL
            . 'This may take a few minutes...'
            . PHP_EOL
            . PHP_EOL
            . 'We downloaded the image!'
            . PHP_EOL
            . 'One download failed'
            . PHP_EOL
            . 'Done!'
            . PHP_EOL
            . PHP_EOL,
            $this->getActualOutput()
        );

        $dockerManageCommandFake = new DockerManageCommand();
        $commandReflection = new \ReflectionClass($dockerManageCommandFake);

        $methodsList = $commandReflection->getMethods();

        for ($i = 0; $i < count($methodsList); $i++) {
            $key = $methodsList[$i]->name;
            $commandMethods[$key] = $methodsList[$i];
            $commandMethods[$key]->setAccessible(true);
        }

        $arguments = [
            'command' => 'docker:manage',
            'interactive' => true,
            'tty'  => true,
            'detached'  => true,
            'phpversion' => '7.2',
            'threadsafe' => 'nts',
            'port' => [
                '7474:80',
                '3306:3306',
            ],
            'mount' => null,
            'volume' => [
                '${PWD}/:/srv/www',
                '${PWD}/:/srv/test',
            ],
            'script' => 'lfphp',
            'execute' => 'run',
        ];

        $arrayInputFake = new InputMock();
        $arrayInputFake->setArguments($arguments);

        $output = $commandMethods['formatInput']->invokeArgs(
            $dockerManageCommandFake,
            [$arrayInputFake]
        );

        $this->assertSame(
            'docker run --restart=always -d -i -t -p 7474:80 -p 3306:3306 '
            . '-v ${PWD}/:/srv/www -v ${PWD}/:/srv/test asclinux/linuxforphp-8.2-ultimate:7.2-nts /bin/bash -c "lfphp"',
            $output
        );

        $dockerManageCommandFake = new DockerManageCommand();
        $commandReflection = new \ReflectionClass($dockerManageCommandFake);

        $methodsList = $commandReflection->getMethods();

        for ($i = 0; $i < count($methodsList); $i++) {
            $key = $methodsList[$i]->name;
            $commandMethods[$key] = $methodsList[$i];
            $commandMethods[$key]->setAccessible(true);
        }

        $arguments = [
            'command' => 'docker:manage',
            'interactive' => true,
            'tty'  => true,
            'detached'  => true,
            'phpversion' => 'custom-7.2',
            'threadsafe' => 'nts',
            'port' => [
                '7474:80',
                '3306:3306',
            ],
            'mount' => null,
            'volume' => [
                '${PWD}/:/srv/www',
                '${PWD}/:/srv/test',
            ],
            'script' => 'lfphp',
            'execute' => 'run',
        ];

        $arrayInputFake = new InputMock();
        $arrayInputFake->setArguments($arguments);

        $output = $commandMethods['formatInput']->invokeArgs(
            $dockerManageCommandFake,
            [$arrayInputFake]
        );

        $this->assertSame(
            'docker run --restart=always -d -i -t -p 7474:80 -p 3306:3306 '
            . '-v ${PWD}/:/srv/www -v ${PWD}/:/srv/test '
            . 'asclinux/linuxforphp-8.2-ultimate:custom-7.2-nts '
            . '/bin/bash -c "lfphp"',
            $output
        );

        $dockerManageCommandFake = new DockerManageCommand();
        $commandReflection = new \ReflectionClass($dockerManageCommandFake);

        $methodsList = $commandReflection->getMethods();

        for ($i = 0; $i < count($methodsList); $i++) {
            $key = $methodsList[$i]->name;
            $commandMethods[$key] = $methodsList[$i];
            $commandMethods[$key]->setAccessible(true);
        }

        $propertiesList = $commandReflection->getProperties();

        for ($i = 0; $i < count($propertiesList); $i++) {
            $key = $propertiesList[$i]->name;
            $commandProperties[$key] = $propertiesList[$i];
            $commandProperties[$key]->setAccessible(true);
        }

        $arguments = [
            'command' => 'docker:manage',
            'interactive' => true,
            'tty'  => true,
            'detached'  => true,
            'phpversion' => '7.2',
            'threadsafe' => 'nts',
            'port' => [
                '7474:80',
                '3306:3306',
            ],
            'mount' => null,
            'volume' => [
                '${PWD}/:/srv/www',
                '${PWD}/:/srv/test',
            ],
            'script' => "echo '<?php phpinfo();' > /srv/www/index.php,,,lfphp",
            'execute' => 'run',
        ];

        $arrayInputFake = new InputMock();
        $arrayInputFake->setArguments($arguments);

        $output = $commandMethods['formatInput']->invokeArgs(
            $dockerManageCommandFake,
            [$arrayInputFake]
        );

        $this->assertSame(
            'docker run --restart=always -d -i -t -p 7474:80 -p 3306:3306 '
            . '-v ${PWD}/:/srv/www -v ${PWD}/:/srv/test '
            . '-v ' . $commandProperties['tempScriptFile']->getValue($dockerManageCommandFake)
            . ':/tmp/script.bash --entrypoint /tmp/script.bash '
            . 'asclinux/linuxforphp-8.2-ultimate:7.2-nts',
            $output
        );

        $dockerManageCommandFake = new DockerManageCommand();
        $commandReflection = new \ReflectionClass($dockerManageCommandFake);

        $methodsList = $commandReflection->getMethods();

        for ($i = 0; $i < count($methodsList); $i++) {
            $key = $methodsList[$i]->name;
            $commandMethods[$key] = $methodsList[$i];
            $commandMethods[$key]->setAccessible(true);
        }

        $propertiesList = $commandReflection->getProperties();

        for ($i = 0; $i < count($propertiesList); $i++) {
            $key = $propertiesList[$i]->name;
            $commandProperties[$key] = $propertiesList[$i];
            $commandProperties[$key]->setAccessible(true);
        }

        $arguments = [
            'command' => 'docker:manage',
            'interactive' => true,
            'tty'  => true,
            'detached'  => true,
            'phpversion' => 'custom-7.2',
            'threadsafe' => 'nts',
            'port' => [
                '7474:80',
                '3306:3306',
            ],
            'mount' => null,
            'volume' => [
                '${PWD}/:/srv/www',
                '${PWD}/:/srv/test',
            ],
            'script' => "echo '<?php phpinfo();' > /srv/www/index.php,,,lfphp",
            'execute' => 'run',
        ];

        $arrayInputFake = new InputMock();
        $arrayInputFake->setArguments($arguments);

        $output = $commandMethods['formatInput']->invokeArgs(
            $dockerManageCommandFake,
            [$arrayInputFake]
        );

        $this->assertSame(
            'docker run --restart=always -d -i -t -p 7474:80 -p 3306:3306 '
            . '-v ${PWD}/:/srv/www -v ${PWD}/:/srv/test '
            . '-v ' . $commandProperties['tempScriptFile']->getValue($dockerManageCommandFake)
            . ':/tmp/script.bash --entrypoint /tmp/script.bash '
            . 'asclinux/linuxforphp-8.2-ultimate:custom-7.2-nts',
            $output
        );

        $dockerManageCommandFake = new DockerManageCommand();
        $commandReflection = new \ReflectionClass($dockerManageCommandFake);

        $methodsList = $commandReflection->getMethods();

        for ($i = 0; $i < count($methodsList); $i++) {
            $key = $methodsList[$i]->name;
            $commandMethods[$key] = $methodsList[$i];
            $commandMethods[$key]->setAccessible(true);
        }

        $arguments = [
            'command' => 'docker:manage',
            'interactive' => true,
            'tty'  => true,
            'detached'  => true,
            'phpversion' => '7.2',
            'threadsafe' => 'nts',
            'port' => [
                '7474:80',
                '3306:3306',
            ],
            'mount' => ['source=unittest_srv_mysql,target=/srv/mysql,,,unittest_srv_mysql,,,,'],
            'volume' => [
                '${PWD}/:/srv/www',
                '${PWD}/:/srv/test',
            ],
            'script' => 'lfphp',
            'execute' => 'run',
        ];

        $arrayInputFake = new InputMock();
        $arrayInputFake->setArguments($arguments);

        $output = $commandMethods['formatInput']->invokeArgs(
            $dockerManageCommandFake,
            [$arrayInputFake]
        );

        $this->assertSame(
            'docker run --restart=always -d -i -t -p 7474:80 -p 3306:3306 '
            . '--mount source=unittest_srv_mysql,target=/srv/mysql '
            . '-v ${PWD}/:/srv/www -v ${PWD}/:/srv/test asclinux/linuxforphp-8.2-ultimate:7.2-nts /bin/bash -c "lfphp"',
            $output
        );

        $dockerManageCommandFake = new DockerManageCommand();
        $commandReflection = new \ReflectionClass($dockerManageCommandFake);

        $methodsList = $commandReflection->getMethods();

        for ($i = 0; $i < count($methodsList); $i++) {
            $key = $methodsList[$i]->name;
            $commandMethods[$key] = $methodsList[$i];
            $commandMethods[$key]->setAccessible(true);
        }

        $arguments = [
            'command' => 'docker:manage',
            'interactive' => true,
            'tty'  => true,
            'detached'  => true,
            'phpversion' => '7.2',
            'threadsafe' => 'nts',
            'port' => [
                '7474:80',
                '3306:3306',
            ],
            'mount' => ['source=unittest_srv_mysql,target=/srv/mysql,,,unittest_srv_mysql,,,,source=unittest_home,target=/home,,,unittest_home,,,,'],
            'volume' => [
                '${PWD}/:/srv/www',
                '${PWD}/:/srv/test',
            ],
            'script' => 'lfphp',
            'execute' => 'run',
        ];

        $arrayInputFake = new InputMock();
        $arrayInputFake->setArguments($arguments);

        $output = $commandMethods['formatInput']->invokeArgs(
            $dockerManageCommandFake,
            [$arrayInputFake]
        );

        $this->assertSame(
            'docker run --restart=always -d -i -t -p 7474:80 -p 3306:3306 '
            . '--mount source=unittest_srv_mysql,target=/srv/mysql '
            . '--mount source=unittest_home,target=/home '
            . '-v ${PWD}/:/srv/www -v ${PWD}/:/srv/test asclinux/linuxforphp-8.2-ultimate:7.2-nts /bin/bash -c "lfphp"',
            $output
        );

        $dockerManageCommandFake = new DockerManageCommand();
        $commandReflection = new \ReflectionClass($dockerManageCommandFake);

        $methodsList = $commandReflection->getMethods();

        for ($i = 0; $i < count($methodsList); $i++) {
            $key = $methodsList[$i]->name;
            $commandMethods[$key] = $methodsList[$i];
            $commandMethods[$key]->setAccessible(true);
        }

        $arguments = [
            'command' => 'docker:manage',
            'interactive' => true,
            'tty'  => true,
            'detached'  => true,
            'phpversion' => '7.2',
            'threadsafe' => 'nts',
            'port' => [
                '7474:80',
                '3306:3306',
            ],
            'mount' => [':unittest_srv,,,,'],
            'volume' => [
                '${PWD}/:/srv/www',
                '${PWD}/:/srv/test',
            ],
            'script' => 'lfphp',
            'execute' => 'run',
        ];

        $arrayInputFake = new InputMock();
        $arrayInputFake->setArguments($arguments);

        $output = $commandMethods['formatInput']->invokeArgs(
            $dockerManageCommandFake,
            [$arrayInputFake]
        );

        $this->assertSame(
            'docker run --restart=always -d -i -t -p 7474:80 -p 3306:3306 '
            . '-v ${PWD}/:/srv/www -v ${PWD}/:/srv/test asclinux/linuxforphp-8.2-ultimate:7.2-nts /bin/bash -c "lfphp"',
            $output
        );

        $dockerManageCommandFake = new DockerManageCommand();
        $commandReflection = new \ReflectionClass($dockerManageCommandFake);

        $methodsList = $commandReflection->getMethods();

        for ($i = 0; $i < count($methodsList); $i++) {
            $key = $methodsList[$i]->name;
            $commandMethods[$key] = $methodsList[$i];
            $commandMethods[$key]->setAccessible(true);
        }

        $arguments = [
            'command' => 'docker:manage',
            'interactive' => true,
            'tty'  => true,
            'detached'  => true,
            'phpversion' => '7.2',
            'threadsafe' => 'nts',
            'port' => [
                '7474:80',
                '3306:3306',
            ],
            'mount' => [':unittest_srv,,,,'],
            'volume' => [
                '${PWD}/:/srv/www',
                '${PWD}/:/srv/test',
            ],
            'script' => 'lfphp',
            'execute' => 'stop',
        ];

        $arrayInputFake = new InputMock();
        $arrayInputFake->setArguments($arguments);

        $output = $commandMethods['formatInput']->invokeArgs(
            $dockerManageCommandFake,
            [$arrayInputFake]
        );

        $this->assertSame(
            'docker run --restart=always -d -i -t -p 7474:80 -p 3306:3306 '
            . '-v ${PWD}/:/srv/www -v ${PWD}/:/srv/test asclinux/linuxforphp-8.2-ultimate:7.2-nts /bin/bash -c "lfphp"',
            $output
        );

        $dockerManageCommandFake = new DockerManageCommand();
        $commandReflection = new \ReflectionClass($dockerManageCommandFake);

        $methodsList = $commandReflection->getMethods();

        for ($i = 0; $i < count($methodsList); $i++) {
            $key = $methodsList[$i]->name;
            $commandMethods[$key] = $methodsList[$i];
            $commandMethods[$key]->setAccessible(true);
        }

        $arguments = [
            'command' => 'docker:manage',
            'interactive' => true,
            'tty'  => true,
            'detached'  => true,
            'phpversion' => '7.2',
            'threadsafe' => 'nts',
            'port' => [
                '7474:80',
                '3306:3306',
            ],
            'mount' => [':unittest_srv_mysql,,,,:unittest_home,,,,'],
            'volume' => [
                '${PWD}/:/srv/www',
                '${PWD}/:/srv/test',
            ],
            'script' => 'lfphp',
            'execute' => 'run',
        ];

        $arrayInputFake = new InputMock();
        $arrayInputFake->setArguments($arguments);

        $output = $commandMethods['formatInput']->invokeArgs(
            $dockerManageCommandFake,
            [$arrayInputFake]
        );

        $this->assertSame(
            'docker run --restart=always -d -i -t -p 7474:80 -p 3306:3306 '
            . '-v ${PWD}/:/srv/www -v ${PWD}/:/srv/test asclinux/linuxforphp-8.2-ultimate:7.2-nts /bin/bash -c "lfphp"',
            $output
        );

        ob_end_clean();
    }

    public function testFormatInputWhenCreatingNewPHPVersion()
    {
        // Redirect output to command output
        //$this->setOutputCallback(function () {
        //});

        ob_start();

        $this->createMocksForUnixEnv();

        $this->dockerLfcProcessMock
            ->shouldReceive('isRunning')
            ->andReturn(false);
        $this->dockerLfcProcessMock
            ->shouldReceive('isSuccessful')
            ->andReturn(true);
        $this->dockerLfcProcessMock
            ->shouldReceive('getOutput')
            ->andReturn('');
        $this->dockerLfcProcessMock
            ->shouldReceive('getErrorOutput')
            ->andReturn('');
        $this->dockerLfcProcessMock
            ->shouldReceive('getExitCode')
            ->andReturn(1);

        $dockerManageCommandFake = new DockerManageCommand();
        $commandReflection = new \ReflectionClass($dockerManageCommandFake);

        $methodsList = $commandReflection->getMethods();

        for ($i = 0; $i < count($methodsList); $i++) {
            $key = $methodsList[$i]->name;
            $commandMethods[$key] = $methodsList[$i];
            $commandMethods[$key]->setAccessible(true);
        }

        $propertiesList = $commandReflection->getProperties();

        for ($i = 0; $i < count($propertiesList); $i++) {
            $key = $propertiesList[$i]->name;
            $commandProperties[$key] = $propertiesList[$i];
            $commandProperties[$key]->setAccessible(true);
        }

        $arguments = [
            'command' => 'docker:manage',
            'interactive' => true,
            'tty'  => true,
            'detached'  => true,
            'phpversion' => '8.0',
            'threadsafe' => 'nts',
            'port' => [
                '7474:80',
                '3306:3306',
            ],
            'mount' => null,
            'volume' => [
                '${PWD}/:/srv/www',
                '${PWD}/:/srv/test',
            ],
            'script' => "lfphp",
            'execute' => 'run',
        ];

        $arrayInputFake = new InputMock();
        $arrayInputFake->setArguments($arguments);

        $output = $commandMethods['formatInput']->invokeArgs(
            $dockerManageCommandFake,
            [$arrayInputFake]
        );

        $this->assertSame(
            'docker run --restart=always -d -i -t -p 7474:80 -p 3306:3306 '
            . '-v ${PWD}/:/srv/www -v ${PWD}/:/srv/test '
            . 'asclinux/linuxforphp-8.2-ultimate:src '
            . '/bin/bash -c "lfphp-compile 8.0 nts ; lfphp"',
            $output
        );

        $dockerManageCommandFake = new DockerManageCommand();
        $commandReflection = new \ReflectionClass($dockerManageCommandFake);

        $methodsList = $commandReflection->getMethods();

        for ($i = 0; $i < count($methodsList); $i++) {
            $key = $methodsList[$i]->name;
            $commandMethods[$key] = $methodsList[$i];
            $commandMethods[$key]->setAccessible(true);
        }

        $propertiesList = $commandReflection->getProperties();

        for ($i = 0; $i < count($propertiesList); $i++) {
            $key = $propertiesList[$i]->name;
            $commandProperties[$key] = $propertiesList[$i];
            $commandProperties[$key]->setAccessible(true);
        }

        $arguments = [
            'command' => 'docker:manage',
            'interactive' => true,
            'tty'  => true,
            'detached'  => true,
            'phpversion' => '8.0',
            'threadsafe' => 'nts',
            'port' => [
                '7474:80',
                '3306:3306',
            ],
            'mount' => null,
            'volume' => [
                '${PWD}/:/srv/www',
                '${PWD}/:/srv/test',
            ],
            'script' => "echo '<?php phpinfo();' > /srv/www/index.php,,,lfphp",
            'execute' => 'run',
        ];

        $arrayInputFake = new InputMock();
        $arrayInputFake->setArguments($arguments);

        $output = $commandMethods['formatInput']->invokeArgs(
            $dockerManageCommandFake,
            [$arrayInputFake]
        );

        $this->assertSame(
            'docker run --restart=always -d -i -t -p 7474:80 -p 3306:3306 '
            . '-v ${PWD}/:/srv/www -v ${PWD}/:/srv/test '
            . '-v ' . $commandProperties['tempScriptFile']->getValue($dockerManageCommandFake)
            . ':/tmp/script.bash --entrypoint /tmp/script.bash '
            . 'asclinux/linuxforphp-8.2-ultimate:src',
            $output
        );

        ob_end_clean();
    }

    public function testGetMountOptionsWithExistingPHPVersion()
    {
        // Redirect output to command output
        //$this->setOutputCallback(function () {
        //});

        ob_start();

        $this->createMocksForUnixEnv();

        $this->dockerLfcProcessMock
            ->shouldReceive('isSuccessful')
            ->andReturn(true);
        $this->dockerLfcProcessMock
            ->shouldReceive('getOutput')
            ->once()
            ->andReturn('We downloaded the image!');
        $this->dockerLfcProcessMock
            ->shouldReceive('getErrorOutput')
            ->once()
            ->andReturn('One download failed');
        $this->dockerLfcProcessMock
            ->shouldReceive('getExitCode')
            ->once()
            ->andReturn(0);

        $dockerManageCommandFake = new DockerManageCommand();
        $commandReflection = new \ReflectionClass($dockerManageCommandFake);

        $methodsList = $commandReflection->getMethods();

        for ($i = 0; $i < count($methodsList); $i++) {
            $key = $methodsList[$i]->name;
            $commandMethods[$key] = $methodsList[$i];
            $commandMethods[$key]->setAccessible(true);
        }

        $arguments = [
            'command' => 'docker:manage',
            'interactive' => true,
            'tty' => true,
            'detached' => true,
            'phpversion' => '7.2',
            'threadsafe' => 'nts',
            'port' => '7474:80',
            'mount' => ['source=unittest_srv_mysql,target=/srv/mysql,,,unittest_srv_mysql,,,,'],
            'volume' => '${PWD}/:/srv/www',
            'script' => 'lfphp',
            'execute' => 'run',
        ];

        $arrayInputFake = new InputMock();
        $arrayInputFake->setArguments($arguments);

        $output = $commandMethods['getMountOptions']->invokeArgs(
            $dockerManageCommandFake,
            [$arrayInputFake->getOption('mount')]
        );

        $this->assertSame(
            '--mount source=unittest_srv_mysql,target=/srv/mysql ',
            $output
        );

        $dockerManageCommandFake = new DockerManageCommand();
        $commandReflection = new \ReflectionClass($dockerManageCommandFake);

        $methodsList = $commandReflection->getMethods();

        for ($i = 0; $i < count($methodsList); $i++) {
            $key = $methodsList[$i]->name;
            $commandMethods[$key] = $methodsList[$i];
            $commandMethods[$key]->setAccessible(true);
        }

        $arguments = [
            'command' => 'docker:manage',
            'interactive' => true,
            'tty' => true,
            'detached' => true,
            'phpversion' => '7.2',
            'threadsafe' => 'nts',
            'port' => '7474:80',
            'mount' => ['source=unittest_srv_mysql,target=/srv/mysql,,,unittest_srv_mysql,,,,source=unittest_home,target=/home,,,unittest_home,,,,'],
            'volume' => '${PWD}/:/srv/www',
            'script' => 'lfphp',
            'execute' => 'run',
        ];

        $arrayInputFake = new InputMock();
        $arrayInputFake->setArguments($arguments);

        $output = $commandMethods['getMountOptions']->invokeArgs(
            $dockerManageCommandFake,
            [$arrayInputFake->getOption('mount')]
        );

        $this->assertSame(
            '--mount source=unittest_srv_mysql,target=/srv/mysql '
            . '--mount source=unittest_home,target=/home ',
            $output
        );

        $dockerManageCommandFake = new DockerManageCommand();
        $commandReflection = new \ReflectionClass($dockerManageCommandFake);

        $methodsList = $commandReflection->getMethods();

        for ($i = 0; $i < count($methodsList); $i++) {
            $key = $methodsList[$i]->name;
            $commandMethods[$key] = $methodsList[$i];
            $commandMethods[$key]->setAccessible(true);
        }

        $arguments = [
            'command' => 'docker:manage',
            'interactive' => true,
            'tty' => true,
            'detached' => true,
            'phpversion' => '7.2',
            'threadsafe' => 'nts',
            'port' => '7474:80',
            'mount' => [':unittest_srv_mysql,,,,'],
            'volume' => '${PWD}/:/srv/www',
            'script' => 'lfphp',
            'execute' => 'run',
        ];

        $arrayInputFake = new InputMock();
        $arrayInputFake->setArguments($arguments);

        $output = $commandMethods['getMountOptions']->invokeArgs(
            $dockerManageCommandFake,
            [$arrayInputFake->getOption('mount')]
        );

        $this->assertEmpty($output);

        $output = $commandMethods['getMountNames']->invokeArgs(
            $dockerManageCommandFake,
            [$arrayInputFake->getOption('mount')]
        );

        $this->assertSame(
            ['unittest_srv_mysql' => 'unittest_srv_mysql'],
            $output
        );

        $dockerManageCommandFake = new DockerManageCommand();
        $commandReflection = new \ReflectionClass($dockerManageCommandFake);

        $methodsList = $commandReflection->getMethods();

        for ($i = 0; $i < count($methodsList); $i++) {
            $key = $methodsList[$i]->name;
            $commandMethods[$key] = $methodsList[$i];
            $commandMethods[$key]->setAccessible(true);
        }

        $arguments = [
            'command' => 'docker:manage',
            'interactive' => true,
            'tty' => true,
            'detached' => true,
            'phpversion' => '7.2',
            'threadsafe' => 'nts',
            'port' => '7474:80',
            'mount' => [':unittest_srv_mysql,,,,:unittest_home,,,,'],
            'volume' => '${PWD}/:/srv/www',
            'script' => 'lfphp',
            'execute' => 'run',
        ];

        $arrayInputFake = new InputMock();
        $arrayInputFake->setArguments($arguments);

        $output = $commandMethods['getMountOptions']->invokeArgs(
            $dockerManageCommandFake,
            [$arrayInputFake->getOption('mount')]
        );

        $this->assertEmpty($output);

        $output = $commandMethods['getMountNames']->invokeArgs(
            $dockerManageCommandFake,
            [$arrayInputFake->getOption('mount')]
        );

        $this->assertSame(
            [
                'unittest_srv_mysql' => 'unittest_srv_mysql',
                'unittest_home' => 'unittest_home'
            ],
            $output
        );

        ob_end_clean();
    }

    public function testExecuteWithBuildCommandWithDockerfile()
    {
        // Redirect output to command output
        //$this->setOutputCallback(function () {
        //});

        ob_start();

        $this->createMocksForUnixEnv();

        $this->dockerLfcProcessMock
            ->shouldReceive('isRunning')
            ->andReturn(false);
        $this->dockerLfcProcessMock
            ->shouldReceive('isSuccessful')
            ->andReturn(true);
        $this->dockerLfcProcessMock
            ->shouldReceive('getOutput')
            ->andReturn('');
        $this->dockerLfcProcessMock
            ->shouldReceive('getErrorOutput')
            ->andReturn('');
        $this->dockerLfcProcessMock
            ->shouldReceive('getExitCode')
            ->andReturn(0);
        $this->dockerLfcProcessMock
            ->shouldReceive('setTempFilename')
            ->withAnyArgs();
        $this->dockerLfcProcessMock
            ->shouldReceive('setDockerCommand')
            ->withAnyArgs();


        $kernel = self::bootKernel();

        $application = new Application($kernel);
        $application->add(new DockerManageCommand());

        $command = $application->find('docker:manage');
        $commandTester = new CommandTester($command);

        $fakeDockerfile = dirname(__DIR__)
            . DIRECTORY_SEPARATOR
            . 'app'
            . DIRECTORY_SEPARATOR
            . 'Dockerfile.fake';

        $arguments = [
            'command' => $command->getName(),
            '--interactive' => true,
            '--tty' => true,
            '--detached' => true,
            '--phpversion' => '7.2',
            '--threadsafe' => 'nts',
            '--port' => '7474:80',
            '--mount' => ['source=unittest_srv_mysql,target=/srv/mysql,,,unittest_srv_mysql,,,,'],
            '--volume' => '${PWD}/:/srv/www',
            '--script' => "dockerfile,,,$fakeDockerfile,,,dockerfiletest",
            'execute' => 'build',
        ];
        $commandTester->execute($arguments);

        $this->assertSame(
            PHP_EOL
            . 'Building all containers...'
            . PHP_EOL
            . PHP_EOL
            . 'Building mount point...'
            . PHP_EOL
            . PHP_EOL
            . 'Starting container...'
            . PHP_EOL,
            $this->getActualOutput()
        );

        ob_end_clean();
    }

    public function testExecuteWithBuildCommandWithDockerfileWithAuth()
    {
        // Redirect output to command output
        //$this->setOutputCallback(function () {
        //});

        ob_start();

        $this->createMocksForUnixEnv();

        $this->dockerLfcProcessMock
            ->shouldReceive('isRunning')
            ->andReturn(false);
        $this->dockerLfcProcessMock
            ->shouldReceive('isSuccessful')
            ->andReturn(true);
        $this->dockerLfcProcessMock
            ->shouldReceive('getOutput')
            ->andReturn('');
        $this->dockerLfcProcessMock
            ->shouldReceive('getErrorOutput')
            ->andReturn('');
        $this->dockerLfcProcessMock
            ->shouldReceive('getExitCode')
            ->andReturn(0);
        $this->dockerLfcProcessMock
            ->shouldReceive('setTempFilename')
            ->withAnyArgs();
        $this->dockerLfcProcessMock
            ->shouldReceive('setDockerCommand')
            ->withAnyArgs();


        $kernel = self::bootKernel();

        $application = new Application($kernel);
        $application->add(new DockerManageCommand());

        $command = $application->find('docker:manage');
        $commandTester = new CommandTester($command);

        $fakeDockerfile = dirname(__DIR__)
            . DIRECTORY_SEPARATOR
            . 'app'
            . DIRECTORY_SEPARATOR
            . 'Dockerfile.fake';

        $arguments = [
            'command' => $command->getName(),
            '--interactive' => true,
            '--tty' => true,
            '--detached' => true,
            '--phpversion' => '7.2',
            '--threadsafe' => 'nts',
            '--port' => '7474:80',
            '--mount' => ['source=unittest_srv_mysql,target=/srv/mysql,,,unittest_srv_mysql,,,,'],
            '--volume' => '${PWD}/:/srv/www',
            '--script' => "dockerfile,,,$fakeDockerfile,,,user1:secret,,,dockerfiletest",
            'execute' => 'build',
        ];
        $commandTester->execute($arguments);

        $this->assertSame(
            PHP_EOL
            . 'Building all containers...'
            . PHP_EOL
            . PHP_EOL
            . 'Building mount point...'
            . PHP_EOL
            . PHP_EOL
            . 'Starting container...'
            . PHP_EOL,
            $this->getActualOutput()
        );

        ob_end_clean();
    }

    public function testExecuteWithBuildCommandWithDockerfileWithWrongUrl()
    {
        // Redirect output to command output
        //$this->setOutputCallback(function () {
        //});

        ob_start();

        $this->createMocksForUnixEnv();

        $this->dockerLfcProcessMock
            ->shouldReceive('isRunning')
            ->andReturn(false);
        $this->dockerLfcProcessMock
            ->shouldReceive('isSuccessful')
            ->andReturn(true);
        $this->dockerLfcProcessMock
            ->shouldReceive('getOutput')
            ->andReturn('');
        $this->dockerLfcProcessMock
            ->shouldReceive('getErrorOutput')
            ->andReturn('');
        $this->dockerLfcProcessMock
            ->shouldReceive('getExitCode')
            ->andReturn(0);
        $this->dockerLfcProcessMock
            ->shouldReceive('setTempFilename')
            ->withAnyArgs();
        $this->dockerLfcProcessMock
            ->shouldReceive('setDockerCommand')
            ->withAnyArgs();


        $kernel = self::bootKernel();

        $application = new Application($kernel);
        $application->add(new DockerManageCommand());

        $command = $application->find('docker:manage');
        $commandTester = new CommandTester($command);

        $fakeDockerfile = dirname(__DIR__)
            . DIRECTORY_SEPARATOR
            . 'app'
            . DIRECTORY_SEPARATOR
            . 'Dockerfile.fake';

        $arguments = [
            'command' => $command->getName(),
            '--interactive' => true,
            '--tty' => true,
            '--detached' => true,
            '--phpversion' => '7.2',
            '--threadsafe' => 'nts',
            '--port' => '7474:80',
            '--mount' => ['source=unittest_srv_mysql,target=/srv/mysql,,,unittest_srv_mysql,,,,'],
            '--volume' => '${PWD}/:/srv/www',
            '--script' => "dockerfile,,,$fakeDockerfile.wrong,,,dockerfiletest",
            'execute' => 'build',
        ];
        $commandTester->execute($arguments);

        $this->assertSame(
            PHP_EOL
            . 'URL is invalid!'
            . PHP_EOL
            . PHP_EOL
            . 'Please make sure that the URL is allowed and valid,'
            . PHP_EOL
            . 'and that cURL and Git are available on your system.'
            . PHP_EOL
            . PHP_EOL,
            $this->getActualOutput()
        );

        ob_end_clean();
    }

    public function testExecuteWithBuildCommandWithDockerfileWithWrongScheme()
    {
        // Redirect output to command output
        //$this->setOutputCallback(function () {
        //});

        ob_start();

        $this->createMocksForUnixEnv();

        $this->dockerLfcProcessMock
            ->shouldReceive('isRunning')
            ->andReturn(false);
        $this->dockerLfcProcessMock
            ->shouldReceive('isSuccessful')
            ->andReturn(true);
        $this->dockerLfcProcessMock
            ->shouldReceive('getOutput')
            ->andReturn('');
        $this->dockerLfcProcessMock
            ->shouldReceive('getErrorOutput')
            ->andReturn('');
        $this->dockerLfcProcessMock
            ->shouldReceive('getExitCode')
            ->andReturn(0);
        $this->dockerLfcProcessMock
            ->shouldReceive('setTempFilename')
            ->withAnyArgs();
        $this->dockerLfcProcessMock
            ->shouldReceive('setDockerCommand')
            ->withAnyArgs();


        $kernel = self::bootKernel();

        $application = new Application($kernel);
        $application->add(new DockerManageCommand());

        $command = $application->find('docker:manage');
        $commandTester = new CommandTester($command);

        $arguments = [
            'command' => $command->getName(),
            '--interactive' => true,
            '--tty' => true,
            '--detached' => true,
            '--phpversion' => '7.2',
            '--threadsafe' => 'nts',
            '--port' => '7474:80',
            '--mount' => ['source=unittest_srv_mysql,target=/srv/mysql,,,unittest_srv_mysql,,,,'],
            '--volume' => '${PWD}/:/srv/www',
            '--script' => "dockerfile,,,ftp://example.com/Dockerfile,,,dockerfiletest",
            'execute' => 'build',
        ];
        $commandTester->execute($arguments);

        $this->assertSame(
            PHP_EOL
            . 'URL is invalid!'
            . PHP_EOL
            . PHP_EOL
            . 'Please make sure that the URL is allowed and valid,'
            . PHP_EOL
            . 'and that cURL and Git are available on your system.'
            . PHP_EOL
            . PHP_EOL,
            $this->getActualOutput()
        );

        ob_end_clean();
    }

    public function testExecuteWithBuildCommandWithDockerCompose()
    {
        // Redirect output to command output
        //$this->setOutputCallback(function () {
        //});

        ob_start();

        $this->createMocksForUnixEnv();

        $this->dockerLfcProcessMock
            ->shouldReceive('isRunning')
            ->andReturn(false);
        $this->dockerLfcProcessMock
            ->shouldReceive('isSuccessful')
            ->andReturn(true);
        $this->dockerLfcProcessMock
            ->shouldReceive('getOutput')
            ->andReturn('');
        $this->dockerLfcProcessMock
            ->shouldReceive('getErrorOutput')
            ->andReturn('');
        $this->dockerLfcProcessMock
            ->shouldReceive('getExitCode')
            ->andReturn(0);
        $this->dockerLfcProcessMock
            ->shouldReceive('setTempFilename')
            ->withAnyArgs();
        $this->dockerLfcProcessMock
            ->shouldReceive('setDockerCommand')
            ->withAnyArgs();

        if (!file_exists(getcwd() . DIRECTORY_SEPARATOR . 'fakerepo')) {
            mkdir(getcwd() . DIRECTORY_SEPARATOR . 'fakerepo');
        }


        $kernel = self::bootKernel();

        $application = new Application($kernel);
        $application->add(new DockerManageCommand());

        $command = $application->find('docker:manage');
        $commandTester = new CommandTester($command);

        $arguments = [
            'command' => $command->getName(),
            '--interactive' => true,
            '--tty' => true,
            '--detached' => true,
            '--phpversion' => '7.2',
            '--threadsafe' => 'nts',
            '--port' => '7474:80',
            '--mount' => ['source=unittest_srv_mysql,target=/srv/mysql,,,unittest_srv_mysql,,,,'],
            '--volume' => '${PWD}/:/srv/www',
            '--script' => "docker-compose,,,https://example.com/fakerepo",
            'execute' => 'build',
        ];
        $commandTester->execute($arguments);

        $this->assertSame(
            PHP_EOL
            . 'Building all containers...'
            . PHP_EOL,
            $this->getActualOutput()
        );

        if (file_exists(getcwd() . DIRECTORY_SEPARATOR . 'fakerepo')) {
            rmdir(getcwd() . DIRECTORY_SEPARATOR . 'fakerepo');
        }

        ob_end_clean();
    }

    public function testExecuteWithBuildCommandWithDockerComposeWithAuth()
    {
        // Redirect output to command output
        //$this->setOutputCallback(function () {
        //});

        ob_start();

        $this->createMocksForUnixEnv();

        $this->dockerLfcProcessMock
            ->shouldReceive('isRunning')
            ->andReturn(false);
        $this->dockerLfcProcessMock
            ->shouldReceive('isSuccessful')
            ->andReturn(true);
        $this->dockerLfcProcessMock
            ->shouldReceive('getOutput')
            ->andReturn('');
        $this->dockerLfcProcessMock
            ->shouldReceive('getErrorOutput')
            ->andReturn('');
        $this->dockerLfcProcessMock
            ->shouldReceive('getExitCode')
            ->andReturn(0);
        $this->dockerLfcProcessMock
            ->shouldReceive('setTempFilename')
            ->withAnyArgs();
        $this->dockerLfcProcessMock
            ->shouldReceive('setDockerCommand')
            ->withAnyArgs();

        if (!file_exists(getcwd() . DIRECTORY_SEPARATOR . 'fakerepo')) {
            mkdir(getcwd() . DIRECTORY_SEPARATOR . 'fakerepo');
        }


        $kernel = self::bootKernel();

        $application = new Application($kernel);
        $application->add(new DockerManageCommand());

        $command = $application->find('docker:manage');
        $commandTester = new CommandTester($command);

        $arguments = [
            'command' => $command->getName(),
            '--interactive' => true,
            '--tty' => true,
            '--detached' => true,
            '--phpversion' => '7.2',
            '--threadsafe' => 'nts',
            '--port' => '7474:80',
            '--mount' => ['source=unittest_srv_mysql,target=/srv/mysql,,,unittest_srv_mysql,,,,'],
            '--volume' => '${PWD}/:/srv/www',
            '--script' => "docker-compose,,,https://example.com/fakerepo,,,user1:secret",
            'execute' => 'build',
        ];
        $commandTester->execute($arguments);

        $this->assertSame(
            PHP_EOL
            . 'Building all containers...'
            . PHP_EOL,
            $this->getActualOutput()
        );

        if (file_exists(getcwd() . DIRECTORY_SEPARATOR . 'fakerepo')) {
            rmdir(getcwd() . DIRECTORY_SEPARATOR . 'fakerepo');
        }

        ob_end_clean();
    }

    public function testExecuteWithBuildCommandWithDockerComposeWithWrongUrl()
    {
        // Redirect output to command output
        //$this->setOutputCallback(function () {
        //});

        ob_start();

        $this->createMocksForUnixEnv();

        $this->dockerLfcProcessMock
            ->shouldReceive('isRunning')
            ->andReturn(false);
        $this->dockerLfcProcessMock
            ->shouldReceive('isSuccessful')
            ->andReturn(true);
        $this->dockerLfcProcessMock
            ->shouldReceive('getOutput')
            ->andReturn('');
        $this->dockerLfcProcessMock
            ->shouldReceive('getErrorOutput')
            ->andReturn('');
        $this->dockerLfcProcessMock
            ->shouldReceive('getExitCode')
            ->andReturn(0);
        $this->dockerLfcProcessMock
            ->shouldReceive('setTempFilename')
            ->withAnyArgs();
        $this->dockerLfcProcessMock
            ->shouldReceive('setDockerCommand')
            ->withAnyArgs();

        if (file_exists(getcwd() . DIRECTORY_SEPARATOR . 'fakerepo')) {
            rmdir(getcwd() . DIRECTORY_SEPARATOR . 'fakerepo');
        }


        $kernel = self::bootKernel();

        $application = new Application($kernel);
        $application->add(new DockerManageCommand());

        $command = $application->find('docker:manage');
        $commandTester = new CommandTester($command);

        $arguments = [
            'command' => $command->getName(),
            '--interactive' => true,
            '--tty' => true,
            '--detached' => true,
            '--phpversion' => '7.2',
            '--threadsafe' => 'nts',
            '--port' => '7474:80',
            '--mount' => ['source=unittest_srv_mysql,target=/srv/mysql,,,unittest_srv_mysql,,,,'],
            '--volume' => '${PWD}/:/srv/www',
            '--script' => "docker-compose,,,https://example.com/fakerepo,,,user1:secret",
            'execute' => 'build',
        ];
        $commandTester->execute($arguments);

        $this->assertSame(
            PHP_EOL
            . 'URL is invalid!'
            . PHP_EOL
            . PHP_EOL
            . 'Please make sure that the URL is allowed and valid,'
            . PHP_EOL
            . 'and that cURL and Git are available on your system.'
            . PHP_EOL
            . PHP_EOL,
            $this->getActualOutput()
        );

        ob_end_clean();
    }

    public function testExecuteWithRunCommand()
    {
        // Redirect output to command output
        //$this->setOutputCallback(function () {
        //});

        ob_start();

        $this->createMocksForUnixEnv();

        $this->dockerLfcProcessMock
            ->shouldReceive('isRunning')
            ->andReturn(false);
        $this->dockerLfcProcessMock
            ->shouldReceive('isSuccessful')
            ->andReturn(true);
        $this->dockerLfcProcessMock
            ->shouldReceive('getOutput')
            ->andReturn('');
        $this->dockerLfcProcessMock
            ->shouldReceive('getErrorOutput')
            ->andReturn('');
        $this->dockerLfcProcessMock
            ->shouldReceive('getExitCode')
            ->andReturn(0);

        $kernel = self::bootKernel();

        $application = new Application($kernel);
        $application->add(new DockerManageCommand());

        $command = $application->find('docker:manage');
        $commandTester = new CommandTester($command);
        $commandTester->execute([
            'command'  => $command->getName(),
            'execute'  => 'run',
        ]);

        $this->assertSame(
            PHP_EOL
            . 'Checking for image availability and downloading if necessary.'
            . PHP_EOL
            . PHP_EOL
            . 'This may take a few minutes...'
            . PHP_EOL
            . PHP_EOL
            . 'Done!'
            . PHP_EOL
            . PHP_EOL
            . PHP_EOL
            . 'Starting container...'
            . PHP_EOL,
            $this->getActualOutput()
        );

        ob_end_clean();
    }

    public function testExecuteWithRunCommandWithStdoutAndStderrFromCommands()
    {
        // Redirect output to command output
        //$this->setOutputCallback(function () {
        //});

        ob_start();

        $this->createMocksForUnixEnv();

        $this->dockerLfcProcessMock
            ->shouldReceive('isRunning')
            ->andReturn(false);
        $this->dockerLfcProcessMock
            ->shouldReceive('isSuccessful')
            ->andReturn(true);
        $this->dockerLfcProcessMock
            ->shouldReceive('getOutput')
            ->andReturn('Fake containers started...');
        $this->dockerLfcProcessMock
            ->shouldReceive('getErrorOutput')
            ->andReturn('We have received a few errors...');
        $this->dockerLfcProcessMock
            ->shouldReceive('getExitCode')
            ->andReturn(0);

        $kernel = self::bootKernel();

        $application = new Application($kernel);
        $application->add(new DockerManageCommand());

        $command = $application->find('docker:manage');
        $commandTester = new CommandTester($command);
        $commandTester->execute([
            'command'  => $command->getName(),
            'execute'  => 'run',
        ]);

        $this->assertSame(
            PHP_EOL
            . 'Checking for image availability and downloading if necessary.'
            . PHP_EOL
            . PHP_EOL
            . 'This may take a few minutes...'
            . PHP_EOL
            . PHP_EOL
            . 'Fake containers started...'
            . PHP_EOL
            . 'We have received a few errors...'
            . PHP_EOL
            . 'Done!'
            . PHP_EOL
            . PHP_EOL
            . PHP_EOL
            . 'Starting container...'
            . PHP_EOL,
            $this->getActualOutput()
        );

        ob_end_clean();
    }

    public function testExecuteWithRunCommandWithOptions()
    {
        // Redirect output to command output
        //$this->setOutputCallback(function () {
        //});

        ob_start();

        $this->createMocksForUnixEnv();

        $this->dockerLfcProcessMock
            ->shouldReceive('isRunning')
            ->andReturn(false);
        $this->dockerLfcProcessMock
            ->shouldReceive('isSuccessful')
            ->andReturn(true);
        $this->dockerLfcProcessMock
            ->shouldReceive('getOutput')
            ->andReturn('');
        $this->dockerLfcProcessMock
            ->shouldReceive('getErrorOutput')
            ->andReturn('');
        $this->dockerLfcProcessMock
            ->shouldReceive('getExitCode')
            ->andReturn(0);
        $this->dockerLfcProcessMock
            ->shouldReceive('setTempFilename')
            ->withAnyArgs();
        $this->dockerLfcProcessMock
            ->shouldReceive('setDockerCommand')
            ->withAnyArgs();


        $kernel = self::bootKernel();

        $application = new Application($kernel);
        $application->add(new DockerManageCommand());

        $command = $application->find('docker:manage');
        $commandTester = new CommandTester($command);

        $arguments = [
            'command' => $command->getName(),
            '--interactive' => true,
            '--tty' => true,
            '--detached' => true,
            '--phpversion' => '7.2',
            '--threadsafe' => 'nts',
            '--port' => '7474:80',
            '--mount' => ['source=unittest_srv_mysql,target=/srv/mysql,,,unittest_srv_mysql,,,,'],
            '--volume' => '${PWD}/:/srv/www',
            '--script' => "lfphp",
            'execute' => 'run',
        ];
        $commandTester->execute($arguments);

        $this->assertSame(
            PHP_EOL
            . 'Building mount point...'
            . PHP_EOL
            . PHP_EOL
            . 'Checking for image availability and downloading if necessary.'
            . PHP_EOL
            . PHP_EOL
            . 'This may take a few minutes...'
            . PHP_EOL
            . PHP_EOL
            . 'Done!'
            . PHP_EOL
            . PHP_EOL
            . PHP_EOL
            . 'Starting container...'
            . PHP_EOL,
            $this->getActualOutput()
        );

        ob_end_clean();
    }

    public function testExecuteWithStopCommand()
    {
        file_put_contents(
            VENDORFOLDERPID
            . DIRECTORY_SEPARATOR
            . 'composer'
            . DIRECTORY_SEPARATOR
            . 'linuxforcomposer.pid',
            'a1a1' . PHP_EOL
        );

        // Redirect output to command output
        $this->setOutputCallback(function () {
        });

        $this->createMocksForUnixEnv();

        $this->dockerLfcProcessMock
            ->shouldReceive('getOutput')
            ->andReturn('');
        $this->dockerLfcProcessMock
            ->shouldReceive('getErrorOutput')
            ->andReturn('');
        $this->dockerLfcProcessMock
            ->shouldReceive('getExitCode')
            ->andReturn(0);

        $kernel = self::bootKernel();

        $application = new Application($kernel);
        $application->add(new DockerManageCommand());

        $command = $application->find('docker:manage');
        $commandTester = new CommandTester($command);
        $commandTester->setInputs(['n']);
        $commandTester->execute([
            'command'  => $command->getName(),
            'execute'  => 'stop',
        ]);

        $this->assertSame(
            PHP_EOL . 'Stopping container...' . PHP_EOL,
            $this->getActualOutput()
        );
    }

    public function testExecuteWithStopForceCommand()
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
            ->andReturn('');
        $this->dockerLfcProcessMock
            ->shouldReceive('getErrorOutput')
            ->andReturn('');
        $this->dockerLfcProcessMock
            ->shouldReceive('getExitCode')
            ->andReturn(0);

        $kernel = self::bootKernel();

        $application = new Application($kernel);
        $application->add(new DockerManageCommand());

        $command = $application->find('docker:manage');
        $commandTester = new CommandTester($command);
        $commandTester->setInputs(['n']);
        $commandTester->execute([
            'command'  => $command->getName(),
            'execute'  => 'stop-force',
        ]);

        $this->assertSame(
            PHP_EOL
            . 'Stopping container...'
            . PHP_EOL,
            $this->getActualOutput()
        );
    }

    public function testExecuteWithStopForceCommandWithRemoveMount()
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
            ->shouldReceive('isRunning')
            ->andReturn(false);
        $this->dockerLfcProcessMock
            ->shouldReceive('isSuccessful')
            ->andReturn(true);
        $this->dockerLfcProcessMock
            ->shouldReceive('getOutput')
            ->andReturn('');
        $this->dockerLfcProcessMock
            ->shouldReceive('getErrorOutput')
            ->andReturn('');
        $this->dockerLfcProcessMock
            ->shouldReceive('getExitCode')
            ->andReturn(0);
        $this->dockerLfcProcessMock
            ->shouldReceive('setTempFilename')
            ->withAnyArgs();
        $this->dockerLfcProcessMock
            ->shouldReceive('setDockerCommand')
            ->withAnyArgs();


        $kernel = self::bootKernel();

        $application = new Application($kernel);
        $application->add(new DockerManageCommand());

        $command = $application->find('docker:manage');
        $commandTester = new CommandTester($command);

        $arguments = [
            'command' => $command->getName(),
            '--interactive' => true,
            '--tty' => true,
            '--detached' => true,
            '--phpversion' => '7.2',
            '--threadsafe' => 'nts',
            '--port' => '7474:80',
            '--mount' => [':unittest_srv_mysql,,,,'],
            '--volume' => '${PWD}/:/srv/www',
            '--script' => "lfphp",
            'execute' => 'stop-force',
        ];
        $commandTester->execute($arguments);

        $this->assertSame(
            PHP_EOL
            . 'Stopping container...'
            . PHP_EOL,
            $this->getActualOutput()
        );
    }

    public function testExecuteWithStopCommandWithStdoutAndStderrFromCommands()
    {
        // Redirect output to command output
        //$this->setOutputCallback(function () {
        //});

        ob_start();

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
            ->andReturn('Fake containers stopped and removed!');
        $this->dockerLfcProcessMock
            ->shouldReceive('getErrorOutput')
            ->andReturn('We have received a few errors...');
        $this->dockerLfcProcessMock
            ->shouldReceive('getExitCode')
            ->andReturn(0);

        $kernel = self::bootKernel();

        $application = new Application($kernel);
        $application->add(new DockerManageCommand());

        $command = $application->find('docker:manage');
        $commandTester = new CommandTester($command);
        $commandTester->setInputs(['n']);
        $commandTester->execute([
            'command'  => $command->getName(),
            'execute'  => 'stop',
        ]);

        $this->assertSame(
            'We have received a few errors...'
            .PHP_EOL,
            $this->getActualOutput()
        );

        ob_end_clean();
    }

    public function testExecuteWithStopCommandWithCommitCommandWithoutOutputToJsonFile()
    {
        file_put_contents(
            VENDORFOLDERPID
            . DIRECTORY_SEPARATOR
            . 'composer'
            . DIRECTORY_SEPARATOR
            . 'linuxforcomposer.pid',
            'a1a1' . PHP_EOL
        );

        // Redirect output to command output
        $this->setOutputCallback(function () {
        });

        $this->createMocksForUnixEnv();

        $this->dockerLfcProcessMock
            ->shouldReceive('getOutput')
            ->once()
            ->andReturn('Container a1a1');
        $this->dockerLfcProcessMock
            ->shouldReceive('getErrorOutput')
            ->andReturn('');
        $this->dockerLfcProcessMock
            ->shouldReceive('getExitCode')
            ->andReturn(0);

        $kernel = self::bootKernel();

        $application = new Application($kernel);
        $application->add(new DockerManageCommand());

        $command = $application->find('docker:manage');
        $commandTester = new CommandTester($command);
        $commandTester->setInputs(['y']);
        $commandTester->setInputs(['test-7.2']);
        $commandTester->setInputs(['n']);
        $commandTester->execute([
            'command'  => $command->getName(),
            'execute'  => 'stop',
        ]);

        $this->assertSame(
            'Container a1a1'
            . PHP_EOL
            . 'Stopping container...'
            . PHP_EOL
            . 'Container a1a1'
            . PHP_EOL,
            $this->getActualOutput()
        );
    }

    public function executeWithStopCommandWithCommitCommandWithOutputToJsonFile()
    {
        file_put_contents(
            VENDORFOLDERPID
            . DIRECTORY_SEPARATOR
            . 'composer'
            . DIRECTORY_SEPARATOR
            . 'linuxforcomposer.pid',
            'a1a1' . PHP_EOL
        );

        // Redirect output to command output
        $this->setOutputCallback(function () {
        });

        $this->createMocksForUnixEnv();

        $this->dockerLfcProcessMock
            ->shouldReceive('getOutput')
            ->once()
            ->andReturn('Container a1a1');
        $this->dockerLfcProcessMock
            ->shouldReceive('getErrorOutput')
            ->andReturn('');
        $this->dockerLfcProcessMock
            ->shouldReceive('getExitCode')
            ->andReturn(0);

        $kernel = self::bootKernel();

        $application = new Application($kernel);
        $application->add(new DockerManageCommand());

        $command = $application->find('docker:manage');
        $commandTester = new CommandTester($command);
        $commandTester->setInputs(['y']);
        $commandTester->setInputs(['test-7.2']);

        // ** TEST DEACTIVATED **
        // The next line is causing a runtime exception within the Symfony console!
        $commandTester->setInputs(['y']);
        $commandTester->execute([
            'command'  => $command->getName(),
            'execute'  => 'stop',
        ]);

        $this->assertSame(
            'Container a1a1'
            . PHP_EOL
            . 'Stopping container...'
            . PHP_EOL
            . 'Container a1a1'
            . PHP_EOL,
            $this->getActualOutput()
        );
    }

    public function testExecuteWithStopCommandWithEmptyPidFile()
    {
        file_put_contents(
            VENDORFOLDERPID
            . DIRECTORY_SEPARATOR
            . 'composer'
            . DIRECTORY_SEPARATOR
            . 'linuxforcomposer.pid',
            '' . PHP_EOL
        );

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
            ->andReturn('');
        $this->dockerLfcProcessMock
            ->shouldReceive('getExitCode')
            ->andReturn(0);

        $kernel = self::bootKernel();

        $application = new Application($kernel);
        $application->add(new DockerManageCommand());

        $command = $application->find('docker:manage');
        $commandTester = new CommandTester($command);
        $commandTester->execute([
            'command'  => $command->getName(),
            'execute'  => 'stop',
        ]);

        $this->assertSame(
            PHP_EOL
            . 'PID file was empty!'
            . PHP_EOL
            . PHP_EOL,
            $this->getActualOutput()
        );
    }

    public function testExecuteWithStopCommandWithoutPidFile()
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
            ->andReturn('');
        $this->dockerLfcProcessMock
            ->shouldReceive('getExitCode')
            ->andReturn(0);

        $kernel = self::bootKernel();

        $application = new Application($kernel);
        $application->add(new DockerManageCommand());

        $command = $application->find('docker:manage');
        $commandTester = new CommandTester($command);
        $commandTester->execute([
            'command'  => $command->getName(),
            'execute'  => 'stop',
        ]);

        $this->assertSame(
            PHP_EOL
            . 'Could not find the PID file!'
            . PHP_EOL
            . 'Please make sure the file exists or stop the containers manually.'
            . PHP_EOL
            . PHP_EOL,
            $this->getActualOutput()
        );
    }

    public function testExecuteStopForceCommandWithDockerfile()
    {
        // Redirect output to command output
        //$this->setOutputCallback(function () {
        //});

        ob_start();

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
            ->shouldReceive('isRunning')
            ->andReturn(false);
        $this->dockerLfcProcessMock
            ->shouldReceive('isSuccessful')
            ->andReturn(true);
        $this->dockerLfcProcessMock
            ->shouldReceive('getOutput')
            ->andReturn('');
        $this->dockerLfcProcessMock
            ->shouldReceive('getErrorOutput')
            ->andReturn('');
        $this->dockerLfcProcessMock
            ->shouldReceive('getExitCode')
            ->andReturn(0);
        $this->dockerLfcProcessMock
            ->shouldReceive('setTempFilename')
            ->withAnyArgs();
        $this->dockerLfcProcessMock
            ->shouldReceive('setDockerCommand')
            ->withAnyArgs();


        $kernel = self::bootKernel();

        $application = new Application($kernel);
        $application->add(new DockerManageCommand());

        $command = $application->find('docker:manage');
        $commandTester = new CommandTester($command);

        $fakeDockerfile = dirname(__DIR__)
            . DIRECTORY_SEPARATOR
            . 'app'
            . DIRECTORY_SEPARATOR
            . 'Dockerfile.fake';

        $arguments = [
            'command' => $command->getName(),
            '--interactive' => true,
            '--tty' => true,
            '--detached' => true,
            '--phpversion' => '7.2',
            '--threadsafe' => 'nts',
            '--port' => '7474:80',
            '--mount' => ['source=unittest_srv_mysql,target=/srv/mysql,,,unittest_srv_mysql,,,,'],
            '--volume' => '${PWD}/:/srv/www',
            '--script' => "dockerfile,,,$fakeDockerfile,,,dockerfiletest",
            'execute' => 'stop-force',
        ];
        $commandTester->execute($arguments);

        $this->assertSame(
            PHP_EOL
            . 'Stopping container...'
            . PHP_EOL,
            $this->getActualOutput()
        );

        ob_end_clean();
    }

    public function testExecuteStopForceCommandWithDockerfileWithWrongURL()
    {
        // Redirect output to command output
        //$this->setOutputCallback(function () {
        //});

        ob_start();

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
            ->shouldReceive('isRunning')
            ->andReturn(false);
        $this->dockerLfcProcessMock
            ->shouldReceive('isSuccessful')
            ->andReturn(true);
        $this->dockerLfcProcessMock
            ->shouldReceive('getOutput')
            ->andReturn('');
        $this->dockerLfcProcessMock
            ->shouldReceive('getErrorOutput')
            ->andReturn('');
        $this->dockerLfcProcessMock
            ->shouldReceive('getExitCode')
            ->andReturn(0);
        $this->dockerLfcProcessMock
            ->shouldReceive('setTempFilename')
            ->withAnyArgs();
        $this->dockerLfcProcessMock
            ->shouldReceive('setDockerCommand')
            ->withAnyArgs();


        $kernel = self::bootKernel();

        $application = new Application($kernel);
        $application->add(new DockerManageCommand());

        $command = $application->find('docker:manage');
        $commandTester = new CommandTester($command);

        $fakeDockerfile = dirname(__DIR__)
            . DIRECTORY_SEPARATOR
            . 'app'
            . DIRECTORY_SEPARATOR
            . 'Dockerfile.fake';

        $arguments = [
            'command' => $command->getName(),
            '--interactive' => true,
            '--tty' => true,
            '--detached' => true,
            '--phpversion' => '7.2',
            '--threadsafe' => 'nts',
            '--port' => '7474:80',
            '--mount' => ['source=unittest_srv_mysql,target=/srv/mysql,,,unittest_srv_mysql,,,,'],
            '--volume' => '${PWD}/:/srv/www',
            '--script' => "dockerfile,,,$fakeDockerfile.wrong,,,dockerfiletest",
            'execute' => 'stop-force',
        ];
        $commandTester->execute($arguments);

        $this->assertSame(
            PHP_EOL
            . 'Stopping container...'
            . PHP_EOL,
            $this->getActualOutput()
        );

        ob_end_clean();
    }

    public function testExecuteStopForceCommandWithDockerCompose()
    {
        // Redirect output to command output
        //$this->setOutputCallback(function () {
        //});

        ob_start();

        $this->createMocksForUnixEnv();

        $this->dockerLfcProcessMock
            ->shouldReceive('isRunning')
            ->andReturn(false);
        $this->dockerLfcProcessMock
            ->shouldReceive('isSuccessful')
            ->andReturn(true);
        $this->dockerLfcProcessMock
            ->shouldReceive('getOutput')
            ->andReturn('');
        $this->dockerLfcProcessMock
            ->shouldReceive('getErrorOutput')
            ->andReturn('');
        $this->dockerLfcProcessMock
            ->shouldReceive('getExitCode')
            ->andReturn(0);
        $this->dockerLfcProcessMock
            ->shouldReceive('setTempFilename')
            ->withAnyArgs();
        $this->dockerLfcProcessMock
            ->shouldReceive('setDockerCommand')
            ->withAnyArgs();

        if (!file_exists(getcwd() . DIRECTORY_SEPARATOR . 'fakerepo')) {
            mkdir(getcwd() . DIRECTORY_SEPARATOR . 'fakerepo');
        }


        $kernel = self::bootKernel();

        $application = new Application($kernel);
        $application->add(new DockerManageCommand());

        $command = $application->find('docker:manage');
        $commandTester = new CommandTester($command);

        $arguments = [
            'command' => $command->getName(),
            '--interactive' => true,
            '--tty' => true,
            '--detached' => true,
            '--phpversion' => '7.2',
            '--threadsafe' => 'nts',
            '--port' => '7474:80',
            '--mount' => ['source=unittest_srv_mysql,target=/srv/mysql,,,unittest_srv_mysql,,,,'],
            '--volume' => '${PWD}/:/srv/www',
            '--script' => "docker-compose,,,https://example.com/fakerepo,,,user1:secret",
            'execute' => 'stop-force',
        ];
        $commandTester->execute($arguments);

        $this->assertSame(
            PHP_EOL
            . 'Stopping all containers...'
            . PHP_EOL,
            $this->getActualOutput()
        );

        if (file_exists(getcwd() . DIRECTORY_SEPARATOR . 'fakerepo')) {
            rmdir(getcwd() . DIRECTORY_SEPARATOR . 'fakerepo');
        }

        ob_end_clean();
    }

    public function testExecuteStopForceCommandWithDockerComposeWithWrongLocalUrl()
    {
        // Redirect output to command output
        //$this->setOutputCallback(function () {
        //});

        ob_start();

        $this->createMocksForUnixEnv();

        $this->dockerLfcProcessMock
            ->shouldReceive('isRunning')
            ->andReturn(false);
        $this->dockerLfcProcessMock
            ->shouldReceive('isSuccessful')
            ->andReturn(true);
        $this->dockerLfcProcessMock
            ->shouldReceive('getOutput')
            ->andReturn('');
        $this->dockerLfcProcessMock
            ->shouldReceive('getErrorOutput')
            ->andReturn('');
        $this->dockerLfcProcessMock
            ->shouldReceive('getExitCode')
            ->andReturn(0);
        $this->dockerLfcProcessMock
            ->shouldReceive('setTempFilename')
            ->withAnyArgs();
        $this->dockerLfcProcessMock
            ->shouldReceive('setDockerCommand')
            ->withAnyArgs();

        if (file_exists(getcwd() . DIRECTORY_SEPARATOR . 'fakerepo')) {
            rmdir(getcwd() . DIRECTORY_SEPARATOR . 'fakerepo');
        }


        $kernel = self::bootKernel();

        $application = new Application($kernel);
        $application->add(new DockerManageCommand());

        $command = $application->find('docker:manage');
        $commandTester = new CommandTester($command);

        $arguments = [
            'command' => $command->getName(),
            '--interactive' => true,
            '--tty' => true,
            '--detached' => true,
            '--phpversion' => '7.2',
            '--threadsafe' => 'nts',
            '--port' => '7474:80',
            '--mount' => ['source=unittest_srv_mysql,target=/srv/mysql,,,unittest_srv_mysql,,,,'],
            '--volume' => '${PWD}/:/srv/www',
            '--script' => "docker-compose,,,fakerepo,,,user1:secret",
            'execute' => 'stop-force',
        ];
        $commandTester->execute($arguments);

        $this->assertSame(
            PHP_EOL
            . 'URL is invalid!'
            . PHP_EOL
            . PHP_EOL
            . 'Please make sure that the URL is allowed and valid,'
            . PHP_EOL
            . 'and that cURL and Git are available on your system.'
            . PHP_EOL
            . PHP_EOL,
            $this->getActualOutput()
        );

        ob_end_clean();
    }

    public function testExecuteStopForceCommandWithDockerComposeWithWrongRemoteUrl()
    {
        // Redirect output to command output
        //$this->setOutputCallback(function () {
        //});

        ob_start();

        $this->createMocksForUnixEnv();

        $this->dockerLfcProcessMock
            ->shouldReceive('isRunning')
            ->andReturn(false);
        $this->dockerLfcProcessMock
            ->shouldReceive('isSuccessful')
            ->andReturn(true);
        $this->dockerLfcProcessMock
            ->shouldReceive('getOutput')
            ->andReturn('');
        $this->dockerLfcProcessMock
            ->shouldReceive('getErrorOutput')
            ->andReturn('');
        $this->dockerLfcProcessMock
            ->shouldReceive('getExitCode')
            ->andReturn(0);
        $this->dockerLfcProcessMock
            ->shouldReceive('setTempFilename')
            ->withAnyArgs();
        $this->dockerLfcProcessMock
            ->shouldReceive('setDockerCommand')
            ->withAnyArgs();

        if (file_exists(getcwd() . DIRECTORY_SEPARATOR . 'fakerepo')) {
            rmdir(getcwd() . DIRECTORY_SEPARATOR . 'fakerepo');
        }


        $kernel = self::bootKernel();

        $application = new Application($kernel);
        $application->add(new DockerManageCommand());

        $command = $application->find('docker:manage');
        $commandTester = new CommandTester($command);

        $arguments = [
            'command' => $command->getName(),
            '--interactive' => true,
            '--tty' => true,
            '--detached' => true,
            '--phpversion' => '7.2',
            '--threadsafe' => 'nts',
            '--port' => '7474:80',
            '--mount' => ['source=unittest_srv_mysql,target=/srv/mysql,,,unittest_srv_mysql,,,,'],
            '--volume' => '${PWD}/:/srv/www',
            '--script' => "docker-compose,,,https://example.com/fakerepo,,,user1:secret",
            'execute' => 'stop-force',
        ];
        $commandTester->execute($arguments);

        $this->assertSame(
            PHP_EOL
            . 'URL is invalid!'
            . PHP_EOL
            . PHP_EOL
            . 'Please make sure that the URL is allowed and valid,'
            . PHP_EOL
            . 'and that cURL and Git are available on your system.'
            . PHP_EOL
            . PHP_EOL,
            $this->getActualOutput()
        );

        ob_end_clean();
    }

    public function testExecuteWithWrongCommand()
    {
        // Redirect output to command output
        $this->setOutputCallback(function () {
        });

        $kernel = self::bootKernel();

        $application = new Application($kernel);
        $application->add(new DockerManageCommand());

        $command = $application->find('docker:manage');
        $commandTester = new CommandTester($command);
        $commandTester->execute([
            'command'  => $command->getName(),
            'execute'  => 'bad',
        ]);

        $this->assertSame(
            PHP_EOL
            . 'Wrong command given!'
            . PHP_EOL
            . PHP_EOL,
            $this->getActualOutput()
        );
    }
}
