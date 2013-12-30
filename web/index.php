<?php
require_once __DIR__.'/../vendor/autoload.php';

$app = new Silex\Application();

$app->get('/hello/{name}', function ($name) use ($app)
{
	$ch = curl_init();

	// set url
	curl_setopt($ch, CURLOPT_URL, "http://api.apo.io/inbox.json?access_token=8N88ng7M9vhDknokojinKknJKkIH9EMj99jokmvCddYrcTnMfokW03riFJ9kNKo9kK0oM98hMOj874IJVMOok9");

	//return the transfer as a string
	curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);

	// $output contains the output string
	$output = curl_exec($ch);

	$output = (array) json_decode($output);
//	var_dump($output);

	if (is_array($output) && isset($output['results']))
	{
		foreach($output['results'] as $entry)
		{
//			var_dump((array) $entry);
			$item = new ApoioListItem((array) $entry);
			echo '
			<div>
			<a href="/conversation/'.$item->getId().'">
			'.$item->getSubject().'
			</a>
			</div>
			';
			echo "<hr>";
		}
	}


	curl_close($ch);

	return 'Hello '.$app->escape($name);
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





class ApoioListItem
{
	public $id;
	public $name;
	public $subject;

	public function __construct(array $data)
	{
		$this->id = $data['id'];
		$this->name = $data['name'];
		$this->subject = $data['subject'];
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
	public $subject;
	public $abstract;
	public $messages;

	public function __construct(array $data)
	{
		$this->id = $data['id'];
		$this->subject = $data['subject'];
		$this->abstract = $data['abstract'];
		$this->setMessages((array) $data['messages']);
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