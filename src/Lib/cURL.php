<?php
namespace Sovereign\Lib;


use Monolog\Logger;

class cURL {
    private $curl;
    private $settings = [];
    protected $log;
    public function __construct(Logger $log) {
        $this->log = $log;
        $this->curl = curl_init();
        $this->settings = [
            CURLOPT_USERAGENT => "Sovereign Discord Bot",
            CURLOPT_TIMEOUT => 5,
            CURLOPT_FORBID_REUSE => false,
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_FAILONERROR => true
        ];
    }

    public function get(String $url, $headers = array()) {
        $headers = array_merge($headers, ["Connection: keep-alive", "Keep-Alive: timeout=10, max=1000"]);

        try {
            curl_setopt_array($this->curl, array_merge($this->settings, [
                CURLOPT_URL => $url,
                CURLOPT_POST => false
            ]));

            $result = curl_exec($this->curl);

            return $result;
        } catch (\Exception $e) {
            $this->log->addError("There was an error using cURL", [$e->getMessage()]);
        }
    }
}