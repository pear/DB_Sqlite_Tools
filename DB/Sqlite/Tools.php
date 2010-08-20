<?php
/* vim: set expandtab tabstop=4 shiftwidth=4 softtabstop=4 foldmethod=marker: */

// {{{ Header

/**
 * Class to manage sqlite database. On object-oriented interface to sqlite
 * integrity check, optimizations and backup.
 *
 * PHP version 5
 *
 * LICENSE:
 *
 * BSD License
 *
 * Copyright (c) 2004-2006 David Costa
 * All rights reserved.
 *
 * Redistribution and use in source and binary forms, with or without
 * modification, are permitted provided that the following conditions
 * are met:
 *
 * 1. Redistributions of source code must retain the above copyright
 *    notice, this list of conditions and the following disclaimer.
 * 2. Redistributions in binary form must reproduce the above
 *    copyright notice, this list of conditions and the following
 *    disclaimer in the documentation and/or other materials provided
 *    with the distribution.
 * 3. Neither the name of David Costa nor the names of
 *    contributors may be used to endorse or promote products derived
 *    from this software without specific prior written permission.
 *
 * THIS SOFTWARE IS PROVIDED BY THE COPYRIGHT HOLDERS AND CONTRIBUTORS
 * "AS IS" AND ANY EXPRESS OR IMPLIED WARRANTIES, INCLUDING, BUT NOT
 * LIMITED TO, THE IMPLIED WARRANTIES OF MERCHANTABILITY AND FITNESS
 * FOR A PARTICULAR PURPOSE ARE DISCLAIMED. IN NO EVENT SHALL THE
 * COPYRIGHT OWNER OR CONTRIBUTORS BE LIABLE FOR ANY DIRECT, INDIRECT,
 * INCIDENTAL, SPECIAL, EXEMPLARY, OR CONSEQUENTIAL DAMAGES (INCLUDING,
 * BUT NOT LIMITED TO, PROCUREMENT OF SUBSTITUTE GOODS OR SERVICES;
 * LOSS OF USE, DATA, OR PROFITS; OR BUSINESS INTERRUPTION) HOWEVER
 * CAUSED AND ON ANY THEORY OF LIABILITY, WHETHER IN CONTRACT, STRICT
 * LIABILITY, OR TORT (INCLUDING NEGLIGENCE OR OTHERWISE) ARISING IN
 * ANY WAY OUT OF THE USE OF THIS SOFTWARE, EVEN IF ADVISED OF THE
 * POSSIBILITY OF SUCH DAMAGE.
 *
 * @category Database
 * @package DB_Sqlite_Tools
 * @license http://www.php.net/license/3_0.txt PHP License 3.0
 * @author David Costa <gurugeek@php.net>
 * @author Ashley Hewson <morbidness@gmail.com>
 * @author Firman Wandayandi <firman@php.net>
 * @copyright Copyright (c) 2004-2006 David Costa
 * @license http://www.opensource.org/licenses/bsd-license.php
 *          BSD License
 * @version CVS: $Id$
 */

// }}}
// {{{ Dependencies

/**
 * Load PEAR_Exception to throws.
 */
require_once 'PEAR/Exception.php';

// }}}
// {{{ Inline codes

// Avoid the fatal error, caused by redeclare
// the magic __autoload() function.
if (!function_exists('__autoload')) {
    function __autoload($class)
    {
        switch ($class) {
            case 'DB_Sqlite_Tools_DBC':
            case 'DB_Sqlite_Tools_XMLParser':
            case 'DB_Sqlite_Tools_ArcFour':
                include str_replace('_', '/', $class) . '.php';
                break;
        }
    }
} else {
// __autoload() already declared in the other line.
// There is no way just load all files.

    require_once 'DB/Sqlite/Tools/DBC.php';
    require_once 'DB/Sqlite/Tools/XMLParser.php';
    require_once 'DB/Sqlite/Tools/ArcFour.php';
}

// }}}
// {{{ Class: DB_Sqlite_Tools

/**
 * Class to manage sqlite database. An object-oriented interface to sqlite
 * integrity check, optimizations and backup and export. It curently supports
 * pragma integrity checks, cache values to optimize performance,
 * synchronisation values for an higher integrity protection.
 *
 * For backups it supports ftp, rsync (both local and remote) and local backups.
 *
 * It offer an extra in build export method to XML for a whole database schema
 * and data which can be particularly useful for backup and export purposes.
 *
 * Via the XML parser it allows to import and XML dump back to an Sqlite
 * database.
 *
 * @category Database
 * @package DB_Sqlite_Tools
 * @author David Costa <gurugeek@php.net>
 * @author Ashley Hewson <morbidness@gmail.com>
 * @author Firman Wandayandi <firman@php.net>
 * @copyright Copyright (c) 2004-2006 David Costa
 * @license http://www.opensource.org/licenses/bsd-license.php
 *          BSD License
 * @version Release: @package_version@
 */
class DB_Sqlite_Tools
{
    // {{{ Properties

