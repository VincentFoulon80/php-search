<?php
if(!($GLOBALS['vfou_admin'] ?? false)) die('unauthorized');
$document = $document ?? [];
$errors = $errors ?? [];
$found = true;
if(empty($document)){
    $found = false;
    $document = [
        'id' => 0,
        'type' => 'example-post'
    ];
}
?>
<div>
    <h2>Document Edition</h2>
    <form action="" class="container">
        <div class="container-v">
            <label for="id">id</label>
            <input id="id" type="text" name="id" value="<?php echo $_GET['id'] ?? ''; ?>">
        </div>
        <div class="container-v">
            <input type="submit" value="Find Document" style="">
        </div>
    </form>
    <?php if($found): ?>
    <form action="" method="post" class="">
        <input type="hidden" name="delete" value="<?php echo $_GET['id']; ?>">
        <input type="submit" class="delete" value="Delete this document">
    </form>
    <?php endif ?>
</div>
<?php
    if(!empty($errors)){
        echo '<div class="container-v">';
        foreach($errors as $error){
            echo '<div class="alert-error">'.$error.'</div>';
        }
        echo '</div>';
    }
?>
<div>
    <form action="" method="post" class="container-v">

        <label for="document-content">Document</label>
        <textarea name="content" id="document-content" cols="30" rows="30"><?php echo (!empty($document) ? json_encode($document, JSON_PRETTY_PRINT): '') ?></textarea>
        <input type="submit" value="Send">
    </form>
</div>
