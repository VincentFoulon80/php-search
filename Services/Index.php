<?php

namespace VFou\Search\Services;

use Closure;
use DateTime;
use Exception;
use RecursiveArrayIterator;
use RecursiveIteratorIterator;
use VFou\Search\Query\QuerySegment;
use VFou\Search\Services\FAL\Directory;
use VFou\Search\Tokenizers\TokenizerInterface;

class Index
{
    /**
     * @var array $config
     */
    private $config;

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
     * @var int $updatingId
     */
    private $updatingId;


    /**
     * Index constructor.
     * @param $config
     * @param $schemas
     * @param $types
     * @throws Exception
     */
    public function __construct($config, $schemas, $types)
    {
        $this->config = $config;
        $this->schemas = $schemas;
        $this->types = $types;
        try {
            $this->index = new Directory($config['var_dir'].$config['index_dir']);
            $this->documents = new Directory($config['var_dir'].$config['documents_dir'], false);
            $this->cache = new Directory($config['var_dir'].$config['cache_dir']);
        } catch (Exception $e) {
            throw new Exception('Unable to load Index : '.$e->getMessage());
        }

    }

    /**
     * @param $document
     * @return bool
     * @throws Exception
     */
    public function update($document)
    {
        if(is_object($document)){
            $document = get_object_vars($document);
        }
        if(!isset($document['id'])){
            throw new Exception("Document should have 'id' property.");
        }
        $this->updatingId = $document['id'];
        if(!isset($document['type'])){
            throw new Exception("Document should have 'type' property.");
        }
        if(!isset($this->schemas[$document['type']])){
            throw new Exception("Document type ".$document['type']." do not match any of existing types : ".implode(", ", array_keys($this->schemas)));
        }
        if($this->documents->open($document['id']) !== null){
            $this->delete($document['id']);
        }
        // we should be good now
        $schema = $this->schemas[$document['type']];
        // building document
        list($doc, $index) = $this->buildDoc($document, $schema);
        $tmp = new RecursiveIteratorIterator(new RecursiveArrayIterator($index));
        $index = [];
        foreach ($tmp as $k=>$v){
            if(isset($index[$k])){
                $index[$k] += !empty($v) ? $v:0;
            } else {
                $index[$k] = !empty($v) ? $v:0;
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
     * @throws Exception
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
     * @throws Exception
     */
    public function delete($id){
        // remove document
        $this->documents->delete($id);
        // clear the index of every references
        $allFiles = $this->index->openAll();
        if($allFiles){
            foreach($allFiles as $file){
                if($file->getName() == 'all') continue;
                $tokens = $file->getContent();
                $tokensToRemove = [];
                foreach($tokens as $tokenName => &$token){
                    if(isset($token[$id])){
                        unset($token[$id]);
                        if(empty($token)){
                            $tokensToRemove[] = $tokenName;
                        }
                    }
                }
                foreach($tokensToRemove as $tokenName){
                    if(isset($tokens[$tokenName]))
                        unset($tokens[$tokenName]);
                }
                if(empty($tokens)){
                    $file->delete();
                } else {
                    $file->setContent($tokens);
                }
            }
        }
        $this->clearCache();
        return true;
    }

    /**
     * WARNING If you use this function be sure to know what you are doing !
     * Be sure that your max execution time ini parameter is big enough to handle.
     * Main reason to use :
     * - refresh fields after updating the engine so that you can use the new index feature
     * @return array : errors encountered while rebuilding
     * @throws Exception
     */
    public function rebuild(){
        $documents = $this->documents->openAll();
        $errors = [];
        foreach($documents as $document){
            try {
                $this->update($document->getContent());
            } catch(Exception $ex){
                $errors[] = "file '".$document->getName()."' : ".$ex->getMessage();
            }
        }
        $this->clearCache();
        return $errors;
    }

    /**
     * @throws Exception
     */
    public function clearCache(){
        $this->cache->deleteAll(false);
    }

    /**
     * @param $query
     * @param array $filters
     * @return array
     * @throws Exception
     */
    public function search($query, $filters = [])
    {
        if(!isset($filters['offset'])) $filters['offset'] = 0;
        if(is_string($query)){
            // simple search
            $tokens = $this->tokenizeQuery($query);

            asort($tokens);
            $tmp = array_merge($tokens, $filters);
            arsort($tmp);
            $md5 = md5(serialize($tmp));
            $cached = $this->getCache($md5);
            if (!empty($cached)) {
                return $cached;
            }

            $results = [];
            if(!empty($tokens)){
                foreach($tokens as $token){
                    $this->computeScore($results, $this->find($token));
                }
            } else {
                $results = array_flip($this->documents->scan());
                foreach($results as $key=>&$value){
                    $value = 0;
                }
            }


        } else {
            // precise search
            $results = [];

            asort($filters);
            $tmp = [
                "query" => $query,
                "filters" => $filters
            ];
            $md5 = "precise_".md5(serialize($tmp));
            $cached = $this->getCache($md5);
            if (!empty($cached)) {
                return $cached;
            }
            $regularResult = [];
            if(is_a($query, QuerySegment::class)){
                /** @var QuerySegment $query */
                if($query->type == QuerySegment::Q_SEARCH){
                    $tokens = $this->tokenizeQuery($query->getValue());
                    if(!empty($tokens)){
                        foreach($tokens as $token){
                            $this->computeScore($regularResult, $this->find($token));
                        }
                    }
                    $query = $query->getChildren()[0];
                }
                $results = $this->depileSegment($query);
            } else {
                /** @var array $query */
                if(isset($query['%'])){
                    $tokens = $this->tokenizeQuery($query['%']);
                    if(!empty($tokens)){
                        foreach($tokens as $token){
                            $this->computeScore($regularResult, $this->find($token));
                        }
                    }
                }
                $this->processAdvancedSearch($query, $results);
            }
            if(!empty($regularResult)){
                $results = array_intersect_key($regularResult, $results);
            }
        }
        arsort($results);
        $facets = $this->processFacets($results, $query, $filters);
        $documents = $this->processResults($results, $filters);
        $response = [
            'numFound' => count($results),
            'maxScore' => !empty($results) ? max($results) : 0,
            'documents' => $documents,
            'facets' => $facets
        ];
        $this->setCache($md5, $response);
        return $response;
    }

    /**
     * @param QuerySegment $qs
     * @return array|string
     * @throws Exception
     */
    private function depileSegment(QuerySegment $qs){
        $results = [];
        $first = true;
        foreach($qs->getSegment() as $field=>$value){
            list($not, $mode, $trueField) = $this->describeField($field);
            if(is_a($value, QuerySegment::class)){
                $currentResult = $this->depileSegment($value);
            } else {
                $currentResult = [];
                $subFirst = true;
                foreach($value as $v){
                    $subResult = $this->getAdvancedResult($mode, $trueField, $v);
                    $this->mergeSegments($qs, $currentResult,$subFirst,$not,$subResult);
                    $subFirst = false;
                }
            }
            $this->mergeSegments($qs, $results, $first, $not, $currentResult);
            $first = false;
        }
        return $results;
    }

    /**
     * @param QuerySegment $qs
     * @param $results
     * @param bool $first
     * @param $not
     * @param $currentResult
     * @return array
     */
    private function mergeSegments(QuerySegment $qs, &$results, bool $first, $not, $currentResult)
    {
        if ($not) {
            if ($first) {
                $results = array_flip($this->documents->scan());
                foreach ($results as $k => &$v) {
                    $v = 0;
                }
            }
            $currentResult = array_diff_key($results, $currentResult);
        }
        if ($qs->type === QuerySegment::Q_OR) {
            $this->computeScore($results, $currentResult);
        } elseif ($qs->type === QuerySegment::Q_AND) {
            if ($first) {
                $results = $currentResult;
            } else {
                $results = array_intersect_key($results, $currentResult);
            }
        }
    }

    /**
     * @param $query
     * @param $results
     * @throws Exception
     */
    private function processAdvancedSearch($query, &$results)
    {
        $gtOrltUsed = [];
        $first = true;
        foreach($query as $field=>$values) {
            if($field == '%') return;
            $fieldResults = [];
            $mergeMode = 'AND';
            list($not, $mode, $trueField) = $this->describeField($field);
            foreach($values as $value){
                $fieldResults = $this->getAdvancedResult($mode, $trueField, $value, $fieldResults);
            }
            if(!empty($mode) && $mode != '%'){ // process multiple iterations of <, >, <= or >= searches
                if(isset($gtOrltUsed[$trueField])){
                    $mergeMode = 'AND'; // make it an AND condition
                }
                $gtOrltUsed[$trueField] = 1;
            }
            if($not){
                if($first){
                    $results = array_flip($this->documents->scan());
                    foreach($results as $key=>&$value){
                        $value = 0;
                    }
                }
                $results = array_diff_key($results, $fieldResults);
            } else {
                if($mergeMode === 'OR'){
                    $this->computeScore($results, $fieldResults);
                } elseif($mergeMode === 'AND') {
                    if($first) {
                        $results = $fieldResults;
                    } else {
                        $results = array_intersect_key($results, $fieldResults);
                    }
                }
            }
            $first = false;
        }
    }

    /**
     * @param $field
     * @return array
     */
    private function describeField($field): array
    {
        $not = false;
        $mode = '';
        $trueField = $field;
        if (substr($field, 0, 1) === '-') {
            $not = true;
            $trueField = substr($field, 1);
        }
        if (in_array(substr($trueField, -1), ['<', '>', '='])) {
            $mode = substr($trueField, -1);
            $trueField = substr($trueField, 0, -1);
            if (in_array(substr($trueField, -1), ['<', '>', '!'])) {
                $mode = substr($trueField, -1) . $mode;
                $trueField = substr($trueField, 0, -1);
            }
        }
        if (substr($field, -1) == "%") {
            $mode = '%';
            $trueField = substr($trueField, 0, -1);
        }
        return array($not, $mode, $trueField);
    }

    /**
     * @param string $mode
     * @param $field
     * @param $value
     * @param array $fieldResults
     * @return array
     * @throws Exception
     */
    private function getAdvancedResult(string $mode, $field, $value, $fieldResults = []): array
    {
        if (is_object($value) && isset($this->config['serializableObjects'][get_class($value)])) $value = $this->config['serializableObjects'][get_class($value)]($value);
        switch ($mode) {
            case '%': // process regular query
                if ($this->index->open('values_' . $field, false) !== null) {
                    $array = $this->index->open('values_' . $field, false)->getContent();
                    $tokens = $this->tokenizeQuery($value);
                    foreach ($tokens as $token) {
                        $this->computeScore($fieldResults, $array[$token] ?? []);
                    }
                }
                break;
            case '<': // process "Lesser than" query
                if ($this->index->open('exact_' . $field, false) !== null) {
                    $array = $this->index->open('exact_' . $field, false)->getContent();
                    ksort($array);
                    foreach ($array as $k => $v) {
                        if ($k >= $value) break;
                        $this->computeScore($fieldResults, $array[$k] ?? []);
                    }
                }
                break;
            case '>': // process "Greater than" query
                if ($this->index->open('exact_' . $field, false) !== null) {
                    $array = $this->index->open('exact_' . $field, false)->getContent();
                    ksort($array);
                    $found = false;
                    foreach ($array as $k => $v) {
                        if ($k >= $value) $found = true;
                        if (!$found) continue;
                        if ($k != $value) $this->computeScore($fieldResults, $array[$k] ?? []);
                    }
                }
                break;
            case '<=': // process "Lesser than or Equal" query
                if ($this->index->open('exact_' . $field, false) !== null) {
                    $array = $this->index->open('exact_' . $field, false)->getContent();
                    ksort($array);
                    foreach ($array as $k => $v) {
                        if ($k > $value) break;
                        $this->computeScore($fieldResults, $array[$k] ?? []);
                    }
                }
                break;
            case '>=': // process "Greater than or Equal" query
                if ($this->index->open('exact_' . $field, false) !== null) {
                    $array = $this->index->open('exact_' . $field, false)->getContent();
                    ksort($array);
                    $found = false;
                    foreach ($array as $k => $v) {
                        if ($k >= $value) $found = true;
                        if (!$found) continue;
                        $this->computeScore($fieldResults, $array[$k] ?? []);
                    }
                }
                break;
            case '!=':
                if ($this->index->open('exact_' . $field, false) !== null) {
                    $array = $this->index->open('exact_' . $field, false)->getContent();
                    foreach ($array as $k => $v) {
                        if ($k == $value) continue;
                        $this->computeScore($fieldResults, $array[$k] ?? []);
                    }
                }
                break;
            default: // process exact search
                if ($this->index->open('exact_' . $field, false) !== null) {
                    $array = $this->index->open('exact_' . $field, false)->getContent();
                    $this->computeScore($fieldResults, $array[$value] ?? []);
                }
        }
        return $fieldResults;
    }

    /**
     * @param $id
     * @return mixed
     * @throws Exception
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
     * @throws Exception
     */
    private function find($token){
        if(empty($token)) return [];
        $file = $this->index->open(substr($token,0,1));
        $index = $file->getContent();
        if(!isset($index[$token])){
            // find approximative tokens
            return $this->fuzzyFind($token);
        }

        return $index[$token];
    }

    /**
     * @param $token
     * @param bool $providePonderations
     * @return array
     * @throws Exception
     */
    public function suggest($token, $providePonderations = false){
        if(empty($token)) return [];
        $all = $this->index->open('all');
        $tokens = array_keys($all->getContent());
        $matching = [];
        foreach($tokens as $indexToken){
            $strPos = strpos($indexToken, $token);
            if($strPos !== false){
                $matching[$indexToken] = $strPos;
            }
        }
        asort($matching);
        if($providePonderations){
            return $matching;
        }
        return array_keys($matching);
    }

    /**
     * @param $token
     * @return array
     * @throws Exception
     */
    private function fuzzyFind($token)
    {
        if(empty($token)) return [];
        $matching = $this->suggest($token, true);
        if(empty($matching)){
            $matching = $this->approximate($token, $this->config['fuzzy_cost']);
        }
        $found = [];
        if(!empty($matching)){
            reset($matching);
            $minPonderation = current($matching);
            foreach($matching as $match => $ponderation){
                if($ponderation == $minPonderation){
                    $found = array_replace($found, $this->find($match));
                }
            }
        } else {
            $found = $this->find(substr($token,0,-1));
        }

        return $found;
    }

    /**
     *
     * @param $term
     * @param $cost
     * @param array $positions
     * @return array|mixed
     * @throws Exception
     */
    private function approximate($term, $cost, $positions = []){
        $cached = $this->getCache('approx_'.$term);
        if(!empty($cached)){
            return $cached;
        }
        $termL = strlen($term);
        if($termL <= 1) return []; // we shouldn't approximate one character
        if($cost > $termL-1) $cost = $termL-1; // The cost can't be more than the term's length itself
        $tokens = array_keys($this->index->open('all')->getContent());
        $matching = [];
        for($i=0;$i<$termL;$i++){
            $termToFind = substr_replace($term, '', $i,1);
            foreach($tokens as $token){
                $originalToken = $token;
                if(!empty($positions)){
                    foreach($positions as $position){
                        $token = substr_replace($token, '', $position,1);
                    }
                }
                if(strlen($token) >= $termL){
                    $tokenToLink = substr_replace($token, '', $i,1);
                    $strPos = strpos($tokenToLink,$termToFind);
                    if($strPos !== false){
                        $matching[$originalToken] = $strPos;
                    }
                }
            }
            if($cost > 1){
                $positions[$cost] = $i;
                $matching = array_replace($matching, $this->approximate($termToFind,$cost-1, $positions));
            }
        }
        asort($matching);
        $this->setCache('approx_'.$term, $matching);
        return $matching;
    }

    /**
     * @param $data
     * @param $schema
     * @return array
     * @throws Exception
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
     * @return array|DateTime|mixed
     * @throws Exception
     */
    private function buildField($fieldName, $definition, $data)
    {
        switch($definition['_type'])
        {
            case 'datetime':
                if(is_a((!empty($fieldName) ? $data[$fieldName] : $data), DateTime::class)){
                    return (!empty($fieldName) ? $data[$fieldName] : $data);
                }
                return new DateTime(!empty($fieldName) ? $data[$fieldName] : $data);
                break;
            case 'list':
                $def = array_merge($definition, ['_type'=>$definition['_type.']]);
                $tmp = [];
                if(!empty($fieldName) ? !empty($data[$fieldName]) : !empty($data)){
                    foreach(!empty($fieldName) ? $data[$fieldName] : $data as $d){
                        $tmp[] = $this->buildField('', $def, $d);
                    }
                }
                return $tmp;
                break;
            case 'array':
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
     * @return array|mixed|null|RecursiveIteratorIterator|string
     * @throws Exception
     */
    private function buildIndex($fieldName, $definition, $data)
    {
        if(empty($definition['_name'])) $definition['_name'] = $fieldName;
        if(!isset($definition['_indexed'])) $definition['_indexed'] = false;
        switch($definition['_type'])
        {
            case 'datetime':
                $this->buildFilter(!empty($fieldName) ? $data[$fieldName] : $data, $definition);
                if(is_a((!empty($fieldName) ? $data[$fieldName] : $data), DateTime::class)){
                    $dt = (!empty($fieldName) ? $data[$fieldName] : $data);
                } else {
                    $dt = new DateTime(!empty($fieldName) ? $data[$fieldName] : $data);
                }
                return $definition['_indexed'] ? $this->tokenize($dt, $definition) : "";
                break;
            case 'list':
                $def = array_merge($definition, ['_type'=>$definition['_type.']]);
                $tmp = [];
                if(!empty($fieldName) ? !empty($data[$fieldName]) : !empty($data)){
                    foreach(!empty($fieldName) ? $data[$fieldName] : $data as $d){
                        $tmp[] = $this->buildIndex('', $def, $d);
                    }
                }
                return $tmp;
                break;
            case 'array':
                return $this->buildDoc(!empty($fieldName) ? $data[$fieldName] : $data, $definition['_array'])[1];
                break;
            default:
                $this->buildFilter(!empty($fieldName) ? $data[$fieldName] : $data, $definition);
                return $definition['_indexed'] ? $this->tokenize(!empty($fieldName) ? $data[$fieldName] : $data, $definition) : '';
                break;
        }
    }

    public function tokenizeQuery($query){
        return array_keys($this->tokenize($query, ['_type'=>'search','_boost'=>0]));
    }

    /**
     * @param $data
     * @param $def
     * @return array|null|RecursiveIteratorIterator
     */
    private function tokenize($data, $def)
    {
        /** @var TokenizerInterface[] $typeDef */
        $typeDef = isset($this->types[$def['_type']]) ? $this->types[$def['_type']] : $this->types['_default'];
        if(!isset($def['_boost'])) $def['_boost'] = 1;

        if(!is_array($data)){
            $data = [$data];
        }
        foreach($typeDef as $tokenizer){
            $tmp = $tokenizer::tokenize($data);
            $data = [];
            array_walk_recursive($tmp, function($e)use(&$data){
                $data[] = $e;
            });
        }
        $data = array_filter($data);
        $res = [];
        foreach($data as $d=>$k){
            if(isset($res[$k])){
                $res[$k] += $def['_boost'];
            } else {
                $res[$k] = $def['_boost'];
            }
        }
        return $res;
    }

    /**
     * @param $data
     * @param $def
     * @return void
     * @throws Exception
     */
    private function buildFilter($data, $def)
    {
        $filterable = isset($def['_filterable']) ? $def['_filterable'] : false;
        if($filterable){
            $file = $this->index->open('facet_'.$def['_name']);
            $array = $file->getContent();
            $array[$data][$this->updatingId] = $this->updatingId;
            $file->setContent($array);
        }
        $file = $this->index->open("values_".$def['_name']);
        $exact = $this->index->open("exact_".$def['_name']);
        $array = $exact->getContent();
        if(!is_array($array)) $array = [];
        if(is_object($data)){
            if(isset($this->config['serializableObjects'][get_class($data)])){
                $data = $this->config['serializableObjects'][get_class($data)]($data);
            } else {
                throw new Exception("Field ".$def['_name']." of document ID ".$this->updatingId." is an object of type ".get_class($data)." that is not supported by the currently configured SerializableObjects.");
            }
        }
        $array[$data][$this->updatingId] = $def['_boost'] ?? 1;
        if(!empty($array)){
            $exact->setContent($array);
        }
        $array = $file->getContent();
        if(!is_array($array)) $array = [];
        $tokens = $this->tokenize($data, $def);
        foreach($tokens as $token => $score){
            $array[$token][$this->updatingId] = $score;
        }
        if(!empty($array)){
            $file->setContent($array);
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

    /**
     * @param $index
     * @param $id
     * @throws Exception
     */
    private function updateIndex($index, $id)
    {
        $file = $this->index->open('all');
        $all = $file->getContent();
        foreach($index as $token=>$score){
            $t = substr($token,0,1);
            if(!isset($all[$token])){
                $all[$token] = $t;
            }
            $f = $this->index->open($t);
            $tokens = $f->getContent();
            if(!is_array($tokens)) $tokens = [];
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
     * @throws Exception
     */
    private function getCache($identifier)
    {
        $file = $this->cache->open($identifier);
        return $file->getContent();
    }

    /**
     * @param $filters
     * @param array $results
     * @return array
     * @throws Exception
     */
    private function processResults(array $results, $filters): array
    {
        if(isset($filters['order']) && !empty($filters['order'])){
            $nonOrdered = $results;
            $results = [];
            foreach($filters['order'] as $field => $direction){
                if($this->index->open('exact_'.$field, false) !== null){
                    $array = $this->index->open('exact_'.$field, false)->getContent();
                    if($direction === 'ASC'){
                        ksort($array);
                    } elseif($direction === 'DESC'){
                        krsort($array);
                    }
                    foreach($array as $key => $ids){
                        foreach($ids as $id => $falseScore){
                            if(in_array($id, array_keys($nonOrdered))){
                                $results[$id] = $nonOrdered[$id];
                            }
                        }
                    }
                }
            }
        }
        $documents = [];
        $i = 0;
        foreach ($results as $doc => $score) {
            if ($i < $filters['offset']) {
                $i++;
                continue;
            }
            if (isset($filters['limit']) && $i >= $filters['offset'] + $filters['limit']) break;
            $documents[$doc] = $this->documents->open($doc)->getContent();
            $documents[$doc]['_score'] = $score;
            $i++;
        }
        return $documents;
    }

    /**
     * @param array $results
     * @param $query
     * @param $filters
     * @return array
     * @throws Exception
     */
    private function processFacets(array $results, $query, $filters): array
    {
        $facets = [];
        if(isset($filters['facets']) && !empty($filters['facets'])){
            if(!empty($query)){
                foreach ($filters['facets'] as $facet) {
                    if ($this->index->open('facet_' . $facet, false) !== null) {
                        $array = $this->index->open('facet_' . $facet, false)->getContent();
                        foreach ($array as $token => $ids) {
                            $facets[$facet][$token] = count(array_intersect_key(array_flip($ids), $results));
                        }
                        arsort($facets[$facet]);
                    }
                }
            } else {
                foreach ($filters['facets'] as $facet) {
                    if ($this->index->open('facet_' . $facet, false) !== null) {
                        $array = $this->index->open('facet_' . $facet, false)->getContent();
                        foreach ($array as $name => $ids) {
                            $facets[$facet][$name] = count($ids);
                        }
                        arsort($facets[$facet]);
                    }
                }
            }
        }
        return $facets;
    }

    /**
     * @param array $results
     * @param array $scoreArray
     */
    private function computeScore(array &$results, array $scoreArray)
    {
        foreach ($scoreArray as $k => $v) {
            if (!isset($results[$k])) {
                $results[$k] = $v;
            } else {
                $results[$k] += $v;
            }
        }
    }
}
