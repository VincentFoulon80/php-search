<?php

namespace VFou\Search\Tokenizers;

use Wamania\Snowball\Romanian;

class RomanianStemmingTokenizer implements TokenizerInterface
{

    public static function tokenize($data)
    {
        return array_map(function($value){
            $stemmer = new Romanian();
            return $stemmer->stem($value);
        }, $data);
    }
}
