<?php
/**
 * Created by PhpStorm.
 * User: dmitrijdorozkin
 * Date: 22.10.14
 * Time: 6:40
 */

class view {

    static function encode($array) {
        $data = array();
        foreach($array as $key => $value)
            if(!empty($value)) $data[$key] = is_numeric($value)?intval($value):$value;
        print json_encode($data);
        return true;
    }

    static function error($text) {
        $result = array(
            'Error' => $text
        );

        print json_encode($result);
        return true;
    }

    static function state($state) {
        print $state==true?json_encode(array('Status' => 'Successful.')):json_encode(array('Status' => 'Invalid token.'));
    }
}