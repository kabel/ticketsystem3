<?php

/**
* 
* Parses for ticket ID links.
* This class implements a Text_Wiki_Parse to add an link to another
* ticket by its ID
* 
* @category Text
* @package Text_Wiki
* @author Kevin Abel <kevin.abel.0 at gmail dot com>
* @license LGPL
* 
*/

class Text_Wiki_Parse_Ticketlink extends Text_Wiki_Parse {
    
    
    /**
    * 
    * The regular expression used to find source text matching this
    * rule.  Performed on standard text like: #1234
    * 
    * @access public
    * 
    * @var string
    * 
    */
    
    var $regex = '/#([0-9]+)/i';
    
    
    /**
    * 
    * Generates a token entry for the matched text.  Token options are:
    * 
    * 'text' => The full matched text, not including the <code></code> tags.
    * 
    * @access public
    *
    * @param array &$matches The array of matches from parse().
    *
    * @return A delimited token number to be used as a placeholder in
    * the source text.
    *
    */
    
    function process(&$matches) {
    
        $ticketId = $matches[1];
        
        return $this->wiki->addToken(
            $this->rule,
            array('id' => $ticketId)
        );
    }
}
?>