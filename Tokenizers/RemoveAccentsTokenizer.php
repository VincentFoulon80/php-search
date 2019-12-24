<?php


namespace VFou\Search\Tokenizers;


class RemoveAccentsTokenizer implements TokenizerInterface
{
    public static function tokenize($data)
    {
        return array_map(function($elem){
            return iconv('ISO-8859-1','ASCII//TRANSLIT//IGNORE',$elem);
        }, $data);
    }
}
