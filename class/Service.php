<?php

class Service {

    protected $url;
    protected $response;
    protected $headersize;
    protected $method;
    protected $debug;

    public function __construct($url) {
        $this->url = $url;
        $this->debug = FALSE;
    }

    public function executeUrl($query_data = null, $headers = null) {
        return $this->call($query_data, null, $headers);
    }

    public function postData($data, $headers = null) {
        if (is_array($data)) {
            $postdata = http_build_query($data);
            $arraypost = $data;
        } else {
            $postdata = $data;
            $arraypost = array("post" => $postdata);
        }
        return $this->call(null, $postdata, $headers);
    }

    public function postJsonData($data) {
        $headers = array("Content-type: application/json; charset=utf-8", "Content-length: " . strlen($data));
        return $this->postData($data, $headers);
    }

    public function postXmlData($data) {
        $headers = array("Content-type: application/x-www-form-urlencoded; charset=UTF-8", "Content-length: " . strlen($data));
        return $this->postData($data, $headers);
    }

    public function postXmlData2($data) {
        $headers = array("Content-type: text/xml; charset=utf-8", "Content-length: " . strlen($data));
        return $this->postData($data, $headers);
    }

    public function restRequest($array_data) {
        return $this->call(@$array_data["get"], @$array_data["post"]);
    }

    public function call($getargs, $postdata, $headers = null) {
        $url = $this->url;
        if ($getargs) {
            $urldata = http_build_query($getargs);
            $url = $url . "?" . $urldata;
        }
        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, $url);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_FOLLOWLOCATION, true);
        if ($postdata) {
            curl_setopt($ch, CURLOPT_POST, true);
            curl_setopt($ch, CURLOPT_POSTFIELDS, $postdata);
        }
        if ($headers) {
            curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);
        }
        $response = trim(curl_exec($ch));
        if ($response === false) {
            $response = 'API "' . $url . '"call failed with cURL error: ' . curl_error($ch);
        }
        curl_close($ch);
        logger(__CLASS__ . __FUNCTION__, array("url" => $url, "postdata" => $postdata, "headers" => $headers, "response" => $response), $this->debug);
        return $response;
    }
    
    public function setDebug($bool) {
        $this->debug = $bool;
    }

    public function getResponse() {
        return $this->response;
    }

    public function getHeadersize() {
        return $this->headersize;
    }

}

?>
