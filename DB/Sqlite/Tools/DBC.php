<?php
/* vim: set expandtab tabstop=4 shiftwidth=4 softtabstop=4 foldmethod=marker: */

// {{{ Header

/**
 * DB_Sqlite_Tools_DBC, the security class for manipulate SQLite database.
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
 * @package  DB_Sqlite_Tools
 * @author David Costa <gurugeek@php.net>
 * @author Ashley Hewson <morbidness@gmail.com>
 * @author Radu Negoescu <negora@dawnideas.com>
 * @copyright Copyright (c) 2004-2006 David Costa
 * @license http://www.opensource.org/licenses/bsd-license.php
 *          BSD License
 * @version CVS: $Id$
 */

// }}}
// {{{ Dependencies

/**
 * Load the PEAR_Exception
 */
require_once 'PEAR/Exception.php';

// }}}
// {{{ Class: DB_Sqlite_Tools_DBC

/**
 * This class, part of DB_Sqlite_Tools allows to insert on an sqlite database
 * securely encrypted data and retrieve and decript on the fly the encrypted
 * data. Since Sqlite might be seen as voulnerable, encrypted database will
 * ensure the data integrity. It doesn't require PHP to be compiled with MCrypt
 * but it uses Crypt_ArcFour, embedded (ported to PHP 5 the original PEAR
 * package).
 *
 * @category Database
 * @package  DB_Sqlite_Tools
 * @author David Costa <gurugeek@php.net>
 * @author Ashley Hewson <morbidness@gmail.com>
 * @author Radu Negoescu <negora@dawnideas.com>
 * @copyright Copyright (c) 2004-2006 David Costa
 * @license http://www.opensource.org/licenses/bsd-license.php
 *          BSD License
 * @version Release: @package_version@
 */
class DB_Sqlite_Tools_DBC
{
    // {{{ Properties

    /**
     * The database object.
     *
     * @var object
     */
    private $dbobj;

    /**
     * The results object.
     *
     * @var object
     */
    public $result;

    /**
     * Whether turn on debug mode or not.
     *
     * @var bool
     */
    public $debug = false;

    /**
     * ArcFour crypt object.
     *
     * @var object
     */
    public $matrix;

    /**
     * An encryption key.
     *
     * @var string
     */
    public $key;

    // }}}
    // {{{ Constants

    /**
     * Delimiter for autoExec.
     */
    const DB_STRING_DELIMITER = "'";

    /**
     * Insert mode constant.
     */
    const DB_AUTOQUERY_INSERT = 1;

    /**
     * Update mode constant.
     */
    const DB_AUTOQUERY_UPDATE = 2;

    // }}}
    // {{{ Constructor

    /**
     * Constructor.
     */
    public function __construct()
    {
        // instantiate a new ArcFour object
        $this->matrix = new DB_Sqlite_Tools_ArcFour;
    }

    // }}}
    // {{{ liteAutoExec()

    /**
     * Auto Execute an insert or update query.
     *
     * @param string $tableName DB table name
     * @param string $tableFields DB fields name
     * @param string $tableValues DB table value
     * @param string $querytype Type of query, insert by default
     * @param string $crypt Crypt with ArcFour before inserting the data, default true
     * @param string $whereString Necessary for UPDATE, default null
     *
     * @return mixed
     * @throws PEAR_Exception
     */
    public function liteautoExec($tableName = null,
                                 $tableFields = null,
                                 $dataValues = null,
                                 $queryType = self::DB_AUTOQUERY_INSERT,
                                 $crypt = true,
                                 $whereString = null)
    {
        if ($this->key === null) {
            throw new PEAR_Exception('You need to specify an encryption key', -1);
        }

        if ($crypt === true) {
            foreach($dataValues as $matrix => &$value) {
                $this->matrix->setkey($this->key);
                $this->matrix->crypt($value);
            }
        }

        if (empty($tableName)) {
            throw new PEAR_Exception('You need to specify a table', -1);
        }

        if (empty($tableFields)) {
            throw new PEAR_Exception('You need to specify the table fields', -1);
        }

        if (empty($dataValues)) {
            throw new  PEAR_Exception('You need to specify the values', -1);
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
                throw new PEAR_Exception(
                    'The array supplied as values does not match the number '.
                    'of fields you have provided', -1
                );
            }
        }

        if ($queryType == self::DB_AUTOQUERY_INSERT) {
            $queryString = 'INSERT INTO ' . $tableName
                         . ' (#fields#) VALUES (#values#)';
            $fieldsString = join(',', $tableFields);
            $valuesString = join(
                self::DB_STRING_DELIMITER . ',' . self::DB_STRING_DELIMITER,
                self::safeQuote($dataValues)
            );

            if ($this->debug == true) print_r($dataValues);
            $queryString = str_replace(
                array(
                    '#fields#',
                    '#values#'
                ),
                array($fieldsString,
                    self::DB_STRING_DELIMITER
                    . $valuesString
                    . self::DB_STRING_DELIMITER
                ),
                $queryString
            );
        } elseif ($queryType == self::DB_AUTOQUERY_UPDATE) {
            $queryString = 'UPDATE ' . $tableName . ' SET #setFields#';

            for ($i = 0; $i < $numberFields; $i++) {
                $setFields[] = $tableFields[$i] . '='
                             . self::DB_STRING_DELIMITER
                             . self::safeQuote($dataValues[$i])
                             .self::DB_STRING_DELIMITER;
            }

            $queryString = str_replace(
                '#setFields#', join(',', $setFields), $queryString
            );

            if (!empty($whereString)) {
                $queryString .= ' WHERE ' . $whereString;
            }
        } else {
            // Unknown queryType
            throw new PEAR_Exception(
                'Unknown query type, please use ' .
                'DB_Sqlite_Tools_DBC::DB_AUTOQUERY_INSERT or '.
                'DB_Sqlite_Tools_DBC::DB_AUTOQUERY_UPDATE', -1
            );
        }

