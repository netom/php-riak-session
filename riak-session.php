<?php

$riak_session_endpoint = 'http://127.0.0.1:8098';
$riak_session_bucket = 'session';

function riak_session_open($savePath, $sessionName) {
    global $riak_session_save_path, $riak_session_name;

    $riak_session_save_path = $savePath;
    $riak_session_name = $sessionName;

    return true;
}

function riak_session_close() {
    return true;
}

function riak_session_read($id) {
    global $riak_session_endpoint, $riak_session_bucket_name;

    $ch = curl_init();

    if ($ch === false) {
        trigger_error(
            "Riak session save handler: curl_init() returned FALSE.",
            E_USER_ERROR
        );
        return '';
    }

    $options = array(
        CURLOPT_URL => $riak_session_endpoint . '/buckets/' . $riak_session_bucket . '/keys/' . urlencode($id),
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
                "error code $http_code upon reading session id $id",
                E_USER_ERROR
            );
        }
        return '';
    }
}

function riak_session_write($id, $data) {
    /* TODO: according to PHP docs, trigger_error messages are never seen
     * because this function is called after the output stream is closed.
     * I'm not sure though that if these are appearing in the php log.
     * Gotta try one day. */

    global $riak_session_endpoint, $riak_session_bucket_name;

    $ch = curl_init();

    if ($ch === false) {
        trigger_error(
            "Riak session save handler: curl_init() returned FALSE.",
            E_USER_ERROR
        );
        return;
    }

    $options = array(
        CURLOPT_URL => $riak_session_endpoint . '/buckets/' . $riak_session_bucket . '/keys/' . urlencode($id),
        CURLOPT_POST => true,
        CURLOPT_POSTFIELDS => $data,
        CURLOPT_HEADER => false
    );

    if (curl_setopt_array($ch, $options) === false) {
        trigger_error(
            "Riak session save handler: curl_setopt_array() returned FALSE. Error message: " . curl_error($ch),
            E_USER_ERROR
        );
        return;
    }

    if (!curl_exec($ch)) {
        trigger_error(
            "Riak session save handler: curl_setopt_array() returned FALSE. Error message: " . curl_error($ch),
            E_USER_ERROR
        );
    }
}

function riak_session_destroy($id) {
    global $riak_session_endpoint, $riak_session_bucket_name;

    $ch = curl_init();

    if ($ch === false) {
        trigger_error(
            "Riak session save handler: curl_init() returned FALSE.",
            E_USER_ERROR
        );
        return false;
    }

    $options = array(
        CURLOPT_URL => $riak_session_endpoint . '/buckets/' . $riak_session_bucket . '/keys/' . urlencode($id),
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

function riak_session_gc($maxlifetime) {
    return true;
}

session_set_save_handler(
    'riak_session_open', 'riak_session_close', 'riak_session_read',
    'riak_session_write', 'riak_session_destroy', 'riak_session_gc'
);
