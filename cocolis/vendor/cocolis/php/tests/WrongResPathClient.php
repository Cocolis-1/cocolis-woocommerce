<?php

namespace Tests\Api;

use Cocolis\Api\Clients\AbstractClient;

class WrongResPathClient extends AbstractClient
{
  public function getWrongPath()
  {
    return $this->getRestPath('test');
  }
}
