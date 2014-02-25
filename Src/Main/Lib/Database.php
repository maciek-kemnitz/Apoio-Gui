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

	protected function connect()
	{
		if(null === self::$db)
		{
			self::$db = new \PDO('mysql:host='.DATABASE_HOST.';dbname=apoio_gui', DATABASE_USER, DATABASE_PASSWORD);
			self::$db->exec("set names utf8");
		}
	}
}

