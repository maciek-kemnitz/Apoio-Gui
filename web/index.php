<?php
require_once __DIR__.'/../vendor/autoload.php';

$app = new Silex\Application();
$app['debug'] = true;

$app->register(new Silex\Provider\TwigServiceProvider(), array(
	'twig.path' => __DIR__.'/../Src/Views',
));

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
});

$app->get('/conversation/{id}', function ($id) use ($app)
{
	$ch = curl_init();

	// set url
	curl_setopt($ch, CURLOPT_URL, "http://api.apo.io/conversations/{$id}.json?access_token=8N88ng7M9vhDknokojinKknJKkIH9EMj99jokmvCddYrcTnMfokW03riFJ9kNKo9kK0oM98hMOj874IJVMOok9");

	//return the transfer as a string
	curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);

	// $output contains the output string
	$output = curl_exec($ch);

	$output = (array) json_decode($output);
//		var_dump($output);

	if (is_array($output))
	{

		$conversation = new Conversation((array) $output);

		$messages = $conversation->getMessages();
		echo "<strong>{$conversation->getSubject()}</strong><br>";

		foreach($messages as $message)
		{
			if ($message->getBody())
			{
				echo "
				<div>
				id: {$message->getId()}
				<br>
				body: {$message->getBody()}
				</div>
				<hr>
			";
			}

		}
	}


	curl_close($ch);

	return 'Hello ';
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

	public function __construct(array $message)
	{
		$this->id = $message['id'];
		$this->type = $message['type'];
		$this->userId = $message['user_id'];
		$this->body = $message['body'];
		$this->created_at = $message['created_at'];

//		if ($this->type == 'staff' && $this->source == 'assigned')
//		{
//
//		}
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


}