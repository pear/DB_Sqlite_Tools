<?php

require_once 'Tools.php';

// one ore more databases in the array 
$databases = array ('blogs.sqlite');  

// new object 
$sqlite = new DB_Sqlite_Tools ($databases);

// check integrity
$sqlite->checkIntegrity();
// the XML output, which will be added to the db (optional) and displayed on the screen
// (always) will look like
/*
<logevent>
   <class>DB_Sqlite_Tools</class>
   <function>checkIntegrity</function>
   <data>
'Integrity check returned ok'
   </data>
</logevent>
*/


?>

