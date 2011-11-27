<?php
require_once 'DB/Sqlite/Tools.php';

// defining one or more databases in our array 
$databases = array('blogs.sqlite');  

$sqlite = new DB_Sqlite_Tools ($databases);
$sqlite->showLogs = true ;
// changing the sync value to FULL for one or more databases 
$sqlite->sync('FULL');

/* output 


<logevent>
   <class>DB_Sqlite_Tools</class>
   <function>sync</function>
   <data>
'Synchronous value correctly reset to FULL'
   </data>
</logevent>


*/
