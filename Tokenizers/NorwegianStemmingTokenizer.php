<?php

namespace VFou\Search\Tokenizers;

use Wamania\Snowball\Norwegian;

class NorwegianStemmingTokenizer implements TokenizerInterface
{

    public static function tokenize($data)
    {
        $stemmer = new Norwegian();
        return array_map(function($value)use($stemmer){
            return array_unique([$stemmer->stem(utf8_encode($value)), $value]);
        }, $data);
    }
}
