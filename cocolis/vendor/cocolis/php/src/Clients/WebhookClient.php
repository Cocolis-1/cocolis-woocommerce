<?php

/**
 * Cocolis API Class
 * API Documentation: https://doc.cocolis.fr
 * Class Documentation: https://github.com/Cocolis-1/cocolis-php/
 *
 * @author Cocolis
 */

namespace Cocolis\Api\Clients;

use Cocolis\Api\Clients\AbstractClient;

class WebhookClient extends AbstractClient
{
  public $_rest_path = 'applications/webhooks';
  public $_model_class = 'Cocolis\Api\Models\Webhook';
  public $_root_key = 'webhook';

  /**
   * Fetch the secret used to sign webhook payloads (X-Cocolis-Signature header).
   * See https://doc.cocolis.fr/docs/installation-et-utilisation/webhooks#sécurité
   *
   * @return string
   */
  public function getSigningSecret()
  {
    $response = $this->getCocolisClient()->callAuthentificated('applications/webhook_signing_secret', 'GET');
    $body = json_decode($response->text(), true);

    if (!isset($body['data']['webhook_signing_secret'])) {
      throw new \UnexpectedValueException('Unexpected response when fetching the webhook signing secret.');
    }

    return $body['data']['webhook_signing_secret'];
  }
}
