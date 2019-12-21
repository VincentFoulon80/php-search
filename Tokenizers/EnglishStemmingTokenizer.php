<?php

namespace VFou\Search\Tokenizers;

use Wamania\Snowball\English;

class EnglishStemmingTokenizer implements TokenizerInterface
{

    public static function tokenize($data)
    {
        $stemmer = new English();
        return array_map(function($value)use($stemmer){
            return array_unique([$stemmer->stem(mb_convert_encoding($value, 'UTF-8')), $value]);
        }, $data);
    }
}
