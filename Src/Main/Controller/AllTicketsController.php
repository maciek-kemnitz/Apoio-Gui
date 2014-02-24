<?php

namespace Src\Main\Controller;

use Silex\Application;
use Silex\ControllerCollection;
use Silex\ControllerProviderInterface;
use Src\Main\Lib\ApoioClient;
use Src\Main\Lib\ListHelper;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpFoundation\Request;

class AllTicketsController implements ControllerProviderInterface
{
	public function connect(Application $app)
	{
		$controllers = $app['controllers_factory'];

		$controllers->get('/', function (Request $request) use ($app)
		{
			$_SESSION['my_tickets'] = false;

			return new RedirectResponse("/");
		});

		return $controllers;
	}
}