<?php

require_once 'Tools.php';

// require one or more databases in the array 
$databases = array ('blogs.sqlite');  

// instantiate a new DB_Sqlite_Tools object
$sqlite = new DB_Sqlite_Tools ($databases);
$sqlite->showLogs = true ;
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