    /**
     * Error handling related Constants
     *
     * Verbose error output in each of the cases
     */
    const DB_SQLITE_TOOLS_COD =
            'DB_DB_Sqlite_Tools Cannot Open Database File Possible Problem:';
    const DB_SQLITE_TOOLS_QRS =
            'Query Result.. ';
    const DB_SQLITE_TOOLS_QNR =
            'Query executed with no object returned as result';
    const DB_SQLITE_TOOLS_NAV =
            'Invalid parameter';
    const DB_SQLITE_TOOLS_COFL =
            'Cannot Open file for Lock';
    const DB_SQLITE_TOOLS_CCFL =
            'Cannot release lock on file';
    const DB_SQLITE_TOOLS_CLF =
            'Unable to activate the lock in the database file';
    const DB_SQLITE_TOOLS_CSF =
            'Cannot Save File';
    const DB_SQLITE_TOOLS_CCFS =
            'Cannot Connect to FTP Server';
    const DB_SQLITE_TOOLS_CLFS =
            'Cannot login to FTP Server';
    const DB_SQLITE_TOOLS_CUFS =
            'Cannot upload file on FTP Server';
    const DB_SQLITE_TOOLS_NAR =
            'The database array is empty';
    const DB_SQLITE_TOOLS_NNU =
            'parameter must be an integer';
    const DB_SQLITE_TOOLS_RSE =
            'Rsync Error see logfile for details';
    const DB_SQLITE_TOOLS_INSC =
            'Cannot backup the database remotely without safecopy';
    const DB_SQLITE_TOOLS_CSD =
            'Cannot scan directory';
    const DB_SQLITE_TOOLS_CCXML =
            'Cannot create XML';
    const DB_SQLITE_TOOLS_CCLXML =
            'Cannot close XML File';

    /**
     * Debug mode.
     *
     * @var string
     */
    public $debug = false;

    /**
     * List of databases.
     *
     * @var string
     */
    public $database = array();

    public $email;

    /**
     * Error storage.
     *
     * @var string
     */
    private $error;

    /**
     * Database opening process.
     *
     * @var string
     */
    private $opendb;

    /**
     * Query results.
     *
     * @var string
     */
    private $result;

    /**
     * FTP connection object.
     *
     * @var string
     */
    private $ftpconnection;

    /**
     * Info stores all the file related information for the given databases.
     *
     * @var string
     */
    private $info;

    /**
     * Used to verify if safecopy takes place before remote backup this is
     * necessary to ensure the live db integrity.
     *
     * @var string
     */
    private $safecopy;

    /**
     * Stores an array of log objects used for verbose output or logging
     * purposes.
     *
     * @array objects
     */
    private $logs = array();

    /**
     * Backup path.
     *
     * @var string
     */
    private $backupp;

    /**
     * An array of the databases available in the backup directory generated
     * after scanning the backup directory for all the .bkp files generated by
     * the copySafe function.
     *
     * @array
     */
    private $backupar;

    /**
     * Database object.
     *
     * @var string
     */
    private $dbobj;

    /**
     * If true, shows logs as they are added to DB.
     *
     * @var bool
     */
    public $showLogs ;

    // }}}
    // {{{ Constructor

    /**
     * Instantiate a new SQLite_Tools Object
     * Expects a db location or an array of db locations for
     * multiple checks
     *
     * @param string $database the Sqlite database.
     *
     * @return return $this->database array
     */
    public function __construct($database)
    {
        if (!is_array($database)) {
            $this->database[] = $database;
        } else{
            $this->database = $database;
        }

    }

    // }}}
    // {{{ displayLogs()

    /**
     * Display The logs on the screen in an XML-like format.
     */
    private function displayLogs()
    {
        foreach($this->logs as $logs) {
            if ($this->showLogs == true) {
                  print $logs;
                  /*echo "<pre>\n";
                  echo strtr($lstr, get_html_translation_table(HTML_SPECIALCHARS));
                  echo "<pre>\n";*/
            }
        }
    }

    // }}}
    // {{{ checkIntegrity()

    /**
     * Checks the database integrity does an integrity check of the entire
     * database including malformed records, corrupted pages out of order
     * records, invalid indeces returns okay on success or the error details,
     * if multiple is true the the value for $database in  the constructor is
     * expected to be an array with a list of databases.
     *
     * @return true
     * @throws PEAR_Exception
     */
    public function checkIntegrity()
    {
        if (!count($this->database)){
            throw new PEAR_Exception(self::DB_SQLITE_TOOLS_NAR . $this->database, -1);
        } else {
            foreach($this->database as $databases) {
                $this->sqliteConnect($databases);
                $this->sqliteQuery('PRAGMA integrity_check');
            }
        }

        $this->logs[] = new DB_Sqlite_Tools_LogObject(
                            __CLASS__, __FUNCTION__,
                            'Integrity check returned ' .
                            $this->result->integrity_check);
        $this->displayLogs();

        return true;
    }

    // }}}
    // {{{ cacheSize()

