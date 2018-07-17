<?php
/**
 * Created by PhpStorm.
 * User: Vincent
 * Date: 15/06/2018
 * Time: 09:02
 */

namespace VFou\Search\Tokenizers;


class WhiteSpaceTokenizer implements TokenizerInterface
{

    public static function tokenize($data)
    {
        return array_map(function($elem){
            return preg_split("/\s/",$elem);
        }, $data);
    }
}
