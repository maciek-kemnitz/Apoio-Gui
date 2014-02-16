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

class ConversationController implements ControllerProviderInterface
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

        $controllers->get('/{id}', function ($id) use ($app)
        {
            $conversation = ApoioClient::getConversationById($id);

            return $app['twig']->render('conversation.page.html.twig', ["conversation" => $conversation]);
        });

        return $controllers;
    }
}
