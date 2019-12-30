<?php


namespace VFou\Search\Tokenizers;


class WordSeparatorTokenizer implements TokenizerInterface
{
    public static function tokenize($data)
    {
        return array_map(function($elem){
            return array_unique([preg_split("/(\s|-|_)/",$elem), $elem]);
        }, $data);
    }
}
