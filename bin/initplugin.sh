#!/usr/bin/env php
<?php

define('INSTALL_PATH', realpath(__DIR__ . '/..') . '/' );

mkdir(INSTALL_PATH . 'tmp');
chmod(INSTALL_PATH . 'tmp', 0777);

mkdir(INSTALL_PATH . 'attachments');
chmod(INSTALL_PATH . 'attachments', 0777);

// END OF FILE