        $r = $this->liteQuery($queryString);
        if ($r == false) {
            throw new PEAR_Exception(
                $this->liteLastError($queryString), -1
            );
        }
    }

    // }}}
    // {{{ safeQuote()

    /**
     * Safety value(s) for query execution.
     *
     * @param mixed $mixedVar A string value or an associative array contain
     *                        list of values.
     *
     * @return mixed Result
     */
    public static function safeQuote($mixedVar) {
        if (is_array($mixedVar)) {
            foreach($mixedVar as $i => &$val) {
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

    // }}}
    // {{{ liteConnect()

    /**
     * Connects to the Sqlite DB.
     *
     * @param string $db The database name.
     *
     * @return bool TRUE on succeed.
     * @throws PEAR_Exception
     */
    public function liteConnect($db)
    {
        $this->debug('Connecting to' .  $db . "\n");

        $obj = false;

        try {
            $obj = $this->dbobj = new SQLiteDatabase($db);
        } catch (Exception $obj) {
            throw new PEAR_Exception(
                'Cannot open database: errorcode'. $obj->getCode() . ': '
                 . $obj->getMessage() . "\n\t" .
                 ' in ' . $obj->getFile() . ': ' . $obj->getLine() . "\n"
            );
        }
        return true;
    }

    // }}}
    // {{{ liteQuery()

    /**
     * Executes the query and return the results objects if available.
     *
     * @param string $sql Query to execute.
     *
     * @return true
     */
    public function liteQuery($sql)
    {
        $this->debug(print_r($sql, true));

        $results = $this->dbobj->query("$sql");
        if ($results != false) {
            $this->result = $results->fetchObject();
            if ($this->result == '') {
                $this->debug('query executed' . "\n");
            }
        }

        return true;
    }

    // }}}
    // {{{ liteAutoFetch()

    /**
     * Executes the query and return the decrypted results single object
     * if available.
     *
     * @param string $sql SQL to execute.
     * @param string $crypt Default true, decrypts the result.
     *
     * @return object Result
     * @throws PEAR_Exception
     * @see DB_Sqlite_Tools_DBC::$result
     */
    public function liteAutoFetch($sql, $crypt = true)
    {
        if ($this->key === null) {
            throw new PEAR_Exception('You need to specify an encryption key', -1);
        }

        $this->debug(print_r($sql, true));

        $results = $this->dbobj->query("$sql");
        if ($results != false) {
            $this->result = $results->fetchObject();
        }

        foreach($this->result as $propertyName => &$value) {
            if (!is_numeric($value)) {
                if ($crypt == true) {
                    $this->matrix->setkey($this->key);
                    $this->matrix->decrypt($value);
                }
            }
        }

        if ($this->result == '') {
            $this->debug('query executed' . "\n");
        } else {
            $this->debug(print_r($this->result, true));
        }

        return $this->result;
    }

    // }}}
    // {{{ liteAll()

    /**
     * Executes the query and return the decrypted results ALL the objects
     * in an array.
     *
     * @param string $sql SQL to execute.
     * @param string $crypt Default true, decrypts the result.
     *
     * @return object Result
     * @throws PEAR_Exception
     * @see DB_Sqlite_Tools_DBC::$result
     */
    public function liteAll($sql, $crypt = true)
    {
        if ($this->key === null) {
            throw new PEAR_Exception('You need to specify an encryption key',-1);
        }

        $this->debug(print_r($sql, true));

        $results = $this->dbobj->query("$sql");
        if ($results != false) {
            $this->result = array();
            while ($result = $results->fetchObject()) {
                foreach($result as $index => &$value) {
                    if (!is_numeric($value)) {
                        if ($crypt == true) {
                            $this->matrix->setkey($this->key);
                            $this->matrix->decrypt($value);
                        }
                    }
                }
                $this->result[] = $result;
            }
        }

        if ($this->result == '') {
            $this->debug('query executed' . "\n");
        } else {
            $this->debug(print_r($this->result, true));
        }

        return $this->result;
    }

    // }}}
    // {{{ liteLastError()

    /**
     * returns the last DB error string.
     *
     * @param string $queryString The query string.
     *
     * @return string
     */
    public function liteLastError($queryString = '')
    {
        return sqlite_error_string($this->dbobj->lastError()) . "\n"
               . $queryString;
    }

    // }}}
    // {{{ liteLastID()

    /**
     * Returns the last inserted row id.
     *
     * @return int
     */
    public function liteLastID ()
    {
        return $this->dbobj->lastInsertRowid();
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
    // {{{ Destructor

    /**
     * Destructor.
     */
    public function __destruct()
    {
        unset($this->dbobj);
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