.. _ConfigurationAnchor:

=============
Configuration
=============

To obtain a sample configuration file, please run this command in a **Composer**-enabled
project's folder, once **Linux for Composer** has been installed::

    $ php vendor/bin/linuxforcomposer.phar docker:run start


You will automatically initialize the project with the following default configuration:

.. code-block:: json

    {
        "name": "linuxforphp/linuxforcomposer",
        "description": "A Composer interface to run 'Linux for PHP' Docker containers, Dockerfiles or docker-compose files.",
        "single": {
            "image": {
                "linuxforcomposer": {
                    "php-versions": [
                        "7.4",
                        "7.3",
                        "7.2"
                    ],
                    "script": [
                        "echo '<?php phpinfo();' > /srv/www/index.php",
                        "lfphp --mysql --phpfpm --apache"
                    ],
                    "thread-safe": "false"
                },
                "dockerfile": {
                    "url": "",
                    "container-name": "",
                    "username": "",
                    "token": ""
                }
            },
            "containers": {
                "modes": {
                    "mode1": "detached",
                    "mode2": "interactive",
                    "mode3": "tty"
                },
                "ports": {
                    "port1": [
                        "7474:80",
                        "7373:80",
                        "7272:80"
                    ],
                    "port2": [
                        "13306:3306",
                        "13307:3306",
                        "13308:3306"
                    ]
                },
                "volumes": {
                    "volume1": "",
                    "volume2": ""
                },
                "persist-data": {
                    "mount": "false",
                    "root-name": "",
                    "directories": {
                        "directory1": "",
                        "directory2": "",
                        "directory3": ""
                    }
                }
            }
        },
        "docker-compose": {
            "url": "",
            "username": "",
            "token": ""
        },
        "lfphp-cloud": {
            "account": "",
            "username": "",
            "token": ""
        }
    }

**Linux for Composer** has three main modes:

* **Single** mode
* **Docker Compose** mode
* **Linux for PHP Cloud** mode

Only one of these modes can be used at one time.

When using the ``single`` mode, one must configure the image that should be used or built,
and the containers that should be spun up.

The image can be configured through the ``image`` setting, and by using the ``linuxforcomposer``
standard mode, which uses **Linux for PHP** images in the background, or by using the ``dockerfile`` mode,
which uses any of the images that you can find in the **Docker Hub** repositories. The
``dockerfile`` mode has precedence over the ``linuxforcomposer`` mode.

.. note:: For more information on configuring the ``linuxforcomposer`` mode, please see the :ref:`linuxforcomposer mode` section.

Since the ``dockerfile`` mode has precedence over the ``linuxforcomposer`` mode, one can keep
all of the ``linuxforcomposer`` configurations intact by simply adding a ``dockerfile``
configuration, which will cause the ``linuxforcomposer`` configurations to be totally ignored.
This is useful when spinning up an ad hoc image to quickly test something in a project's code base.

.. note:: For more information on configuring the ``dockerfile`` mode, please see the :ref:`dockerfile mode` section.

Once the image is configured, the containers must be configured by using the ``containers`` setting.
In this section, it is possible to configure the modes, ports, volumes and mount points for each
container.

.. note:: For more information on configuring the ``containers`` setting, please see the :ref:`containers` section.

When not using ``single`` mode, but when using the ``docker-compose`` mode instead, one must give
the URL of a Git repository which contains a valid ``docker-compose.yml`` file in its root folder.
Private repositories are also supported, but require that a ``username`` and an access ``token``
be given in this section of the ``linuxforcomposer.json`` file.

.. note:: For more information on configuring the ``docker-compose`` mode, please see the :ref:`docker-compose mode` section.

Finally, when not using either of the previous modes (``single`` or ``docker-compose``), but when using
the ``lfphp-cloud`` mode in their place, it is possible to set up an automatic deployment of a project
to the **Linux for PHP Cloud**, by configuring the ``account`` name, the ``username``, and the public
access ``token`` to a valid account.

.. note:: For more information on configuring the ``lfphp-cloud`` mode, please see the :ref:`lfphp-cloud mode` section.

