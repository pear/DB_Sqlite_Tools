<?php

require_once 'Tools.php';

// require one or more databases in the array 
$databases = array ('blogs.sqlite');  

// instantiate a new DB_Sqlite_Tools object
$sqlite = new DB_Sqlite_Tools ($databases);
$sqlite->showLogs = true ;
// we increase the Cache value to 10000
$sqlite->CacheSize(10000);
// and add the event to a log db logs.sqlite
// note that on the second parameter we specify
// true for the table creation and eventually the table
// name mylogs. This will work the first time with the table created, once we have a table
// we will call it as $sqlite->sqliteLogs('logs.sqlite.',false,'mylogs');
$sqlite->sqliteLogs('logs.sqlite.',true,'mylogs');
 
?>

