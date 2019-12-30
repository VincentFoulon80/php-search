<?php
if (!($GLOBALS['vfou_admin'] ?? false)) die('unauthorized');
$types = $types ?? [];
$debugTokens = $debugTokens ?? [];
?>
<form action="">
    <h2>Type debugging</h2>
    <div class="container">
        <div class="container-v">
            <div>
                <label for="type">Type</label>
                <select name="type" id="type" onchange="this.form.submit()">
                    <?php
                    foreach ($types as $type => $tokenizers) {
                        echo "<option value='$type' ".($_GET['type'] == $type ? 'selected="true"':'').">$type</option>";
                    }
                    ?>
                </select>
            </div>
            <div style="flex:1;margin-top:15px;">
                <table>
                    <caption>Tokenizers</caption>
                    <tbody>
                    <?php
                    foreach ($types[$_GET['type']] as $tokenizer) {
                        echo "<tr><td>$tokenizer</td></tr>";
                    }
                    ?>
                    </tbody>
                </table>
            </div>
        </div>
        <div style="flex:3;" class="container-v">
            <div>
                <div class="container-v">
                    <label for="text">String to tokenize</label>
                    <input id="text" type="text" class="query" name="text" value="<?php echo $_GET['text'] ?? '' ?>">
                </div>
                <input type="submit" value="Tokenize">
            </div>
            <div>
                <p style="margin-top:10px;">Tokens generated :</p>
                <?php
                if (!empty($debugTokens)) {
                    echo '<table><tbody>';
                    foreach ($debugTokens as $token) {
                        echo "<tr><td>$token</td></tr>";
                    }
                    echo '</tbody></table>';
                }
                ?>
            </div>
        </div>
    </div>
</form>

