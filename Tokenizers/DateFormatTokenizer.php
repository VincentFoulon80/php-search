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
            if(is_a($dt, \DateTime::class)){
                return $dt->format(DATE_ATOM);
            } else {
                return $dt;
            }
        }, $data);
    }
}
