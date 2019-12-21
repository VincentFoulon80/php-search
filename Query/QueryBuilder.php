<?php


namespace VFou\Search\Query;


class QueryBuilder
{
    /** @var string|array|QuerySegment $search */
    private $search;
    private $limit;
    private $offset;
    private $order;
    private $facets;
    private $connex = false;

    /**
     * QueryBuilder constructor.
     * @param string|QuerySegment $query
     * @param QuerySegment $querySegment
     */
    public function __construct($query = null, QuerySegment $querySegment = null)
    {
        $this->search = $query ?? '';
        if($querySegment != null){
            if(is_string($query)){
                $this->search = QuerySegment::search($query, $querySegment);
            } else {
                $this->search = $querySegment;
            }
        }
        $this->limit = 10;
        $this->offset = 0;
        $this->order = [];
        $this->facets = [];
    }

    public function setQuerySegment(QuerySegment $query){
        $this->search = $query;
    }

    /**
     *
     * @param string $query
     */
    public function search($query = "")
    {
        if(is_array($this->search)){
            $this->search['%'] = $query;
        } else {
            $this->search = $query;
        }
        return $this;
    }

    /**
     * @param $field
     * @param $terms
     */
    public function exactSearch($field, $terms)
    {
        $this->search = [
            $field => $terms
        ];
        return $this;
    }
    public function addExactSearch($field, $terms)
    {
        if(!is_array($this->search)){
            if(!empty($this->search)){
                $this->search = ['%'=>$this->search];
            } else {
                $this->search = [];
            }
        }
        $this->search[$field][] = $terms;
        return $this;
    }

    public function fieldSearch($field, $terms)
    {
        return $this->exactSearch($field.'%', $terms);
    }
    public function addFieldSearch($field, $terms)
    {
        return $this->addExactSearch($field.'%', $terms);
    }

    public function lesserSearch($field, $terms){
        return $this->addExactSearch($field.'<', $terms);
    }
    public function lesserEqualSearch($field, $terms){
        return $this->addExactSearch($field.'<=', $terms);
    }
    public function greaterSearch($field, $terms){
        return $this->addExactSearch($field.'>', $terms);
    }
    public function greaterEqualSearch($field, $terms){
        return $this->addExactSearch($field.'>=', $terms);
    }
    public function notEqualSearch($field, $terms){
        return $this->addExactSearch($field.'!=', $terms);
    }
    public function notSearch($field, $terms){
        return $this->addExactSearch('-'.$field, $terms);
    }

    public function orderBy($field, $order = 'ASC'){
        $this->order = [
            $field => $order
        ];
        return $this;
    }

    public function addFacet($field){
        $this->facets[$field] = $field;
    }

    public function setLimit(int $limit){
        $this->limit = $limit;
    }

    public function setOffset(int $offset){
        $this->offset = $offset;
    }

    public function enableConnex() {
        $this->connex = true;
    }
    public function disableConnex() {
        $this->connex = false;
    }

    public function getQuery()
    {
        return $this->search;
    }

    public function getFilters(){
        return [
            'limit' => $this->limit,
            'offset' => $this->offset,
            'order' => $this->order,
            'facets' => $this->facets,
            'connex' => $this->connex
        ];
    }
}
