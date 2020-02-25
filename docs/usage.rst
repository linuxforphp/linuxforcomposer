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