<?php

namespace Netom\Session;

/**
 * Session handler using Riak
 * 
 * Tested with Riak 2.2.3
 */
class Riak implements \SessionHandlerInterface {

    public
        $endpoint,
        $bucketType,
        $bucket;

    public static function register(
        $endpoint = 'http://127.0.0.1:8098',
        $bucketType = 'default',
        $bucket = 'session'
    )
    {
        $h = new self($endpoint, $bucketType, $bucket);
        session_set_save_handler($h, true);
        return $h;
    }

    public function __construct($endpoint, $bucketType, $bucket)
    {
        $this->endpoint = $endpoint;
        $this->bucketType = $bucketType;
        $this->bucket = $bucket;
    }

    public function close()
    {
        return true;
    }

    public function destroy($session_id)
    {
        $ch = curl_init();

        if ($ch === false) {
            throw new \Exception("Riak session save handler: curl_init() returned FALSE.");
        }

        $options = array(
            CURLOPT_URL =>
                $this->endpoint .
                '/types/' . $this->bucketType .
                '/buckets/' . $this->bucket .
                '/keys/' . urlencode($session_id),
            CURLOPT_CUSTOMREQUEST => "DELETE",
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_HEADER => false
        );

        if (curl_setopt_array($ch, $options) === false) {
            throw new \Exception(
                "Riak session save handler: curl_setopt_array() returned FALSE. Error message: " . curl_error($ch)
            );
        }

        curl_exec($ch);
        curl_close($ch);

        return true;
    }

    /**
     * Garbage collection - remove old sessions
     * 
     * This method fetches all matching session IDs and then performs a delete, therefore it's slow.
     * It's recommended to use other means to remove old sessions - like setting expiry with BitCask.
     * 
     * Pay attention to use the appropriate backend (like leveldb) that supports secondary indexes.
     */
    public function gc($maxlifetime)
    {
        $ch = curl_init();

        if ($ch === false) {
            throw new \Exception("Riak session save handler: curl_init() returned FALSE.");
        }

        $options = array(
            CURLOPT_URL =>
                $this->endpoint .
                '/types/' . $this->bucketType .
                '/buckets/' . $this->bucket .
                '/index/t_int/0/' . (time() - $maxlifetime),
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_HEADER => false
        );

        if (curl_setopt_array($ch, $options) === false) {
            throw new \Exception(
                "Riak session save handler: curl_setopt_array() returned FALSE. Error message: " . curl_error($ch)
            );
        }

        $keysData = curl_exec($ch);

        $http_code = curl_getinfo($ch, CURLINFO_HTTP_CODE);

        curl_close($ch);

        if ($http_code == 404) {
            return true;
        }

        if ($http_code != 200) {
            throw new \Exception(
                "Riak session save handler: server returned " .
                "error code $http_code upon querying expired session ids. " .
                "The answer was: $keysData"
            );
        }

        $keysArray = json_decode($keysData, true);

        if ($keysArray === null)  {
            throw new \Exception("Could not decode object returned by Riak: $keysJson");
        }

        foreach ($keysArray['keys'] as $session_id) {
            $ch = curl_init();

            if ($ch === false) {
                throw new \Exception("Riak session save handler: curl_init() returned FALSE.");
            }

            $options = array(
                CURLOPT_URL =>
                    $this->endpoint .
                    '/types/' . $this->bucketType .
                    '/buckets/' . $this->bucket .
                    '/keys/' . urlencode($session_id),
                CURLOPT_CUSTOMREQUEST => "DELETE",
                CURLOPT_RETURNTRANSFER => true,
                CURLOPT_HEADER => false
            );

            if (curl_setopt_array($ch, $options) === false) {
                throw new \Exception(
                    "Riak session save handler: curl_setopt_array() returned FALSE. Error message: " . curl_error($ch)
                );
            }

            curl_exec($ch);
            curl_close($ch);
        }
    }

    public function open($save_path, $session_name) {
        global $riak_session_save_path, $riak_session_name;

        $this->save_path = $save_path;
        $this->session_name = $session_name;

        return true;
    }

    public function read($session_id)
    {
        $ch = curl_init();

        if ($ch === false) {
            throw new \Exception("Riak session save handler: curl_init() returned FALSE.");
        }

        $options = array(
            CURLOPT_URL =>
                $this->endpoint .
                '/types/' . $this->bucketType .
                '/buckets/' . $this->bucket .
                '/keys/' . urlencode($session_id),
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_HEADER => false
        );

        if (curl_setopt_array($ch, $options) === false) {
            throw new \Exception(
                "Riak session save handler: curl_setopt_array() returned FALSE. Error message: " . curl_error($ch)
            );
        }

        $session_data = curl_exec($ch);

        $http_code = curl_getinfo($ch, CURLINFO_HTTP_CODE);

        curl_close($ch);

        if ($http_code == '200') {
            return $session_data;
        } else {
            if ($http_code != 404) {
                throw new \Exception(
                    "Riak session save handler: server returned " .
                    "error code $http_code upon reading session id $session_id"
                );
            }
            return '';
        }
    }

    public function write($session_id, $session_data)
    {
        $ch = curl_init();

        if ($ch === false) {
            throw new \Exception(
                "Riak session save handler: curl_init() returned FALSE."
            );
        }

        $options = array(
            CURLOPT_URL =>
                $this->endpoint .
                '/types/' . $this->bucketType .
                '/buckets/' . $this->bucket .
                '/keys/' . urlencode($session_id),
            CURLOPT_POST => true,
            CURLOPT_POSTFIELDS => $session_data,
            CURLOPT_HEADER => false,
            CURLOPT_HTTPHEADER => [
                'x-riak-index-t_int: ' . time()
            ]
        );

        if (curl_setopt_array($ch, $options) === false) {
            throw new \Exception(
                "Riak session save handler: curl_setopt_array() returned FALSE. Error message: " . curl_error($ch)
            );
        }

        if (!curl_exec($ch)) {
            throw new \Exception(
                "Riak session save handler: curl_setopt_array() returned FALSE. Error message: " . curl_error($ch)
            );
        }

        return true;
    }
}

/*

// Quick & dirty test

$h = Riak::register();

session_id('asdf123');
session_start();

print session_id() . "\n";

var_dump($_SESSION);

$_SESSION['key'] = "value";

var_dump($_SESSION);

//session_destroy();

session_write_close();

//$h->gc(-1);

*/
