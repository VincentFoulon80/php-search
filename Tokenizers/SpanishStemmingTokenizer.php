<?php

namespace VFou\Search\Tokenizers;

use Wamania\Snowball\Spanish;

class SpanishStemmingTokenizer implements TokenizerInterface
{

    public static function tokenize($data)
    {
        return array_map(function($value){
            $stemmer = new Spanish();
            return [$stemmer->stem($value), $value];
        }, $data);
    }
}
