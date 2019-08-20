<?php

namespace VFou\Search\Tokenizers;

use Wamania\Snowball\Romanian;

class RomanianStemmingTokenizer implements TokenizerInterface
{

    public static function tokenize($data)
    {
        $stemmer = new Romanian();
        return array_map(function($value)use($stemmer){
            return array_unique([$stemmer->stem($value), $value]);
        }, $data);
    }
}
