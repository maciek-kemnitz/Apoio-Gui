<?php
require_once __DIR__.'/../vendor/autoload.php';
require_once __DIR__ . '/../vendor/emberlabs/gravatarlib/emberlabs/gravatarlib/Gravatar.php';

$app = getAppConfigured();
session_start();

$app->mount('/ajax', new \Src\Main\Controller\AjaxController());
$app->mount('/archive', new \Src\Main\Controller\ArchiveController());
$app->mount('/conversation', new \Src\Main\Controller\ConversationController());

$app->post('/send-reply', function (\Symfony\Component\HttpFoundation\Request $request) use ($app)
{
    $messageId = $request->request->get("messageId");
    $comment = $request->request->get("comment");
    $subject = $request->request->get("subject");
    $name = $request->request->get("name");
    $message = \Swift_Message::newInstance()
        ->setSubject($subject)
        ->setFrom(array('front.office@docplanner.com' => $name))
        ->setTo(array('front.office@docplanner.com'))
        ->setBody($comment);

    /** @var Swift_Mime_SimpleHeaderSet $headers */
    $headers = $message->getHeaders();
    $headers->addTextHeader('In-Reply-To', $messageId);


    $app['mailer']->send($message);
    if ($app['mailer.initialized']) {
        $app['swiftmailer.spooltransport']->getSpool()->flushQueue($app['swiftmailer.transport']);
    }

    return new \Symfony\Component\HttpFoundation\RedirectResponse('/');

});

$app->get('/', function (\Symfony\Component\HttpFoundation\Request $request) use ($app)
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
})
->bind('homepage');


$app->match('/oauth2callback', function (\Symfony\Component\HttpFoundation\Request $request) use ($app)
{
	/** @var Google_Client $client */
	$client = $app['google_client'];

	if (isset($_GET['code'])) {

		$client->authenticate($_GET['code']);
		$_SESSION['access_token'] = $client->getAccessToken();

		$url = $app['url_generator']->generate('homepage');
		return new \Symfony\Component\HttpFoundation\RedirectResponse($url);
	}
	else
	{
		$url = $app['url_generator']->generate('login');
		return new \Symfony\Component\HttpFoundation\RedirectResponse($url);
	}
});

$app->match('/login', function (\Symfony\Component\HttpFoundation\Request $request) use ($app)
{
	/** @var Google_Client $client */
	$client = $app['google_client'];
	$authUrl = $client->createAuthUrl();

    $params = [];
	$params['authUrl'] = $authUrl;

    return $app['twig']->render('login.page.html.twig', $params);
})
->method("GET|POST")
->bind('login');

$app->get('/logout', function (\Symfony\Component\HttpFoundation\Request $request) use ($app)
{
	unset($_SESSION['access_token']);

	$url = $app['url_generator']->generate('login');
	return new \Symfony\Component\HttpFoundation\RedirectResponse($url);

})
->bind('logout');

$client = prepareGoogleClient();
$app['google_client'] = $client;

if (!isset($_SESSION['access_token']) && ($_SERVER["REQUEST_URI"] != '/login' && false == strstr($_SERVER["REQUEST_URI"], '/oauth2callback')))
{
	header( 'Location: /login' );
	exit;
}
elseif (isset($_SESSION['access_token']) && $_SESSION['access_token'])
{
    $token = $_SESSION['access_token'];
    $tokenArray = (array) json_decode($token);

    if (isset($tokenArray['refresh_token']))
    {
        $client->refreshToken($tokenArray['refresh_token']);
    }
////    $client->verifyIdToken($_SESSION['access_token']);
////    $plus = new Google_Service_Plus($client);
//    var_dump((array)json_decode($_SESSION["access_token"]));
//    exit;

    try
    {
//        var_dump($_SESSION['access_token']);
        $client->setAccessToken($_SESSION['access_token']);

        $plus = new Google_Service_Plus($client);
        $emails = $plus->people->get("me")->getEmails();
        $email = $emails[0]['value'];
        $display_name = explode("@",$email);
        $app['user_email'] = $email;
        $app['twig']->addGlobal('user_email', $email);
        $app['twig']->addGlobal('display_name', $display_name[0]);
    }
    catch (Exception $exc)
    {
        unset($_SESSION['access_token']);
        header( 'Location: /login' );
        exit;
    }
}
//var_dump($_SESSION['access_token']);

$app->run();


/**
 * @return Silex\Application
 */
function getAppConfigured()
{
    $app = new Silex\Application();

    $app['debug'] = true;

    $app->register(new Silex\Provider\TwigServiceProvider(), array(
        'twig.path' => __DIR__.'/../Src/Main/Views',
    ));

    $app->register(new Silex\Provider\UrlGeneratorServiceProvider());

    $app['swiftmailer.options'] = array(
        'host' => 'ssl://smtp.gmail.com',
        'port' => '465',
        'username' => 'front.office@docplanner.com',
        'password' => 'Front1234',
        'encryption' => null,
        'auth_mode' => null
    );

    $app->register(new Silex\Provider\SwiftmailerServiceProvider());

    $app['twig']->addGlobal('gravatar', \Src\Main\Lib\GravatarClient::getGravatar());

    return $app;
}

/**
 * @return Google_Client
 */
function prepareGoogleClient()
{
    $client = new Google_Client();

    $client->setClientId('439195701200-lpl78q0mf721f8s13r4evn641uk17b6h.apps.googleusercontent.com');
    $client->setClientSecret('yAAISB1eZLLuubnneke0YMQ8');
    $client->setRedirectUri("http://".$_SERVER["HTTP_HOST"].'/oauth2callback');
    $client->setScopes("https://www.googleapis.com/auth/userinfo.email");
    $client->setAccessType('offline');

    return $client;
}