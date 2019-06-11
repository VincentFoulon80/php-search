<?php

namespace VFou\Search\Tokenizers;

use Wamania\Snowball\Swedish;

class SwedishStemmingTokenizer implements TokenizerInterface
{

    public static function tokenize($data)
    {
        return array_map(function($value){
            $stemmer = new Swedish();
            return [$stemmer->stem($value), $value];
        }, $data);
    }
}
