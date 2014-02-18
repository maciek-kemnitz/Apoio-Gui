<?php
/**
 * Created by JetBrains PhpStorm.
 * User: maciek
 * Date: 15.02.14
 * Time: 21:52
 * To change this template use File | Settings | File Templates.
 */

namespace Src\Main\Lib;


class ApoioClient
{
    const ACCESS_TOKEN = "8N88ng7M9vhDknokojinKknJKkIH9EMj99jokmvCddYrcTnMfokW03riFJ9kNKo9kK0oM98hMOj874IJVMOok9";
    const ACCESS_POINT_USERS = "users.json";
    const ACCESS_POINT_INBOX = "inbox.json";
    const ACCESS_POINT_SEARCH = "search.json";
    const ACCESS_POINT_CONVERSATIONS = "conversations";
    const ACCESS_POINT_ARCHIVE = "archive.json";
    const ACCESS_POINT_API = "https://api.apo.io/";

    /** @var  User[] */
    public static $users;

    /**
     * @param $type
     * @param $users
     * @param int $page
     * @return Conversation[]
     */
    public static function getConversations($type, $users, $page=1)
    {
        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, self::ACCESS_POINT_API . $type . "?access_token=" . self::ACCESS_TOKEN . "&page=" . $page);
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

    /**
     * @param $type
     * @param $users
     * @param int $page
     * @return Conversation[]
     */
    public static function getConversationsByQuery($query, $users, $page=1)
    {
        $ch = curl_init();

        curl_setopt($ch, CURLOPT_URL, self::ACCESS_POINT_API . self::ACCESS_POINT_SEARCH . "?access_token=" . self::ACCESS_TOKEN . "&query=". $query ."&page=" . $page);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
//var_dump(self::ACCESS_POINT_API . self::ACCESS_POINT_SEARCH . "?access_token=" . self::ACCESS_TOKEN . "&query=". $query ."&page=" . $page);
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

    /**
     * @return User[]
     */
    public static function getUsers()
    {
        if (self::$users)
        {
            return self::$users;
        }

        $ch = curl_init();
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
        curl_setopt($ch, CURLOPT_URL, self::ACCESS_POINT_API . self::ACCESS_POINT_USERS . "?access_token=" .self::ACCESS_TOKEN);
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

        curl_close($ch);

        return $users;
    }

    /**
     * @param $id
     * @return Conversation|null
     */
    public static function getConversationById($id)
    {
        $conversation = null;
        $users = self::getUsers();

        $ch = curl_init();

        curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
        curl_setopt($ch, CURLOPT_URL, self::ACCESS_POINT_API . self::ACCESS_POINT_CONVERSATIONS . "/{$id}.json?access_token=" . self::ACCESS_TOKEN);

        $output = curl_exec($ch);

        $output = (array) json_decode($output);

        if (is_array($output))
        {
            $conversation = new Conversation((array) $output, $users);
        }

        curl_close($ch);

        return $conversation;
    }
}