    /**
     * Check or set the cacheSize of the database.
     *
     * As a default value an sqlite database will hold 2000 disk pages in the
     * memory each page requires approximately 1.5 K of memory. For intensive
     * UPDATE or DELETE queries an higher memory might result in a speed
     * increase. If pages parameter is not defined it will return the current
     * cache size of the database or the multiple databases if $this->database
     * is an array with more then one element
     *
     * If a valid integer is passed as $pages parameter it will set the cache
     * size for the database or for multiple database if $this->database is an
     * array with more then one element
     *
     * If defined it will set the new cache size (in pages) as per the specified
     * integer.
     *
     * Note:
     * When setting a new cache size. It might get a query error if on
     * the database or one of the databases doesn't have the relevant write
     * permissions. Please set the necessary permissions on
     * the database/databases before attemping to change their cache value.
     *
     * @param int $pages (optional) number of pages to hold in the updated
     *                              memory allocation
     *
     * @return true
     * @throws PEAR_Exception
     */
    public function cacheSize($pages = false)
    {
        if (empty($pages)) {
            foreach($this->database as $databases) {
                $this->sqliteConnect($databases);
                $this->sqliteQuery('PRAGMA default_cache_size');
            }
        } else {
            if (!preg_match('/\d/', $pages)) {
                throw new PEAR_Exception(self::DB_SQLITE_TOOLS_NNU . $pages, -1);
            } else {
                foreach($this->database as $databases) {
                    $this->sqliteConnect($databases);
                    $this->sqliteQuery('PRAGMA default_cache_size = ' . $pages);
                }
            }
        }

        $this->logs[] = new DB_Sqlite_Tools_LogObject(
                            __CLASS__, __FUNCTION__,
                            'cache correctly reset to ' . $pages);
        $this->displayLogs();

        return true;
    }

    // }}}
    // {{{ sync()

    /**
     * Check or set the synchronous value for the database.
     *
     * Sqlite provides with different synchronous modes. The synchronous level
     * determines the integrity protection. At NORMAL (default mode) the sqlite
     * engine will stop at most critical moment providing with a limited
     * integrity protection of the database. In FULL mode the engine will stop
     * more often thus ensuring and higher integrity level. When OFF the engine
     * will continue as soon as the new data is provided without stopping.
     * Values are represented by an integer, 1 for NORMAL, 2 for FULL and 0 for
     * off.
     *
     * If the parameter value is not set it will return the current default
     * syncronous for the databases. If value is set with either FULL, NORMAL
     * or OFF it will change the default syncronous value for each of
     * the databases.
     *
     * @param sting $value (optional) A synchronous value.
     *
     * @return true
     * @throws PEAR_Exception
     */
    public function sync($value = false)
    {
        if (empty ($value)) {
            foreach($this->database as $databases) {
                $this->sqliteConnect($databases);
                $this->sqliteQuery('PRAGMA default_synchronous');
            }
        } else {
            foreach($this->database as $databases) {
                $this->sqliteConnect($databases);
                if (eregi('FULL|NORMAL|OFF', $value)) {
                    $this->sqliteQuery('PRAGMA default_synchronous = ' . $value);
                } else {
                    throw new PEAR_Exception(self::DB_SQLITE_TOOLS_NAV . $value, -1);
                }
            }
        }

        $this->logs[] = new DB_Sqlite_Tools_LogObject(
                            __CLASS__, __FUNCTION__,
                            'Synchronous value correctly reset to ' . $value);
        $this->displayLogs();

        return true;
    }

    // }}}
    // {{{ dbFileInfo()

    /**
     * Retrieve general information on the database files like filename,
     * owner id, group, name, permissions, library encoding and version,
     * the size of the database, last time it was modified, blocks and
     * generally every possible information on the database files.
     *
     * @return array An array of databases info.
     * @throws PEAR_Exception
     */
    public function dbFileInfo()
    {
        foreach($this->database as $databases) {
            $this->info[$databases] = array();
            $stat = lstat($databases);
            $this->info[$databases]['file_name'] = $databases;
            $this->info[$databases]['file_owner_id'] = fileowner($databases);
            if (function_exists('posix_getpwuid')) {
                $userinfo = posix_getpwuid($this->info[$databases]['file_owner_id']);
            }

            //on windows $userinfo=null
            $this->info[$databases]['file_owner_name'] =
                empty($userinfo['name']) ? '' : $userinfo['name'];
            //on windows $userinfo=null
            $this->info[$databases]['file_owner_group'] =
                empty($userinfo['gid']) ? '' : $userinfo['gid'];
            $this->info[$databases]['libenconding'] = sqlite_libencoding();
            $this->info[$databases]['libversion'] = sqlite_libversion();
            $this->info[$databases]['permissions'] = fileperms($databases);
            $this->info[$databases]['file_dev'] = $stat['dev'];
            $this->info[$databases]['file_ino'] = $stat['ino'];
            $this->info[$databases]['file_mode'] = $stat['mode'];
            $this->info[$databases]['file_nlink'] = $stat['nlink'];
            $this->info[$databases]['file_uid'] = $stat['uid'];
            $this->info[$databases]['file_rdev'] = $stat['rdev'];
            $this->info[$databases]['file_size'] = ($stat['size'] / 1024) .'KB';
            $this->info[$databases]['file_atime'] = date('r', $stat['atime']);
            $this->info[$databases]['file_mtime'] = date('r', $stat['mtime']);
            $this->info[$databases]['file_ctime'] = date('r', $stat['ctime']);
            $this->info[$databases]['file_blksize'] = $stat['blksize'];
            $this->info[$databases]['file_blocks'] = $stat['blocks'];
            $this->logs [] = new DB_Sqlite_Tools_LogObject(
                                __CLASS__, __FUNCTION__,
                                $this->info[$databases]);
        }

        $this->displayLogs();

        return $this->info;
    }

