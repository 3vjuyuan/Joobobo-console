#!/usr/bin/env php
<?php

//@todo get autoload file
require __DIR__ . '/vendor/autoload.php';

$app = new \Joobobo\Console\JooboboConsoleApplication();
$app->setFactory(new \Joobobo\Console\Factory\CommandFactory());
$app->run();

