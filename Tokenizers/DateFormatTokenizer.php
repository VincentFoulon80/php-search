<?php
/**
 * Created by PhpStorm.
 * User: Vincent
 * Date: 15/06/2018
 * Time: 08:53
 */

namespace VFou\Search\Tokenizers;

class DateFormatTokenizer implements TokenizerInterface
{

    public static function tokenize($data)
    {
        return array_map(function($dt){
            if(is_numeric($dt)){
                $dt = \DateTime::createFromFormat('U', $dt);
            } elseif(!is_a($dt, \DateTime::class)) {
                try{
                    $dt = new \DateTime($dt);
                } catch(\Exception $e){}
            }
            if(!is_a($dt, \DateTime::class)) return $dt; // fail-safe if datetime can't be created
            return $dt->format(DATE_ATOM);
        }, $data);
    }
}
