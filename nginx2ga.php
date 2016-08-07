<?php

require(__DIR__ . "/config.php");

$reporter = new Reporter();
$parser = new Parser($argv[1]);
while($hit = $parser->fetch()) {
  $reporter->reportHit($hit);
}


class Parser {
  private $fp;

  public function __construct($filename) {
    $this->fp = popen("tail -c 0 -f {$filename}", "r");
    if ($this->fp == false) {
      throw new Exception("Cant open file {$filename}: ".error_get_last()['message']);
    }
  }

  /**
   * Returns new next record from log
   * @return array
   */
  public function fetch() {
    global $conf;

    do {
      if (feof($this->fp)) return false;
      $rec = fgetcsv($this->fp, NULL, " ");
      if ($rec == false) {
        fwrite(STDERR, "Error parsing line\n");
        continue;
      }

      $ip = $rec[$conf['ip_pos']];
      if ($ip == "127.0.0.1") continue;  // skip local stuff
      $request = explode(" ", $rec[$conf['request_pos']]);
      if (!isset($request[1])) {
        fwrite(STDERR, "Wrong request: {$rec[$conf['request_pos']]}\n");
        continue;
      }

      $url = $request[1];
      $pathInfo = pathinfo($url);
    } while(!isset($pathInfo['extension']) || $pathInfo['extension'] != 'ts');

    $ua = $rec[$conf['ua_pos']];
    $cid = $rec[$conf['cookie_pos']];
    if ($cid == "" || $cid == "-") {
      $cid = md5($ip.$ua);  // no cookie, generate some id from IP and UA
    }

    return array(
      'uip' => $ip,
      'ua' => $ua,
      // event label, use first part filename ("medium" for medium-1635.ts)
      'el' => explode("-", $pathInfo['basename'])[0],
      // event value, use filesize
      'ev' => $rec[$conf['size_pos']],
      'dr' => $rec[$conf['referer_pos']],
      'cid' => $cid,
    );
  }
}

class Reporter {
  private $payload = "";
  private $payloadHits = 0;
  private $payloadTime = array();

  public function reportHit($hit) {
    global $conf;

    $hit['v'] = 1;
    $hit['tid'] = $conf['tracking_id'];
    $hit['t'] = 'event';
    $hit['ec'] = $conf['event_category'];
    $hit['ea'] = 'play';
    $hit['aip'] = 1;

    $payload = http_build_query($hit);
    $this->reportPayload($payload);
  }

  /**
   * Add payload to batch request and report it if batch full
   * @param $payload
   */
  private function reportPayload($payload) {
    // check if 16Kb payload limit exceeded
    if (strlen($this->payload)+strlen($payload) > (16*1024-10*20)) { // 10*20 is reserved for &qt=100000
      $this->flushPayload();
    }
    $this->payload .= $payload."\n";
    $this->payloadHits++;
    $this->payloadTime[] = microtime(true);
    // check if 20 hits limit exceeded
    if ($this->payloadHits>=20) {
      $this->flushPayload();
    }
  }

  /**
   * report batch payload
   */
  private function flushPayload() {
    // add &qt (queue times) to payload
    $time = microtime(true);
    $payload = explode("\n", $this->payload);
    foreach ($payload as $key=>$value) {
      $payload[$key] .= "&qt=".ceil(($time - $this->payloadTime[$key]) * 1000);
    }
    $payload = join("\n", $payload);

    $url = 'http://www.google-analytics.com/batch';

    $context = stream_context_create(array(
      'http' => array(
        'header'  => "Content-type: application/x-www-form-urlencoded\r\n",
        'method'  => 'POST',
        'content' => $payload
      )
    ));
    $result = file_get_contents($url, false, $context);

    if ($result === FALSE) {
      fwrite(STDERR, "Error sending request to Google Analytics: ".error_get_last()['message']);
    }

    $this->payload = "";
    $this->payloadHits = 0;
    $this->payloadTime = array();
  }

}




