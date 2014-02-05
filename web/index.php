<?php
require_once __DIR__.'/../vendor/autoload.php';
require_once __DIR__ . '/../vendor/emberlabs/gravatarlib/emberlabs/gravatarlib/Gravatar.php';

$app = new Silex\Application();
$app['debug'] = true;
session_start();




$app->register(new Silex\Provider\TwigServiceProvider(), array(
	'twig.path' => __DIR__.'/../Src/Views',
));
$app->register(new Silex\Provider\UrlGeneratorServiceProvider());

$gravatar = new \emberlabs\GravatarLib\Gravatar();

$gravatar->setDefaultImage('mm');
$gravatar->setAvatarSize(50);

$gravatar->setMaxRating('pg');

$app['twig']->addGlobal('gravatar', $gravatar);

$app->post('/ajax/inbox', function (\Symfony\Component\HttpFoundation\Request $request) use ($app)
{
	$page = $request->request->get('page');

	$ch = curl_init();

	// set url
	curl_setopt($ch, CURLOPT_URL, "https://api.apo.io/users.json?access_token=8N88ng7M9vhDknokojinKknJKkIH9EMj99jokmvCddYrcTnMfokW03riFJ9kNKo9kK0oM98hMOj874IJVMOok9");

	//return the transfer as a string
	curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);

	// $output contains the output string
	$output = curl_exec($ch);

	$output = (array) json_decode($output);
	//	var_dump($output);
	$users = [];

	if (is_array($output) && isset($output['results']))
	{
		foreach($output['results'] as $user)
		{
			$user = new User((array) $user);
			$users[$user->getId()] = $user;
		}
	}

	curl_setopt($ch, CURLOPT_URL, "https://api.apo.io/inbox.json?access_token=8N88ng7M9vhDknokojinKknJKkIH9EMj99jokmvCddYrcTnMfokW03riFJ9kNKo9kK0oM98hMOj874IJVMOok9&page=".$page);

	$output = curl_exec($ch);

	$output = (array) json_decode($output);

	$items = [];
	$result = [];

	if (is_array($output) && isset($output['results']))
	{
		foreach($output['results'] as $entry)
		{
			$item = new Conversation((array) $entry, $users);
			$result['list-item'][] = $app['twig']->render('list-item.block.html.twig', ["item" => $item]);
		}

		$result['status'] = 'ok';
	}

	curl_close($ch);

	return new \Symfony\Component\HttpFoundation\JsonResponse($result);
});



$app->get('/archive', function (\Symfony\Component\HttpFoundation\Request $request) use ($app)
{
	$users = getUsers();
	list($conversations, $totalCount) = getConversations('archive', $users);

	return $app['twig']->render('list.page.html.twig', ["items" => $conversations, "users" => $users, "type" => "archive", "totalCount" => $totalCount]);
})
->bind('archive');

$app->get('/', function (\Symfony\Component\HttpFoundation\Request $request) use ($app)
{
	$users = getUsers();
	list($conversations, $totalCount) = getConversations('inbox', $users);
	$pageCount = ceil($totalCount/30);

	return $app['twig']->render('list.page.html.twig', ["items" => $conversations, "users" => $users, "type" => "inbox", "totalCount" => $totalCount, "pageCount" => $pageCount]);
})
->bind('homepage');

$app->get('/conversation/{id}', function ($id) use ($app)
{
	$users = getUsers();

	$ch = curl_init();
    //return the transfer as a string
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);

	// set url
	curl_setopt($ch, CURLOPT_URL, "https://api.apo.io/conversations/{$id}.json?access_token=8N88ng7M9vhDknokojinKknJKkIH9EMj99jokmvCddYrcTnMfokW03riFJ9kNKo9kK0oM98hMOj874IJVMOok9");

	// $output contains the output string
	$output = curl_exec($ch);

	$output = (array) json_decode($output);
//		var_dump($output);

	if (is_array($output))
	{

		$conversation = new Conversation((array) $output, $users);
	}


	curl_close($ch);

    return $app['twig']->render('conversation.page.html.twig', ["conversation" => $conversation]);
});



