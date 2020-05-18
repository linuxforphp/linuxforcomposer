.. _WhatsNewAnchor:

What's New
==========


What's New in Version 2.0.7 (2020-05-18)
----------------------------------------

* Fixes an issue with clean restarts on local/Windows computers.


What's New in Version 2.0.6 (2020-05-04)
----------------------------------------

* Fixes an issue when restarting an LfC container using Docker.


What's New in Version 2.0.5 (2020-05-01)
----------------------------------------

* Fixes an issue when reading from environment variables.


What's New in Version 2.0.4 (2020-04-30)
----------------------------------------

* Fixes an issue when the JSON file is invalid.
* Fixes a minor regression when compiling PHP from source.
* Replaces the flocker driver by the local driver for Docker shared volume creation.


What's New in Version 2.0.3 (2020-04-09)
----------------------------------------

* Adds the shared Docker volume size feature for the LfPHP Cloud.


What's New in Version 2.0.2 (2020-03-19)
----------------------------------------

* Fixes an issue when changing from a Dockerfile to Linux for Composer in order to start containers.
* Updates the LfPHP client to take into account the new Cloud API.
* Adds the '--version' option.


What's New in Version 2.0.1 (2020-03-09)
----------------------------------------

* Fixes an issue with volume paths on Windows.


What's New in Version 2.0.0 (2020-02-24)
----------------------------------------

* Adds new Dockerfile and docker-compose functionality.
* Adds data persistence through mounted storage.
* Adds a 'stop-force' command.
* Updates the PHP versions to the Linux for PHP 8.2.0 pre-compiled versions.
* Adds new deployment functionality for the LfPHP Cloud.


What's New in Version 1.0.2 (2019-01-15)
----------------------------------------

* Updates the PHP versions to the Linux for PHP 8.1.3 pre-compiled versions.


What's New in Version 1.0.1 (2019-01-13)
----------------------------------------

* Fixes an issue whereby the Linux for Composer PID file could be deleted by the 'composer update' command.

* Fixes an issue with the JSON formatting of the main configuration file.


What's New in Version 1.0.0 (2018-11-07)
----------------------------------------

* Adds a new 'commit' feature when stopping containers.

* Adds official documentation.

* Fixes an issue with Docker commands on Windows 10.
