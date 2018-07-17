<?php
/**
 * Created by PhpStorm.
 * User: Vincent
 * Date: 16/07/2018
 * Time: 12:00
 */

namespace VFou\Search\Tokenizers;

class StripTagsTokenizer implements TokenizerInterface
{

    public static function tokenize($data)
    {
        return array_map("strip_tags", $data);
    }
}