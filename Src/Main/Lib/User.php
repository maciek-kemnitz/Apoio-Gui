<?php

namespace Src\Main\Lib;

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