$app->get('/index-filters', function () use ($app)
{
	$dbh = new PDO('mysql:host=localhost.phpmyadmin;dbname=apoio', 'root', 'pass');
	$dbh->exec("set names utf8");

	$ch = curl_init();

	curl_setopt($ch, CURLOPT_URL, "https://api.apo.io/inbox.json?access_token=token");
	curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
	curl_setopt($ch, CURLOPT_ENCODING, 'utf-8');

	$output = curl_exec($ch);
	$output = (array) json_decode($output);


	if (is_array($output) && isset($output['results']))
	{
		foreach($output['results'] as $entry)
		{
			$entry = (array) $entry;

			$author 	= $entry['name'];
			$id 		= $entry['id'];
			$state 		= (array) $entry['state'];
			$status 	= $state['state'];
			$subject 	= $entry['subject'];
			var_dump($subject);
			$created_at = $entry['created_at'];
			$updated_at = $entry['updated_at'];

			$stm = $dbh->prepare('INSERT INTO `conversation` VALUES (:id,:author,:status,:subject,:created_at,:updated_at) ON DUPLICATE KEY UPDATE updated_at=VALUES(updated_at)');
			$stm->execute([
				"id" => $id,
				"author" => $author,
				"status" => $status,
				"subject" => $subject,
				"created_at" => $created_at,
				"updated_at" => $updated_at,
			]);
		}
	}

	curl_close($ch);


	return 'Hello ';
});



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

$client = new Google_Client();
$client->setClientId('439195701200-lpl78q0mf721f8s13r4evn641uk17b6h.apps.googleusercontent.com');
$client->setClientSecret('yAAISB1eZLLuubnneke0YMQ8');
$client->setRedirectUri('http://local.apoio-gui.pl/oauth2callback');
//$client->setScopes("https://www.googleapis.com/auth/plus.login");
$client->setScopes("https://www.googleapis.com/auth/userinfo.email");

$app['google_client'] = $client;

if (!isset($_SESSION['access_token']) && ($_SERVER["REQUEST_URI"] != '/login' && false == strstr($_SERVER["REQUEST_URI"], '/oauth2callback')))
{
	header( 'Location: http://local.apoio-gui.pl/login' );
	exit;
}
elseif (isset($_SESSION['access_token']) && $_SESSION['access_token'])
{
	$client->setAccessToken($_SESSION['access_token']);
	$plus = new Google_Service_Plus($client);
	$emails = $plus->people->get("me")->getEmails();
	$email = $emails[0]['value'];
	$display_name = explode("@",$email);
	$app['user_email'] = $email;
	$app['twig']->addGlobal('user_email', $email);
	$app['twig']->addGlobal('display_name', $display_name[0]);
}


$app->run();




class User
{
    public $id;
    public $name;
    public $surname;
    public $avatar;

    public function __construct(array $data)
    {
        $this->id = $data['id'];
        $this->name = $data['firstname'];
        $this->surname = $data['lastname'];
        $this->avatar = $data['avatar'];
    }

    /**
     * @return mixed
     */
    public function getId()
    {
        return $this->id;
    }

    /**
     * @return mixed
     */
    public function getName()
    {
        return $this->name;
    }

    /**
     * @return mixed
     */
    public function getSurname()
    {
        return $this->surname;
    }

    /**
     * @return mixed
     */
    public function getAvatar()
    {
        return $this->avatar;
    }


}

class ApoioListItem
{
	public $id;
	public $name;
	public $subject;

	public function __construct(array $data)
	{
		$this->id = $data['id'];
		$this->name = $data['name'];
		$this->subject = $data['subject'] ?: $data['abstract'];
	}

	public function getId()
	{
		return $this->id;
	}

	public function getName()
	{
		return $this->name;
	}

	public function getSubject()
	{
		return $this->subject;
	}
}

class Conversation
{
	public $id;
    public $author;
    public $status;
	public $subject;
	public $created_at;
	public $updated_at;
	public $last_reply_at;
	public $assigned_to_id;
	public $abstract;
	public $messages;
    /** @var  User[] */
    public $users;

