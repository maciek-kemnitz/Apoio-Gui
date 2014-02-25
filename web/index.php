<?php
require_once __DIR__.'/../vendor/autoload.php';
require_once __DIR__ . '/../vendor/emberlabs/gravatarlib/emberlabs/gravatarlib/Gravatar.php';
require_once __DIR__.'/../Src/Config/config.php';

// Register FF Silex Less service provider
$app = getAppConfigured();
$app->register(new \FF\ServiceProvider\LessServiceProvider(), array(
	'less.sources'     => array(__DIR__.'/../Src/Resources/less/styles.less'),
	'less.target'      => __DIR__.'/../web/css/styles.css',
	'less.target_mode' => 0775,));

session_start();

$app->mount('/ajax', new \Src\Main\Controller\AjaxController());
$app->mount('/archive', new \Src\Main\Controller\ArchiveController());
$app->mount('/conversation', new \Src\Main\Controller\ConversationController());
$app->mount('/', new \Src\Main\Controller\InboxController());
$app->mount('/my-tickets', new \Src\Main\Controller\MyTicketsController());
$app->mount('/all-tickets', new \Src\Main\Controller\AllTicketsController());
$app->mount('/update-users', new \Src\Main\Controller\UpdateUsersController());





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

$app->match('/oauth2callback', function (\Symfony\Component\HttpFoundation\Request $request) use ($app)
{
	/** @var Google_Client $client */
	$client = $app['google_client'];

	if (isset($_GET['code'])) {

		$client->authenticate($_GET['code']);
		$_SESSION['access_token'] = $client->getAccessToken();
		$_SESSION['my_tickets'] = true;
		$url = '/';
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
	if (isset($_SESSION['wrongDomain']) && $_SESSION['wrongDomain'])
	{
		$params['wrongDomain'] = true;
		unset($_SESSION['wrongDomain']);
	}

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

    try
    {
        $client->setAccessToken($_SESSION['access_token']);

        $plus = new Google_Service_Plus($client);
        $emails = $plus->people->get("me")->getEmails();
        $email = $emails[0]['value'];

		if (strpos($email, "docplanner.com") == false)
		{
			unset($_SESSION['access_token']);
			$_SESSION['wrongDomain'] = true;
			header( 'Location: /login' );
			exit;
		}

		$display_name = explode("@",$email);
        $app['user_email'] = $email;
        $app['twig']->addGlobal('user_email', $email);
		$displayName = count($displayName = explode('.', $display_name[0])) == 2 ? ucwords(implode(" ", $displayName)) : $display_name[0];

        $app['twig']->addGlobal('display_name', $displayName);

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
        'username' => SWIFT_MAILER_USERNAME,
        'password' => SWIFT_MAILER_PASSWORD,
        'encryption' => null,
        'auth_mode' => null
    );

    $app->register(new Silex\Provider\SwiftmailerServiceProvider());

    $app['twig']->addGlobal('gravatar', \Src\Main\Lib\GravatarClient::getGravatar());

	$users = \Src\Main\Lib\Database::getUsers();
	$app['users'] = $users;

    return $app;
}

/**
 * @return Google_Client
 */
function prepareGoogleClient()
{
    $client = new Google_Client();

	$client->setClientId(GOOGLE_API_CLIENT_ID);
	$client->setClientSecret(GOOGLE_API_CLIENT_SECRET);
    $client->setRedirectUri("http://".$_SERVER["HTTP_HOST"].'/oauth2callback');
    $client->setScopes("https://www.googleapis.com/auth/userinfo.email");
    $client->setAccessType('offline');

    return $client;
}