<?php

namespace TheLoop\ServiceContainer;

use LeadpagesWP\Lib\Pimple\Container;

class ServiceContainer
{
    public $ioc;

    public function __construct()
    {
        $this->ioc = new Container();

    }

    public function getContainer()
    {
        return $this->ioc;
    }
}

