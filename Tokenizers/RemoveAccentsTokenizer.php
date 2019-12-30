<?php


namespace VFou\Search\Tokenizers;


class RemoveAccentsTokenizer implements TokenizerInterface
{
    public static function tokenize($data)
    {
        $regexp = '/&([a-z]{1,2})(acute|cedil|circ|grave|lig|orn|ring|slash|th|tilde|uml|caron);/i';
        return array_map(function($elem)use($regexp){
            return html_entity_decode(preg_replace($regexp, '$1', htmlentities($elem)));
        }, $data);
    }
}
