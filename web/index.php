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

$app->get('/', function () use ($app)
{
	$ch = curl_init();

	// set url
	curl_setopt($ch, CURLOPT_URL, "http://api.apo.io/users.json?access_token=8N88ng7M9vhDknokojinKknJKkIH9EMj99jokmvCddYrcTnMfokW03riFJ9kNKo9kK0oM98hMOj874IJVMOok9");

	//return the transfer as a string
	curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);

	// $output contains the output string
	$output = curl_exec($ch);

	$output = (array) json_decode($output);
//	var_dump($output);
    $users = [];
    $gravatar = new \emberlabs\GravatarLib\Gravatar();

    $gravatar->setDefaultImage('mm');
    $gravatar->setAvatarSize(150);

    $gravatar->setMaxRating('pg');

    $app['twig']->addGlobal('gravatar', $gravatar);
    if (is_array($output) && isset($output['results']))
    {
        foreach($output['results'] as $user)
        {
            $user = new User((array) $user);
            $users[$user->getId()] = $user;
        }
    }

    curl_setopt($ch, CURLOPT_URL, "http://api.apo.io/inbox.json?access_token=8N88ng7M9vhDknokojinKknJKkIH9EMj99jokmvCddYrcTnMfokW03riFJ9kNKo9kK0oM98hMOj874IJVMOok9");

    $output = curl_exec($ch);

    $output = (array) json_decode($output);

    $items = [];

	if (is_array($output) && isset($output['results']))
	{
		foreach($output['results'] as $entry)
		{
//			var_dump((array) $entry);
			$item = new Conversation((array) $entry, $users);
			$items[] = $item;
//			echo '
//			<div>
//			<a href="/conversation/'.$item->getId().'">
//			'.$item->getSubject().'
//			</a>
//			</div>
//			';
//			echo "<hr>";
		}
	}


	curl_close($ch);

	return $app['twig']->render('list.page.html.twig', ["items" => $items, "users" => $users]);
})
->bind('homepage');

$app->get('/conversation/{id}', function ($id) use ($app)
{
	$ch = curl_init();

    // set url
    curl_setopt($ch, CURLOPT_URL, "http://api.apo.io/users.json?access_token=8N88ng7M9vhDknokojinKknJKkIH9EMj99jokmvCddYrcTnMfokW03riFJ9kNKo9kK0oM98hMOj874IJVMOok9");

    //return the transfer as a string
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);

    // $output contains the output string
    $output = curl_exec($ch);

    $output = (array) json_decode($output);

    $users = [];

    if (is_array($output) && isset($output['results']))
    {
        foreach($output['results'] as $user)
        {
            $user = new User((array) $user);
            $users[$user->getId()] = $user;
        }
    }

	// set url
	curl_setopt($ch, CURLOPT_URL, "http://api.apo.io/conversations/{$id}.json?access_token=8N88ng7M9vhDknokojinKknJKkIH9EMj99jokmvCddYrcTnMfokW03riFJ9kNKo9kK0oM98hMOj874IJVMOok9");

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

	curl_setopt($ch, CURLOPT_URL, "http://api.apo.io/inbox.json?access_token=token");
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

$app->match('/login', function (\Symfony\Component\HttpFoundation\Request $request) use ($app)
{

    $params = [];

    if ($request->isMethod('POST'))
    {
        $email = $request->request->get("email");

        if (null === $email)
        {
            return new \Symfony\Component\HttpFoundation\Response("Is it so difficult to give us an email?", 500);
        }

        $emailChunk = strstr($email, '@');

        if ($emailChunk !== '@docplanner.com')
        {
            $params['error'] = "Use your docplanner.com email account";
        }
        else
        {
            $_SESSION['user_email'] = $email;

            $url = $app['url_generator']->generate('homepage');
            return new \Symfony\Component\HttpFoundation\RedirectResponse($url);
        }
    }

    return $app['twig']->render('login.page.html.twig', $params);
})
->method("GET|POST");

if (!isset($_SESSION['user_email']) && $_SERVER["REQUEST_URI"] != '/login')
{
    header( 'Location: http://local.apoio-gui.pl/login' ) ;
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
	public $id;
	public $type;
	public $userId;
	public $body;
	public $created_at;
	public $source;
    public $authorName;

	public function __construct(array $message)
	{

		$this->id = $message['id'];
		$this->type = $message['type'];
		$this->userId = $message['user_id'];
		$this->body = $message['body'];
		$this->created_at = $message['created_at'];
        $this->authorName = $message['name'];
        $this->source = $message['source'];
	}

	/**
	 * @return mixed
	 */
	public function getBody()
	{
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
        $numberHours = $timeDiff/3600;


        if ($numberDays > 0)
        {
            return $numberDays . " days ago";
        }
        else if ($numberHours > 0)
        {
            return $numberDays . " hours ago";
        }
        else
        {
            $numberMinuets = $timeDiff/60;
            return $numberDays . " minuets ago";
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
        if ($this->type == 'staff' && in_array($this->source, ['assigned', 'github', 'note']))
        {
            return false;
        }

        return true;
    }
}