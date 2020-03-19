<?php
/**
 * Linux for PHP/Linux for Composer
 *
 * Copyright 2017 - 2020 Foreach Code Factory <lfphp@asclinux.net>
 * Version 2.0.2
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

namespace LinuxforcomposerTest\Mock;

use Symfony\Component\Console\Input\InputDefinition;
use Symfony\Component\Console\Input\InputInterface;

class InputMock implements InputInterface
{
    protected $arguments;

    /**
     * @return mixed
     */
    public function getArguments()
    {
        return $this->arguments;
    }

    /**
     * @param mixed $arguments
     */
    public function setArguments($arguments)
    {
        $this->arguments = $arguments;
    }

    public function getFirstArgument()
    {
        ;
    }

    public function hasParameterOption($values, $onlyParams = false)
    {
        ;
    }

    public function getParameterOption($values, $default = false, $onlyParams = false)
    {
        ;
    }

    public function bind(InputDefinition $definition)
    {
        ;
    }

    public function validate()
    {
        ;
    }

    public function getArgument($name)
    {
        ;
    }

    public function setArgument($name, $value)
    {
        ;
    }

    public function hasArgument($name)
    {
        ;
    }

    public function getOptions()
    {
        ;
    }

    public function getOption($name)
    {
        return $this->arguments[$name];
    }

    public function setOption($name, $value)
    {
        ;
    }

    public function hasOption($name)
    {
        ;
    }

    public function isInteractive()
    {
        ;
    }

    public function setInteractive($interactive)
    {
        ;
    }
}
