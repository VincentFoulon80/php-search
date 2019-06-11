<?php

namespace VFou\Search\Tokenizers;

use Wamania\Snowball\Dutch;

class DutchStemmingTokenizer implements TokenizerInterface
{

    public static function tokenize($data)
    {
        return array_map(function($value){
            $stemmer = new Dutch();
            return [$stemmer->stem($value), $value];
        }, $data);
    }
}
