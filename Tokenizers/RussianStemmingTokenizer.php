<?php

namespace VFou\Search\Tokenizers;

use Wamania\Snowball\Russian;

class RussianStemmingTokenizer implements TokenizerInterface
{

    public static function tokenize($data)
    {
        $stemmer = new Russian();
        return array_map(function($value)use($stemmer){
            return array_unique([$stemmer->stem(utf8_encode($value)), $value]);
        }, $data);
    }
}
