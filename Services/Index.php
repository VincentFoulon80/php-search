<?php

namespace VFou\Search\Services;

use VFou\Search\Services\FAL\Directory;
use VFou\Search\Tokenizers\TokenizerInterface;

class Index
{
    /**
     * @var Directory $index
     */
    private $index;

    /**
     * @var Directory $documents
     */
    private $documents;

    /**
     * @var Directory $cache
     */
    private $cache;

    /**
     * @var array $schema
     */
    private $schemas;

    /**
     * @var array $types
     */
    private $types;

    /**
     * Index constructor.
     * @param $config
     * @param $schemas
     * @param $types
     * @throws \Exception
     */
    public function __construct($config, $schemas, $types)
    {
        $this->schemas = $schemas;
        $this->types = $types;
        try {
            $this->index = new Directory($config['var_dir'].$config['index_dir']);
            $this->documents = new Directory($config['var_dir'].$config['documents_dir'], false);
            $this->cache = new Directory($config['var_dir'].$config['cache_dir']);
        } catch (\Exception $e) {
            throw new \Exception("Unable to load Index : ".$e->getMessage());
        }

    }

    /**
     * @param $document
     * @return bool
     * @throws \Exception
     */
    public function update($document)
    {
        if(is_object($document)){
            $document = get_object_vars($document);
        }
        if(!isset($document['id'])){
            throw new \Exception("Document should have 'id' property.");
        }
        if(!isset($document['type'])){
            throw new \Exception("Document should have 'type' property.");
        }
        if(!isset($this->schemas[$document['type']])){
            throw new \Exception("Document type ".$document['type']." do not match any of existing types :".implode(", ", array_keys($this->schemas)));
        }
        // we should be good now
        $schema = $this->schemas[$document['type']];
        // building document
        list($doc, $index) = $this->buildDoc($document, $schema);
        $tmp = new \RecursiveIteratorIterator(new \RecursiveArrayIterator($index));
        $index = [];
        foreach ($tmp as $k=>$v){
            if(isset($index[$k])){
                $index[$k] += $v;
            } else {
                $index[$k] = $v;
            }
        }

        $this->updateIndex($index, $document['id']);
        $this->updateDocument($doc, $document['id']);
        $this->clearCache();
        return true;
    }


    /**
     * memory optimized indexation of multiple files
     * @param array $documents
     * @return bool
     * @throws \Exception
     */
    public function updateMultiple(array &$documents){
        foreach($documents as &$document){
            $this->update($document);
            $document = null;
            $this->freeMemory();
        }
        return true;
    }

    /**
     * @param $id
     * @return bool
     */
    public function delete($id){
        // remove document
        $this->documents->delete($id);
        // clear the index of every references
        $allFiles = $this->index->openAll();
        foreach($allFiles as $file){
            if($file->getName() == "all") continue;
            $tokens = $file->getContent();
            $tokensToRemove = [];
            foreach($tokens as &$token){
                if(isset($token[$id])){
                    unset($token[$id]);
                    if(empty($token)){
                        $tokensToRemove[] = $token;
                    }
                }
            }
            foreach($tokensToRemove as $token){
                unset($tokens[$token]);
            }
            if(empty($tokens)){
                $file->delete();
            } else {
                $file->setContent($tokens);
            }
        }
        $this->clearCache();
        return true;
    }

    /**
     * @throws \Exception
     */
    public function rebuild(){
        $documents = $this->documents->openAll();
        foreach($documents as $document){
            $this->update($document->getContent());
            $document->unload();
        }
        $this->clearCache();
    }

    public function clearCache(){
        $this->cache->deleteAll(false);
    }

    /**
     * @param $query
     * @param array $filters
     * @return array
     */
    public function search($query, $filters = [])
    {
        $default = [
            "offset" => 0
        ];
        $filters = array_merge($default,$filters);
        if(!is_array($query)){
            // simple search

            $start = microtime(true);

            $tokens = array_keys($this->tokenize($query, ["_type"=>"search","_boost"=>0]));

            $end = microtime(true);
            echo("tokenization : ".(($end-$start)*1000)." ms<br>");
            asort($tokens);
            $tmp = array_merge($tokens, $filters);
            arsort($tmp);
            $md5 = md5(serialize($tmp));
            $cached = $this->getCache($md5);
            if (!empty($cached)) {
                return $cached;
            }

            $results = [];

            $start = microtime(true);

            foreach($tokens as $token){
                $tmp = $this->find($token);
                foreach($tmp as $k => $v){
                    if(!isset($results[$k])){
                        $results[$k] = $v;
                    } else {
                        $results[$k] += $v;
                    }
                }
            }
            arsort($results);

            $end = microtime(true);
            echo("fetching results : ".(($end-$start)*1000)." ms<br>");

            $documents = [];

            $start = microtime(true);

            $i = 0;
            foreach($results as $doc => $score){
                if($i < $filters['offset']){
                    $i++;
                    continue;
                }
                if(isset($filters['limit']) && $i >= $filters['offset']+$filters['limit']) break;
                $documents[$doc] = $this->documents->open($doc)->getContent();
                $documents[$doc]['_score'] = $score;
                $i++;
            }

            $end = microtime(true);
            echo("fetching documents : ".(($end-$start)*1000)." ms<br>");

            $response = [
                "numFound" => count($results),
                "maxScore" => !empty($results) ? max($results) : 0,
                "documents" => $documents,
                "facets" => []
            ];
            $this->setCache($md5, $response);

            return $response;
        } else {
            // precise search
            $response = [];
            return $response;
        }
    }

