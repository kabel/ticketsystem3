<?php

/**
* 
* Parses for text marked as a code example block.
* 
* @category Text
* 
* @package Text_Wiki
* 
* @author Paul M. Jones <pmjones@php.net>
* 
* @license LGPL
* 
* @version $Id: Code.php,v 1.11 2007/06/09 23:11:25 justinpatrin Exp $
* 
*/

/**
* 
* Parses for text marked as a code example block.
* 
* This class implements a Text_Wiki_Parse to find sections marked as code
* examples.  Blocks are marked as the string <code> on a line by itself,
* followed by the inline code example, and terminated with the string
* </code> on a line by itself.  The code example is run through the
* native PHP highlight_string() function to colorize it, then surrounded
* with <pre>...</pre> tags when rendered as XHTML.
*
* @category Text
* 
* @package Text_Wiki
* 
* @author Paul M. Jones <pmjones@php.net>
* 
*/

class Text_Wiki_Parse_Code extends Text_Wiki_Parse {
    
    
    /**
    * 
    * The regular expression used to find source text matching this
    * rule.
    * 
    * @access public
    * 
    * @var string
    * 
    */
    
/*    var $regex = '/^(\<code( .+)?\>)\n(.+)\n(\<\/code\>)(\s|$)/Umsi';*/
    var $regex = array(
        '/^\{\{\{\n(?:#!([a-zA-Z0-9\-_]+)\n)?(.*?)\n\}\}\}(\s|$)/msi',
    	';^<code(\s[^>]*)?>((?:(?R)|.*?)*)\n</code>(\s|$);msi'
    );
    
    var $typeMap = array(
        'html' => 'html4strict'
    );
    
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
    
    function parse()
    {
        // -------------------------------------------------------------
        // 
        // shebang style code block
        // 
        // the regular expression for this kind of block
        $tmp_regex = $this->regex[0];
        
        // use a custom callback processing method to generate
        // the replacement text for matches.
        $this->wiki->source = preg_replace_callback(
            $tmp_regex,
            array(&$this, 'processShebang'),
            $this->wiki->source
        );
        
        // -------------------------------------------------------------
        // 
        // code tag style code block
        // 
        // the regular expression for this kind of block
        $tmp_regex = $this->regex[1];
        
        // use a custom callback processing method to generate
        // the replacement text for matches.
        $this->wiki->source = preg_replace_callback(
            $tmp_regex,
            array(&$this, 'process'),
            $this->wiki->source
        );
    }
    
    function processShebang(&$matches)
    {
        $text = $matches[2];
        $type = $matches[1];
        
        $options = array(
            'text' => $text,
            'attr' => array('type' => $type)
        );
        
        if (!empty($type)) {
            if (array_key_exists($type, $this->typeMap)) {
    	        $type = $this->typeMap[$type];
    	    }
            require_once 'geshi.php';
            $geshi = new GeSHi($text, $type);
            $geshi->enable_classes();
            $geshi->enable_keyword_links(false);
            $options['geshi'] = $geshi;
        }
        
        return $this->wiki->addToken($this->rule, $options) . $matches[3];
    }
    
    function process(&$matches)
    {
        // are there additional attribute arguments?
        $args = trim($matches[1]);
        
        if ($args == '') {
            $options = array(
                'text' => $matches[2],
                'attr' => array('type' => '')
            );
        } else {
        	// get the attributes...
        	$attr = $this->getAttrs($args);
        	
        	// ... and make sure we have a 'type'
        	if (! isset($attr['type'])) {
        		$attr['type'] = '';
        	}
        	
        	// retain the options
        	 $options = array(
                'text' => $matches[2],
                'attr' => $attr
            );
        	
        	if (!empty($attr['type'])) {
        	    $type = $attr['type'];
        	    if (array_key_exists($type, $this->typeMap)) {
        	        $type = $this->typeMap[$type];
        	    }
                require_once 'geshi.php';
                $geshi = new GeSHi($options['text'], $type);
                $geshi->enable_classes();
                $options['geshi'] = $geshi;
        	}
        }
        
        return $this->wiki->addToken($this->rule, $options) . $matches[3];
    }
}
?>
