<?php

/** @noinspection PhpIncludeInspection */

// ensure we get report on all possible php errors
error_reporting(-1);

(static function () {
    $composerAutoload = getcwd() . '/vendor/autoload.php';
    if (!is_file($composerAutoload)) {
        die('You need to set up the project dependencies using Composer');
    }

    require_once $composerAutoload;
})();
