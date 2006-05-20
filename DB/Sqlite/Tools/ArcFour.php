<?php
/* vim: set expandtab tabstop=4 shiftwidth=4 softtabstop=4 foldmethod=marker: */

// {{{ Header

/**
 * ArcFour stream cipher routines implementation PHP.
 *
 * PHP version 5
 *
 * LICENSE:
 *
 * The PHP License, version 2.0
 *
 * Copyright (c) 1997-2003 The PHP Group
 *
 * This source file is subject to version 2.0 of the PHP license,
 * that is bundled with this package in the file LICENSE, and is
 * available at through the world-wide-web at
 * http://www.php.net/license/2_02.txt.
 * If you did not receive a copy of the PHP license and are unable to
 * obtain it through the world-wide-web, please send a note to
 * license@php.net so we can mail you a copy immediately.
 *
 * @category Crypt
 * @package DB_Sqlite_Tools
 * @author Dave Mertens <dmertens@zyprexia.com>
 * @author David Costa <gurugeek@php.net>
 * @copyright Copyright (c) 1997-2003 The PHP Group
 * @license http://www.php.net/license/2_02.txt
 *          The PHP License, version 2.0
 * @version CVS: $Id$
 */

// }}}
// {{{ Class: DB_Sqlite_Tools_ArcFour

/**
 * ArcFour stream cipher routines class.
 *
 * ArcFour stream cipher routines implementation in PHP written by
 * Dave Mertens <dmertens@zyprexia.com> based on code written by
 * Damien Miller <djm@mindrot.org> ported from PEAR::RC4 to PHP 5 by
 * David Costa <gurugeek@php.net>.
 *
 * Usage:
 * <pre>
 * $key = "pear";
 * $message = "PEAR rulez!";
 *
 * $rc4 = new Crypt_RC4;
 * $rc4->key($key);
 * echo "Original message: $message <br>\n";
 * $rc4->crypt($message);
 * echo "Encrypted message: $message <br>\n";
 * $rc4->decrypt($message);
 * echo "Decrypted message: $message <br>\n";
 * </pre>
 *
 * @category Crypt
 * @package DB_Sqlite_Tools
 * @author Dave Mertens <dmertens@zyprexia.com>
 * @author David Costa <gurugeek@php.net>
 * @copyright Copyright (c) 1997-2003 The PHP Group
 * @license http://www.php.net/license/2_02.txt
 *          The PHP License, version 2.0
 * @version Release: @package_version@
 */
class DB_Sqlite_Tools_ArcFour
{
    // {{{ Properties

    /**
     * Real programmers...
     *
     * @var array
     */
    private $s = array();

    /**
     * Real programmers...
     *
     * @var array
     */
    private  $i = 0;

    /**
     * Real programmers...
     *
     * @var array
     */
    private $j = 0;

    /**
     * Key holder
     *
     * @var string
     */
    private $key;

    // }}}
    // {{{ Constructor

    /**
     * Constructor
     *
     * @param  string $key Key which will be used for encryption
     *
     * @see DB_Sqlite_Tools_ArcFour::setKey()
     */
    public function __construct($key = null)
    {
        if ($key !== null) {
            $this->setKey($key);
        }
    }

    // }}}
    // {{{ setKey()

    /**
     * Set the encryption key.
     *
     * @param string $key Key which will be user for encrytion
     */
    public function setKey($key)
    {
        if (strlen($key) > 0) {
            $this->key = $key;
        }
    }

    // }}}
    // {{{ key()

    /**
     * Initialize the encryption key.
     *
     * @param string $key Key which will be used for encryption
     */
    private function key($key)
    {
        $len= strlen($key);
        for ($this->i = 0; $this->i < 256; $this->i++) {
            $this->s[$this->i] = $this->i;
        }

        $this->j = 0;
        for ($this->i = 0; $this->i < 256; $this->i++) {
            $this->j = ($this->j + $this->s[$this->i] + ord($key[$this->i % $len])) % 256;
            $t = $this->s[$this->i];
            $this->s[$this->i] = $this->s[$this->j];
            $this->s[$this->j] = $t;
        }
        $this->i = $this->j = 0;
    }

    // }}}
    // {{{ crypt()

    /**
     * Encrypt function.
     *
     * This function will be encrypt the string argument given.
     *
     * @param string $paramstr (reference) string that will encrypted
     *
     * @see DB_Sqlite_Tools_ArcFour::key()
     */
    public function crypt(&$paramstr)
    {
        //Init key for every call, Bugfix 22316
        $this->key($this->key);

        $len= strlen($paramstr);
        for ($c= 0; $c < $len; $c++) {
            $this->i = ($this->i + 1) % 256;
            $this->j = ($this->j + $this->s[$this->i]) % 256;
            $t = $this->s[$this->i];
            $this->s[$this->i] = $this->s[$this->j];
            $this->s[$this->j] = $t;

            $t = ($this->s[$this->i] + $this->s[$this->j]) % 256;

            $paramstr[$c] = chr(ord($paramstr[$c]) ^ $this->s[$t]);
        }
    }

    // }}}
    // {{{ decrypt()

    /**
     * Decrypt function.
     *
     * This function will be decrypt the string argument given.
     *
     * @param  string $paramstr (reference) The string that will decrypted
     *
     * @see DB_Sqlite_Tools_ArcFour::crypt()
     */
    public function decrypt(&$paramstr)
    {
        //Decrypt is exactly the same as encrypting the string. Reuse (en)crypt code
        $this->crypt($paramstr);
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