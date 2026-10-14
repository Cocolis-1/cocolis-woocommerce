<?php

/*
 * (c) Cocolis <it@cocolis.fr>
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

namespace Cocolis\Api;

class Version
{
  public function __toString(): string
  {
    $json = json_decode(file_get_contents('./composer.json'));
    return $json->version;
  }
}
