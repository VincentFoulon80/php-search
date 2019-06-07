<?php

namespace VFou\Search\Tokenizers;

use Wamania\Snowball\German;

class GermanStemmingTokenizer implements TokenizerInterface
{

    public static function tokenize($data)
    {
        return array_map(function($value){
            $stemmer = new German();
            return $stemmer->stem($value);
        }, $data);
    }
}
