<?php

/**
* 
* Parses for heading text.
* 
* @category Text
* 
* @package Text_Wiki
* 
* @author Paul M. Jones <pmjones@php.net>
* 
* @license LGPL
* 
* @version $Id: Heading.php,v 1.3 2005/02/23 17:38:29 pmjones Exp $
* 
*/

/**
* 
* Parses for heading text.
* 
* This class implements a Text_Wiki_Parse to find source text marked to
* be a heading element, as defined by text on a line by itself prefixed
* with a number of plus signs (+). The heading text itself is left in
* the source, but is prefixed and suffixed with delimited tokens marking
* the start and end of the heading.
*
* @category Text
* 
* @package Text_Wiki
* 
* @author Paul M. Jones <pmjones@php.net>
* 
*/

class Text_Wiki_Parse_Heading extends Text_Wiki_Parse {
    
    
    /**
    * 
    * The regular expression used to parse the source text and find
    * matches conforming to this rule.  Used by the parse() method.
    * 
    * @access public
    * 
    * @var array
    * 
    * @see parse()
    * 
    */
    
    var $regex = array(
    	'/^(\+{1,6}) (.*)/m',
        '/^(={1,6})\s*(.*)\s*\1/m'
    );
    
    var $conf = array(
        'id_prefix' => 'toc'
    );
    
    function parse()
    {
        // -------------------------------------------------------------
        // 
        // + style headings
        // 
        // the regular expression for this kind of heading
        $tmp_regex = $this->regex[0];
        
        // use a custom callback processing method to generate
        // the replacement text for matches.
        $this->wiki->source = preg_replace_callback(
            $tmp_regex,
            array(&$this, 'process'),
            $this->wiki->source
        );
        
        // -------------------------------------------------------------
        // 
        // = style headings
        // 
        // the regular expression for this kind of heading
        $tmp_regex = $this->regex[1];
        
        // use a custom callback processing method to generate
        // the replacement text for matches.
        $this->wiki->source = preg_replace_callback(
            $tmp_regex,
            array(&$this, 'process'),
            $this->wiki->source
        );
    }
    
    /**
    * 
    * Generates a replacement for the matched text.  Token options are:
    * 
    * 'type' => ['start'|'end'] The starting or ending point of the
    * heading text.  The text itself is left in the source.
    * 
    * @access public
    *
    * @param array &$matches The array of matches from parse().
    *
    * @return string A pair of delimited tokens to be used as a
    * placeholder in the source text surrounding the heading text.
    *
    */
    
    function process(&$matches)
    {
        // keep a running count for header IDs.  we use this later
        // when constructing TOC entries, etc.
        static $id;
        if (! isset($id)) {
            $id = 0;
        }
        
        $prefix = htmlspecialchars($this->getConf('id_prefix'));
        
        $start = $this->wiki->addToken(
            $this->rule, 
            array(
                'type' => 'start',
                'level' => strlen($matches[1]),
                'text' => $matches[2],
                'id' => $prefix . $id ++
            )
        );
        
        $end = $this->wiki->addToken(
            $this->rule, 
            array(
                'type' => 'end',
                'level' => strlen($matches[1])
            )
        );
        
        return $start . $matches[2] . $end . "\n";
    }
}
?>