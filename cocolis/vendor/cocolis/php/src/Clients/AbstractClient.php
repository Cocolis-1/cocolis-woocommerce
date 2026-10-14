<?php

/**
 * Cocolis API Class
 * API Documentation: https://doc.cocolis.fr
 * Class Documentation: https://github.com/Cocolis-1/cocolis-php/
 *
 * @author Cocolis
 */

namespace Cocolis\Api\Clients;

use Cocolis\Api\Client;
use Exception;
use PhpCsFixer\ConfigurationException\InvalidConfigurationException;

abstract class AbstractClient
{
  private $_cocolis_client;
  public $_rest_path;
  public $_root_key;

  public function __construct(\Cocolis\Api\Client $cocolisClient)
  {
    $this->_cocolis_client = $cocolisClient;
  }

  public function hydrate(array $array, $transformToModel = true)
  {
    $transformedToStdClass = json_decode(json_encode($array));
    if ($transformToModel) {
      if (is_array($transformedToStdClass)) {
        $result = [];
        foreach ($transformedToStdClass as $item) {
          array_push($result, new $this->_model_class($item, $this));
        }
      }
      if (is_object($transformedToStdClass)) {
        $result = new $this->_model_class($transformedToStdClass, $this);
      }
    } else {
      $result = $transformedToStdClass;
    }
    return $result;
  }

  public function getCocolisClient()
  {
    return $this->_cocolis_client;
  }

  public function getBaseURL()
  {
    return Client::isLive() ? Client::FRONTEND_PROD : Client::FRONTEND_SANDBOX;
  }

  public function getRestPath($path)
  {
    if (empty($this->_rest_path)) {
      throw new InvalidConfigurationException('The child class shoud defined $_rest_path');
    }

    return $this->_rest_path . '/' . $path;
  }

  public function create(array $params)
  {
    if ($this->_root_key) {
      $paramsTemp = [];
      $paramsTemp[$this->_root_key] = $params;
      $params = $paramsTemp;
    }
    return $this->hydrate(json_decode($this->getCocolisClient()->callAuthentificated($this->getRestPath(''), 'POST', $params)->text(), true));
  }

  public function update(array $params, string $id)
  {
    if ($this->_root_key) {
      $paramsTemp = [];
      $paramsTemp[$this->_root_key] = $params;
      $params = $paramsTemp;
    }
    return $this->hydrate(json_decode($this->getCocolisClient()->callAuthentificated($this->getRestPath('/') . $id, 'PUT', $params)->text(), true));
  }

  public function getAll()
  {
    return $this->hydrate(json_decode($this->getCocolisClient()->callAuthentificated($this->getRestPath(''), 'GET')->text(), true));
  }

  public function get(string $id)
  {
    return $this->hydrate(json_decode($this->getCocolisClient()->callAuthentificated($this->getRestPath('/' . $id), 'GET')->text(), true));
  }

  public function remove(string $id)
  {
    return json_decode($this->getCocolisClient()->callAuthentificated($this->getRestPath('') . $id, 'DELETE')->text(), true);
  }

  public function notSupported()
  {
    throw new Exception('This feature is not accessible in this Class');
  }
}
