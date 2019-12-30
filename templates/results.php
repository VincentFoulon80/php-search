<?php
if(!($GLOBALS['vfou_admin'] ?? false)) die('unauthorized');
$results = $results ?? [];
$sw = $sw ?? '?';

function displayValue($value){
    if(is_a($value, DateTime::class)){
        return $value->format(DATE_ATOM);
    } elseif (is_array($value)){
        return getFields($value);
    } else {
        return $value;
    }
}

function getFields($fields, $id = 0){
    $title = $fields['title'] ?? $fields['name'] ?? (isset($fields['id']) ? 'Document ID = '.$fields['id'] : '');
    if(!empty($title)) $title = '<caption style="padding:0;"><a title="Edit this document" href="'.$_SERVER['SCRIPT_NAME'].'/edit?id='.$id.'">'.$title.'</a></caption>';
    $html = '<table>'.$title.'<tbody>';
    foreach($fields as $field=>$value){
        $html .= '<tr><td>';
        if(!is_numeric($field)){
            $html .= $field.'</td><td>';
        }
        $html .= displayValue($value);
        $html .= '</td></tr>';
    }
    $html .= '</tbody></table>';
    return $html;
}

?>
<form action="">
    <div>
        <h2>Document Search</h2>
        <div class="container">
            <div class="container-v">
                <label for="q">query</label>
                <input id="q" class="query" type="text" name="q" value="<?php echo $_GET['q'] ?? ''; ?>">
            </div>
            <div class="container-v">
                <label for="limit">limit</label>
                <input id="limit" type="number" name="limit" value="<?php echo $_GET['limit'] ?? 10; ?>">
            </div>
            <div class="container-v">
                <label for="offset">offset</label>
                <input id="offset" type="number" name="offset" value="<?php echo $_GET['offset'] ?? 0; ?>">
            </div>
            <div class="container-v">
                <label for="facets">facets</label>
                <input id="facets" type="text" name="facets" value="<?php echo $_GET['facets'] ?? ''; ?>">
            </div>
            <div>
                <input id="connex" type="checkbox" name="connex" value="1" <?php echo ($_GET['connex'] ?? false) ? 'checked':'' ?>>
                <label for="connex">Enable Connex Search</label>
            </div>
            <div class="container-v">
                <input type="submit" value="Search" style="">
            </div>
        </div>
    </div>
    <div class="container">
        <?php if(!empty($results['facets'])): ?>
        <div class="container-v" style="flex:1">
            <?php if(!empty($results['facets'])): ?>
            <div>
                <h3>Facets</h3>
                <div class="container-v">
                    <?php
                        foreach($results['facets'] as $name=>$values){
                            echo '<div><h4>'.$name.'</h4><div style="margin-top:-20px;" class="container-v">';
                            $count = 0;
                            foreach($values as $value=>$count){
                                echo '<div>';
                                echo "<input type='checkbox' id='facet-$name-$count' name='facet-".$name."[]' value='$value' ".(in_array($value, $_GET['facet-'.$name] ?? []) ? 'checked':'')." onclick='this.form.submit();' />";
                                echo "<label for='facet-$name-$count'>$value ($count)</label>";
                                echo '</div>';
                                $count++;
                            }
                            echo '</div></div>';
                        }
                    ?>
                </div>
            </div>
            <?php endif ?>
        </div>
        <?php endif ?>
        <div style="flex: 3;">
            <p><?php echo $results['numFound'] ?> Documents found in <?php echo $sw ?> ms</p>
            <div class="container">
                <?php
                foreach($results['documents'] as $id=>$result){
                    echo '<div class="flex-item">'.getFields($result, $id).'</div>';
                }
                ?>
            </div>
            <?php if(!empty($results['connex'])): ?>
                <div class="flex-delimiter">
                    <h3>Connex Search</h3>
                </div>
                <div>
                    <div class="container">
                        <?php
                        if(empty($results['connex']['documents'])){
                            echo '<p>Nothing relevant found</p>';
                        } else {
                            foreach($results['connex']['documents'] as $id=>$result){
                                echo '<div class="flex-item">'.getFields($result, $id).'</div>';
                            }
                        }
                        ?>
                    </div>
                </div>
            <?php endif ?>
        </div>
    </div>
</form>
