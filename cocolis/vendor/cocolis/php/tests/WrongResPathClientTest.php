<?php

namespace Tests\Api;

use Cocolis\Api\Client;
use PhpCsFixer\ConfigurationException\InvalidConfigurationException;
use Tests\Api\WrongResPathClient;

class WrongResPathClientTest extends CocolisTest
{
  public function testPath()
  {
    $this->expectException(InvalidConfigurationException::class);
    $this->expectExceptionMessage('The child class shoud defined $_rest_path');
    $client = new Client();
    $path = new WrongResPathClient($client->getClient());
    $path->getWrongPath();
  }
}
