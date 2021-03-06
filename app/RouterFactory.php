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
		$router[] = new Route('kurzy-astro-konstelaci', 'Web:rodinneKonstelace', Route::ONE_WAY);
		$router[] = new Route('<action>/<city>', 'Web:kontaktyTerapeutu');
		$router[] = new Route('<action>', 'Web:uvodniStranka');
		$router[] = new Route('<presenter>/<action>', 'Web:uvodniStranka');
		return $router;
	}

}
