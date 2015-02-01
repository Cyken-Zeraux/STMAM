<?php
error_reporting(E_ALL);
ini_set('display_errors', 1);
ini_set('memory_limit','16M');
set_time_limit(0);
require_once(dirname(__FILE__)."/rollingcurlx.class.php");
$time_start = microtime(true);

$errorrate = 0;
function callback_functn($response, $url, $request_info, $user_data, $time) {
    echo '<br>',$request_info['url'];
    if ($request_info['http_code'] == '200') {
        echo '<br>Http Code: <span style="color:green;">',$request_info['http_code'], '</span>';
    } else {
        echo '<br>Http Code: <span style="color:red;">',$request_info['http_code'], '</span>';
        $GLOBALS['errorrate']++;
    }
    echo '<br>Port:      ',$request_info['local_port'];
    echo '<br>Time:      ', $request_info['total_time'];
    echo '<br>';
    print_r($request_info);
    echo '<br>';
}



//The maximum connections isn't used to cap the amount of connections given.
//That is used in the loop below.
$RCX = new RollingCurlX(100);
$fileload = fopen("top-1m.csv", "r");
$linerequest = 1000;
$chunk_size = 128;
$post_data = NULL;
$user_data = ['foo', 'bar'];
$RCX->setTimeout(12000);
$agent = 'Mozilla/5.0 (Windows NT 6.1; WOW64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/40.0.2214.93 Safari/537.36';
$RCX->setOptions = [CURLOPT_TIMEOUT_MS => 5000, CURLOPT_CONNECTTIMEOUT => 0, CURLOPT_VERBOSE => true, CURLOPT_RETURNTRANSFER => true, CURLOPT_FOLLOWLOCATION => true, CURLOPT_REFERER => true, CURLOPT_SSL_VERIFYPEER => 0, CURLOPT_SSL_VERIFYHOST => 0];

$y = 0;
$q = 0;
for ($i=0; $i < $linerequest; $i++) {
    $suparay = array(fgets($fileload,$chunk_size),&$fileload);
    $chunkplode = explode(',', $suparay[0]);
    $xURL = "www.$chunkplode[1]";
    //This is the batch processing, it controls how many requests are added before executing them.
    //Since RCX is blocking, it will wait until those requests are done before the for loop can continue.
    echo 'queued ',$i, ' : ', $xURL, '<br>';
    $RCX->addRequest($xURL, $post_data, 'callback_functn', $user_data);
    if ($y >= 75 || $i >= $linerequest - 1) {
        $y = 0;
        echo 'Executing queue';
        //RCX is blocking during execution.
        $RCX->execute();
    } else {
        $y++;
    }
}





?>