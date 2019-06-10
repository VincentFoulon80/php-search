<?php


namespace VFou\Search\Tokenizers;


class singleQuoteTokenizer implements TokenizerInterface
{
    public static function tokenize($data)
    {
        return array_map(function($elem){
            return explode("'",$elem); // TODO : preg_replace single quotes
        }, $data);
    }
}
