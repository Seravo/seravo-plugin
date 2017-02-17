<?php
require 'plugin-update-checker/plugin-update-checker.php';

$myUpdateChecker = Puc_v4_Factory::buildUpdateChecker(
    'https://github.com/seravo/seravo-plugin/',
    __FILE__,
    'seravo-plugin'
);
