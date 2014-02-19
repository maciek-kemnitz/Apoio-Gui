<?php
/**
 * Created by JetBrains PhpStorm.
 * User: mkemnitz
 * Date: 19.02.14
 * Time: 14:59
 * To change this template use File | Settings | File Templates.
 */

namespace Src\Main\Lib;


class ListHelper
{
	public $inboxList;
	public $archiveList;
	public $searchList;
	public $inboxCount;
	public $archiveCount;
	public $searchCount;
	public $users;

	public function __construct($users)
	{
		$this->users = $users;
	}

	public function getList()
	{
		if (null !== $this->searchList && count($this->searchList) > 0)
		{
			return $this->searchList;
		}
		elseif (count($this->inboxList) > 0)
		{
			return $this->inboxList;
		}

		return $this->archiveList;
	}

	public function getInboxCount()
	{
		return ceil($this->inboxCount/30);
	}

	public function getArchiveCount()
	{
		return ceil($this->archiveCount/30);
	}

	public function getTotalCount()
	{
		if ($this->searchList)
		{
			return $this->searchCount;
		}

		return $this->inboxCount + $this->archiveCount;
	}

	public function getSearchCount()
	{
		return ceil($this->searchCount/30);
	}

	public function initMixedList()
	{
		list($this->inboxList, $this->inboxCount) = ApoioClient::getConversations(\Src\Main\Lib\ApoioClient::ACCESS_POINT_INBOX, $this->users);
		list($this->archiveList, $this->archiveCount) = ApoioClient::getConversations(\Src\Main\Lib\ApoioClient::ACCESS_POINT_ARCHIVE, $this->users);
	}

	public function initSearchList($search)
	{
		list($this->searchList, $this->searchCount) = \Src\Main\Lib\ApoioClient::getConversationsByQuery($search, $this->users);
	}

}