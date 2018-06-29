<?php
/**
 * Created by PhpStorm.
 * User: Vincent
 * Date: 15/06/2018
 * Time: 09:12
 */

namespace VFou\Search\Tokenizers;


class DateSplitTokenizer implements TokenizerInterface
{

    public static function tokenize($data)
    {
        return array_map(function($date){
            return [$date, substr($date,0,10), substr($date, 11,8)];
        }, $data);
    }
}
