.. _UsageAnchor:

Usage
=====

.. index:: start command

.. index:: Commands

.. _start command:

linuxforcomposer docker:run start
---------------------------------

Once you are done modifying the JSON file, you can start the container or containers by issuing the following command::

    $ php vendor/bin/linuxforcomposer.phar docker:run start


.. index:: stop command

.. _stop command:

linuxforcomposer docker:run stop
--------------------------------

In order to stop all the containers that were started using **Linux for Composer**, please enter the following command::

    $ php vendor/bin/linuxforcomposer.phar docker:run stop

The ``docker:run stop`` command will automatically ask you if you want to commit each and every container that
you have started before stopping and removing them.

.. image:: /images/image001.png
    :align: center

If you do wish to save them, you will be asked to give each commit a unique name and you will also be asked
if you wish to save the new name to the ``linuxforcomposer.json`` file for use the next time you start
containers with **Linux for Composer**.

.. image:: /images/image002.png
    :align: center

.. index:: stop-force command

.. _stop-force command:

linuxforcomposer docker:run stop-force
--------------------------------------

In order to force stop all the containers that were started using **Linux for Composer** without being asked to commit
each and every container, please use the following command::

    $ php vendor/bin/linuxforcomposer.phar docker:run stop-force

The ``docker:run stop-force`` command will automatically stop and remove each and every container that
you have started.

.. index:: deploy command

.. _deploy command:

linuxforcomposer docker:run deploy
--------------------------------------

In order to deploy your current configuration file to the **Linux for PHP Cloud Services**, please use the following command::

    $ php vendor/bin/linuxforcomposer.phar docker:run deploy

The ``docker:run deploy`` command will automatically post your configuration to the **Linux for PHP Cloud Services**.

.. note:: Please note that some configurations might be restricted due to the limitations of your service plan. Please see https://linuxforphp.com/account for more details on your service plan.

.. index:: list command

.. _list command:

linuxforcomposer list
--------------------------------------

Use the following to list the currently available commands::

    $ php vendor/bin/linuxforcomposer.phar list

**Linux for Composer 2.0.8**

Usage:
  command [options] [arguments]

.. list-table:: Options
   :widths: 10 30 60
   :header-rows: 1

   * - Simple
     - Readable
     - Description
   * - -h
     - --help
     - Display this help message
   * - -q
     - --quiet
     - Do not output any message
   * - -V
     - --version
     - Display this application version
   * -
     - --ansi
     - Force ANSI output
   * -
     - --no-ansi
     - Disable ANSI output
   * - -n
     - --no-interaction
     - Do not ask any interactive questions
   * - -v
     - --verbose
     - Increase the verbosity of messages:
   * -
     -
     - "v" for normal output,
   * -
     -
     - "vv" for more verbose output and
   * -
     -
     - "vvv" for debug

.. list-table:: Available Commands:
   :widths: 40 60
   :header-rows: 1

   * - Command
     - Description
   * - help
     - Displays help for a command
   * - list
     - Lists commands
   * - docker:commit
     - Docker commit commands
   * - docker:manage
     - Run Docker management commands
   * - docker:parsejson
     - Parse JSON file for instructions for Docker
   * - docker:run
     - Run 'Linux for PHP' containers
