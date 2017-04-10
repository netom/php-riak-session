php-riak-sessionhandler
=======================

Riak-session is a very simple session handler plugin for PHP.

It uses the HTTP interface and the CURL extension to communicate with
a Riak cluster.

The session handler only works with one node, no failover capability is
provided here. Use HaProxy or an application-local Riak node to increase
availability.

If you'd like to make use of the gc() method of the save handler, you
have to configure Riak with a backend that supports secondary indexes.
Alternatively, you can setup BitCask (for example) with expiry to
automatically retire old sessions.

## Usage

Use composer to include 'netom/php-riak-session'.

Use the code below to register a riak session handler instance, and
get the actual handler instance in a signle line:

        $h = \Netom\Session\Riak::register();

See the code for parameters.

The default is to connect to the localhost, and to the 'default' bucket
type, and 'session' bucket.

The time() is stored to a t_int indexed field for every session.
