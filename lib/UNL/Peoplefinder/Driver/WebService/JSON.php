<?php
class UNL_Peoplefinder_Driver_WebService_JSON extends UNL_Peoplefinder_Driver_WebService
{
    protected $_respFormat = 'json';

    public function resultCallback($result)
    {
        if ($result) {
            $result = json_decode($result);
        }

        return $result;
    }
}
