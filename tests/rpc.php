<?php

require 'lib/server.php';
require 'lib/client.php';

class RPCMethods {

  public function subtract($a, $b) {
    return $a - $b;
  }

  public function subtractNamed($params) {
    return $params['minuend'] - $params['subtrahend'];
  }

}

class RPCMethodsExtended extends Callchedan\Server {

  public function subtract($a, $b) {
    return $a - $b;
  }

  public function subtractNamed($params) {
    return $params['minuend'] - $params['subtrahend'];
  }

}

class MockTransport {
  private $server;
  
  public function __construct($methods = array()) {
    $this->server = new Callchedan\Server($methods);
  }

  public function call($request) {
    return $this->server->handle($request);
  }
}

class CallchedanTest extends PHPUnit_Framework_TestCase {

  public function testEmptyShouldReturnInvalidRequest() {
    $server = new Callchedan\Server(array());

    $this->assertEquals('{"jsonrpc":"2.0","id":null,"error":{"code":-32600,"message":"Invalid Request"}}', $server->handle(''));
  }

  public function testCallPositional() {
    $server = new Callchedan\Server(array(
      'subtract' => function ($a, $b) {
        return $a - $b;
      }
    ));

    $this->assertEquals('{"jsonrpc":"2.0","id":1,"result":19}', $server->handle('{"jsonrpc": "2.0", "method": "subtract", "params": [42, 23], "id": 1}'));
    $this->assertEquals('{"jsonrpc":"2.0","id":2,"result":-19}', $server->handle('{"jsonrpc": "2.0", "method": "subtract", "params": [23, 42], "id": 2}'));
  }

  public function testCallPositionalWithObject() {
    $methods = new RPCMethods();
    $server = new Callchedan\Server($methods);

    $this->assertEquals('{"jsonrpc":"2.0","id":1,"result":19}', $server->handle('{"jsonrpc": "2.0", "method": "subtract", "params": [42, 23], "id": 1}'));
    $this->assertEquals('{"jsonrpc":"2.0","id":2,"result":-19}', $server->handle('{"jsonrpc": "2.0", "method": "subtract", "params": [23, 42], "id": 2}'));
  }

  public function testCallPositionalWithExtended() {
    $server = new RPCMethodsExtended();

    $this->assertEquals('{"jsonrpc":"2.0","id":1,"result":19}', $server->handle('{"jsonrpc": "2.0", "method": "subtract", "params": [42, 23], "id": 1}'));
    $this->assertEquals('{"jsonrpc":"2.0","id":2,"result":-19}', $server->handle('{"jsonrpc": "2.0", "method": "subtract", "params": [23, 42], "id": 2}'));
  }

  public function testCallNamed() {
    $server = new Callchedan\Server(array(
      'subtract' => function ($params) {
        return $params['minuend'] - $params['subtrahend'];
      }
    ));

    $this->assertEquals('{"jsonrpc":"2.0","id":3,"result":19}', $server->handle('{"jsonrpc": "2.0", "method": "subtract", "params": {"subtrahend": 23, "minuend": 42}, "id": 3}'));
    $this->assertEquals('{"jsonrpc":"2.0","id":4,"result":19}', $server->handle('{"jsonrpc": "2.0", "method": "subtract", "params": {"minuend": 42, "subtrahend": 23}, "id": 4}'));
  }

  public function testCallNamedWithObject() {
    $methods = new RPCMethods();
    $server = new Callchedan\Server($methods);

    $this->assertEquals('{"jsonrpc":"2.0","id":3,"result":19}', $server->handle('{"jsonrpc": "2.0", "method": "subtractNamed", "params": {"subtrahend": 23, "minuend": 42}, "id": 3}'));
    $this->assertEquals('{"jsonrpc":"2.0","id":4,"result":19}', $server->handle('{"jsonrpc": "2.0", "method": "subtractNamed", "params": {"minuend": 42, "subtrahend": 23}, "id": 4}'));
  }

  public function testCallNamedWithExtended() {
    $server = new RPCMethodsExtended();

    $this->assertEquals('{"jsonrpc":"2.0","id":3,"result":19}', $server->handle('{"jsonrpc": "2.0", "method": "subtractNamed", "params": {"subtrahend": 23, "minuend": 42}, "id": 3}'));
    $this->assertEquals('{"jsonrpc":"2.0","id":4,"result":19}', $server->handle('{"jsonrpc": "2.0", "method": "subtractNamed", "params": {"minuend": 42, "subtrahend": 23}, "id": 4}'));
  }

  public function testNotificationShouldReturnEmpty() {
    $server = new Callchedan\Server(array());

    $this->assertEquals('', $server->handle('{"jsonrpc": "2.0", "method": "update", "params": [1,2,3,4,5]}'));
    $this->assertEquals('', $server->handle('{"jsonrpc": "2.0", "method": "foobar"}'));
  }

