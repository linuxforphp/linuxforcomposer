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
 * @since 1.0.0
 */

namespace Linuxforcomposer\Command;

use Symfony\Component\Console\Command\Command;

abstract class BaseCommand extends Command
{
    public function __construct()
    {
        // you *must* call the parent constructor
        parent::__construct();
    }

    // @codeCoverageIgnoreStart
    protected function isWindows()
    {
        return strtoupper(substr(PHP_OS, 0, 3)) === 'WIN';
    }

    protected function isWindows10()
    {
        return strstr(php_uname('v'), 'Windows 10') !== false && php_uname('r') === '10.0';
    }
    // @codeCoverageIgnoreEnd
}