    // }}}
    // {{{ copySafe()

    /**
     * Safely duplicates a database file. copySafe should be performed before
     * a backup as transfering via ftp or rsync.
     *
     * With this function we obtain an exclusive lock on the database before the
     * copy, we then check the integrity of the copied databases.
     * if the integrity check on the copied database returns OK this means that
     * we have a cloned database ready for backup purposes.
     *
     * Individual or multiple databases are duplicated with the same name of the
     * original database and the suffix .bkp
     *
     * @param string $path (optional) Destination path, default is current
     *                                working path.
     *
     * @throws PEAR_Exception
     */
    public function copySafe($path = '')
    {
        // Set the output path to current working dir.
        if (trim($path) == '') {
            $path = './';
        }

        $this->backupp = $path;

        if (!count($this->database)) {
            throw new PEAR_Exception(self::DB_SQLITE_TOOLS_NAR . $this->database, -1);
        } else {
            foreach($this->database as $databases) {
                $fopenlock = @fopen($databases, 'r');
                if (!@$fopenlock) {
                    throw new PEAR_Exception(self::DB_SQLITE_TOOLS_COFL,-1);
                }

                $lock = @flock($fopenlock, LOCK_EX);
                if (!@$lock) {
                    throw new PEAR_Exception(self::DB_SQLITE_TOOLS_CLF,-1);
                }

                $clone = @copy($databases, $path . DIRECTORY_SEPARATOR . basename($databases) .'.bkp');
                if (!@$clone) {
                    throw new PEAR_Exception(self::DB_SQLITE_TOOLS_CSF,-1);
                } else {
                    $this->sqliteConnect($databases.'.bkp');
                    $this->sqliteQuery('PRAGMA integrity_check');
                }

                $release = @flock($fopenlock, LOCK_UN);
                if (!@$release) {
                    throw new PEAR_Exception(self::DB_SQLITE_TOOLS_CCFL,-1);
                }
            }
        }

        $this->safecopy = true;
        $this->logs[] = new DB_Sqlite_Tools_LogObject(__CLASS__, __FUNCTION__, 'done');
        $this->displayLogs();
        return true;
    }

    // }}}
    // {{{ scanBackupDir()

    /**
     * Scans the backup directory as defined on copysafe for sqlite database
     * preparing for the ftp backup
     *
     * It looks for previously backed up files with extension .bkp
     *
     * @return true
     * @throws PEAR_Exception
     */
    private function scanBackupDir()
    {
        //scanning the directory
        $scanned = @scandir($this->backupp, 0);

        if (!$scanned) {
            throw new PEAR_Exception(self::DB_SQLITE_TOOLS_CSD, -1);
        }

        foreach ($scanned as $this->parsed) {
            // check if is an backup db file obtained in the previous copySafe
            if(substr($this->parsed, -4) == '.bkp'){
                      $this->backupar[] = $this->parsed;
            }
        }

        $this->logs[] = new DB_Sqlite_Tools_LogObject(
                            __CLASS__, __FUNCTION__, $this->backupar);
        return true ;
    }

    // }}}
    // {{{ ftpBackup()

    /**
     * Safely copy the backup db to a remote ftp server. this function can be
     * called only after a copySafe operation is performed.
     *
     * It will backup every database in the backup dir which is set with
     * copySafe. If the intended use is to backup different group of databases
     * the user can simply set a different path for copySafe.
     *
     * @param string $server   remote ftp server
     * @param string $username ftp username
     * @param string $password ftp password
     * @param string $path     remote ftp path
     *
     * @return true
     * @throws PEAR_Exception
     */
    public function ftpBackup($server, $username, $password, $path = '')
    {
        if ($this->safecopy == false) {
            throw new PEAR_Exception(self::DB_SQLITE_TOOLS_INSC, -1);
        }

        $this->scanBackupDir();
        $this->ftpConnect($server, $username, $password);

        foreach($this->backupar as $database) {
            $localdb = $this->backupp . DIRECTORY_SEPARATOR . $database;
            $put = @ftp_put($this->ftpconnection, $path . '/' . $database,
                           $localdb, FTP_BINARY);

            if (!$put) {
                throw new PEAR_Exception(self::DB_SQLITE_TOOLS_CUFS);
            } else {
                $this->logs[] = new DB_Sqlite_Tools_LogObject(
                                    __CLASS__, __FUNCTION__,
                                    $localdb . ' uploaded');
            }
        }

        $this->displayLogs();
        return true;
    }

    // }}}
    // {{{ ftpConnect()

    /**
     * Connects to the remote ftp server
     *
     * @param string $server   remote ftp server
     * @param string $username ftp username
     * @param string $password ftp password
     *
     * @throws PEAR_Exception
     */
    private function ftpConnect ($server,$username,$password)
    {
        $this->ftpconnection = @ftp_connect($server);
        if (!@$this->ftpconnection) {
            throw new PEAR_Exception (self::DB_SQLITE_TOOLS_CCFS,-1);
        }

        $logged = ftp_login($this->ftpconnection, $username, $password);
        if (!@$logged) {
            throw new PEAR_Exception(self::DB_SQLITE_TOOLS_CLFS,-1);
        }
    }

