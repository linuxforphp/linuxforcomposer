<?php

/**
 * Linux for PHP/Linux for Composer
 *
 * Copyright 2010 - 2018 A. Caya <andrewscaya@yahoo.ca>
 * Version 1.0.0-dev
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
