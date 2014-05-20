# Callchedan [kɔːlʧedan]

Simple (not to say primitive) and lightweight transport-agnostic client and server implementations of [JSON-RPC 2.0](http://www.jsonrpc.org/specification) (only) protocol for PHP.

# Usage

## Server

You can use closures to pass methods to the server (recommended):

```php
require 'callchedan/lib/server.php';

$methods = array(
  'subtract' => function ($a, $b) {
    return $a - $b;
  }
);

$server = new Callchedan\Server($methods);
echo $server->handle();
```

Or you can pass an object:

```php
require 'callchedan/lib/server.php';

class RPCMethods {

  public function subtract($a, $b) {
    return $a - $b;
  }

}

$methods = new RPCMethods();
$server = new Callchedan\Server($methods);
echo $server->handle();
```

Or you can extend the server class:

```php
require 'callchedan/lib/server.php';

class RPCServer extends Callchedan\Server {

  public function subtract($a, $b) {
    return $a - $b;
  }

}

$server = new RPCServer();
echo $server->handle();
```

By default the server reads raw POST input, but you can pass to it JSON string:

```php
echo $server->handle('{"jsonrpc": "2.0", "method": "subtract", "params": [42, 23], "id": 1}');
```

If request is not a notification, than `handle` method returns result JSON string, that you can `echo` right away or do whatever you want with it to transfer it to a client.

## Client

Initialize the client:

```php
require 'callchedan/lib/client.php';

$client = new Callchedan\Client('http://127.0.0.1:5080/');
```

Instead of URL you can pass a transport object that will be used to make calls to the server.

# Methods

## Server

### _string_ handle([_string_ $JSON])

## Client

### _mixed_ call(_string_ $method[, _array_ $params])
### notify(_string_ $method[, _array_ $params])

Immediate call to the server. Notification will not return any result. Any errors will be thrown as exceptions.

### _string_ addCall(_string_ $method[, _array_ $params])
### addNotification(_string_ $method[, _array_ $params])
### _array_ batchRequest()

Add calls or notifications to a batch, than do `batchRequest` to send the batch to the server.
`addCall` returns `id` that you can use to handle the result of `batchRequest`.

# Tests

Surprisingly enough, there are some tests. Use [PHPUnit framework](http://phpunit.de/) to run them.

Simple tests of the library logic:

```
phpunit tests\rpc
```

# License

The MIT License (MIT)

Copyright (c) 2014 Alexey Komarov <alex7kom@gmail.com>

Permission is hereby granted, free of charge, to any person obtaining a copy of
this software and associated documentation files (the "Software"), to deal in
the Software without restriction, including without limitation the rights to
use, copy, modify, merge, publish, distribute, sublicense, and/or sell copies of
the Software, and to permit persons to whom the Software is furnished to do so,
subject to the following conditions:

The above copyright notice and this permission notice shall be included in all
copies or substantial portions of the Software.

THE SOFTWARE IS PROVIDED "AS IS", WITHOUT WARRANTY OF ANY KIND, EXPRESS OR
IMPLIED, INCLUDING BUT NOT LIMITED TO THE WARRANTIES OF MERCHANTABILITY, FITNESS
FOR A PARTICULAR PURPOSE AND NONINFRINGEMENT. IN NO EVENT SHALL THE AUTHORS OR
COPYRIGHT HOLDERS BE LIABLE FOR ANY CLAIM, DAMAGES OR OTHER LIABILITY, WHETHER
IN AN ACTION OF CONTRACT, TORT OR OTHERWISE, ARISING FROM, OUT OF OR IN
CONNECTION WITH THE SOFTWARE OR THE USE OR OTHER DEALINGS IN THE SOFTWARE.