<?php

namespace VFou\Search\Tokenizers;

use Wamania\Snowball\Danish;

class DanishStemmingTokenizer implements TokenizerInterface
{

    public static function tokenize($data)
    {
        return array_map(function($value){
            $stemmer = new Danish();
            return [$stemmer->stem($value), $value];
        }, $data);
    }
}
