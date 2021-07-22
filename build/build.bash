#!/usr/bin/env bash
cd /srv/linuxforcomposer || exit 1
rm bin/linuxforcomposer.phar
php build/build_linuxforcomposer_phar.php
chmod +x bin/linuxforcomposer.phar
