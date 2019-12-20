<?php


namespace VFou\Search\Query;


class QuerySegment
{
    const Q_NOTHING = '';
    const Q_OR = 'OR';
    const Q_AND = 'AND';
    const Q_NOT = 'NOT';
    const Q_SEARCH = 'SEARCH';

    public $type;

    private $field;
    private $value;

    /** @var QuerySegment[] $child */
    private $children = [];

    public function __construct($type = self::Q_NOTHING)
    {
        $this->type = $type;
    }

    private function setChildren(array $children){
        $this->children = $children;
    }

    public function getChildren(){
        return $this->children;
    }

    public function setField($field){
        $this->field = $field;
    }

    public function getField()
    {
        return $this->field;
    }

    public function getValue()
    {
        return $this->value;
    }

    public function getSegment(){
        if(empty($this->children)){
            return [$this->field => [$this->value]];
        } else {
            $segment = [];
            foreach($this->children as $child){
                if($child->type != self::Q_NOTHING) $segment[] = $child;
                else $segment = array_merge_recursive($segment, $child->getSegment());
            }
            return $segment;
        }
    }

    // --- Static functions ----------------------

    /**
     * Search for the exact $term provided in $field
     * @param $field
     * @param $terms
     * @return QuerySegment
     */
    public static function exactSearch($field, $terms){
        $qs = new QuerySegment();
        $qs->field = $field;
        $qs->value = $terms;
        return $qs;
    }

    /**
     * Makes an array of QuerySegments based on exactSearch method
     * @see QuerySegment::exactSearch()
     * @param $field
     * @param $searches
     * @return array
     */
    public static function bulkExactSearch($field, $searches){
        $segs = [];
        foreach($searches as $search){
            $segs[] = self::exactSearch($field, $search);
        }
        return $segs;
    }

    /**
     * Regular Search of $term in $field
     * @param $field
     * @param $terms
     * @return QuerySegment
     */
    public static function fieldSearch($field, $terms){
        return self::exactSearch($field.'%', $terms);
    }

    /**
     * Makes an array of QuerySegments based on fieldSearch method
     * @see QuerySegment::fieldSearch()
     * @param $field
     * @param $searches
     * @return array
     */
    public static function bulkFieldSearch($field, $searches){
        $segs = [];
        foreach($searches as $search){
            $segs[] = self::fieldSearch($field, $search);
        }
        return $segs;
    }

    /**
     * Search for values in $field where the values is lesser than $terms
     * @param $field
     * @param $terms
     * @return QuerySegment
     */
    public static function lesserSearch($field, $terms){
        return self::exactSearch($field.'<', $terms);
    }
    /**
     * Search for values in $field where the values is lesser or equal to $terms
     * @param $field
     * @param $terms
     * @return QuerySegment
     */
    public static function lesserEqualSearch($field, $terms){
        return self::exactSearch($field.'<=', $terms);
    }
    /**
     * Search for values in $field where the values is greater than $terms
     * @param $field
     * @param $terms
     * @return QuerySegment
     */
    public static function greaterSearch($field, $terms){
        return self::exactSearch($field.'>', $terms);
    }
    /**
     * Search for values in $field where the values is greater or equal to $terms
     * @param $field
     * @param $terms
     * @return QuerySegment
     */
    public static function greaterEqualSearch($field, $terms){
        return self::exactSearch($field.'>=', $terms);
    }
    /**
     * Search for values in $field where the values is not equal to $terms
     * @param $field
     * @param $terms
     * @return QuerySegment
     */
    public static function notEqualSearch($field, $terms){
        return self::exactSearch($field.'!=', $terms);
    }

    /**
     * @param $simpleQuery
     * @param QuerySegment $childSegment
     * @return QuerySegment
     */
    public static function search($simpleQuery, QuerySegment $childSegment){
        $qs = new QuerySegment(self::Q_SEARCH);
        $qs->field = '%';
        $qs->value = $simpleQuery;
        $qs->children = [$childSegment];
        return $qs;
    }

    /**
     * Merges an array of QuerySegments with an AND link
     * @param QuerySegment[] $segments
     * @return QuerySegment
     */
    public static function and(...$segments)
    {
        if(empty($segments)) return null;
        if(count($segments) == 1 && is_array($segments[0])){
            $segments = $segments[0];
        }
        $qs = new QuerySegment(self::Q_AND);
        $qs->children = $segments;
        return $qs;
    }

    /**
     * Merges an array of QuerySegments with an OR link
     * @param QuerySegment[] $segments
     * @return QuerySegment
     */
    public static function or(...$segments)
    {
        if(empty($segments)) return null;
        if(count($segments) == 1 && is_array($segments[0])){
            $segments = $segments[0];
        }
        $qs = new QuerySegment(self::Q_OR);
        $qs->children = $segments;
        return $qs;
    }

    /**
     * Negates the provided $segment
     * @param QuerySegment $segment
     * @return QuerySegment
     */
    public static function not(QuerySegment $segment)
    {
        $segment->field = '-'.$segment->field;
        return $segment;
    }

    /**
     * Debug your query by creating a human readable string
     * @param QuerySegment $seg
     * @return string
     */
    public static function debug(QuerySegment $seg){
        $rtn = [];
        if($seg->type === QuerySegment::Q_NOT){
            $rtn[] = '';
        }
        foreach($seg->getSegment() as $field=>$value){
            if(is_a($value, QuerySegment::class)){
                $rtn[] = ' ('.self::debug($value).')';
                continue;
            }
            foreach($value as $v){
                if(is_a($v, \DateTime::class)) $v = $v->format(DATE_ATOM);
                $rtn[] = ' '.$field.':"'.$v.'"';
            }
        }
        return implode(' '.$seg->type,$rtn);
    }
}