For more details on how to get a **Linux for PHP Cloud** account, please see the `Linux for PHP Cloud Services website <https://linuxforphp.com/>`_.

.. index:: single setting

.. index:: Single Mode

.. _single mode:

###########
Single Mode
###########

In ``single`` mode, **Linux for Composer** will either use a **Linux for PHP** image,
or an image that will be built using a Dockerfile. Once the image is ready, **Linux for Composer**
will spin up one or more containers according to the options given in the :ref:`containers` setting.

.. index:: image setting

.. index:: Image Setting

.. _image setting:

Image
#####

The image section configures **Linux for Composer** to use and/or build an image. One must
choose between the :ref:`linuxforcomposer mode` or :ref:`dockerfile mode` mode.

.. note:: The ``dockerfile`` mode has precedence over the ``linuxforcomposer`` mode.

.. index:: linuxforcomposer setting

.. index:: Linux for Composer Mode

.. _linuxforcomposer mode:

Linux for Composer Mode
=======================

The main configuration settings for the ``linuxforcomposer`` mode are:

* :ref:`php-versions setting`
* :ref:`script setting`
* :ref:`thread-safe setting`

.. index:: php-versions setting

.. index:: PHP Versions

.. _php-versions setting:

PHP Versions
------------

``php-versions`` (Required - Default: none)

A list of the available pre-compiled versions can be found in the Linux for PHP repository
on `Docker Hub <https://hub.docker.com/r/asclinux/linuxforphp-8.2-ultimate/tags/>`_.

If many versions are chosen at once, **Linux for Composer** will start a detached container for each chosen version.

If you wish to obtain an interactive shell, enter ``/bin/bash`` in the script section (see :ref:`script setting`)
and do not ask for the 'detached' mode in the modes section (see :ref:`modes setting`).

Finally, if you enter a version number like ``8.0`` (without the 'dev' part),
**Linux for Composer** will COMPILE the latest version from source!!!
Now, that's really bleeding edge, isn't it?

.. index:: script setting

.. index:: Scripts

.. _script setting:

Scripts
-------

``script`` (Optional - Default: 'lfphp')

You can enter any command that you wish to execute as soon as the **Linux for PHP** container has finished starting.
The most common ones are 'lfphp' and '/bin/bash'. But, you could also execute a PHP script directly or launch one of
the recipes from the `Linux for PHP documentation <https://linux-for-php-documentation.readthedocs.io/en/latest/cookbook.html>`_.
You may enter as many commands as you need,
as long as you enter one command per line of the script setting.

For example, to install **Drupal** automatically, you could enter:

``"lfphp-get cms drupal testapp"``

Another example would be to install **Laravel**:

``"lfphp-get php-frameworks laravel testapp"``

And, then, to start the LAMPP stack only:

``"lfphp --mysql --phpfpm --apache"``

.. index:: thread-safe setting

.. index:: Thread-Safety

.. _thread-safe setting:

Thread-Safety
-------------

``thread-safe`` (Optional - Default: 'false')

It is possible to run a Zend thread-safe ('true') or a non-thread safe ('false') version of PHP.

.. index:: dockerfile setting

.. index:: Dockerfile Mode

.. _dockerfile mode:

Dockerfile Mode
===============

``dockerfile`` (Optional - Default: none)

When configuring the ``dockerfile`` mode, one must give the ``url`` of the Dockerfile that is
to be used, and a name (``container-name``) to the image and its subsequently-created container.
The file's URL can be local (path) or remote (http/https protocols only). If a remote
Dockerfile requires authentication, it is possible to add a ``username`` and an access ``token``
to access a private repository, for example.

.. code-block:: json

    [...]

    "dockerfile": {
        "url": "Dockerfile",
        "container-name": "specialproject",
        "username": "",
        "token": ""
    }

    [...]

Or,

.. code-block:: json

    [...]

    "dockerfile": {
        "url": "https://example.com/repo/Dockerfile",
        "container-name": "specialproject2",
        "username": "user1",
        "token": "roviquerhoqiuerhvoqierbvoi"
    }

    [...]

