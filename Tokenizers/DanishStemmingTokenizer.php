<?php

namespace VFou\Search\Tokenizers;

use Wamania\Snowball\Danish;

class DanishStemmingTokenizer implements TokenizerInterface
{

    public static function tokenize($data)
    {
        $stemmer = new Danish();
        return array_map(function($value)use($stemmer){
            return array_unique([$stemmer->stem($value), $value]);
        }, $data);
    }
}
