<?php

ini_set('error_reporing', 'E_ALL');
ini_set('display_errors', 'on');

require 'riak-session.php';

session_start();

@$_SESSION['counter'] += 1;

class A {
    public $field1;
    private $field2;
    public function __construct() {
        $this->field1 = 1;
        $this->field2 = 2;
    }
    public function __toString() {
        return "Instance of class 'A' field1: {$this->field1}, field2: {$this->field2}";
    }
}

if (!isset($_SESSION['object'])) {
    $_SESSION['object'] = new A();
}

?>
<!DOCTYPE html>
<html>



<head>

<title>Session test</title>

</head>



<body>

<h1>Session test</h1>

<p>This is a session test.</p>

<p>Counter: <?= $_SESSION['counter'] ?></p>

<p>Object: <?= $_SESSION['object'] ?></p>

</body>




</html>
