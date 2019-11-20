<?php


namespace VFou\Search\Query;


class QueryBuilder
{
    private $search;
    private $limit;
    private $offset;
    private $order;
    private $facets;

    /**
     * QueryBuilder constructor.
     * @param string $query
     */
    public function __construct($query = "")
    {
        $this->search = $query;
        $this->limit = 10;
        $this->offset = 0;
        $this->order = [];
        $this->facets = [];
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

    public function getQuery()
    {
        return $this->search;
    }

    public function getFilters(){
        return [
            'limit' => $this->limit,
            'offset' => $this->offset,
            'order' => $this->order,
            'facets' => $this->facets
        ];
    }
}
