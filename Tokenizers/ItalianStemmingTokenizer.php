<?php

namespace VFou\Search\Tokenizers;

use Wamania\Snowball\Italian;

class ItalianStemmingTokenizer implements TokenizerInterface
{

    public static function tokenize($data)
    {
        return array_map(function($value){
            $stemmer = new Italian();
            return [$stemmer->stem($value), $value];
        }, $data);
    }
}
