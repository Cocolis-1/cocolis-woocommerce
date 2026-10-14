<?php

/**
 * Cocolis API Class
 * API Documentation: https://doc.cocolis.fr
 * Class Documentation: https://github.com/Cocolis-1/cocolis-php/
 *
 * @author Cocolis
 */

namespace Cocolis\Api\Clients;

use Exception;

class RideClient extends AbstractClient
{
  public $_rest_path = 'rides';
  public $_model_class = 'Cocolis\Api\Models\Ride';
  public $_root_key = 'ride';

  public function mine()
  {
    return $this->hydrate(json_decode($this->getCocolisClient()->callAuthentificated($this->getRestPath('mine'))->text(), true)["rides"]);
  }

  public function canMatch($zipfrom, $zipto, $volume, $value = null)
  {
    $params = ['from' => ['postal_code' => $zipfrom], 'to' => ['postal_code' => $zipto], 'volume' => $volume];
    if (!empty($value)) {
      $params['content_value'] = $value;
    }

    return $this->hydrate(json_decode(
      $this->getCocolisClient()->callAuthentificated(
        $this->getRestPath('can_match'),
        'POST',
        $params
      )->text(),
      true
    ), false);
  }

  public function update(array $params, string $id)
  {
    return $this->notSupported();
  }

  public function getAll()
  {
    return $this->notSupported();
  }
}
