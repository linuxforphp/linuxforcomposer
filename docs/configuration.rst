.. _ConfigurationAnchor:

Configuration
=============

Here are the main configuration settings:

.. index:: php-versions setting

.. index:: PHP Versions

.. _php-versions setting:

PHP Versions
------------

``php-versions`` (Required - Default: none)

A list of the available pre-compiled versions can be found in the Linux for PHP repository
on `Docker Hub <https://hub.docker.com/r/asclinux/linuxforphp-8.1/tags/>`_.

If you choose many versions, **Linux for Composer** will start a detached container for each chosen version.

If you choose only one version, enter ``/bin/bash`` in the script section (see :ref:`script setting`)
and do not ask for the 'detached' mode in the modes section (see :ref:`modes setting`),
you will get an interactive shell.

Finally, if you enter a version number like ``7.4.0`` (without the 'dev' part),
**Linux for Composer** will COMPILE the latest version from source!!!
Now, that's really bleeding edge, isn't it?

.. index:: modes setting

.. index:: Modes

.. _modes setting:

Modes
-----

``modes`` (Optional - Default: detached mode)

There are three possible modes when running Docker containers with **Linux for Composer**:

* Detached

* Interactive

* TTY

Whenever, you ask for the detached mode, it will take precedence over any other mode that you ask for in the
``linuxforcomposer.json`` file.

.. index:: ports setting

.. index:: Ports

.. _ports setting:

Ports
-----

``ports`` (Optional - Default: none)

You can share ports from the host system with your containers.

If you enter many port mappings for each shared port, **Linux for Composer** will share each mapping
with one container in the order they were given.
For example, 'port1' contains two mappings (8181:80 and 8282:80) and so does 'port2' (13306:3306 and 13307:3306).
The first element of each mapping (8181:80 and 13306:3306) will be given to container 1, which corresponds
to the first given PHP version in the ``php-versions`` section (see :ref:`php-versions setting`).
The second element of each mapping (8282:80 and 13307:3306) will be given to container 2.

.. index:: volumes setting

.. index:: Volumes

.. _volumes setting:

Volumes
-------

``volumes`` (Optional - Default: none)

You can share volumes between the host and your containers.

.. note:: Each volume will be shared with each and every container.

Linux/Unix/Mac users can insert Bash environment variables in this part of the JSON file.
For example, you can share your current working directory with your containers
by entering: "${PWD}/:/srv/www". This will make your working directory available
to the web server inside the Linux for PHP container.

On Windows 10 (PowerShell), please share the volume by using the following format:

``"c:/Users/test:/srv/test"``

On Windows 8 (Bash), please use the following format:

``"/c/Users/test:/srv/test"``

.. note:: Windows users must make sure to turn volume sharing on in the Docker settings.

.. index:: script setting

.. index:: Scripts

.. _script setting:

Scripts
-------

``script`` (Optional - Default: 'lfphp')

You can enter any command that you wish to execute as soon as the Linux for PHP container has finished starting.
The most common ones are 'lfphp' and '/bin/bash'.

But, you could also execute a PHP script directly or launch one of the recipes from the Linux for PHP documentation.

For example, to install Blackfire.io automatically, you could enter:

``"'/bin/bash -c \"lfphp-get blackfire ; /bin/bash\"'"``

Another example would be to install a PHP Framework:

``"'/bin/bash -c \"lfphp-get php-frameworks ; /bin/bash\"'"``

Please don't forget the single quotes at the beginning and the end of the string, and to escape the double quotes to avoid invalidating your JSON!

On Windows (both 8 - Bash - and 10 - PowerShell), it is necessary to invert the quotes and double quotes, as follows:

``"\"/bin/bash -c 'lfphp-get php-frameworks ; /bin/bash'\""``

If you are using multiple commands while compiling a new version of PHP simultaneously, please omit the first call to Bash, as follows:

``"\"lfphp-get php-frameworks ; /bin/bash\""``

.. index:: thread-safe setting

.. index:: Thread-Safety

.. _thread-safe setting:

Thread-Safety
-------------

``thread-safe`` (Optional - Default: 'false')

It is possible to run a Zend thread-safe ('true') or a non-thread safe ('false') version of PHP.
