<?php

require __DIR__ . '/../vendor/autoload.php';

$configurator = new Nette\Configurator;


// aktivuje laděnku pouze pro dané ip adresy
//$configurator->setDebugMode(array('80.188.94.196'));

//$configurator->setDebugMode(FALSE);
$configurator->enableDebugger(__DIR__ . '/../log');

$configurator->setTempDirectory(__DIR__ . '/../temp');

$configurator->createRobotLoader()
		  ->addDirectory(__DIR__)
		  ->register();

$configurator->addConfig(__DIR__ . '/config.neon');
if (file_exists(__DIR__ . '/config.local.neon')) {
	$configurator->addConfig(__DIR__ . '/config.local.neon');
}

$container = $configurator->createContainer();

setlocale(LC_ALL, $container->parameters['setlocale']);

return $container;
