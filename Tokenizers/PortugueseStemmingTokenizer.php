<?php

namespace VFou\Search\Tokenizers;

use Wamania\Snowball\Portuguese;

class PortugueseStemmingTokenizer implements TokenizerInterface
{

    public static function tokenize($data)
    {
        $stemmer = new Portuguese();
        return array_map(function($value)use($stemmer){
            return array_unique([$stemmer->stem($value), $value]);
        }, $data);
    }
}
