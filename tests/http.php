<?php

require 'lib/client.php';

class CallchedanHTTPTest extends PHPUnit_Framework_TestCase {

  public function testPositionalShouldReturnResult() {

    $client = new Callchedan\Client('http://php.loc/callchedan/tests/server/server.php');
    $this->assertEquals(19, $client->call('subtract', array(42, 23)));
    
  }

  public function testNamedShouldReturnResult() {

    $client = new Callchedan\Client('http://php.loc/callchedan/tests/server/server.php');
    $this->assertEquals(19, $client->call('subtractNamed', array('minuend' => 42, 'subtrahend' => 23)));
    
  }

}