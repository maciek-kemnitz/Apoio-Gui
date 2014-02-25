<?php
/**
 * Created by JetBrains PhpStorm.
 * User: maciek
 * Date: 15.02.14
 * Time: 21:38
 * To change this template use File | Settings | File Templates.
 */

namespace Src\Main\Controller;


use Silex\Application;
use Silex\ControllerCollection;
use Silex\ControllerProviderInterface;
use Src\Main\Lib\ApoioClient;
use Symfony\Component\HttpFoundation\Request;

class ArchiveController implements ControllerProviderInterface
{

    /**
     * Returns routes to connect to the given application.
     *
     * @param Application $app An Application instance
     *
     * @return ControllerCollection A ControllerCollection instance
     */
    public function connect(Application $app)
    {
        $controllers = $app['controllers_factory'];

        $controllers->get('/', function (Request $request) use ($app)
        {
			$users = $app['users'];
            list($conversations, $totalCount) = ApoioClient::getConversations(ApoioClient::ACCESS_POINT_ARCHIVE, $users);

            $pageCount = ceil($totalCount/30);

            return $app['twig']->render('list.page.html.twig', ["items" => $conversations, "users" => $users, "type" => "archive", "totalCount" => $totalCount, "pageCount" => $pageCount]);
        });

        return $controllers;
    }
}