    // }}}
    // {{{ localBackup()

    /**
     * Creates a local backup of the database file and checks for its integrity.
     *
     * @param string $destination Local backup path.
     *
     * @throws PEAR_Exception
     */
    public function localBackup($destination)
    {
        foreach($this->database as $databases) {
            $backupfile = $destination.'/'.$databases.'.bkp';

            $fopenlock = @fopen($databases, 'r');
            if (!@$fopenlock) {
                throw new PEAR_Exception(self::DB_SQLITE_TOOLS_COFL, -1);
            }

            $lock = @flock($fopenlock, LOCK_EX);
            if (!@$lock) {
                throw new PEAR_Exception(self::DB_SQLITE_TOOLS_CLF, -1);
            }

            $clone = @copy($databases, $backupfile);
            if (!@$clone) {
                throw new PEAR_Exception(self::DB_SQLITE_TOOLS_CSF, -1);
            } else {
                $this->logs[] = new DB_Sqlite_Tools_LogObject(
                                    __CLASS__, __FUNCTION__,
                                    $backupfile .' successfully copied');

                $this->sqliteConnect($backupfile);
                $this->sqliteQuery('PRAGMA integrity_check');
            }

            $release = @flock($fopenlock, LOCK_UN);
            if (!@$release) {
                throw new PEAR_Exception(self::DB_SQLITE_TOOLS_CCFL, -1);
            }
        }

        $this->displayLogs();
        return true;
    }

    // }}}
    // {{{ localRsync()

    /**
     * Initiaties a local rsync of a given database path requires rsync
     * installed locally.
     *
     * @param string $databasesPath  Path to the databases folder to sync locally
     * @param string $backupsPath    Sync Destionation path
     * @param string $options        Rsync options
     *
     * @throws PEAR_Exception
     */
    public function localRsync ($databasesPath,$backupsPath,
                                $options='--verbose --stats --recursive --progress --delete')
    {
        $command = 'rsync ' . $options . ' ' . $databasesPath . ' ' . $backupsPath;
        $this->logs[] = new DB_Sqlite_Tools_LogObject(__CLASS__, __FUNCTION__, $command);
        $command = escapeshellcmd($command);
        $exec = shell_exec($command);
        $this->logs[] = new DB_Sqlite_Tools_LogObject(__CLASS__, __FUNCTION__, $exec);
        $this->displayLogs();
    }

    // }}}
    // {{{ remoteRsync()

    /**
     * remoteRsync prepared the server where this package is running to receive
     * read connections over rsync for the purpose of remote synchronisation.
     * it created a valid rsync configuration file and starts the deamon
     * allowing connection from the allowed host.
     *
     * This function is experimental.
     *
     * @param sting $databasesPath    Path for the databases folder
     * @param string $configfile      name of the rsync config file
     * @param string $configfilepath  path where the rsync config file will be saved
     *                                the users that runs apache must have permissions
     *                                on this path for the config, the pid and the lock file
     * @param string $uid             user id under which rsync will run
     * @param string $gid             group id under which rsync will run
     * @param string $allowedhost     ip or domain of the allowed host
     * @param string $logs            logs file name and path
     * @param string $secretfile      rsync secret file location (which should contain the password)
     *
     * @throws PEAR_Exception
     */
    public function remoteRsync ($databasesPath, $configfile, $configfilepath,
                                $uid ,$gid , $allowedhost,$logs,$secretfile)
    {
        $rsynconfig= <<<RSYNC
uid = $uid
gid = $gid
use chroot = no
max connections = 4
syslog facility = local5
pid file = $configfilepath/rsyncd.pid
lock file =  $configfilepath/rsyncd.lock
strict modes = no
[sqlite_tools]
read only= yes
path = $databasesPath
auth users = rsync
hosts allow = $allowedhost
secrets file = $secretfile
RSYNC;

        $cffile = $configfilepath . '/' . $configfile;
        $sfile=@fopen($cffile,'w+');
        if (!@$sfile){
            throw new PEAR_Exception (self::DB_SQLITE_TOOLS_RSE,-1);
        }

        $sfilew=@fwrite($sfile,$rsynconfig);
        if (!@$sfilew){
            throw new PEAR_Exception (self::DB_SQLITE_TOOLS_RSE,-1);
        }

        $sfileclose=fclose($sfile);
        if (!@$sfileclose){
            throw new PEAR_Exception (self::DB_SQLITE_TOOLS_RSE,-1);
        }

        print_r($cffile);

        $spec = array(
            0 => array('pipe', 'r'),  // read pipe
            1 => array('pipe', 'w'),  // write pipe
            2 => array('file', $logs, 'a') // logs file
        );
        $pipes = '';

        // added port 2000 because the apache user doesn't have permissions in
        // the default rsync port
        $proc = proc_open("rsync --no-detach --daemon --port=2000 --config=$cffile",
                          $spec, $pipes);

        fclose($pipes[1]);
        $output = proc_close($proc);
        $this->logs[] = new DB_Sqlite_Log_Object(__CLASS__, __FUNCTION__, $output);
        $this->displayLogs();
    }

    // }}}
    // {{{ sqliteLogs()

