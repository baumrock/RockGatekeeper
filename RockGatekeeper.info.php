<?php

namespace ProcessWire;

$info = [
  'title' => 'RockGatekeeper',
  'version' => json_decode(file_get_contents(__DIR__ . "/package.json"))->version,
  'summary' => 'Prevent access to your site without password (eg for staging)',
  'autoload' => true,
  'singular' => true,
  'icon' => 'check',
  'requires' => [
    'PHP>=8.1',
  ],
];
