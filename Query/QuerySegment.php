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

    public static function exactSearch($field, $terms){
        $qs = new QuerySegment();
        $qs->field = $field;
        $qs->value = $terms;
        return $qs;
    }

    public static function fieldSearch($field, $terms){
        return self::exactSearch($field.'%', $terms);
    }
    public static function lesserSearch($field, $terms){
        return self::exactSearch($field.'<', $terms);
    }
    public static function lesserEqualSearch($field, $terms){
        return self::exactSearch($field.'<=', $terms);
    }
    public static function greaterSearch($field, $terms){
        return self::exactSearch($field.'>', $terms);
    }
    public static function greaterEqualSearch($field, $terms){
        return self::exactSearch($field.'>=', $terms);
    }
    public static function notEqualSearch($field, $terms){
        return self::exactSearch($field.'!=', $terms);
    }

    public static function search($simpleQuery, QuerySegment $childSegment){
        $qs = new QuerySegment(self::Q_SEARCH);
        $qs->field = '%';
        $qs->value = $simpleQuery;
        $qs->children = [$childSegment];
        return $qs;
    }

    /**
     * @param QuerySegment[] $segments
     * @return QuerySegment
     */
    public static function and(...$segments)
    {
        if(count($segments) == 1 && is_array($segments[0])){
            $segments = $segments[0];
        }
        $qs = new QuerySegment(self::Q_AND);
        $qs->children = $segments;
        return $qs;
    }

    /**
     * @param QuerySegment[] $segments
     * @return QuerySegment
     */
    public static function or(...$segments)
    {
        if(count($segments) == 1 && is_array($segments[0])){
            $segments = $segments[0];
        }
        $qs = new QuerySegment(self::Q_OR);
        $qs->children = $segments;
        return $qs;
    }

    /**
     * @param QuerySegment $segment
     * @return QuerySegment
     */
    public static function not(QuerySegment $segment)
    {
        $segment->field = '-'.$segment->field;
        return $segment;
    }

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
