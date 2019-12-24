<?php


namespace VFou\Search\Tokenizers;


class AlphaNumericTokenizer implements TokenizerInterface
{
    public static function tokenize($data)
    {
        return array_map(function($elem){
            return preg_replace('/[^A-Za-z0-9]/', '',$elem);
        }, $data);
    }
}
