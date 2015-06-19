<?php

namespace App;

use Nette,
	 Nette\Application\Routers\RouteList,
	 Nette\Application\Routers\Route;

class RouterFactory {

	/**
	 * @return Nette\Application\IRouter
	 */
	public static function createRouter() {
		$router = new RouteList();
		$router[] = new Route('<action>', 'Web:uvod');
		$router[] = new Route('<presenter>/<action>', 'Web:uvod');
		return $router;
	}

}
