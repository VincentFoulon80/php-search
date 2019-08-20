<?php

namespace VFou\Search\Tokenizers;

use Wamania\Snowball\Dutch;

class DutchStemmingTokenizer implements TokenizerInterface
{

    public static function tokenize($data)
    {
        $stemmer = new Dutch();
        return array_map(function($value)use($stemmer){
            return array_unique([$stemmer->stem($value), $value]);
        }, $data);
    }
}
