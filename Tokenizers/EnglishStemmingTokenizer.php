<?php

namespace VFou\Search\Tokenizers;

use Wamania\Snowball\English;

class EnglishStemmingTokenizer implements TokenizerInterface
{

    public static function tokenize($data)
    {
        return array_map(function($value){
            $stemmer = new English();
            return $stemmer->stem($value);
        }, $data);
    }
}
