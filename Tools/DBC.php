 <?php
/* vim: set expandtab tabstop=4 shiftwidth=4 softtabstop=4  */
// +----------------------------------------------------------------------+
// | PHP version 5                                                        |
// +----------------------------------------------------------------------+
// | Copyright (c) 2004 David Costa                                       |
// | All rights reserved.                                                 |
// +----------------------------------------------------------------------+
// |                                                                      |
// | Redistribution and use in source and binary forms, with or without   |
// | modification, are permitted provided that the following conditions   |
// | are met:                                                             |
// |                                                                      |
// | Redistributions of source code must retain the above copyright       |
// | notice, this list of conditions and the following disclaimer.        |
// |                                                                      |
// | Redistributions in binary form must reproduce the above copyright    |
// | notice, this list of conditions and the following disclaimer in the  |
// | documentation and/or other materials provided with the distribution. |
// |                                                                      |
// | Neither the name of David Costa nor the names of his contributors may|
// | be used to endorse or promote products derived from this software    |
// | without specific prior written permission.                           |
// |                                                                      |
// | THIS SOFTWARE IS PROVIDED BY THE COPYRIGHT HOLDERS AND CONTRIBUTORS  |
// | "AS IS" AND ANY EXPRESS OR IMPLIED WARRANTIES, INCLUDING, BUT NOT    |
// | LIMITED TO, THE IMPLIED WARRANTIES OF MERCHANTABILITY AND FITNESS    |
// | FOR A PARTICULAR PURPOSE ARE DISCLAIMED.  IN NO EVENT SHALL THE      |
// | REGENTS OR CONTRIBUTORS BE LIABLE FOR ANY DIRECT, INDIRECT,          |
// | INCIDENTAL, SPECIAL, EXEMPLARY, OR CONSEQUENTIAL DAMAGES (INCLUDING, |
// | BUT NOT LIMITED TO, PROCUREMENT OF SUBSTITUTE GOODS OR SERVICES; LOSS|
// |  OF USE, DATA, OR PROFITS; OR BUSINESS INTERRUPTION) HOWEVER CAUSED  |
// | AND ON ANY THEORY OF LIABILITY, WHETHER IN CONTRACT, STRICT          |
// | LIABILITY, OR TORT (INCLUDING NEGLIGENCE OR OTHERWISE) ARISING IN ANY|
// | WAY OUT OF THE USE OF THIS SOFTWARE, EVEN IF ADVISED OF THE          |
// | POSSIBILITY OF SUCH DAMAGE.                                          |
// +----------------------------------------------------------------------+
// | Authors: David Costa     <gurugeek@php.net>                          |
// | Authors: Radu Negoescu   <negora@dawnideas.com>                      |
// +----------------------------------------------------------------------+
//
// \$Id$  $

/**
 * Class to manage sqlite database. On object-oriented interface to
 * sqlite integrity check, optimizations and backup
 *
 * @category Database
 * @package  DB_Sqlite_Tools
 * @author   David Costa <gurugeek@php.net>
 * @author   Ashley Hewson <morbidness@gmail.com>
 * @author   Radu Negoescu <negora@dawnideas.com>
 * @license  http://www.php.net/license/3_0.txt PHP License 3.0
 * @version  @VER@
 * @version  $Id$
 *
 */

/**
 * This class, part of DB_Sqlite_Tools allows to insert on an sqlite database
 * securely encrypted data and retrieve and decript on the fly the encrypted data.
 * Since Sqlite might be seen as voulnerable, encrypted database will ensure the data integrity.
 * It doesn't require PHP to be compiled with MCrypt but it uses Crypt_ArcFour, embedded (ported to
 * PHP 5 the original PEAR package).
 *
 * @class    DB_Sqlite_Tools_DBC 
 * @author   David Costa <gurugeek@php.net>
 * @author   Radu Negoescu <negora@dawnideas.com>
 * @license  http://www.php.net/license/3_0.txt PHP License 3.0
 * @version  @VER@
 * @version  $Id$
 *
 * @ 
 */
    