	public function __construct(array $data, $users)
	{
		$this->id = $data['id'];
        $this->author 	= $data['name'];
        $this->subject = $data['subject'];
        $this->abstract = $data['abstract'];
        $this->setMessages((array) $data['messages']);


        $state 		= (array) $data['state'];
        $this->status 	= $state['state'];
        $this->created_at = $data['created_at'];
        $this->updated_at = $data['updated_at'];
        $this->assigned_to_id = $data['assigned_to_id'];
        $this->last_reply_at = $data['last_reply_at'];
        $this->users = $users;
	}

	protected function setMessages(array $messages)
	{
		foreach($messages as $messageData)
		{
			$message = new Message((array) $messageData);
			$this->messages[] = $message;
		}
	}

	/**
	 * @return mixed
	 */
	public function getAbstract()
	{
		return $this->abstract;
	}

	/**
	 * @return mixed
	 */
	public function getId()
	{
		return $this->id;
	}

	/**
	 * @return Message[]
	 */
	public function getMessages()
	{
		return $this->messages;
	}

	/**
	 * @return mixed
	 */
	public function getSubject()
	{
		return $this->subject;
	}

    /**
     * @return mixed
     */
    public function getAuthor()
    {
        return $this->author;
    }

    /**
     * @return mixed
     */
    public function getCreatedAt()
    {
        return $this->created_at;
    }

    /**
     * @return mixed
     */
    public function getStatus()
    {
        return $this->status;
    }

    /**
     * @return mixed
     */
    public function getUpdatedAt()
    {
        return $this->updated_at;
    }

    public function timePassedSinceUpdate()
    {
        $startTimeStamp = strtotime($this->last_reply_at);
        $endTimeStamp = time();

        $timeDiff = abs($endTimeStamp - $startTimeStamp);

        $numberDays = $timeDiff/86400;
        $numberDays = intval($numberDays);

        return $numberDays;
    }

    public function getPastString()
    {
        $startTimeStamp = strtotime($this->last_reply_at);
        $endTimeStamp = time();

        $timeDiff = abs($endTimeStamp - $startTimeStamp);

        $numberDays = $timeDiff/86400;
        $numberDays = intval($numberDays);

        $numberHours = $timeDiff/3600;
        $numberHours = intval($numberHours);

        if ($numberDays > 0)
        {
            return $numberDays . " days ago";
        }
        else if ($numberHours > 0)
        {
            return $numberHours . " hours ago";
        }
        else
        {
            $numberMinutes = $timeDiff/60;
            $numberMinutes = intval($numberMinutes);
            return $numberMinutes . " minutes ago";
        }
    }

    /**
     * @return mixed
     */
    public function getAssignedToId()
    {
        return $this->assigned_to_id;
    }

    /**
     * @return mixed
     */
    public function getLastReplyAt()
    {
        return $this->last_reply_at;
    }

    public function getAssignedTo()
    {
        if ($this->assigned_to_id)
        {
            $user = $this->users[$this->assigned_to_id];

            if ($user)
            {
                return $user->getName() . " " . $user->getSurname();
            }
        }

        return "";
    }

    public function getAssignedUser()
    {

        if ($this->assigned_to_id)
        {
            $user = $this->users[$this->assigned_to_id];

            if ($user)
            {
                return $user;
            }
        }

        return null;
    }
}

class Message
{
	const GITHUB_ISSUE_CREATED = 'created';
	const GITHUB_ISSUE_CLOSED = 'closed';

	public $id;
	public $type;
	public $userId;
	public $body;
	public $created_at;
	public $source;
    public $authorName;
	public $sentFrom;
	public $avatar;
	public $metaData;
	public $githubAction;

	public function __construct(array $message)
	{
		$this->id = $message['id'];
		$this->type = $message['type'];
		$this->userId = $message['user_id'];
		$this->body = $message['body'];
		$this->created_at = $message['created_at'];
        $this->authorName = $message['name'];
        $this->source = $message['source'];
        $this->sentFrom = $message['sent_from'];
		$this->avatar = $message['avatar'];
		$metaData = (array) $message['meta_data'];
		if (isset($metaData['github_action']))
		{
			$this->githubAction = $metaData['github_action'];
		}

	}

