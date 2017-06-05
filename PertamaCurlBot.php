<?php

/**
 * Bot PHP Telegram ver Curl
 * Lebih Bersih
 * Sample Sederhana untuk Ebook Edisi 3: Membuat Bot Sendiri Menggunakan PHP
 * 
 * Dibuat oleh Hasanudin HS
 * @hasanudinhs di Telegram dan Twitter
 * Ebook live http://telegram.banghasan.com/
 * -----------------------
 * Grup @botphp
 * Jika ada pertanyaan jangan via PM
 * langsung ke grup saja.
 * ----------------------
 * PertamaCurlBot.php
 * Bot PHP sederhana Menggunakan Curl
 * Versi 0.02
 * Juli 2016
 * Last Update : 23 Juli 2016 22:40 WIB
 * 
 * Default adalah webhook!
 * Default pake API pihak ke-3, siap tanpa https / SSL
 */

// masukkan bot token di sini
define('BOT_TOKEN', '399057270:AAG67eQ3C06rvnPeIPz3HYTyBc1oNVVXMzQ'); 

// versi official telegram bot
define('API_URL', 'https://api.telegram.org/bot'.BOT_TOKEN.'/');

define('myVERSI','0.03');

// aktifkan ini jika ingin menampilkan debugging poll
$debug = false;

function exec_curl_request($handle) {
  $response = curl_exec($handle);

  if ($response === false) {
    $errno = curl_errno($handle);
    $error = curl_error($handle);
    error_log("Curl returned error $errno: $error\n");
    curl_close($handle);
    return false;
  }

  $http_code = intval(curl_getinfo($handle, CURLINFO_HTTP_CODE));
  curl_close($handle);

  if ($http_code >= 500) {
    // do not wat to DDOS server if something goes wrong
    sleep(10);
    return false;
  } else if ($http_code != 200) {
    $response = json_decode($response, true);
    error_log("Request has failed with error {$response['error_code']}: {$response['description']}\n");
    if ($http_code == 401) {
      throw new Exception('Invalid access token provided');
    }
    return false;
  } else {
    $response = json_decode($response, true);
    if (isset($response['description'])) {
      error_log("Request was successfull: {$response['description']}\n");
    }
    $response = $response['result'];
  }

  return $response;
}

function apiRequest($method, $parameters=null) {
  if (!is_string($method)) {
    error_log("Method name must be a string\n");
    return false;
  }

  if (!$parameters) {
    $parameters = array();
  } else if (!is_array($parameters)) {
    error_log("Parameters must be an array\n");
    return false;
  }

  foreach ($parameters as $key => &$val) {
    // encoding to JSON array parameters, for example reply_markup
    if (!is_numeric($val) && !is_string($val)) {
      $val = json_encode($val);
    }
  }
  $url = API_URL.$method.'?'.http_build_query($parameters);

  $handle = curl_init($url);
  curl_setopt($handle, CURLOPT_RETURNTRANSFER, true);
  curl_setopt($handle, CURLOPT_CONNECTTIMEOUT, 5);
  curl_setopt($handle, CURLOPT_TIMEOUT, 60);
  curl_setopt($handle, CURLOPT_SSL_VERIFYPEER, false);

  return exec_curl_request($handle);
}

function apiRequestJson($method, $parameters) {
  if (!is_string($method)) {
    error_log("Method name must be a string\n");
    return false;
  }

  if (!$parameters) {
    $parameters = array();
  } else if (!is_array($parameters)) {
    error_log("Parameters must be an array\n");
    return false;
  }

  $parameters["method"] = $method;

  $handle = curl_init(API_URL);
  curl_setopt($handle, CURLOPT_RETURNTRANSFER, true);
  curl_setopt($handle, CURLOPT_CONNECTTIMEOUT, 5);
  curl_setopt($handle, CURLOPT_TIMEOUT, 60);
  curl_setopt($handle, CURLOPT_POSTFIELDS, json_encode($parameters));
  curl_setopt($handle, CURLOPT_HTTPHEADER, array("Content-Type: application/json"));

  return exec_curl_request($handle);
}

// jebakan token, klo ga diisi akan mati
if (strlen(BOT_TOKEN)<20) 
    die(PHP_EOL."-> -> Token BOT API nya mohon diisi dengan benar!\n");

function getUpdates($last_id = null){
  $params = [];
  if (!empty($last_id)){
    $params = ['offset' => $last_id+1, 'limit' => 1];
  }
  //echo print_r($params, true);
  return apiRequest('getUpdates', $params);
}

// matikan ini jika ingin bot berjalan
die('baca dengan teliti yak!');

// ----------- pantengin mulai ini
function sendMessage($idpesan, $idchat, $pesan){
  $data = [
    'chat_id'=> $idchat,
    'text' => $pesan,
    "reply_to_message_id" => $idpesan
  ];
  return apiRequest("sendMessage", $data);
}

function processMessage($message) {
  if ( $GLOBALS['debug']) print_r($message);

  if (isset($message["message"])) {
    $sumber   = $message['message'];
    $idpesan  = $sumber['message_id'];
    $idchat   = $sumber['chat']['id'];

    $namamu   = $sumber['from']['first_name'];

    if (isset($sumber['text'])) {
      $pesan  =  $sumber['text'];
      $pecah  = explode(' ', $pesan);
      $katapertama = strtolower($pecah[0]);
      switch ($katapertama) {
        case '/start':
          $text = "Hai $namamu.. Akhirnya kita bertemu!";
          break;

        case '/time':
          $text  = "Waktu Sekarang :\n";
          $text .= date("d-m-Y H:i:s");
          break;
        
        default:
          $text = "Pesan sudah diterima, terimakasih ya!";
          break;
      }
    } else {
      $text  = "Ada sesuatu di bola matamu..";
    }
    
    $hasil = sendMessage($idpesan, $idchat, $text);
    if ( $GLOBALS['debug']) {
      // hanya nampak saat metode poll dan debug = true;
      echo "Pesan yang dikirim: ".$text.PHP_EOL;
      print_r($hasil);
    }
  }    

}

// pencetakan versi dan info waktu server, berfungsi jika test hook
echo "Ver. ".myVERSI." OK Start!".PHP_EOL.date('Y-m-d H:i:s'). PHP_EOL;

function printUpdates($result){
  foreach($result as $obj){
    // echo $obj['message']['text'].PHP_EOL;
    processMessage($obj);
    $last_id = $obj['update_id'];
  }
  return $last_id;
}

/*
// AKTIFKAN INI jika menggunakan metode poll
// ~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~
$last_id = null;
while (true){
  $result = getUpdates($last_id);
  if (!empty($result)) {
    echo "+";
    $last_id = printUpdates($result);
  } else {
    echo "-";
  }
  
 sleep(1);
}
*/

// AKTIFKAN INI jika menggunakan metode webhook
// ~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~
$content = file_get_contents("php://input");
$update = json_decode($content, true);

if (!$update) {
  // ini jebakan jika ada yang iseng mengirim sesuatu ke hook
  // dan tidak sesuai format JSON harus ditolak!
  exit;
} else {
  // sesuai format JSON, proses pesannya
  processMessage($update);
}

/*

Sekian.

*/
    
?>
