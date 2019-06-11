<?php

namespace VFou\Search\Tokenizers;

use Wamania\Snowball\French;

class FrenchStemmingTokenizer implements TokenizerInterface
{

    public static function tokenize($data)
    {
        return array_map(function($value){
            $stemmer = new French();
            return [$stemmer->stem($value), $value];
        }, $data);
    }
}
