<?php

namespace VFou\Search\Tokenizers;

use Wamania\Snowball\Italian;

class ItalianStemmingTokenizer implements TokenizerInterface
{

    public static function tokenize($data)
    {
        $stemmer = new Italian();
        return array_map(function($value)use($stemmer){
            return array_unique([$stemmer->stem(mb_convert_encoding($value, 'UTF-8')), $value]);
        }, $data);
    }
}
