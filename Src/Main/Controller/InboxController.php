<?php

namespace Src\Main\Controller;

use Silex\Application;
use Silex\ControllerCollection;
use Silex\ControllerProviderInterface;
use Src\Main\Lib\ApoioClient;
use Symfony\Component\HttpFoundation\Request;

class InboxController implements ControllerProviderInterface
{
	public function connect(Application $app)
	{
		$controllers = $app['controllers_factory'];

		$controllers->get('/', function (Request $request) use ($app)
		{
			$search = $request->query->get("search");

			$users = \Src\Main\Lib\ApoioClient::getUsers();
			if ($search)
			{
				list($conversations, $totalCount) = \Src\Main\Lib\ApoioClient::getConversationsByQuery($search, $users);
			}
			else
			{
				list($conversations, $totalCount) = \Src\Main\Lib\ApoioClient::getConversations(\Src\Main\Lib\ApoioClient::ACCESS_POINT_INBOX, $users);
			}
			$pageCount = ceil($totalCount/30);

			return $app['twig']->render('list.page.html.twig', ["items" => $conversations, "users" => $users, "type" => "inbox", "totalCount" => $totalCount, "pageCount" => $pageCount, "search" => $search]);
		});

		return $controllers;
	}
}