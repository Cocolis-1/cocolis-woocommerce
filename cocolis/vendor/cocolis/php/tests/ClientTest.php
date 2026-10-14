<?php

namespace Tests\Api;

use Cocolis\Api\Client;
use InvalidArgumentException;
use Tests\Api\CocolisTest;

class ClientTest extends CocolisTest
{
  public function testEmptyClient()
  {
    $client = Client::getClient([
      'app_id' => 'e0611906',
      'password' => 'sebfie',
      'live' => false
    ]);
    $this->assertNotEmpty($client);
  }


  public function testContent()
  {
    $this->assertEquals('https://sandbox-api.cocolis.fr/api/v1/', Client::API_SANDBOX);
    $this->assertEquals('https://api.cocolis.fr/api/v1/', Client::API_PROD);
  }

  public function testClient()
  {
    $client = Client::create([
      'app_id' => 'e0611906',
      'password' => 'sebfie',
      'live' => false
    ]);
    $this->assertNotEmpty($client);
  }


  public function testSignInWithWrongCredentials()
  {
    $this->expectException(\Cocolis\Api\Errors\UnauthorizedException::class);

    $client = Client::create([
      'app_id' => 'e0611906',
      'password' => 'notsebfie',
      'live' => false
    ]);
    $result = $client->signIn();
    $this->assertEquals($result, false);
  }

  public function testStaticCreate()
  {
    $client = Client::create([
      'app_id' => 'e0611906',
      'password' => 'sebfie',
      'live' => false
    ]);
    $result = $client->signIn();
    $this->assertEquals([
      'access-token' => 'pq915g6fXYsZwByHh2Tr7g',
      'client' => 'FdTizxGOBSm9fXrkShOVdw',
      'expiry' => '1643332505',
      'uid' => 'e0611906'
    ], $result);
  }

  public function testAppIdException()
  {
    $this->expectException(InvalidArgumentException::class);
    $this->expectExceptionMessage('Key app_id is missing');
    Client::create([
      'app_id' => '',
      'password' => 'test'
    ]);
  }

  public function testPasswordException()
  {
    $this->expectException(InvalidArgumentException::class);
    $this->expectExceptionMessage('Key password is missing');
    Client::create([
      'app_id' => 'test',
      'password' => ''
    ]);
  }

  public function testTokenValid()
  {
    $client = Client::create([
      'app_id' => 'e0611906',
      'password' => 'sebfie',
      'live' => false
    ]);
    $result = $client->validateToken([
      'access-token' => 'nBfCK2mL3rp83hSjhUFLYg',
      'client' => 'wFPP7k01OacAzC-tXni-fA',
      'expiry' => '1604318220',
      'uid' => 'e0611906'
    ]);
    $this->assertEquals($result, true);
  }

  public function testTokenNoArgs()
  {
    $client = Client::create([
      'app_id' => 'e0611906',
      'password' => 'sebfie',
      'live' => false
    ]);
    // Clear auth
    $client->setAuth(null);

    // No arguments
    $this->expectException(InvalidArgumentException::class);
    $client->validateToken();
  }


  public function testTokenInvalid()
  {
    $this->expectException(\Cocolis\Api\Errors\UnauthorizedException::class);
    $this->expectExceptionMessage('{"success":false,"errors":["Mot de passe ou identifiant invalide."]}');

    $client = Client::create([
      'app_id' => 'e0611906',
      'password' => 'sebfie',
      'live' => false
    ]);

    // Invalid params
    $result = $client->validateToken(["uid" => "e0611906", "access-token" => "thisisnotavalidtoken", "client" => "HLSmEW1TIDqsSMiwuKjnQg", "expiry" => "1590748027"]);
    $this->assertEquals($result, false);
  }

  public function testTokenNoAuth()
  {
    $client = Client::create([
      'app_id' => 'e0611906',
      'password' => 'sebfie',
      'live' => false
    ]);
    $client->signIn();
    $result = $client->validateToken();
    $this->assertNotEmpty($result);
  }

  public function testApiKey()
  {
    $client = Client::create(
      [
        'api_key' => 'd699daaeeb9a4303bee8dd6d3652ee05',
        'live' => false
      ]
    );
    $rides = $client->getRideClient()->mine();
    $this->assertNotEmpty($rides);
  }


  public function testApiKeyException()
  {
    $this->expectException(InvalidArgumentException::class);
    $this->expectExceptionMessage('Key api_key is missing or your app_id and password are missing');
    Client::create([
      'api_key' => '',
      'live' => false
    ]);
  }
}