    /**
     * Logs all the perfomed action in a database log
     * this includes backups, integrity checks
     * each of the integrity queries. Verbose XML style output for each of the
     * actions.
     *
     * Sample log output -
     * <logevent>  <class>DB_Sqlite_Tools</class>  <function>cacheSize</function>
     * <data> 'cache correctly reset to 10000'  </data> </logevent>
     *
     * All the logs are saved with the time of execution. The user can easily
     * generate customized logs from the raw data provided in this package.
     *
     * @param string $db             Logs database
     * @param string $maketable      create a default log table
     * @param string $table          table name
     *
     * @throws PEAR_Exception on failure
     */
    public function sqliteLogs($db = '', $maketable = true, $table = '')
    {
        $this->sqliteConnect($db);
        if ($maketable == true) {
             $sql = "CREATE table $table (
                        id  INTEGER PRIMARY KEY,
                        log VARCHAR (200),
                        date VARCHAR (200))";
            $this->sqliteQuery($sql);
        }

        foreach($this->logs as $logs) {
                $time = time();
                $lstr = $logs->toString() ;
                $sstr = sqlite_escape_string($lstr);
                $sql = "INSERT into $table(log, date)
                        VALUES ('$sstr',$time)";
                $this->sqliteQuery($sql);
        }

        unset($this->logs);
    }

    // }}}
    // {{{ sqliteConnect()

    /**
     * Connects to the SQLite database.
     *
     * @param string $db Path to the database
     *
     * @return true
     * @throws PEAR_Exception on failure
     */
    private function sqliteConnect($db)
    {
        $this->debug('Connecting to ' . $db . "\n");

        try {
            $obj = $this->dbobj = new SQLiteDatabase($db);
        } catch (PEAR_Exception $obj) {
            print $obj;
            return false ;
        }

        return true;
    }

    // }}}
    // {{{ sqliteConnectProc()

    /**
     * Procedural version of sqliteConnect.
     *
     * @param string $db Path to the database
     *
     * @return TRUE
     * @throws PEAR_Exception
     */
    private function sqliteConnectProc ($db)
    {
        $this->debug(print 'Connecting to ' . $db . "\n");

        $this->opendb = @sqlite_open($db, 0666, $this->error);

        if (!@$this->opendb){
            throw new PEAR_Exception (self::DB_SQLITE_TOOLS_COD . $this->error, -1);
        }

        return true ;
    }

    // }}}
    // {{{ sqliteQuery()

    /**
     * Queries the sqlite database.
     *
     * @param string $sql query
     *
     * @return $this->results
     */
    private function sqliteQuery($sql)
    {
        $results = $this->dbobj->query($sql);
        if ($results != false) {
            $this->result = $results->fetchObject();
            if (!is_object($this->result)) {
                $this->debug(self::DB_SQLITE_TOOLS_QNR);
            } else {
                $this->debug(
                    self::DB_SQLITE_TOOLS_QRS . "\n" .
                    print_r($this->result, true)
                );
            }
        }
        return $this->result;
    }

    // }}}
    // {{{ XMLDecode()

    /**
     * Decodes the XML content.
     *
     * @param string $str A content.
     *
     * @return string
     */
    public function XMLDecode($str)
    {
        return strtr($str, array_flip(get_html_translation_table(HTML_SPECIALCHARS)));
    }

    // }}}
    // {{{ XMLEncode()

    /**
     * Encodes the XML content.
     *
     * @param string $str A content.
     *
     * @return string
     */
    public function XMLEncode($str)
    {
       return strtr($str, get_html_translation_table(HTML_SPECIALCHARS));
    }

    // }}}
    // {{{ performXMLDump()

    /**
     * Creates an XML dump for a full sqlite database the dump can be
     * manipulated or used to export the data in several other formats or
     * converted back to an sqlite database with the method createDBFromXML
     *
     * @param string $db the sqlite database file to convert
     * @param string $fh the XML file used for the dump
     *
     */
    private function performXMLDump($db, $fh)
    {
        $this->sqliteConnectProc($db);

        // Obtain a list of all tables
        $tableList = array();
        $result = sqlite_query($this->opendb, "SELECT name FROM sqlite_master WHERE type='table'");

        if ($result) {
            while (sqlite_has_more($result)) {
                $table = array();
                // Fetch name
                $table["name"] = sqlite_fetch_single($result);
                // Perform a query on the table to get it's columns
                $table["columns"] = array();
                $columnQuery = sqlite_query($this->opendb, "SELECT ALL * FROM ".$table["name"]." LIMIT 1");
                $numColumns = sqlite_num_fields($columnQuery);
                for ($i = 0;$i<$numColumns;$i++) {
                    $table["columns"][$i] = array();
                    $table["columns"][$i]["name"] = sqlite_field_name($columnQuery, $i);
                    $table["columns"][$i]["type"] = "";
                }
                array_push($tableList, $table);
            }
        }

        // XML declaration
        fputs($fh, '<?xml version="1.0" encoding="ISO-8859-1" ?>'."\n");
        fputs($fh, "<db name=\"$db\">\n");
        foreach($tableList as $table) {
            fputs($fh, "   <table name=\"".$table["name"]."\">\n");
            fputs($fh, "      <columns count=\"".count($table["columns"]) ."\">\n");
            foreach($table["columns"] as $column) {
                fputs($fh, "          <column name=\"".$column["name"]."\" />\n");
            }
            fputs($fh, "      </columns>\n");
            $rowQuery = sqlite_query($this->opendb, "SELECT ALL * FROM ".$table["name"]);
            fputs($fh, "      <rows count=\"".sqlite_num_rows($rowQuery) ."\">\n");
            while (sqlite_has_more($rowQuery)) {
                $rowArray = sqlite_fetch_array($rowQuery);
                fputs($fh, "          <row>\n");
                foreach($table["columns"] as $column) {
                    fputs($fh, "             <column name=\"".$column["name"]."\">");
                    fputs($fh, $this->XMLEncode($rowArray[$column["name"]]));
                    fputs($fh, "</column>\n");
                }
                fputs($fh, "          </row>\n");
            }
            fputs($fh, "      </rows>\n");
            fputs($fh, "   </table>\n");
        }
        fputs($fh, "</db>");
    }

    // }}}
    // {{{ createXMLDumps()

    /**
     * For each of the databases in our contructor array creates an XML dump
     * including all the tables of that database.
     * The XML file is generated via the function performXMLDump
     * The backup file is by default the database name .xml
     *
     * @return true
     * @throws  PEAR exception on failure
     */
    public function createXMLDumps($path ='')
    {
        if ($this->safecopy == false) {
            throw new PEAR_Exception(self::DB_SQLITE_TOOLS_INSC, -1);
        }

        $this->scanBackupDir();
        foreach($this->backupar as $database) {
            $XMLFile = @fopen($path . $database . '.xml', 'w');
            if (!$XMLFile) {
                throw new PEAR_Exception(self::DB_SQLITE_TOOLS_CCXML . $database . '.xml', -1);
            }

            $this->performXMLDump($this->backupp.DIRECTORY_SEPARATOR . $database, $XMLFile);
            $XMLclose = @fclose($XMLFile);
            if (!$XMLclose) {
                throw new PEAR_Exception(self::DB_SQLITE_TOOLS_CCLXML . $database . '.xml', -1);
            }

            $this->debug("{$database}.xml successfully created\n");
            $this->logs[] = new DB_Sqlite_Tools_LogObject(
                __CLASS__, __FUNCTION__, "{$database}.xml successfully created"
            );
        }

        $this->displayLogs();
        return true;
    }

    // }}}
    // {{{ createDBFromXML()

    /**
     * Converts an XML exported database back to an Sqlite database
     *
     * @param string $xmlFile  the XML database dump to convert
     * @param string $db       the sqlite db to be generated from the XML data
     *
     */
    public function createDBFromXML($xmlFile, $db)
    {
        $this->debug("Now importing XML from $xmlFile to $db\n");

        $fh = fopen($xmlFile, "r");

        $this->debug("opened $xmlFile as $fh\n");

        $parser = new DB_Sqlite_Tools_XMLParser( $fh ) ;
        $parser->ignore('^\?.*$') ;
        $parser->ignore('^!--.*$') ;

        $parser->getNextElement() ;

        if ($parser->getElementName() != 'db') {
            return false;
        }

        $tables = array() ;

        while ($parser->getNextElement()) {
            if ($parser->getElementName() == '/db') {
                break ;
            } else if ($parser->getElementName() == 'table') {
                $tableName = $parser->getElementAttribute('name');
                $columns = array() ;
                $rows = array() ;

                while($parser->getNextElement()) {
                    if($parser->getElementName() == 'rows') {
                        $this->debug("Begin rows\n");
                        $i = 0 ;
                        while ($parser->getNextElement()) {
                            if ($parser->getElementName() == 'row') {
                                $rows[$i] = array() ;
                                while ($parser->getNextElement()) {
                                    if ($parser->getElementName() == 'column') {
                                        $curColumn = $parser->getElementAttribute('name');
                                        $this->debug("Column $curColumn in row $i\n");
                                    } else if ($parser->getElementName() == '/column') {
                                        $rows[$i][$curColumn] = $this->XMLDecode($parser->getEnclosed());
                                        $this->debug("Close column $curColumn in row $i\n");
                                    } else if ($parser->getElementName() == '/row') {
                                        break ;
                                    }
                                // end while
                                }
                                $i++ ;
                            // end "row"
                            } else if ($parser->getElementName() == '/rows') {
                                break ;
                            }
                        // end while
                        }
                    // end "rows"
                    } else if ($parser->getElementName() == 'columns') {
                        $this->debug("Begin columns\n");
                        $i = 0 ;
                        while ($parser->getNextElement()) {
                            if ($parser->getElementName() == 'column') {
                                $columns[$i] = array() ;
                                $columns[$i]['name'] = $parser->getElementAttribute('name');
                                $i++ ;
                            // end "row"
                            } else if ($parser->getElementName() == '/columns') {
                                $this->debug("End columns\n");
                                break ;
                            }
                        // end while
                        }
                    // end columns
                    }

                    if ($parser->getElementName() == '/table') {
                        break ;
                    }

                // end while
                }

                array_push( $tables, array( "name" => $tableName, "columns" => $columns, "rows" => $rows ) ) ;

            // end table
            }

        // end while
        }

        $this->debug("Now performing SQL queries...\n");

        $this->sqliteConnect($db);
        $this->sqliteQuery('BEGIN TRANSACTION;');

        foreach ($tables as $table) {
            $columnList ='';
            foreach ($table['columns'] as $column) {
                $columnList=$columnList.$column["name"]."," ;
            }

            // Remove trailing comma
            $columnList = substr($columnList, 0, strlen($columnList) - 1);

            $this->sqliteQuery("CREATE TABLE {$table['name']} ($columnList);");
            foreach ($table['rows'] as $row ) {
                $fieldNameList = '';
                $fieldDataList = '';
                foreach( $row as $fieldName => $fieldData ) {
                    $fieldNameList = $fieldNameList . $fieldName . ',';
                    $fieldDataList = $fieldDataList . '\'' . sqlite_escape_string($fieldData) . '\',';
                }

                // Remove trailing commas
                $fieldNameList=substr($fieldNameList,0,strlen($fieldNameList)-1) ;
                $fieldDataList=substr($fieldDataList,0,strlen($fieldDataList)-1) ;
                $this->sqliteQuery(
                    "INSERT OR ROLLBACK INTO
                        {$table['name']} ($fieldNameList)
                     VALUES
                        ($fieldDataList);"
                );
            }
        }

        $this->sqliteQuery('COMMIT;') ;
        fclose($fh) ;
    }

    // }}}
    // {{{ debug()

    /**
     * Set and display debug message if debug is On.
     *
     * @param string $msg Debug message.
     */
    private function debug($msg)
    {
        if ($this->debug) {
            // Web context.
            if (isset($_SERVER['REQUEST_URI'])) {
                $msg = nl2br($msg);
            }

            echo 'DB_Sqlite_Tools debug: ' . $msg;
        }
    }

    // }}}
}