    /**
     * @param $id
     * @return mixed
     */
    public function getDocument($id)
    {
        $file = $this->documents->open($id);
        return $file->getContent();
    }

    /**
     * @return array
     */
    public function getSchemas()
    {
        return $this->schemas;
    }

    /**
     * @param array $schemas
     */
    public function setSchemas($schemas)
    {
        $this->schemas = $schemas;
    }

    public function freeMemory(){
        $this->index->free();
        $this->documents->free();
        $this->cache->free();
    }

    /**
     * @param $token
     * @return array
     */
    private function find($token){
        if(empty($token)) return [];
        $file = $this->index->open(substr($token,0,1));
        $index = $file->getContent();
        if(!isset($index[$token])){
            return $this->find(substr($token,0,-1));
        }
        return $index[$token];
    }

    /**
     * @param $data
     * @param $schema
     * @return array
     */
    private function buildDoc($data, $schema)
    {
        $doc = [];
        if(isset($data['id'])) $doc['id'] = $data['id'];
        if(isset($data['type'])) $doc['type'] = $data['type'];
        $index = [];
        foreach($schema as $field=>$definition)
        {
            $doc[$field] = $this->buildField($field, $definition, $data);
            $index[$field] = $this->buildIndex($field, $definition, $data);
        }
        return [$doc, $index];
    }

    /**
     * @param $fieldName
     * @param $definition
     * @param $data
     * @return array|\DateTime|mixed
     */
    private function buildField($fieldName, $definition, $data)
    {
        switch($definition['_type'])
        {
            case "datetime":
                return new \DateTime(!empty($fieldName) ? $data[$fieldName] : $data);
                break;
            case "list":
                $def = array_merge($definition, ["_type"=>$definition['_type.']]);
                $tmp = [];
                foreach(!empty($fieldName) ? $data[$fieldName] : $data as $d){
                    $tmp[] = $this->buildField("", $def, $d);
                }
                return $tmp;
                break;
            case "array":
                return $this->buildDoc(!empty($fieldName) ? $data[$fieldName] : $data, $definition['_array'])[0];
                break;
            default:
                return !empty($fieldName) ? $data[$fieldName] : $data;
                break;
        }
    }

    /**
     * @param $fieldName
     * @param $definition
     * @param $data
     * @return array|mixed|null|\RecursiveIteratorIterator|string
     */
    private function buildIndex($fieldName, $definition, $data)
    {
            switch($definition['_type'])
            {
                case "datetime":
                    $this->buildFilter(!empty($fieldName) ? $data[$fieldName] : $data, $definition);
                    return $definition['_indexed'] ? $this->tokenize(new \DateTime(!empty($fieldName) ? $data[$fieldName] : $data), $definition) : "";
                    break;
                case "list":
                    $def = array_merge($definition, ["_type"=>$definition['_type.']]);
                    $tmp = [];
                    foreach(!empty($fieldName) ? $data[$fieldName] : $data as $d){
                        $tmp[] = $this->buildIndex("", $def, $d);
                    }
                    return $tmp;
                    break;
                case "array":
                    return $this->buildDoc(!empty($fieldName) ? $data[$fieldName] : $data, $definition['_array'])[1];
                    break;
                default:
                    $this->buildFilter(!empty($fieldName) ? $data[$fieldName] : $data, $definition);
                    return $definition['_indexed'] ? $this->tokenize(!empty($fieldName) ? $data[$fieldName] : $data, $definition) : "";
                    break;
            }
    }

    /**
     * @param $data
     * @param $def
     * @return array|null|\RecursiveIteratorIterator
     */
    private function tokenize($data, $def)
    {
        /** @var TokenizerInterface[] $typeDef */
        $typeDef = isset($this->types[$def['_type']]) ? $this->types[$def['_type']] : $this->types['_default'];

        if(!is_array($data)){
            $data = [$data];
        }
        foreach($typeDef as $tokenizer){
            $data = iterator_to_array(new \RecursiveIteratorIterator(new \RecursiveArrayIterator($tokenizer::tokenize($data))));
        }
        $data = array_flip(array_filter($data));
        foreach($data as $k=>$d){
            $data[$k] = $def['_boost'];
        }
        return $data;
    }

    /**
     * @param $data
     * @param $def
     * @return array|null|\RecursiveIteratorIterator
     */
    private function buildFilter($data, $def)
    {
        $filterable = isset($def['_filterable']) ? $def['_filterable'] : false;
        if($filterable){

        }
    }

    /**
     * @param $doc
     * @param $id
     */
    private function updateDocument($doc, $id)
    {
        $file = $this->documents->open($id);
        $file->setContent($doc);
    }

    private function updateIndex($index, $id)
    {
        $file = $this->index->open("all");
        $all = $file->getContent();
        foreach($index as $token=>$score){
            $t = substr($token,0,1);
            if(!isset($all[$token])){
                $all[$token] = $t;
            }
            $f = $this->index->open($t);
            $tokens = $f->getContent();
            if(!isset($tokens[$token])){
                $tokens[$token] = ["$id"=>$score];
            } else {
                $tokens[$token]["$id"] = $score;
            }
            $f->setContent($tokens);
        }
        $file->setContent($all);
    }

    /**
     * @param $identifier
     * @param $response
     */
    private function setCache($identifier, $response)
    {
        $file = $this->cache->open($identifier);
        $file->setContent($response);
    }

    /**
     * @param $identifier
     * @return mixed
     */
    private function getCache($identifier)
    {
        $file = $this->cache->open($identifier);
        return $file->getContent();
    }

}