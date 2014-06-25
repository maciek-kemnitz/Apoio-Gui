<?php
/**
 * Created by JetBrains PhpStorm.
 * User: maciek
 * Date: 15.02.14
 * Time: 21:52
 * To change this template use File | Settings | File Templates.
 */

namespace Src\Main\Lib;


class Database
{
	/** @var  \PDO */
    static $db;

	/**
	 * @return User[]
	 */
	public static function getUsers()
	{
		self::connect();

		$users = [];
		$userArray = self::$db->query('SELECT * FROM user')->fetchAll();
		foreach($userArray as $data)
		{
			$data['firstname'] = $data['name'];
			$data['lastname'] = $data['surname'];
			$users[$data['id']] = new User($data);
		}



		return $users;
	}

	public static function addUser(User $user)
	{
		self::connect();
		$stm = self::$db->prepare('INSERT INTO `user` VALUES (:id,:name,:surname,:avatar)');
		$stm->execute([
			"id" => $user->getId(),
			"name" => $user->getName(),
			"surname" => $user->getSurname(),
			"avatar" => $user->getAvatar()
		]);
	}

	public static function addConversation(Conversation $conversation)
	{
		self::connect();
		$stm = self::$db->prepare('INSERT INTO `conversation` VALUES (:id, :real_owner, :message_count, :last_reply_at) ON DUPLICATE KEY UPDATE last_reply_at=VALUES(last_reply_at), message_count=VALUES(message_count)');

		$stm->execute([
			"id" => $conversation->getId(),
			"real_owner" => $conversation->getRealOwner(),
			"message_count" => $conversation->msgCount,
			"last_reply_at" => $conversation->getLastReplyAt()
		]);
	}

	/**
	 * @param $id
	 * @return mixed
	 */
	public static function  getAdditionalConversationInfoById($id)
	{
		self::connect();

		$conversationArray = self::$db->query('SELECT * FROM conversation WHERE id = '. $id)->fetch();

		return $conversationArray;
	}


	protected function connect()
	{
		if(null === self::$db)
		{
			self::$db = new \PDO('mysql:host='.DATABASE_HOST.';dbname=apoio_gui', DATABASE_USER, DATABASE_PASSWORD);
			self::$db->exec("set names utf8");
		}
	}

	/**
	 * @param $conversationId
	 * @param $type
	 * @return mixed
	 */
	public static function getReallyArrayByIdAndType($conversationId, $type)
	{
		self::connect();

		$reallyArray = self::$db->query('SELECT * FROM really WHERE conversation = '. $conversationId . ' AND type="'.$type.'"')->fetch();

		return $reallyArray;
	}

	/**
	 * @param $conversationId
	 * @param $type
	 */
	public static function addReally($conversationId, $type)
	{
		self::connect();
		$stm = self::$db->prepare('INSERT INTO `really`(`conversation`, `type`, `created_at`) VALUES (:conversation, :type, :created_at)');

		$stm->execute([
			"conversation" => $conversationId,
			"created_at" => date('Y-m-d H:i:s'),
			"type"	=> $type
		]);
	}

	/**
	 * @param $id
	 * @return mixed
	 */
	public static function deleteReally($id)
	{
		self::connect();

		$reallyArray = self::$db->query('DELETE FROM `really` WHERE id = '. $id)->fetch();

		return $reallyArray;
	}
}

