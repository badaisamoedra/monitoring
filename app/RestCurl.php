<?php

namespace App;

Class RestCurl {
  public static function exec($method, $url, $obj = array()) {
    $header = [
      'Accept: application/json',
      'Content-Type: application/json',
      'Authorization: Bearer '.ACCESS_TOKEN, 
      // 'client-id : '. CLIENT_ID,
      // 'client-secret : '. CLIENT_SECRET,
      // 'client-token-id : '. CLIENT_TOKEN_ID,
      // 'client-token-secret : '. CLIENT_TOKEN_SECRET,
      // 'access_token : '. ACCESS_TOKEN,
      // 'refresh_token : '. REFRESH_TOKEN,
      // 'username : '. USERNAME_PARSE,
      // 'password : '. PASSWORD_PARSE,
      // 'scope : '. SCOPE
  ];
    $curl = curl_init();
     
    switch($method) {
      case 'GET':
        if(strrpos($url, "?") === FALSE) {
          $url .= '?' . http_build_query($obj);
        }
        break;

      case 'POST': 
        curl_setopt($curl, CURLOPT_POST, TRUE);
        curl_setopt($curl, CURLOPT_POSTFIELDS, json_encode($obj));
        break;

      case 'PUT':
      case 'DELETE':
      default:
        curl_setopt($curl, CURLOPT_CUSTOMREQUEST, strtoupper($method));
        curl_setopt($curl, CURLOPT_POSTFIELDS, json_encode($obj));
    }

    curl_setopt($curl, CURLOPT_RETURNTRANSFER, TRUE);
    curl_setopt($curl, CURLOPT_HTTPHEADER, $header); 
    curl_setopt($curl, CURLOPT_URL, $url);
    curl_setopt($curl, CURLOPT_HEADER, TRUE);
    curl_setopt($curl, CURLOPT_SSL_VERIFYPEER, TRUE);
    
     // Exec
    $response = curl_exec($curl);
    $info     = curl_getinfo($curl);
    if(curl_errno($curl)){
        throw new \Exception('Request Error: ' . curl_error($curl), $info['http_code']);   
    }

    curl_close($curl);

    // Data
    $header = trim(substr($response, 0, $info['header_size']));
    $body = substr($response, $info['header_size']);
     
    return array('status' => $info['http_code'], 'header' => $header, 'data' => json_decode($body));
  }

  public static function get($url, $obj = array()) {
     return RestCurl::exec("GET", $url, $obj);
  }

  public static function post($url, $obj = array()) {
     return RestCurl::exec("POST", $url, $obj);
  }

  public static function put($url, $obj = array()) {
     return RestCurl::exec("PUT", $url, $obj);
  }

  public static function delete($url, $obj = array()) {
     return RestCurl::exec("DELETE", $url, $obj);
  }

}

