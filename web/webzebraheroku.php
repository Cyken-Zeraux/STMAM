<?php
error_reporting(E_ALL);
ini_set('display_errors', 1);
ini_set('zlib.output_compression', 0);
ob_implicit_flush(true);
set_time_limit(0);
require_once(dirname(__FILE__)."/Zebra_cURL.php");
$stdout = fopen('php://stdout', 'w');
fwrite($stdout, "Initializing STMAM, 'mam\n");

function htmlstatus($string) {
    $html = 
        "<html>
        <head>
        </head>
        <body>

        <p style='font-size:1.3em'>$string</p>

        </body>
        </html>";
    return $html;
}


function convert($size)
{
    $unit=array('b','kb','mb','gb','tb','pb');
    return @round($size/pow(1024,($i=floor(log($size,1024)))),2).' '.$unit[$i];
}


class queque {
    var $preiterate = 0;
    var $htmlcode = 0;
    var $curlcode = 0;
    var $time_start = NULL;
    var $time_end = NULL;
    var $setfile = NULL;
    var $writefile = NULL;
    var $statusfile = NULL;
    var $setoptions = NULL;
    
    public function __construct($file, $outputfile, $statfile) {
        $this->time_start = microtime(true);
        $this->setfile = new SplFileObject(dirname(__FILE__).$file);
        $this->writefile = new SplFileObject(dirname(__FILE__).$outputfile, "w");
        $this->statusfile = new SplFileObject(dirname(__FILE__).$statfile, "w");
        $this->setfile->READ_CSV = true;
        $this->RCX = new Zebra_cURL();
        $this->RCX->queue();
    }
    
    //Currently used as printer, will store data in future.
    public function callback_functn($result) {
        $url = '';
        $connect_time = '';
        $size_download = '';
        $speed_download = '';
        $primary_ip = '';
        $content_type = '';
        $namelookup_time = '';
        // everything went well at cURL level
        if ($result->response[1] == CURLE_OK) {
            if ($result->info['http_code'] == 200) {
                $url = $result->info['url'];
                $connect_time = $result->info['connect_time'];
                $size_download = $result->info['size_download'];
                $speed_download = $result->info['speed_download'];
                $primary_ip = $result->info['primary_ip'];
                $content_type = $result->info['content_type'];
                $namelookup_time = $result->info['namelookup_time'];
            } else {
                $this->htmlcode++;
            }
        } else {
            $this->curlcode++;;
        }
        $this->writefile->fputcsv(array($url, $connect_time, $size_download, $speed_download, $primary_ip, $content_type, $namelookup_time));
        unset($result);
    }
    public function requestcsv($start, $setiteratethrough) {
        if ($this->setfile) {
            $handle = $this->setfile;
            $this->RCX->option($this->setoptions);
            $setray = array();
            $z = $start;
            $handle->seek($start);
            
            for ($i=0; $i < $setiteratethrough + 1; $i++) {
                $suparay = $handle->fgets();
                if ($handle->eof() || $i >= $setiteratethrough) {
                    $this->RCX->get($setray, array($this, 'callback_functn'));
                    $setray = array();
                    $zbefore = $z - 499;
                    fwrite($GLOBALS['stdout'], "Executing queue: $zbefore - $i\n");
                    //$this->statusfile->ftruncate(0);
                    //$this->statusfile->fwrite(htmlstatus("Executing queue: $zbefore - $z"));
                    $this->RCX->start();
                    break;
                }
                $z++;
                $chunkplode = explode(',', $suparay);
                unset($suparay);
                $xURL = "www.$chunkplode[1]";
                $setray[] = $xURL;
                $this->preiterate++;
                if ($this->preiterate >= 500) {
                    $this->preiterate = 0;
                    //Sets callback back to this object.
                    $this->RCX->get($setray, array($this, 'callback_functn'));
                    $setray = array();
                    $zbefore = $z - 499;
                    fwrite($GLOBALS['stdout'], "Executing queue: $zbefore - $i\n");
                    //$this->statusfile->ftruncate(0);
                    //$this->statusfile->fwrite(htmlstatus("Executing queue: $zbefore - $i"));
                    $this->RCX->start();
                }
            }
        }
    }
    public function __destruct() {
        $this->time_end = microtime(true);
        $time = round($this->time_end - $this->time_start, 4);
        $this->writefile->fputcsv(array('Time:'.$time, 'htmlcode:'.$this->htmlcode, 'curlcode:'.$this->curlcode));
        unset($this->setfile);
        unset($this->writefile);
        unset($this->RCX);
    }
}

$RCque = new queque("top-1m.csv", "export.csv", "status.html"); //(Input File, Output File)
$RCque->setoptions = array(CURLOPT_CONNECTTIMEOUT => 32, CURLOPT_TIMEOUT => 32, CURLOPT_NOBODY => 1, CURLOPT_VERBOSE => true, CURLOPT_RETURNTRANSFER => true, CURLOPT_FOLLOWLOCATION => true, CURLOPT_REFERER => true, CURLOPT_SSL_VERIFYPEER => 0, CURLOPT_SSL_VERIFYHOST => 0,);
$RCque->requestcsv(0, 1000000); //(Starting line, Integer of lines to process)
?>