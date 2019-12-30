<?php
if(!($GLOBALS['vfou_admin'] ?? false)) die('unauthorized');
$schemas = $schemas ?? [];

function getFieldType($schemaField){
    $result = [];
    $type = $schemaField['_type'] ?? '';
    $subType = $schemaField['_type.'] ?? '';
    $indexed = $schemaField['_indexed'] ?? false;
    $filterable = $schemaField['_filterable'] ?? false;
    if($filterable && !$indexed){
        $result[] = '<span class="schema-error">WARNING : field is \'_filterable\' but not \'_indexed\'. undefined behavior may occur</span>';
    }
    if(!empty($type)){
        if($type == 'array' || $subType == 'array'){
            if(isset($schemaField['_array'])){
                $result[] = displaySchema('',$schemaField['_array']);
            } else {
                return '<span class="schema-error">INVALID SCHEMA : field is of type \'array\' without an \'_array\' entry</span>';
            }
        } else {
            if($type == 'list'){
                if(!empty($subType)){
                    $result[] = ($indexed ? 'Indexed ':'').'<span class="schema-type">list of '. $subType.'</span>'.($filterable ? ' (Filterable)' :'');
                } else {
                    return '<span class="schema-error">INVALID SCHEMA : field is of type \'list\' without a \'_type.\' entry</span>';
                }
            } else {
                $result[] = ($indexed ? 'Indexed ':'').'<span class="schema-type">'.$type.'</span>'.($filterable ? ' (Filterable)' :'');
            }
            if($schemaField['_boost'] ?? false){
                $result[] = 'Boost = '.$schemaField['_boost'];
            }
        }
    } else {
        return '<span class="schema-error">INVALID SCHEMA : field don\'t have a \'_type\' entry</span>';
    }

    return implode('<br>',$result);
}

function displaySchema($name, $schema){
    $html = '';
    if(!empty($name)){
        $html .= '<table style="width:100%;"><caption>'.$name.'</caption><tbody>';
    } else {
        $html .= '<table style="width:100%;"><tbody>';
    }

    foreach($schema as $name=>$value){
        $html .= '<tr><td>'.$name.'</td>';
        $html .= '<td>'.getFieldType($value).'</td>';
        $html .= '</tr>';
    }

    $html .= '</tbody></table>';
    return $html;
}

echo '<div class="container">';
foreach($schemas as $schemaName => $schema){
    echo '<div class="flex-item">';
    echo displaySchema($schemaName, $schema);
    echo '</div>';
}
echo '</div>';
?>


