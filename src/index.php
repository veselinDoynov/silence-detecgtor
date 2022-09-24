<?php
require 'vendor/autoload.php';

$task = new \App\Task();
print('<PRE>');
print_r($task->getAllData());
print('</PRE>');







