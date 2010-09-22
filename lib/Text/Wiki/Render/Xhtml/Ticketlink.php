<?php
/**
 * Ticket link rule end renderer for Xhtml
 *
 * PHP versions 4 and 5
 *
 * @category   Text
 * @package    Text_Wiki
 * @author     Paul M. Jones <pmjones@php.net>
 * @license    http://www.gnu.org/copyleft/lesser.html  LGPL License 2.1
 */
class Text_Wiki_Render_Xhtml_Ticketlink extends Text_Wiki_Render {

    var $conf = array(
        'view' => null
    );

    function token($options)
    {
        if ($view = $this->getConf('view')) {
            $src = $view->url(array(
                'id' => sprintf('%d', $options['id'])
            ), 'ticket', true);
        } else {
            return '#' . $options['id'];
        }
        
        $output = "<a href=\"$src\">#{$options['id']}</a>";
        
        return $output;
    }
}

?>