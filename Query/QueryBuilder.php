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

    /**
     * @param QuerySegment $query
     */
    public function setQuerySegment(QuerySegment $query){
        $this->search = $query;
    }

    /**
     * @param string $query
     * @return QueryBuilder
     * @deprecated Please use QuerySegment::search($query, *other segments*) instead
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
     * @return QueryBuilder
     * @deprecated Please use QuerySegment::exactSearch($field, $terms) instead
     */
    public function exactSearch($field, $terms)
    {
        $this->search = [
            $field => $terms
        ];
        return $this;
    }

    /**
     * @param $field
     * @param $terms
     * @return $this
     * @deprecated Please use QuerySegment::exactSearch($field, $terms) instead
     */
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

    /**
     * @param $field
     * @param $terms
     * @return QueryBuilder
     * @deprecated Please use QuerySegment::fieldSearch($field, $terms) instead
     */
    public function fieldSearch($field, $terms)
    {
        return $this->exactSearch($field.'%', $terms);
    }

    /**
     * @param $field
     * @param $terms
     * @return $this
     * @deprecated Please use QuerySegment::fieldSearch($field, $terms) instead
     */
    public function addFieldSearch($field, $terms)
    {
        return $this->addExactSearch($field.'%', $terms);
    }

    /**
     * @param $field
     * @param $terms
     * @return $this
     * @deprecated Please use QuerySegment::lesserSearch($field, $terms) instead
     */
    public function lesserSearch($field, $terms){
        return $this->addExactSearch($field.'<', $terms);
    }

    /**
     * @param $field
     * @param $terms
     * @return $this
     * @deprecated Please use QuerySegment::lesserEqualSearch($field, $terms) instead
     */
    public function lesserEqualSearch($field, $terms){
        return $this->addExactSearch($field.'<=', $terms);
    }

    /**
     * @param $field
     * @param $terms
     * @return $this
     * @deprecated Please use QuerySegment::greaterSearch($field, $terms) instead
     */
    public function greaterSearch($field, $terms){
        return $this->addExactSearch($field.'>', $terms);
    }

    /**
     * @param $field
     * @param $terms
     * @return $this
     * @deprecated Please use QuerySegment::greaterEqualSearch($field, $terms) instead
     */
    public function greaterEqualSearch($field, $terms){
        return $this->addExactSearch($field.'>=', $terms);
    }

    /**
     * @param $field
     * @param $terms
     * @return $this
     * @deprecated Please use QuerySegment::notEqualSearch($field, $terms) instead
     */
    public function notEqualSearch($field, $terms){
        return $this->addExactSearch($field.'!=', $terms);
    }
    /**
     * @param $field
     * @param $terms
     * @return $this
     * @deprecated Please use QuerySegment::not(QuerySegment::fieldSearch($field, $terms)) instead
     */
    public function notSearch($field, $terms){
        return $this->addExactSearch('-'.$field, $terms);
    }

    /**
     * Set the ordering to a specific $field with the provided $order (ASC/DESC)
     * By default the ordering is based on the document's score
     * @param $field
     * @param string $order
     * @return $this
     */
    public function orderBy($field, $order = 'ASC'){
        $this->order = [
            $field => $order
        ];
        return $this;
    }

    /**
     * Ask for $field's facet to be provided in the result array
     * @param $field
     */
    public function addFacet($field){
        $this->facets[$field] = $field;
    }

    /**
     * Set the number of documents you want to retrieve
     * @param int $limit
     */
    public function setLimit(int $limit){
        $this->limit = $limit;
    }

    /**
     * Set the offset of documents, useful for pagination
     * @param int $offset
     */
    public function setOffset(int $offset){
        $this->offset = $offset;
    }

    /**
     * Enables the connex feature
     */
    public function enableConnex() {
        $this->connex = true;
    }

    /**
     * Disables the connex feature
     */
    public function disableConnex() {
        $this->connex = false;
    }

    /**
     * Returns the user's query or QuerySegment
     * @return array|string|QuerySegment
     */
    public function getQuery()
    {
        return $this->search;
    }

    /**
     * Compiles the filters into an array
     * @return array
     */
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