// }}}
// {{{ Class: DB_Sqlite_Tools_LogObject

/**
 * A class provides few function for working with the event log.
 *
 * @category Database
 * @package DB_Sqlite_Tools
 * @author David Costa <gurugeek@php.net>
 * @author Ashley Hewson <morbidness@gmail.com>
 * @author Firman Wandayandi <firman@php.net>
 * @copyright Copyright (c) 2004-2006 David Costa
 * @license http://www.opensource.org/licenses/bsd-license.php
 *          BSD License
 * @version Release: @package_version@
 */
class DB_Sqlite_Tools_LogObject
{
    // {{{ Properties

    /**
     * Class name.
     *
     * @var string
     */
    private $class;

    /**
     * Function name.
     *
     * @var string
     */
    private $function;

    /**
     * Log data or message.
     *
     * @var string
     */
    public $data;

    // }}}
    // {{{ Constructor()

    /**
     * Constructor.
     *
     * @param string $class A class name.
     * @param string $function A function name.
     * @param string $data Log data or message.
     */
    public function __construct($class, $function, $data)
    {       $this->class = $class;
            $this->function = $function;
            $this->data = $data;
    }

    // }}}
    // {{{ __toString()

    /**
     * Magic function, to print this object.
     *
     * This function determine the context of which representation
     * to return.
     *
     * @return string The event log.
     */
    public function __toString()
    {
        if (isset($_SERVER['REQUEST_URI'])) {
            return $this->toHtml();
        }
        return $this->toText();
    }

