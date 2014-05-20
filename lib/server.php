<?php

namespace Callchedan;

class Server {

  private $methods;

  public function __construct($methods = false) {

    if ($methods !== false) {
      if (!in_array(gettype($methods), array('array', 'object'))) {
        throw new \Exception('Invalid methods object');
      }

      $this->methods = $methods;
    }
  }

  private function process($request) {

    try {

      $id = NULL;
      $notification = false;

      try {

        if (gettype($request) != 'array') {
          throw new \Exception('Not an array');
        }
        
        if (!isset($request['jsonrpc']) || $request['jsonrpc'] !== '2.0') {
          throw new \Exception('No jsonrpc property');
        }


        if (!isset($request['method']) || gettype($request['method']) != 'string') {
          throw new \Exception('Invalid method');
        }

        if (isset($request['params'])) {
          if (gettype($request['params']) != 'array') {
            throw new \Exception('Invalid params');
          }
        }

        if (isset($request['id'])) {
          if (!in_array(gettype($request['id']), array('string', 'integer'))) {
            throw new \Exception('Invalid id');
          }
          $id = $request['id'];
        } else {
          $notification = true;
        }

      } catch (\Exception $e) {
        throw new \Exception('Invalid Request', -32600, $e);
      }

      try {
        if (gettype($this->methods) == 'array') {
          if (!isset($this->methods[$request['method']])){
            throw new \Exception('No such function');
          }
          if (!is_callable($this->methods[$request['method']])) {
            throw new \Exception('Method is not callable');
          }
        } else if (gettype($this->methods) == 'object') {
          if (!method_exists($this->methods, $request['method'])){
            throw new \Exception('No such method');
          }
          if (!is_callable(array($this->methods, $request['method']))) {
            throw new \Exception('Method is not callable');
          }
        } else {
          if (!method_exists($this, $request['method'])){
            throw new \Exception('No such method');
          }
          if (!is_callable(array($this, $request['method']))) {
            throw new \Exception('Method is not callable');
          }
        }
      } catch (\Exception $e) {
        throw new \Exception('Method not found', -32601, $e);
      }

      try {

        if (isset($request['params'])) {

          if (!isset($request['params'][0])) {
            $request['params'] = array($request['params']);
          }

          if (gettype($this->methods) == 'array') {
            $result = call_user_func_array($this->methods[$request['method']], $request['params']);
          } else if (gettype($this->methods) == 'object') {
            $result = call_user_func_array(array($this->methods, $request['method']), $request['params']);
          } else {
            $result = call_user_func_array(array($this, $request['method']), $request['params']);
          }

        } else {

          if (gettype($this->methods) == 'array') {
            $result = call_user_func($this->methods[$request['method']]);
          } else {
            $result = call_user_func(array($this->methods, $request['method']));
          }

        }

      } catch (\Exception $e) {
        throw new \Exception('Server error', -32000, $e);
      }

    } catch (\Exception $e) {
      $error = array(
        'code' => $e->getCode(),
        'message' => $e->getMessage(),
        'data' => $e->getPrevious()->getMessage()
      );
    }
      
    if (!$notification) {
      $response = array(
        'jsonrpc' => '2.0',
        'id' => $id
      );

      if (isset($result)) {
        $response['result'] = $result;
      } else if (isset($error)) {
        $response['error'] = $error;
      }

      return $response;
    }
  }

  public function handle($input = false) {
    if ($input !== false) {
      $raw = $input;
    } else {
      $raw = file_get_contents('php://input');
    }

    try {

      if ($raw == '') {
        throw new \Exception('Invalid Request', -32600);
      }

      try {
        $request = json_decode($raw, true);
      } catch (\Exception $e) {
        throw new \Exception('Parse error', -32700);
      }

      if (gettype($request) != 'array') {
        throw new \Exception('Invalid Request', -32600);
      }

      if (isset($request[0])) {
        $response = array();
        foreach ($request as $item) {
          $result = $this->process($item);
          if ($result) {
            $response[] = $result;
          }
        }
      } else {
        $response = $this->process($request);
      }
      
      if ($response) {
        return json_encode($response);
      }

    } catch (\Exception $e) {
      $error = array(
        'code' => $e->getCode(),
        'message' => $e->getMessage()
      );

      $response = array(
        'jsonrpc' => '2.0',
        'id' => NULL,
        'error' => $error
      );

      return json_encode($response);
    }
  }
}