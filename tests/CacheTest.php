<?php

require_once 'Tools.php';

// require one or more databases in the array 
$databases = array ('blogs.sqlite');  

// instantiate a new DB_Sqlite_Tools object
$sqlite = new DB_Sqlite_Tools ($databases);

// with the debug true the new cache value will be displayed on the screen
$sqlite->debug = false;
// we increase the Cache value to 10000
$sqlite->CacheSize(10000);
// and exect the following output on the browser and optionally on our log db

/*

<logevent>
   <class>DB_Sqlite_Tools</class>
   <function>CacheSize</function>
   <data>
'cache correctly reset to 10000'
   </data>
</logevent>



*/ 


?>