    // }}}
    // {{{ toXML()

    /**
     * Creates an XML representation of event log.
     *
     * @return string The XML representation of the event log.
     */
    public function toXML()
    {
      $data = var_export($this->data, true);

return <<<XML
<logevent>
  <class>$this->class</class>
  <function>$this->function</function>
  <data>$data</data>
</logevent>
XML;
    }

    // }}}
    // {{{ toText()

    /**
     * Creates a plain-text representation of event log.
     *
     * @return string The plain-text representation of event log.
     */
    public function toText()
    {
        return 'DB_Sqlite_Tools log: ' .
               'logged on ' . $this->class . '::' . $this->function . '(), ' .
               'data=' . print_r($this->data, true) . "\n";
    }

    // }}}
    // {{{ toHTML()

    /**
     * Creates a HTML representation of event log.
     *
     * @return string The HTML representation of event log.
     */
    public function toHTML()
    {
        $data = $this->data;
        if (is_array($data) || is_object($this->data)) {
            $data = '<pre>' . "\n"
                  . print_r($data, true)
                  . '</pre>' . "\n";
        }

        return '<table border="1" cellspacing="0">' . "\n"
             . '<tr><td colspan="3" bgcolor="#aaaaaa" align="center"><b>DB_Sqlite_Tools log</b></td></tr>' . "\n"
             . '<tr>'
             . '<td align="center" bgcolor="#cccccc"><b>Event</b></td>'
             . '<td align="center" bgcolor="#cccccc"><b>Data</b></td>'
             . '</tr>' . "\n"
             . '<tr>'
             . '<td valign="top">' . $this->class . '::' . $this->function . '()' . '</td>'
             . '<td>' . $data . '</td>'
             . '</tr>' . "\n"
             . '</table>';
    }

    // }}}
}

// }}}

/*
 * Local variables:
 * mode: php
 * tab-width: 4
 * c-basic-offset: 4
 * c-hanging-comment-ender-p: nil
 * End:
 */
?>
