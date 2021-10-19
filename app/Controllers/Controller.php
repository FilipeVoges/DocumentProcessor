<?php

namespace App\Controllers;

use App\Model;

class Controller extends Model
{
    /**
     * @var array
     * @access protected
     */
    protected array $request;

    /**
     * @var bool
     * @access protected
     */
    protected bool $hasConn = false;

    public function __construct()
    {
        parent::__construct();

        $this->set('request', $_REQUEST);
    }
}