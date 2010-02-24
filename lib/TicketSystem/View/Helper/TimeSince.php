<?php

class TicketSystem_View_Helper_TimeSince extends Zend_View_Helper_Abstract
{
    protected $_dateChucks = array(
        31536000 => 'year',
        2592000  => 'month',
        604800   => 'week',
        86400    => 'day',
        3600     => 'hour',
        60       => 'minute',
        1        => 'second'
    );
    
    public function timeSince(Zend_Date $then, $depth = 1, $now = null)
    {
        if (null === $now) {
            $now = new Zend_Date();
        }
        if ($depth < 1 || $depth > count($this->_dateChucks)) {
            $depth = 1;
        }
        
        $since = $now->sub($then)->toValue();
        $chunks = array();
        
        $currentDepth = 0;
        foreach ($this->_dateChucks as $seconds => $name) {
            if ($currentDepth >= $depth) {
                break;
            }
            
            $ratio = $since / $seconds;
            $chunk = ($since < 0) ? -floor(abs($ratio)) : floor($ratio);
            
            if ($chunk != 0) {
                $part = array();
                $part[0] = ($chunk == 1) ? $name : $name . 's';
                $part[1] = $chunk;
                
                $chunks[] = $part;
                $since -= $chunk * $seconds;
                $currentDepth++;
            }
        }
        
        if (empty($chunks)) {
            return 'less than a second';
        }
        
        $output = '';
        
        $count = count($chunks);
        foreach ($chunks as $i => $part) {
            if ($i > 0) {
                if ($count > 2) {
                    $output .= ', ';
                    if ($i == $count - 1) {
                        $output .= ' and ';
                    }
                } else {
                    $output .= ' and ';
                }
            }
            
            $output .= $part[1] . ' ' . $part[0];
        }
        
        return $output;
    }
}