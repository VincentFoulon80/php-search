<?php

namespace VFou\Search\Tokenizers;

use Wamania\Snowball\Russian;

class RussianStemmingTokenizer implements TokenizerInterface
{

    public static function tokenize($data)
    {
        return array_map(function($value){
            $stemmer = new Russian();
            return $stemmer->stem($value);
        }, $data);
    }
}