require_once 'DB/Sqlite/Tools.php';

class DB_Sqlite_Tools_DBC {
        private $dbobj;                     // the database object
        public $result;                     // results object
        public $debug = true;               // debug mode
        public $matrix;                     // ArcFour crypt object
        public $key;                        // crypt key 
        const DB_STRING_DELIMITER = "'";    // delimited for autoExec
        const DB_AUTOQUERY_INSERT = 1;      // insert mode
        const DB_AUTOQUERY_UPDATE = 2;      // update mode 
    

        public function __construct() 
        {
            $this->matrix = new DB_Sqlite_Tools_ArcFour; // instantiate a new ArcFour object
            $key = $this->key;
            $this->matrix->key($key);
        }


    /**
     * Auto Execute an insert or update query  I
     * @param string $tableName     DB table name
     * @param string $tableFields   DB fields name
     * @param string $tableValues   DB table value
     * @param string $querytype     type of query, insert by default
     * @param string $crypt         crypt with ArcFour before inserting the data, default true
     * @param string $whereString   necessary for UPDATE, default null
     * @return $mixedvar
     * @throws DB_Sqlite_Tools_Exception
     */

    public function liteautoExec($tableName = null, $tableFields = null, $dataValues = null, 
    $queryType = self::DB_AUTOQUERY_INSERT, $crypt = true, $whereString = null) 
    {
        if ($this->key == null) 
        throw new DB_Sqlite_Tools_Exception
        ('You need to specify an encryption key',-1);
        if ($crypt == true) {
            foreach($dataValues as $matrix=>&$value) {   
                 $this->matrix->crypt($value);
            }
        }
        if (empty($tableName)) {
            throw new DB_Sqlite_Tools_Exception
            ('You need to specify a table',-1);
        }
        if (empty($tableFields)) {
            throw new DB_Sqlite_Tools_Exception
            ('You need to specify the table fields',-1);
        }
        if (empty($dataValues)) {
            throw new  DB_Sqlite_Tools_Exception
            ('You need to specify the values',-1);
        }
        if (!is_array($tableFields)) {
            $tableFields = array($tableFields);
        }
        if (!is_array($dataValues)) {
            $tableFields = array($dataValues);
        }
        $numberFields = count($tableFields);
        if ($numberFields != count($dataValues)) {
            $multiData = false;
            if (!$multiData) {
                // it's not multidata, so the array supplied is no good
                throw new DB_Sqlite_Tools_Exception
                ('The array supplied as values does not 
                match the number of fields you have provided',-1);
            }
        }
        if ($queryType == self::DB_AUTOQUERY_INSERT) {
            $queryString = "INSERT INTO ".$tableName." (#fields#) VALUES (#values#)";
            $fieldsString = join(",", $tableFields);
            $valuesString = join(self::DB_STRING_DELIMITER.",".self::DB_STRING_DELIMITER, 
            self::safeQuote($dataValues));
            if ($this->debug == true) print_r($dataValues);
            $queryString = str_replace(array('#fields#', '#values#'), array($fieldsString, 
            self::DB_STRING_DELIMITER.$valuesString.self::DB_STRING_DELIMITER), $queryString);
        } elseif ($queryType == self::DB_AUTOQUERY_UPDATE) {
            $queryString = "UPDATE ".$tableName." SET #setFields#";
            for ($i = 0;$i<$numberFields;$i++) {
                $setFields[] = $tableFields[$i]." =
                ".self::DB_STRING_DELIMITER.self::safeQuote($dataValues[$i]) .self::DB_STRING_DELIMITER;
            }
            $queryString = str_replace('#setFields#', join(",", $setFields), $queryString);
            if (!empty($whereString)) {
                $queryString.= " WHERE ".$whereString;
            }
        } else {
            // unknown queryType
            throw new DB_Sqlite_Tools_Exception
            ('Unknown query type, please use Database::DB_AUTOQUERY_INSERT or
            Database::DB_AUTOQUERY_UPDATE',-1);
        }
        $r = $this->liteQuery($queryString);
        if ($r == false) {
            throw new DB_Sqlite_Tools_Exception
            ($this->liteLastError($queryString),-1);
        }
    }
            public static function safeQuote($mixedVar = "") {
                if (is_array($mixedVar)) {
                    foreach($mixedVar as $i=>&$val) {
                        $val = self::safeQuote($val);
                    }
                } else {
                    if (get_magic_quotes_gpc()) {
                        $mixedVar = stripslashes($mixedVar);
                    }
                    return sqlite_escape_string($mixedVar);
                }
                return $mixedVar;
            }


    /**
     * Connects to the Sqlite DB
     * @param string $db the db name
     * @return true
     */

    public function liteConnect($db) 
    {
        $obj = '';
        if ($this->debug == true) echo "Connecting to $db <BR>";
        try {
            $obj = $this->dbobj = new SQLiteDatabase("$db");
        }
        catch(Exception $obj) {
            echo 'Cannot open database: errorcode'.$obj->getCode() .": ".$obj->getMessage() 
            ."\n\t";
            echo "on ".$obj->getFile() .":".$obj->getLine() ."\n";
            exit;
            return false;
        }
        return true;
    }

    

    /**
     * Executes the query and return the results objects if available
     * @param string $sql sql to execute
     * @return true
     */

    public function liteQuery($sql) 
    {
        if ($this->debug == true) print_r($sql);
        $results = $this->dbobj->query("$sql");
        if ($results != false) {
            $this->result = $results->fetchObject();
            if ($this->debug == true) {
                if ($this->result == '') {
                    echo "query executed";
                }
            }
        }
        return true;
    }
     
    
    
    /**
     * Executes the query and return the decrypted results single object if available
     * @param string $sql sql to execute
     * @param string $crypt default true, decrypts the result
     * @return $this->result object 
     */

    
    public function liteAutoFetch($sql,$crypt = true) 
    {
        if ($this->key == null) throw new DB_Sqlite_Tools_Exception
        ('You need to specify an encryption key',-1);
        if ($this->debug == true) print_r($sql);
        $results = $this->dbobj->query("$sql");
        if ($results != false) {
            $this->result = $results->fetchObject();
        }
        foreach($this->result as $propertyName=>&$value) {
            if (!is_numeric($value)) {
                if ($crypt == true) {
                    $this->matrix->decrypt($value);
                }
            }
        }
        if ($this->debug == true) print_r($this->result);
        if ($this->result == '') {
            echo "query executed";
        }
        return $this->result;
    }
    
    

    /**
     * Executes the query and return the decrypted results ALL the objects in an array
     * @param string $sql sql to execute
     * @param string $crypt default true, decrypts the result
     * @return $this->result object 
     */
    
    public function liteAll($sql, $crypt = true) 
    {
        if ($this->key == null) throw new  DB_Sqlite_Tools_Exception
        ('You need to specify an encryption key',-1);
        if ($this->debug == true) print_r($sql);
        $results = $this->dbobj->query("$sql");
        if ($results != false) {
            $this->result = array();
            while ($result = $results->fetchObject()) {
                foreach($result as $index=>&$value) {
                    if (!is_numeric($value)) {
                        if ($crypt == true) {
                            $this->matrix->decrypt($value);
                        }
                    }
                }
                $this->result[] = $result;
            }
        }
        if ($this->debug == true) print_r($this->result);
        if ($this->debug == true) {
            if ($this->result == '') {
                echo "query executed";
            }
        }
        return $this->result;
    }
    
    
    /**
     * returns the last DB error string
     * @param string $queryString the query string
     * @return sqlite_error_string 
     */

    public function liteLastError($queryString = "")
    {
        return sqlite_error_string($this->dbobj->lastError())."\n".$queryString;
    }
     
    /**
     * returns the last inserted row id
     * @return $this->dbobj->lastInsertRowid(); 
     */

    public function liteLastID () 
    {
        return $this->dbobj->lastInsertRowid();
        
    }
    
   function __destruct() 
   {
        unset ($this->dbobj); 
   }

}
?>
