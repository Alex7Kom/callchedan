<?php

require '../../lib/server.php';

$methods = array(
  'subtract' => function ($a, $b) {
    return $a - $b;
  },
  'subtractNamed' => function ($params) {
    return $params['minuend'] - $params['subtrahend'];
  }
);

$server = new Callchedan\Server($methods);
echo $server->handle();