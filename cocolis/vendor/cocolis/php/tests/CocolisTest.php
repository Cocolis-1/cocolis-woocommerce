<?php

namespace Tests\Api;

use PHPUnit\Framework\TestCase;
use Cocolis\Api\Client;

class CocolisTest extends TestCase
{
  /**
   * @before
   */
  public function startVcr()
  {
    \VCR\VCR::turnOn();

    // Record requests and responses in cassette file 'example'
    $cassette_name = strtolower(str_replace('\\', '_', get_class($this))) . '/' . $this->getName();
    \VCR\VCR::insertCassette($cassette_name);
  }

  /**
   * @after
   */
  public function EndVcr()
  {
    $cassette_name = strtolower(str_replace('\\', '_', get_class($this))) . '/' . $this->getName();
    // To stop recording requests, eject the cassette
    \VCR\VCR::eject();

    // Turn off VCR to stop intercepting requests
    \VCR\VCR::turnOff();
  }

  public function authenticatedClient($runSignIn = true)
  {
    $client = Client::create([
      'app_id' => 'e0611906',
      'password' => 'sebfie',
      'live' => false
    ]);
    if ($runSignIn) {
      $client->signIn();
    }
    return $client;
  }
}
