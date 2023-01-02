<?php
namespace TheLoop\Contracts;

interface MetaBox
{
    public function defineMetaBox();
    public function callback($object, $box);
    public function registerMetaBox();
}
