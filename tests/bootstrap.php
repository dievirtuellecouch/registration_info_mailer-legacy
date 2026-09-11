<?php
declare(strict_types=1);

require getenv('RIM_PROJECT_AUTOLOAD') ?: dirname(__DIR__).'/vendor/autoload.php';
$loader = new Composer\Autoload\ClassLoader();
$loader->addPsr4('MenAtWork\\RegistrationInfoMailerBundle\\', dirname(__DIR__).'/src');
$loader->register(true);
