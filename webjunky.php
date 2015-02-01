<?php
error_reporting(E_ALL);
ini_set('display_errors', 1);
require_once(dirname(__FILE__)."/rollingcurlx.class.php");



// Number of websites to iterate through. use 'true' to iterate through all in file.
$setiteratethrough = 1000;
$setfile = "top-1m.csv";
$settimeout = 12000;

$setpost_data = NULL; //set to NULL if not using POST
$setuser_data = ['foo', 'bar'];
$setoptions = [CURLOPT_FOLLOWLOCATION => true, CURLOPT_SSL_VERIFYPEER => 0, CURLOPT_SSL_VERIFYHOST => 0, CURLOPT_TCP_NODELAY => 1, CURLOPT_NOBODY => true];




$time_start = microtime(true);

//http://stackoverflow.com/questions/5249279/file-get-contents-php-fatal-error-allowed-memory-exhausted/5249971#5249971
//Gets data as chunks.
function file_get_contents_chunked($file,$setiteration,$chunk_size,$callback)
{
    try
    {
        $handle = fopen($file, "r");
        for ($i=0; $i < $setiteration; $i++) {
                call_user_func_array($callback,array(fgets($handle,$chunk_size),&$handle,$i));
        }
        fclose($handle);

    }
    catch(Exception $e)
    {
         trigger_error("file_get_contents_chunked::" . $e->getMessage(),E_USER_NOTICE);
         return false;
    }

    return true;
}

$RCX = new RollingCurlX(250);
$RCX->setTimeout($GLOBALS['settimeout']);
function requestadd($url, $iteration) {
    $xURL = "www.$url";
    $setpost_data = NULL; //set to NULL if not using POST
    $setuser_data = NULL;
    $setoptions = [CURLOPT_FOLLOWLOCATION => true, CURLOPT_SSL_VERIFYPEER => 0, CURLOPT_SSL_VERIFYHOST => 0, CURLOPT_TCP_NODELAY => 1, CURLOPT_NOBODY => true];
    $GLOBALS['RCX']->addRequest($xURL, $setpost_data, 'callback_functn', $setuser_data, $setoptions);
}

$x = 0;
//Request amount of websites, add them to RCX queue.
$success = file_get_contents_chunked($GLOBALS['setfile'],$GLOBALS['setiteratethrough'],64,function($chunk,&$handle,$iteration) {
    $chunkplode = explode(',', $chunk);
    echo $chunkplode[1], ' : ', $iteration, '<br>';
    requestadd($chunkplode[1], $iteration);

    if ($GLOBALS['x'] >= 250 || $iteration >= $GLOBALS['setiteratethrough'] - 1) {
        $GLOBALS['x'] = 0;
        //RCX is blocking during execution.
        $GLOBALS['RCX']->execute();
    } else {
        $GLOBALS['x']++;
    }
});

if(!$success) {
    echo "Couldn't return chunked data.";
}

function callback_functn($response, $url, $request_info, $user_data, $time) {
    $time; //how long the request took in milliseconds (float)
    $request_info; //returned by curl_getinfo($ch)
    print_r($request_info);
    echo '<br>';
    if ($time > $GLOBALS['settimeout']) {
        echo $time;
        echo "Took too long, adding to timeout queue.";
    }
    echo '<br>';
}
$time_end = microtime(true);
$time = $time_end - $time_start;

echo "$time seconds\n";
?>