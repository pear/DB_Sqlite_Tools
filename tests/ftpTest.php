<?php
error_reporting(E_ALL|E_STRICT);

// FTP backup test

require_once 'DB/Sqlite/Tools.php';

$databases = array ('blogs.sqlite', 'code.sqlite');  // we set 2 databases for the backup operation


$sqlite = new DB_Sqlite_Tools ($databases);
$sqlite->showLogs = true ;
$path = '//Users/davidcosta/Sites/dbsqlitetools/bkp/';  // our backup path where the safe copies are prepared
$sqlite->copySafe($path); // safely copy the live databases to be uploaded
$sqlite->ftpBackup('hostname.com','username','password','remotedir'); // uploding the file in the remote backup server

/*


<logevent>  <class> DB_Sqlite_Tools </class>  <function> copySafe</function>  <data>'done'</data> </logevent> 

 <logevent>  <class> DB_Sqlite_Tools </class>  <function> scanBackupDir</function>  <data>array (  0 => 'blogs.sqlite.bkp',  1 => 'code.sqlite.bkp', )</data> </logevent> 

 <logevent>  <class> DB_Sqlite_Tools </class>  <function> ftpBackup</function>  <data>'//Users/davidcosta/Sites/dbsqlitetools/bkp/.blogs.sqlite.bkp uploaded'</data> </logevent> 

 <logevent>  <class> DB_Sqlite_Tools </class>  <function> ftpBackup</function>  <data>'//Users/davidcosta/Sites/dbsqlitetools/bkp/.code.sqlite.bkp uploaded'</data> </logevent> 
 */



