<?php

namespace VFou\Search\Tokenizers;

use Wamania\Snowball\Swedish;

class SwedishStemmingTokenizer implements TokenizerInterface
{

    public static function tokenize($data)
    {
        $stemmer = new Swedish();
        return array_map(function($value)use($stemmer){
            return array_unique([$stemmer->stem($value), $value]);
        }, $data);
    }
}
