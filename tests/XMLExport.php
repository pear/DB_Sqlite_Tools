<?php
error_reporting(E_ALL|E_STRICT);
require_once 'DB/Sqlite/Tools.php';

// require one or more databases in the array 
$databases = array ('blogs.sqlite','rkc.sqlite');  

// instantiate a new DB_Sqlite_Tools object
$sqlite = new DB_Sqlite_Tools($databases);
$sqlite->showLogs = true ;

// copysafe is required before performing the XML export operation
// this is to ensure the integrity of the original database 
$path = '//Users/davidcosta/Sites/dbsqlitetools/bkpnew/';
$sqlite->copySafe($path);

// creating the XML export so we specify the path for the resulting XML databases 
$sqlite->createXMLdumps($path);


// and add the event to a log db logs.sqlite
// note that on the second parameter we specify
// true for the table creation and eventually the table
// name mylogs. This will work the first time with the table created, once we have a table
// we will call it as $sqlite->sqliteLogs('logs.sqlite.',false,'mylogs');
$sqlite->sqliteLogs('logs.sqlite.',false,'mylogs');


/* sample output 

<logevent>  <class> DB_Sqlite_Tools </class>  <function> createXMLDumps</function>  <data>'blogs.sqlite.xml successfully created'</data> </logevent> 

 <logevent>  <class> DB_Sqlite_Tools </class>  <function> createXMLDumps</function>  <data>'rkc.sqlite.xml successfully created'</data> </logevent> 


*/

