<?php

namespace Src\Main\Controller;

use Silex\Application;
use Silex\ControllerCollection;
use Silex\ControllerProviderInterface;
use Src\Main\Lib\ApoioClient;
use Src\Main\Lib\Database;
use Src\Main\Lib\ListHelper;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpFoundation\Request;

class UpdateUsersController implements ControllerProviderInterface
{
	public function connect(Application $app)
	{
		$controllers = $app['controllers_factory'];

		$controllers->get('/', function (Request $request) use ($app)
		{
			$users = $app['users'];

			foreach($users as $user)
			{
				Database::addUser($user);
			}

			return new RedirectResponse("/");
		});

		return $controllers;
	}
}