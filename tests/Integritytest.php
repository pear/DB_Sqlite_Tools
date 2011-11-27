<?php
require_once 'DB/Sqlite/Tools.php';

// one ore more databases in the array 
$databases = array('blogs.sqlite');  

// new object 
$sqlite = new DB_Sqlite_Tools($databases);
$sqlite->showLogs = true ;
// check integrity
$sqlite->checkIntegrity();
// the XML output, which will be displayed on the screen
// (if $sqlite->showLogs == true) and written to the logs DB (always)
// will look like
/*
<logevent>
   <class>DB_Sqlite_Tools</class>
   <function>checkIntegrity</function>
   <data>
'Integrity check returned ok'
   </data>
</logevent>
*/


