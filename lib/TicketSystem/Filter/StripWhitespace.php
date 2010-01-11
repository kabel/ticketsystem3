<?php

class TicketSystem_Filter_StripWhitespace implements Zend_Filter_Interface
{
    public function filter($value)
    {
        return str_replace(array(" ", "\t", "\r", "\n"), '', $value);
    }
}