<?php

namespace Netom\Session;

class Riak implements \SessionHandlerInterface {

    public
        $endpoint,
        $bucket;

    public static function register(
        $endpoint = 'http://127.0.0.1:8098',
        $bucket = 'session'
    )
    {
        $h = new self($endpoint, $bucket);
        session_set_save_handler($h, true);
        return $h;
    }

    public function __construct($endpoint, $bucket)
    {
        $this->endpoint = $endpoint;
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
            trigger_error(
                "Riak session save handler: curl_init() returned FALSE.",
                E_USER_ERROR
            );
            return false;
        }

        $options = array(
            CURLOPT_URL => $this->endpoint . '/buckets/' . $this->buckets . '/keys/' . urlencode($session_id),
            CURLOPT_CUSTOMREQUEST => "DELETE",
            CURLOPT_HEADER => false
        );

        if (curl_setopt_array($ch, $options) === false) {
            trigger_error(
                "Riak session save handler: curl_setopt_array() returned FALSE. Error message: " . curl_error($ch),
                E_USER_ERROR
            );
            return false;
        }

        if (curl_exec($ch)) {
            return true;
        } else {
            trigger_error(
                "Riak session save handler: curl_setopt_array() returned FALSE. Error message: " . curl_error($ch),
                E_USER_ERROR
            );
            return false;
        }
    }

    public function gc($maxlifetime)
    {
        return true;
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
            trigger_error(
                "Riak session save handler: curl_init() returned FALSE.",
                E_USER_ERROR
            );
            return '';
        }

        $options = array(
            CURLOPT_URL => $this->endpoint . '/buckets/' . $this->bucket . '/keys/' . urlencode($session_id),
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_HEADER => false
        );

        if (curl_setopt_array($ch, $options) === false) {
            trigger_error(
                "Riak session save handler: curl_setopt_array() returned FALSE. Error message: " . curl_error($ch),
                E_USER_ERROR
            );
            return '';
        }

        $data = curl_exec($ch);

        $http_code = curl_getinfo($ch, CURLINFO_HTTP_CODE);

        curl_close($ch);

        if ($http_code == '200') {
            return $data;
        } else {
            if ($http_code != 404) {
                trigger_error(
                    "Riak session save handler: server returned " .
                    "error code $http_code upon reading session id $session_id",
                    E_USER_ERROR
                );
            }
            return '';
        }
    }

    public function write($session_id, $session_data)
    {
        /* TODO: according to PHP docs, trigger_error messages are never seen
         * because this function is called after the output stream is closed.
         * I'm not sure though that if these are appearing in the php log.
         * Gotta try one day. */
        $ch = curl_init();

        if ($ch === false) {
            trigger_error(
                "Riak session save handler: curl_init() returned FALSE.",
                E_USER_ERROR
            );
            return false;
        }

        $data = null;
        $options = array(
            CURLOPT_URL => $this->endpoint . '/buckets/' . $this->bucket . '/keys/' . urlencode($session_id),
            CURLOPT_POST => true,
            CURLOPT_POSTFIELDS => $data,
            CURLOPT_HEADER => false
        );

        if (curl_setopt_array($ch, $options) === false) {
            trigger_error(
                "Riak session save handler: curl_setopt_array() returned FALSE. Error message: " . curl_error($ch),
                E_USER_ERROR
            );
            return false;
        }

        if (!curl_exec($ch)) {
            trigger_error(
                "Riak session save handler: curl_setopt_array() returned FALSE. Error message: " . curl_error($ch),
                E_USER_ERROR
            );
        }

        return true;
    }
}



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


