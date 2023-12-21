<?php

require_once 'vendor/autoload.php';

use App\Command\FileCommand;
use App\Command\InitCommand;
use Symfony\Component\Console\Application;

define('PROJECT_ROOT_DIR', __DIR__.DIRECTORY_SEPARATOR);

$application = new Application();
$application->setVersion('1.0.0');

$application->add(new InitCommand());
$application->add(new FileCommand());

$application->run();
