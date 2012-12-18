<?php

interface Ctct_HttpRequest
{
    public function setOption($name, $value);
    public function execute();
    public function error();
    public function getInfo($name);
    public function close();
}
