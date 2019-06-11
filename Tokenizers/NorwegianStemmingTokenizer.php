<?php

namespace VFou\Search\Tokenizers;

use Wamania\Snowball\Norwegian;

class NorwegianStemmingTokenizer implements TokenizerInterface
{

    public static function tokenize($data)
    {
        return array_map(function($value){
            $stemmer = new Norwegian();
            return [$stemmer->stem($value), $value];
        }, $data);
    }
}
