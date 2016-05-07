<?php
namespace Sovereign\Lib;


use Monolog\Logger;

class cURL {
    protected $log;
    public function __construct(Logger $log) {
        $this->log = $log;
    }

    public function get(String $url, $headers = array()) {
        $headers = array_merge($headers, ["Connection: keep-alive", "Keep-Alive: timeout=10, max=1000"]);

        try {
            $curl = curl_init();
            curl_setopt_array($curl, [
                CURLOPT_USERAGENT => "Sovereign Discord Bot",
                CURLOPT_TIMEOUT => 8,
                CURLOPT_FORBID_REUSE => false,
                CURLOPT_RETURNTRANSFER => true,
                CURLOPT_FAILONERROR => true,
                CURLOPT_URL => $url,
                CURLOPT_POST => false,
                CURLOPT_HTTPHEADER => $headers
            ]);

            $result = curl_exec($curl);

            return $result;
        } catch (\Exception $e) {
            $this->log->addError("There was an error using cURL", [$e->getMessage()]);
        }
    }

    public function post(String $url, $parameters = array(), $headers = array()) {
        $headers = array_merge($headers, ["Connection: keep-alive", "Keep-Alive: timeout=10, max=1000", "Content-Type: application/x-www-form-urlencoded"]);

        try {
            $curl = curl_init();
            curl_setopt_array($curl, [
                CURLOPT_USERAGENT => "Sovereign Discord Bot",
                CURLOPT_TIMEOUT => 8,
                CURLOPT_FORBID_REUSE => false,
                CURLOPT_RETURNTRANSFER => true,
                CURLOPT_FAILONERROR => true,
                CURLOPT_URL => $url,
                CURLOPT_POST => true,
                CURLOPT_HTTPHEADER => $headers,
                CURLOPT_POSTFIELDS => http_build_query($parameters)
            ]);

            $result = curl_exec($curl);

            return $result;
        } catch (\Exception $e) {
            $this->log->addError("There was an error using cURL", [$e->getMessage()]);
        }

    }
}