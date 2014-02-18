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
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;

class AjaxController implements ControllerProviderInterface
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

        $controllers->post('/inbox', function (Request $request) use ($app)
        {
            $page = $request->request->get('page');
            $type = $request->request->get('type');
            $search = $request->request->get('search');

            $users = ApoioClient::getUsers();

            if ($search)
            {
                list($conversations, ) = ApoioClient::getConversationsByQuery($search, $users, $page);
            }
            else
            {
                list($conversations, ) = ApoioClient::getConversations($type, $users, $page);
            }

            foreach($conversations as $item)
            {
                $result['list-item'][] = $app['twig']->render('list-item.block.html.twig', ["item" => $item]);
            }

            $result['status'] = 'ok';

            return new JsonResponse($result);
        });

        return $controllers;
    }
}