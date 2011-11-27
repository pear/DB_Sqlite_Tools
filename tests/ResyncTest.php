<?php
error_reporting(E_ALL|E_STRICT);
require_once 'DB/Sqlite/Tools.php';

// require one or more databases in the array 
$databases = array ('blogs.sqlite','rkc.sqlite');  

// instantiate a new DB_Sqlite_Tools object
$sqlite = new DB_Sqlite_Tools ($databases);
$sqlite->showLogs = true ;

/// we specify the path for our databases
// and the destination path for rsync
$dbpath ='/Users/davidcosta/Sites/dbsqlitetools/bkp/';
$dest = '/Users/davidcosta/Sites/dbsqlitetools/sync/';

$sqlite->localRsync($dbpath,$dest);


// and add the event to a log db logs.sqlite
// note that on the second parameter we specify
// true for the table creation and eventually the table
// name mylogs. This will work the first time with the table created, once we have a table
// we will call it as $sqlite->sqliteLogs('logs.sqlite.',false,'mylogs');
$sqlite->sqliteLogs('logs.sqlite.',false,'mylogs');


/* sample output 

<logevent>  <class> DB_Sqlite_Tools </class>  <function> localRsync</function>  <data>'rsync --verbose --stats --recursive --progress --delete /Users/davidcosta/Sites/dbsqlitetools/bkp/ /Users/davidcosta/Sites/dbsqlitetools/sync/'</data> </logevent> 

 <logevent>  <class> DB_Sqlite_Tools </class>  <function> localRsync</function>  <data>'building file list ... 0 files... 3 files to consider created directory /Users/davidcosta/Sites/dbsqlitetools/sync blogs.sqlite.bkp  32768 15% 0.00kB/s 0:00:00  207872 100% 7.26MB/s 0:00:00 rkc.sqlite  32768 46% 0.00kB/s 0:00:00  70656 100% 18.07MB/s 0:00:00  Number of files: 3 Number of files transferred: 2 Total file size: 278528 bytes Total transferred file size: 278528 bytes Literal data: 278528 bytes Matched data: 0 bytes File list size: 70 Total bytes written: 278726 Total bytes read: 52  wrote 278726 bytes read 52 bytes 185852.00 bytes/sec total size is 278528 speedup is 1.00 '</data> </logevent> 

*/ 


