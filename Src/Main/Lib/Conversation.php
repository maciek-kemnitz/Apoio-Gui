<?php
/**
 * Created by JetBrains PhpStorm.
 * User: maciek
 * Date: 15.02.14
 * Time: 21:29
 * To change this template use File | Settings | File Templates.
 */

namespace Src\Main\Lib;


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
    /** @var  \Src\Main\Lib\User[] */
    public $users;

    public $lastMessageId;
	public $msgCount;

    public $readBy;
	public $realOwner;



    public function __construct(array $data, $users)
    {
        $this->id = $data['id'];
        $this->author 	= $data['name'];
        $this->subject = $data['subject'];
        $this->abstract = $data['abstract'];
        $this->setMessages((array) @$data['messages']);

		$state 		= (array) $data['state'];
		$this->status 	= $state['state'];

		$this->created_at = $data['created_at'];
        $this->updated_at = $data['updated_at'];
        $this->assigned_to_id = isset($data['assigned_to_id']) ? $data['assigned_to_id'] : null;
        $this->last_reply_at = $data['last_reply_at'];
        $this->users = $users;
        $this->readBy = (array) $data['read_by'];
    }

	public function isArchived()
	{
		return $this->status == "closed";
	}

	/**
	 * @return mixed
	 */
	public function getRealOwner()
	{
		return $this->realOwner;
	}

    /**
     * @return array
     */
    public function getReadBy()
    {
        return $this->readBy;
    }

    public function hasBeenRead()
    {
        return count($this->readBy) > 0;
    }

    protected function setMessages(array $messages)
    {
		$count = 1;
        foreach($messages as $messageData)
        {
            $message = new \Src\Main\Lib\Message((array) $messageData);
            $this->messages[] = $message;

			if ($count == 1)
			{
				$this->realOwner = $message->getAuthorName();
			}

            if ($message->messageId)
            {
                $this->lastMessageId = $message->messageId;
            }

			$count++;
        }
    }

    /**
     * @return mixed
     */
    public function getLastMessageId()
    {
        return $this->lastMessageId;
    }

	/**
	 * @return mixed
	 */
	public function getMsgCount()
	{
		return $this->msgCount;
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

        return "Front Office";
    }

    public function getAssignedToAvatar()
    {
        $user = $this->users[$this->assigned_to_id];

		if ($user)
		{
			return $user->getAvatar();
		}
		else
		{
			return 'img/house.gif';
		}
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