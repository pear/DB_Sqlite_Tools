<?php


/* vim: set expandtab tabstop=4 shiftwidth=4 softtabstop=4  */
// +----------------------------------------------------------------------+
// | PHP version 5                                                        |
// +----------------------------------------------------------------------+
// | Copyright (c) 1997-2004 The PHP Group                                |
// +----------------------------------------------------------------------+
// | This source file is subject to version 3.0 of the PHP license,       |
// | that is bundled with this package in the file LICENSE, and is        |
// | available through the world-wide-web at the following url:           |
// | http://www.php.net/license/3_0.txt.                                  |
// | If you did not receive a copy of the PHP license and are unable to   |
// | obtain it through the world-wide-web, please send a note to          |
// | license@php.net so we can mail you a copy immediately.               |
// +----------------------------------------------------------------------+
// | Authors: David Costa     <gurugeek@php.net>                          |
// +----------------------------------------------------------------------+
//
// \$Id$  $


// more comments will be added soon. Alpha version of the XML parser
// to be possibly replaced by a proper parse or extension as suggested by
// Stephan Schimdt 

class DB_Sqlite_Tools_XMLParser {
        public $fh;
        public $element;
        public $enclosed;
        public $ignoreList = array();
        private $str;
        
        public function __construct($fh, $pos = 0) {
            $this->fh = $fh;
            fseek($fh, $pos);
            $this->strlen = strlen($this->str);
        }
        public function getNextElement() 
        {
            // the loop is so that if we are told to ignore a certain
            // element, then we can continue on to the next one and
            // return that.
            while (true) {
                // obviously, if we're at EOF, then there are no more
                // elements ;)
                if (feof($this->fh)) return false;
                // read up to the first open bracket, storing what's
                // in between
                $this->enclosed = "";
                $c = fgetc($this->fh);
                while (($c != '<') && !feof($this->fh)) {
                    $this->enclosed.= $c;
                    $c = fgetc($this->fh);
                }
                // read up to the first close bracket that isn't within
                // quote marks, storing what's in between.
                $this->element = "";
                $inQuote = false;
                $c = fgetc($this->fh);
                while (($c != '>') // end if $c == '>'
                 || ($inQuote) // unless this is within a quote,
                 || (feof($this->fh)) // or if we have reached EOF.
                ) {
                    // toggle quote flag
                    if ($c == '"') $inQuote = !$inQuote;
                    $this->element.= $c;
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
                if ($break) break;
            }
            return true;
        }
        public function ignore($str) 
        {
            // add $str to ignore list
            $this->ignoreList[] = $str;
        }
        public function getElement() 
        {
            return trim($this->element);
        }
        public function getEnclosed() 
        {
            return $this->enclosed;
        }
        public function getElementAttribute($name) 
        {  
            $el = $this->getElement();
            preg_match('/[ ]+'.$name.'[ ]*=[ ]*"([^"]*)"/', $el, $result);
            return $result[1];
        }
        public function getElementName() 
        {
            preg_match("/^([^ ]*).*$/", $this->getElement(), $result);
            #echo $result[1] ;
            return $result[1];
        }
    }
?>

