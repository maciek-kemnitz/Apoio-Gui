<?php
/**
 * Created by JetBrains PhpStorm.
 * User: maciek
 * Date: 16.02.14
 * Time: 15:14
 * To change this template use File | Settings | File Templates.
 */

namespace Src\Main\Lib;


class Attachment
{
    public $type;
    public $url;
    public $filename;

    public function __construct(array $data)
    {
        $this->url = $data['url'];
        $this->type = $data['type'];
        $this->filename = $data['filename'];
    }

    /**
     * @return mixed
     */
    public function getFilename()
    {
        return $this->filename;
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
    public function getUrl()
    {
        return $this->url;
    }
}