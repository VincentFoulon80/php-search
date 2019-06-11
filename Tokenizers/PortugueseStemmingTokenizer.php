<?php

namespace VFou\Search\Tokenizers;

use Wamania\Snowball\Portuguese;

class PortugueseStemmingTokenizer implements TokenizerInterface
{

    public static function tokenize($data)
    {
        return array_map(function($value){
            $stemmer = new Portuguese();
            return [$stemmer->stem($value), $value];
        }, $data);
    }
}
