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
// | Authors: <Ashley Hewson <morbidness@gmail.com>                       |
// +----------------------------------------------------------------------+
//$Id$  $


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

