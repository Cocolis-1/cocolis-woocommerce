<?php

namespace Tests\Api;

use Cocolis\Api\Client;

class WebhookClientTest extends CocolisTest
{
  public function testCreate()
  {
    $client = $this->authenticatedClient();
    $webhook = $client->getWebhookClient()->create(['event' => 'ride_published', 'url' => 'https://www.test.com/ride_webhook', 'active' => true]);
    $this->assertNotEmpty($webhook);
    $this->assertInstanceOf('Cocolis\Api\Models\Webhook', $webhook);
    $this->assertNotNull($webhook->id);
    $this->assertEquals($webhook->id, 10);
  }

  public function testUpdate()
  {
    $client = $this->authenticatedClient();
    $webhook = $client->getWebhookClient()->update(['url' => 'https://www.test.com/ride_webhook', 'active' => true], '9');
    $this->assertNotEmpty($webhook);
    $this->assertInstanceOf('Cocolis\Api\Models\Webhook', $webhook);
    $this->assertNotNull($webhook->id);
    $this->assertEquals($webhook->id, 9);
    $this->assertEquals($webhook->event, 'offer_completed');
  }

  public function testGetAll()
  {
    $client = $this->authenticatedClient();
    $webhooks = $client->getWebhookClient()->getAll();
    $this->assertNotEmpty($webhooks);
    $this->assertCount(1, $webhooks);
    $this->assertInstanceOf('Cocolis\Api\Models\Webhook', $webhooks[0]);
  }

  public function testGet()
  {
    $client = $this->authenticatedClient();
    $webhook = $client->getWebhookClient()->get('10');
    $this->assertInstanceOf('Cocolis\Api\Models\Webhook', $webhook);
  }

  public function testRemove()
  {
    $client = $this->authenticatedClient();
    $result = $client->getWebhookClient()->remove('10');
    $this->assertEmpty($result);
  }

  public function testGetSigningSecret()
  {
    $client = $this->authenticatedClient();
    $secret = $client->getWebhookClient()->getSigningSecret();
    $this->assertNotEmpty($secret);
    $this->assertEquals('0123456789abcdef0123456789abcdef0123456789abcdef', $secret);
  }
}