.. note:: Please make sure cURL and Git are available on your system when trying to access remote files.

.. index:: containers setting

.. index:: Containers Settings

.. _containers:

Containers
##########

The main configuration settings for the ``containers`` section are:

* :ref:`modes setting`
* :ref:`ports setting`
* :ref:`volumes setting`
* :ref:`persist-data setting`

.. index:: modes setting

.. index:: Modes

.. _modes setting:

Modes
=====

``modes`` (Optional - Default: detached mode)

There are three possible modes when running Docker containers with **Linux for Composer**:

* Detached

* Interactive

* TTY

Whenever, you ask for the detached mode, it will take precedence over any other mode that you ask for in the
``linuxforcomposer.json`` file.

.. note:: For more information on Docker modes, please read the `Docker documentation <https://docs.docker.com/engine/reference/run/>`_.

.. index:: ports setting

.. index:: Ports

.. _ports setting:

Ports
=====

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
=======

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

.. index:: persist-data setting

.. index:: Persist Data

.. _persist-data setting:

Persist Data
============

``persist-data`` (Optional - Default: false)

You can persist data by using **Docker** volumes and mounting them inside a container or sharing them
between containers.

To mount **Docker** volumes to persist data from inside the container, one must set the ``mount`` setting
to ``true``, give a root name to the mounted volumes (we recommend setting it to the unique name of
the project), and giving the names of the container's directories that should be persisted. For example,
one could persist the container's '/srv' folder like so:

.. code-block:: json

    [...]

    "persist-data": {
        "mount": "true",
        "root-name": "unique_name_of_my_project",
        "directories": {
            "directory1": "/srv",
            "directory2": "",
            "directory3": ""
        }
    }

    [...]

This will instruct **Linux for Composer** to create a **Docker** volume with the name
``unique_name_of_my_project_srv`` and to share it with the container(s) created in
the :ref:`linuxforcomposer mode`, or the container created in the :ref:`dockerfile mode`.

Upon creation of the volume, **Linux for Composer** will sync the new volume with the
data that it will find in the designated directory.

.. note:: Windows containers are still NOT supported as of version 2.0.0 of Linux for Composer.

.. index:: docker-compose setting

.. index:: Docker Compose Mode

.. _docker-compose mode:

###################
Docker Compose Mode
###################

``docker-compose`` (Optional - Default: none)

When configuring the ``docker-compose`` mode, one must give the ``url`` of the Git repository that has
the main ``docker-compose.yml`` file in its root folder. The repository's URL can be local (path) or
remote (Git supported protocols only). If the remote repository requires authentication,
it is possible to add a ``username`` and a ``token`` to access the repository.

.. code-block:: json

    [...]

    "docker-compose": {
        "url": "asclinux-docker-compose",
        "username": "",
        "token": ""
    },

    [...]

Or,

.. code-block:: json

    [...]

    "docker-compose": {
        "url": "https://github.com/andrewscaya/asclinux-docker-compose",
        "username": "",
        "token": ""
    },

    [...]

.. note:: Please make sure cURL and Git are available on your system when trying to access remote files.

.. index:: lfphp-cloud setting

.. index:: Linux for PHP Cloud Mode

.. _lfphp-cloud mode:

########################
Linux for PHP Cloud Mode
########################

``lfphp-cloud`` (Optional - Default: none)

When configuring the ``lfphp-cloud`` mode, one must give the name of the ``account``,
the ``username`` and the public access ``token`` of the **Linux for PHP Cloud Services**
for the account that should to be used.

.. code-block:: json

    [...]

    "lfphp-cloud": {
        "account": "johnsmithexamplecom",
        "username": "john.smith@example.com",
        "token": "rnvaernlaiurnaliurnfgalriunvaernveiruneirug"
    }

    [...]

.. note:: Not all Linux for Composer settings are available on all Linux for PHP Cloud hosting plans. For more information, please see the `Linux for PHP Cloud Services website <https://linuxforphp.com/>`_.
