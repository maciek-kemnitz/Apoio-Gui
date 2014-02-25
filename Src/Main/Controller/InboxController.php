<?php

namespace Src\Main\Controller;

use Silex\Application;
use Silex\ControllerCollection;
use Silex\ControllerProviderInterface;
use Src\Main\Lib\ApoioClient;
use Src\Main\Lib\Database;
use Src\Main\Lib\ListHelper;
use Symfony\Component\HttpFoundation\Request;

class InboxController implements ControllerProviderInterface
{
	public function connect(Application $app)
	{
		$controllers = $app['controllers_factory'];

		$controllers->get('/', function (Request $request) use ($app)
		{
			$search = $request->query->get("search");
			$myTickets = false;

			if (isset($_SESSION['my_tickets']) && $_SESSION['my_tickets'])
			{
				$tmp = '';
				if ($search)
				{
					$tmp = " ".$search;
				}
				$search = $app['user_email'] . $tmp;
				$myTickets = true;
			}

			$users = $app['users'];
			$helper = new ListHelper($users);

			if ($search)
			{
				$helper->initSearchList($search);
			}
			else
			{
				$helper->initMixedList();
			}

			$params = [
				"helper" => $helper,
				"type" => "inbox",
				"totalCount" => 22,
				"pageCount" => 3,
				"search" => $search,
				"myTickets" => $myTickets
			];

			return $app['twig']->render('list.page.html.twig', $params);
		});

		return $controllers;
	}
}