	public function formStaff()
	{
		return $this->type == "staff";
	}

	/**
	 * @return mixed
	 */
	public function getMetaData()
	{
		return $this->metaData;
	}

	/**
	 * @return mixed
	 */
	public function getAvatar()
	{
		return $this->avatar;
	}

	/**
	 * @return mixed
	 */
	public function getBody()
	{
		if ($this->type == 'staff' && $this->source =='github')
		{
			if ($this->githubAction == self::GITHUB_ISSUE_CREATED)
			{
				return "Github issue has been created";
			}
			else
			{
				return "Github issue has been closed";
			}
		}
		return $this->body;
	}

	/**
	 * @return mixed
	 */
	public function getCreatedAt()
	{
		return $this->created_at;
	}

    public function getDaysAgo()
    {
        $startTimeStamp = strtotime($this->created_at);
        $endTimeStamp = time();

        $timeDiff = abs($endTimeStamp - $startTimeStamp);

        $numberDays = $timeDiff/86400;
        $numberDays = intval($numberDays);

        return $numberDays;
    }

    public function getPastString()
    {
        $startTimeStamp = strtotime($this->created_at);
        $endTimeStamp = time();

        $timeDiff = abs($endTimeStamp - $startTimeStamp);

		$numberDays = $timeDiff/86400;
		$numberDays = intval($numberDays);

		$numberHours = $timeDiff/3600;
		$numberHours = intval($numberHours);

		if ($numberDays > 0)
		{
			return $numberDays . " days ago";
		}
		else if ($numberHours > 0)
		{
			return $numberHours . " hours ago";
		}
		else
		{
			$numberMinutes = $timeDiff/60;
			$numberMinutes = intval($numberMinutes);
			return $numberMinutes . " minutes ago";
		}
    }

	/**
	 * @return mixed
	 */
	public function getId()
	{
		return $this->id;
	}

	/**
	 * @return mixed
	 */
	public function getType()
	{
		return $this->type;
	}

	/**
	 * @return mixed
	 */
	public function getUserId()
	{
		return $this->userId;
	}

    /**
     * @return mixed
     */
    public function getAuthorName()
    {
        return $this->authorName;
    }

    /**
     * @return mixed
     */
    public function getSource()
    {
        return $this->source;
    }

    public function show()
    {
        if ($this->type == 'staff' && in_array($this->source, ['assigned', 'note']))
        {
            return false;
        }

        return true;
    }

	/**
	 * @return mixed
	 */
	public function getSentFrom()
	{
		return $this->sentFrom;
	}


}

function getUsers()
{
	$ch = curl_init();

	// set url
	curl_setopt($ch, CURLOPT_URL, "https://api.apo.io/users.json?access_token=8N88ng7M9vhDknokojinKknJKkIH9EMj99jokmvCddYrcTnMfokW03riFJ9kNKo9kK0oM98hMOj874IJVMOok9");

	//return the transfer as a string
	curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);

	// $output contains the output string
	$output = curl_exec($ch);

	$output = (array) json_decode($output);
	//	var_dump($output);
	$users = [];

	if (is_array($output) && isset($output['results']))
	{
		foreach($output['results'] as $user)
		{
			$user = new User((array) $user);
			$users[$user->getId()] = $user;
		}
	}

	curl_close($ch);

	return $users;
}

function getConversations($type, $users)
{
	$ch = curl_init();

	curl_setopt($ch, CURLOPT_URL, "https://api.apo.io/".$type.".json?access_token=8N88ng7M9vhDknokojinKknJKkIH9EMj99jokmvCddYrcTnMfokW03riFJ9kNKo9kK0oM98hMOj874IJVMOok9");
	curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);

	$output = curl_exec($ch);

	$output = (array) json_decode($output);

	$items = [];
	$totalCount = 0;

	if (is_array($output) && isset($output['results']))
	{
		$totalCount = $output["total"];

		foreach($output['results'] as $entry)
		{
			$item = new Conversation((array) $entry, $users);
			$items[] = $item;
		}
	}

	curl_close($ch);

	return [$items, $totalCount];
}