  public function testCallNonExistent() {
    $server = new Callchedan\Server(array());

    $this->assertEquals('{"jsonrpc":"2.0","id":10,"error":{"code":-32601,"message":"Method not found","data":"No such function"}}', $server->handle('{"jsonrpc": "2.0", "method": "foobar", "id": 10}'));
  }

  public function testCallNonExistentWithObject() {
    $methods = new RPCMethods();
    $server = new Callchedan\Server($methods);

    $this->assertEquals('{"jsonrpc":"2.0","id":10,"error":{"code":-32601,"message":"Method not found","data":"No such method"}}', $server->handle('{"jsonrpc": "2.0", "method": "foobar", "id": 10}'));
  }

  public function testCallNonExistentWithExtended() {
    $server = new RPCMethodsExtended();

    $this->assertEquals('{"jsonrpc":"2.0","id":10,"error":{"code":-32601,"message":"Method not found","data":"No such method"}}', $server->handle('{"jsonrpc": "2.0", "method": "foobar", "id": 10}'));
  }

  public function testCallInvalidJSON() {
    $server = new Callchedan\Server(array());

    $this->assertEquals('{"jsonrpc":"2.0","id":null,"error":{"code":-32600,"message":"Invalid Request"}}', $server->handle('{"jsonrpc": "2.0", "method": "foobar", "params": "bar", "baz"]'));
  }

  public function testCallInvalidJSONRPC() {
    $server = new Callchedan\Server(array());

    $this->assertEquals('[{"jsonrpc":"2.0","id":null,"error":{"code":-32600,"message":"Invalid Request","data":"Not an array"}},{"jsonrpc":"2.0","id":null,"error":{"code":-32600,"message":"Invalid Request","data":"Not an array"}},{"jsonrpc":"2.0","id":null,"error":{"code":-32600,"message":"Invalid Request","data":"Not an array"}}]', $server->handle('[1,2,3]'));
    $this->assertEquals('{"jsonrpc":"2.0","id":null,"error":{"code":-32600,"message":"Invalid Request","data":"Invalid method"}}', $server->handle('{"jsonrpc": "2.0", "method": 1, "params": "bar"}'));
  }

  /**
   * @expectedException        Exception
   * @expectedExceptionMessage Invalid JSON-RPC method
   */
  public function testClientInvalidMethod() {
    $trp = new MockTransport();
    $client = new Callchedan\Client($trp);
    $client->call(2);
  }

  /**
   * @expectedException        Exception
   * @expectedExceptionMessage Invalid JSON-RPC params
   */
  public function testClientInvalidParams() {
    $trp = new MockTransport();
    $client = new Callchedan\Client($trp);
    $client->call('method', 2);
  }

  /**
   * @expectedException        Exception
   * @expectedExceptionMessage Invalid JSON-RPC server URL
   */
  public function testClientInvalidURL() {
    $client = new Callchedan\Client('');
  }

  /**
   * @expectedException        Exception
   * @expectedExceptionMessage Invalid JSON-RPC server URL scheme
   */
  public function testClientInvalidURLScheme() {
    $client = new Callchedan\Client('invalid://example.com');
  }

  /**
   * @expectedException        Exception
   * @expectedExceptionMessage Method not found
   * @expectedExceptionCode    -32601
   */
  public function testClientCallNonExistent() {
    $trp = new MockTransport();
    $client = new Callchedan\Client($trp);

    $client->call('noSuchMethod');
  }

  /**
   * @expectedException        Exception
   * @expectedExceptionMessage Server error 
   * @expectedExceptionCode    -32000
   */
  public function testClientCallServerError() {
    $trp = new MockTransport(array(
      'errorMethod' => function ()
      {
        throw new Exception("Some Error");
      }
    ));
    $client = new Callchedan\Client($trp);

    $client->call('errorMethod');
  }

  public function testClientCallReturnResult() {
    $trp = new MockTransport(array(
      'subtract' => function ($a, $b) {
        return $a - $b;
      }
    ));
    $client = new Callchedan\Client($trp);

    $this->assertEquals(19, $client->call('subtract', array(42, 23)));
  }

  public function testClientNotify() {
    $trp = new MockTransport();
    $client = new Callchedan\Client($trp);

    $this->assertEquals(NULL, $client->notify('method'));
  }

  public function testClientCallReturnResultBatch() {
    $trp = new MockTransport(array(
      'subtract' => function ($a, $b) {
        return $a - $b;
      }
    ));
    $client = new Callchedan\Client($trp);
    $id = $client->addCall('subtract', array(42, 23));
    
    $result = $client->batchRequest();

    $this->assertEquals($id, $result[0]['id']);
    $this->assertEquals(19, $result[0]['result']);
  }

}