<?php

namespace Callchedan;

class Transport {
  
  private $ch;
  
  private $scheme;
  private $host;
  private $path;
  private $port;
  private $user;
  private $pass;

  public function __construct($url) {
    $parsedUrl = parse_url($url);

    if (!$parsedUrl['scheme'] || !$parsedUrl['host']) {
      throw new \Exception("Invalid JSON-RPC server URL");
    }
    if (!in_array($parsedUrl['scheme'], array('http','https'))) {
      throw new \Exception("Invalid JSON-RPC server URL scheme");
    }
    $this->scheme = $parsedUrl['scheme'];
    $this->host = $parsedUrl['host'];

    if (isset($parsedUrl['path'])) {
      $this->path = $parsedUrl['path'];
    } else {
      $this->path = '/';
    }
    if (isset($parsedUrl['port'])) {
      $this->port = $parsedUrl['port'];
    }

    if (isset($parsedUrl['user']) && isset($parsedUrl['pass'])) {
      $this->user = $parsedUrl['user'];
      $this->pass = $parsedUrl['pass'];
    }
  }

  public function call($request) {
    if (!$this->ch) {
      $this->ch = curl_init($this->scheme.'://'.$this->host.$this->path);
      if ($this->port) {
        curl_setopt($this->ch, CURLOPT_PORT, $this->port);
      }
      if ($this->user && $this->pass) {
        curl_setopt($this->ch, CURLOPT_USERPWD, $this->user.':'.$this->pass);
      }
      curl_setopt($this->ch, CURLOPT_POST, true);
      curl_setopt($this->ch, CURLOPT_HTTPHEADER, array('Content-type: application/json'));
      curl_setopt($this->ch, CURLOPT_RETURNTRANSFER, true);
    }
    curl_setopt($this->ch, CURLOPT_POSTFIELDS, $request);
    $raw = curl_exec($this->ch);
    return $raw;
  }

}

class Client {

  private $transport;
  private $batch = array();

  public function __construct($param = NULL) {
    if (gettype($param) == 'string') {
      $this->transport = new Transport($param);
    } elseif (gettype($param) == 'object') {
      if (!method_exists($param, 'call')){
        throw new \Exception('Invalid transport object');
      } else {
        $this->transport = $param;
      }
    }
  }

  private function constructRequest($method, $params, $notify) {
    $request = array(
      'jsonrpc' => '2.0'
    );

    if (!$notify) {
      $request['id'] = uniqid();
    }

    if (gettype($method) != 'string') {
      throw new \Exception("Invalid JSON-RPC method");
    }
    $request['method'] = $method;

    if ($params) {
      if (gettype($params) != 'array') {
        throw new \Exception("Invalid JSON-RPC params");
      }
      $request['params'] = $params;
    }

    return $request;
  }

  private function doRequest($request) {
    return $this->transport->call($request);
  }

  private function handleResult($raw, $request) {
    $result = json_decode($raw, true);
    
    if (isset($result['error'])) {
      throw new \Exception($result['error']['message'], $result['error']['code']);
    }
    if (isset($result['result'])) {
      return $result['result'];
    }
    return $result;
  }

  public function call($method, $params = array(), $notify = false) {
    $request = $this->constructRequest($method, $params, $notify);
    if ($notify) {
      $this->doRequest(json_encode($request));
      return;
    }
    return $this->handleResult($this->doRequest(json_encode($request)), $request);
  }

  public function notify($method, $params = array()) {
    $this->call($method, $params, true);
  }

  public function addCall($method, $params = array(), $notify = false) {
    $request = $this->constructRequest($method, $params, $notify);
    $this->batch[] = $request;
    if (!$notify) {
      return $request['id'];
    }
  }

  public function addNotification($method, $params = array()) {
    $this->addCall($method, $params, true);
  }

  public function batchRequest() {
    $batch = $this->batch;
    $this->batch = array();
    return $this->handleResult($this->doRequest(json_encode($batch)), $request);
  }

}