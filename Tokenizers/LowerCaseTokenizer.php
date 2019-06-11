<?php

namespace VFou\Search\Tokenizers;

class LowerCaseTokenizer implements TokenizerInterface
{
    public static function tokenize($data){
        return array_map('strtolower', $data);
    }
}
