php-riak-sessionhandler
=======================

Riak-session is a very simple session handler plugin for PHP.

It uses the HTTP interface and the CURL extension to communicate with
a Riak cluster.

The session handler only works with one node, no failover capability is
provided here. Use HaProxy or an application-local Riak node to increase
availability.

To use the library, simply include riak-session.php into your project.

Use the following global varialbes to fine-tune your application.
Default values are also as they are below:

    $riak_session_endpoint = 'http://127.0.0.1:8098';
    $riak_session_bucket = 'session';

The n_val, r, and w parameters must be set on the bucket before use.
