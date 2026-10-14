<?php

require_once __DIR__ . '/vendor/autoload.php';

\VCR\VCR::configure()
  ->enableRequestMatchers(['method', 'url', 'host'])
  ->setMode('once');
