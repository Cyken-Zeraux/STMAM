<?php
header('Content-type: text/html; charset=utf-8');
header("Cache-Control: no-cache, must-revalidate");
header("Pragma: no-cache"); 
header("Expires: Mon, 24 Sep 2012 04:00:00 GMT");

require('../vendor/autoload.php');
error_reporting(E_ALL);
ini_set('display_errors', 1);
ini_set('zlib.output_compression', 0);
ini_set('output_buffering', 0);
set_time_limit(0);
require_once(dirname(__FILE__)."/rollingcurlx.class.php");
$time_start = microtime(true);

ob_start();
echo str_pad('',4096)."\n";

$y = 0;
//Currently used as printer, will store data in future.
function callback_functn($response, $url, $request_info, $user_data, $time) {
    if ($request_info['http_code'] == '200') {
    } else {
        $GLOBALS['y']++;
    }
}

class queque {
    var $loadhandle = 1200;
    var $preiterate = 0;
    var $chunk_size = 128;
    var $agent = NULL;
    var $setiteratethrough = NULL;
    var $setfile = NULL;
    var $sethandle = NULL;
    var $settimeout = NULL;
    var $setpost_data = NULL; //set to NULL if not using POST
    var $setuser_data = NULL;
    var $setoptions = NULL;
    
    public function __construct($file, $iterate, $timeout, $headers) {
        $this->setfile = fopen($file, "r");
        $this->setiteratethrough = $iterate;
        $this->settimeout = $timeout;
        $this->RCX = new RollingCurlX($this->loadhandle);
        $this->RCX->setTimeout($timeout);
        $this->RCX->setHeaders($headers);
    }
    public function requestcsv() {
        $handle = $this->setfile;
        for ($i=0; $i < $this->setiteratethrough; $i++) {
            $suparay = array(fgets($handle,$this->chunk_size),&$handle);
            $chunkplode = explode(',', $suparay[0]);
            unset($suparay);
            $xURL = "www.$chunkplode[1]";
            $this->RCX->addRequest($xURL, $this->setpost_data, 'callback_functn', $this->setuser_data, $this->setoptions);
            //print_r($this->setoptions);
            if ($this->preiterate >= 500 || $i >= $this->setiteratethrough - 1) {
                $this->preiterate = 0;
                echo str_pad('',8096)."Executing queue $i ----------------------------------------------------------------------";
                //RCX is blocking during execution.
                $this->RCX->execute();
                echo '<br>';
            } else {
                $this->preiterate++;
            }
        }
    }
    public function __destruct() {
        fclose($this->setfile);
    }
}
$header[] = 'Accept: text/html';
$header[] = "Accept-Encoding: gzip";
$RCque = new queque("top-1m.csv", 10000, 14000, $header); //($file, $iterate, $timeout)
$timeouty = 50000;
$RCque->setuser_data = ['foo', 'bar'];
$RCque->agent = 'Mozilla/5.0 (Windows NT 6.1; WOW64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/40.0.2214.93 Safari/537.36';
$RCque->setoptions = [CURLOPT_CONNECTTIMEOUT => 0, CURLOPT_VERBOSE => true, CURLOPT_RETURNTRANSFER => true, CURLOPT_FOLLOWLOCATION => true, CURLOPT_REFERER => true, CURLOPT_SSL_VERIFYPEER => 0, CURLOPT_SSL_VERIFYHOST => 0];
$RCque->requestcsv();


$time_end = microtime(true);
$time = round($time_end - $time_start, 4);
echo 'Errors: ', $GLOBALS['y'], '<br>';
echo "$time seconds\n";
ob_end_flush();
ob_flush();
?>
