<?php
/**
 * Created by JetBrains PhpStorm.
 * User: maciek
 * Date: 15.02.14
 * Time: 21:28
 * To change this template use File | Settings | File Templates.
 */

namespace Src\Main\Lib;


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
    public $emailyakId;

    /** @var  Attachment[] */
    public $attachments;

    public function __construct(array $message)
    {
//        var_dump($message);
        $this->id = $message['id'];
        $this->type = $message['type'];
        $this->userId = isset($message['user_id']) ? $message['user_id'] : null;
        $this->body = $message['body'];
        $this->created_at = $message['created_at'];
        $this->authorName = $message['name'];
        $this->source = $message['source'];
        $this->sentFrom = $message['sent_from'];
        $this->avatar = isset($message['avatar']) ? $message['avatar'] : null;
        $metaData = (array) $message['meta_data'];
        if (isset($metaData['github_action']))
        {
            $this->githubAction = $metaData['github_action'];
        }

        if (isset($message['attachments']))
        {
            foreach($message['attachments'] as $attachment)
            {
                $this->attachments[] = new Attachment((array) $attachment);
            }
        }

//        if (isset($message['emailyak_id']))
//        {
//            $this->emailyakId = $message['emailyak_id'];
//        }

    }

    public function hasAttachments()
    {
        return count($this->attachments) > 0;
    }

    /**
     * @return \Src\Main\Lib\Attachment[]
     */
    public function getAttachments()
    {
        return $this->attachments;
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
