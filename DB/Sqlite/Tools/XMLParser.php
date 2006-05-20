<?php
/* vim: set expandtab tabstop=4 shiftwidth=4 softtabstop=4 foldmethod=marker: */

// {{{ Header

/**
 * DB_Sqlite_Tools_XMLParser, XML parser class.
 *
 * PHP version 5
 *
 * LICENSE:
 *
 * BSD License
 *
 * Copyright (c) 2004 David Costa
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
 * @category XML
 * @package DB_Sqlite_Tools
 * @author David Costa <gurugeek@php.net>
 * @author Ashley Hewson <morbidness@gmail.com>
 * @copyright Copyright (c) 2004 David Costa
 * @license http://www.opensource.org/licenses/bsd-license.php
 *          BSD License
 * @version CVS: $Id$
 */

// }}}
// {{{ Class: DB_Sqlite_Tools_XMLParser

/**
 * Alpha version of the XML parser to be possibly replaced by a proper parse or
 * extension as suggested by Stephan Schimdt.
 *
 * @category XML
 * @package DB_Sqlite_Tools
 * @author David Costa <gurugeek@php.net>
 * @author Ashley Hewson <morbidness@gmail.com>
 * @copyright Copyright (c) 2004 David Costa
 * @license http://www.opensource.org/licenses/bsd-license.php
 *          BSD License
 * @version Release: @package_version@
 * @todo Add more comments
 */
class DB_Sqlite_Tools_XMLParser
{
    // {{{ Properties

    /**
     * File resource handler.
     *
     * @var resource
     */
    public $fh;

    /**
     * Current parse element.
     *
     * @var string
     */
    public $element;

    /**
     * Enclosed element.
     *
     * @var string
     */
    public $enclosed;

    /**
     * List of elements to ignore.
     *
     * @var array
     */
    public $ignoreList = array();

    private $str;

    // {{{ Constructor

    /**
     * Construct.
     *
     * @param resource $fh File pointer resource handler.
     * @param int $pos (optional) Byte as start position.
     */
    public function __construct($fh, $pos = 0)
    {
        $this->fh = $fh;
        fseek($fh, $pos);
        $this->strlen = strlen($this->str);
    }

    // }}}
    // {{{ getNextElement()

    public function getNextElement() 
    {
        // the loop is so that if we are told to ignore a certain
        // element, then we can continue on to the next one and
        // return that.
        while (true) {
            // obviously, if we're at EOF, then there are no more
            // elements ;)
            if (feof($this->fh)) {
                return false;
            }

            // read up to the first open bracket, storing what's
            // in between
            $this->enclosed = '';
            $c = fgetc($this->fh);
            while (($c != '<') && !feof($this->fh)) {
                $this->enclosed .= $c;
                $c = fgetc($this->fh);
            }

            // read up to the first close bracket that isn't within
            // quote marks, storing what's in between.
            $this->element = '';
            $inQuote = false;
            $c = fgetc($this->fh);
            while (($c != '>') // end if $c == '>'
                   || ($inQuote) // unless this is within a quote,
                   || (feof($this->fh)) // or if we have reached EOF.
                  )
            {
                // toggle quote flag
                if ($c == '"') {
                    $inQuote = !$inQuote;
                }

                $this->element .= $c;
                $c = fgetc($this->fh);
            }

            // default action is to accept this element, however we have
            // to check it against the list of elements to ignore, like
            // <!-- -->
            $break = true;
            foreach($this->ignoreList as $ignore) {
                if (preg_match("/$ignore/", $this->getElement())) $break = false;
            }
            // break the while loop if this is an acceptable element
            if ($break) {
                break;
            }
        }

        return true;
    }

    // }}}
    // {{{ ignore()

    /**
     * Add $str to ignore list.
     *
     * @param string $str The element to ignore.
     */
    public function ignore($str) 
    {
        $this->ignoreList[] = $str;
    }

    // }}}
    // {{{ getElement()

    /**
     * Get the element.
     *
     * @return string
     */
    public function getElement() 
    {
        return trim($this->element);
    }

    // }}}
    // {{{ getEnclosed()

    public function getEnclosed() 
    {
        return $this->enclosed;
    }

    // }}}
    // {{{ getElementAttribute()

    public function getElementAttribute($name) 
    {  
        $el = $this->getElement();
        preg_match('/[ ]+'.$name.'[ ]*=[ ]*"([^"]*)"/', $el, $result);
        return $result[1];
    }

    // }}}
    // {{{ getElementName()

    public function getElementName() 
    {
        preg_match("/^([^ ]*).*$/", $this->getElement(), $result);
        return $result[1];
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