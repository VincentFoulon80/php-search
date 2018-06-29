<?php
/**
 * Created by PhpStorm.
 * User: Vincent
 * Date: 15/06/2018
 * Time: 12:50
 */

namespace VFou\Search\Tokenizers;


class TrimPunctuationTokenizer implements TokenizerInterface
{
    public static function tokenize($data)
    {
        return array_map(function($elem){
            return trim($elem, ",?;.:!"); // TODO : preg_replace
        }, $data);
    }
}