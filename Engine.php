<?php

namespace VFou\Search;

use VFou\Search\Services\Index;
use VFou\Search\Tokenizers\DateFormatTokenizer;
use VFou\Search\Tokenizers\DateSplitTokenizer;
use VFou\Search\Tokenizers\LowerCaseTokenizer;
use VFou\Search\Tokenizers\TrimPunctuationTokenizer;
use VFou\Search\Tokenizers\WhiteSpaceTokenizer;

class Engine
{
    /**
     * @var Index $index
     */
    private $index;

    /**
     * @var array $config
     */
    private $config;


    /**
     * Engine constructor.
     * @param array $config
     * @throws \Exception
     */
    public function __construct($config = [])
    {
        $defaultConfig = $this->getDefaultConfig();
        $this->config = array_replace_recursive($defaultConfig, $config);
        $this->index = new Index($this->config['config'], $this->config['schemas'], $this->config['types']);
    }

    /**
     * @return Index
     */
    public function getIndex(){
        return $this->index;
    }

    /**
     * @param $document
     * @return bool
     * @throws \Exception
     */
    public function update($document){
        return $this->index->update($document);
    }

    /**
     * @param array $document
     * @return bool
     * @throws \Exception
     */
    public function updateMultiple(array $document){
        return $this->index->updateMultiple($document);
    }

    /**
     * @param $query
     * @param array $filters
     * @return array
     */
    public function search($query, $filters = []){
        return $this->index->search($query, $filters);
    }

    /**
     * @param $id
     * @return bool
     */
    public function delete($id){
        return $this->index->delete($id);
    }

    /**
     * @return array
     */
    private function getDefaultConfig(){
        return [
            "config" => [
                "var_dir" => $_SERVER['DOCUMENT_ROOT'].DIRECTORY_SEPARATOR."var",
                "index_dir" => DIRECTORY_SEPARATOR."engine".DIRECTORY_SEPARATOR."index",
                "documents_dir" => DIRECTORY_SEPARATOR."engine".DIRECTORY_SEPARATOR."documents",
                "cache_dir" => DIRECTORY_SEPARATOR."engine".DIRECTORY_SEPARATOR."cache"
            ],
            "schemas" => [
                "example-post" => [
                    "title" => [
                        "_type" => "string",
                        "_indexed" => true,
                        "_boost" => 10
                    ],
                    "content" => [
                        "_type" => "text",
                        "_indexed" => true,
                        "_boost" => 0.5
                    ],
                    "date" => [
                        "_type" => "datetime",
                        "_indexed" => true,
                        "_boost" => 2
                    ],
                    "categories" => [
                        "_type" => "list",
                        "_type." => "string",
                        "_indexed" => true,
                        "_filterable" => true,
                        "_boost" => 6
                    ],
                    "comments" => [
                        "_type" => "list",
                        "_type." => "array",
                        "_array" => [
                            "author" => [
                                '_type' => "string",
                                "_indexed" => true,
                                "_filterable" => true,
                                "_boost" => 1
                            ],
                            "date" => [
                                "_type" => "datetime",
                                "_indexed" => true,
                                "_boost" => 0
                            ],
                            "message" => [
                                "_type" => "text",
                                "_indexed" => true,
                                "_boost" => 0.1
                            ]
                        ]
                    ]
                ]
            ],
            "types" => [
                "datetime" => [
                    DateFormatTokenizer::class,
                    DateSplitTokenizer::class
                ],
                "_default" => [
                    LowerCaseTokenizer::class,
                    WhiteSpaceTokenizer::class,
                    TrimPunctuationTokenizer::class
                ]
            ]
        ];
    }

}
