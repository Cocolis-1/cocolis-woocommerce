<?php

declare(strict_types=1);

use Cocolis\Api\Version;

final class VersionTest extends \Tests\Api\CocolisTest
{
  public function testCanBeUsedAsString(): void
  {
    $json = json_decode(file_get_contents('./composer.json'));
    $this->assertEquals(
      $json->version,
      (string) new Version()
    );
  }
}
