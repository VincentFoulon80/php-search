<?php
if(!($GLOBALS['vfou_admin'] ?? false)) die('unauthorized');
$uri = $uri ?? '';
?>
<html>
<head>
    <meta charset="UTF-8">
    <meta name="viewport"
          content="width=device-width, user-scalable=no, initial-scale=1.0, maximum-scale=1.0, minimum-scale=1.0">
    <meta http-equiv="X-UA-Compatible" content="ie=edge">
    <title>vfou/php-search Admin panel</title>
    <style>
        <?php include('style.css') ?>
    </style>
</head>
<body>
<div class="container-v">
    <nav>
        <div>
            <h1>vfou/php-search Admin panel</h1>
        </div>
        <div class="container">
            <a href="<?php echo $GLOBALS['vfou_baseurl'] ?>/" <?php echo ($uri == '/' ? 'class="active"':'') ?>>Statistics</a>
            <a href="<?php echo $GLOBALS['vfou_baseurl'] ?>/query" <?php echo ($uri == '/query' ? 'class="active"':'') ?>>Query</a>
            <a href="<?php echo $GLOBALS['vfou_baseurl'] ?>/edit" <?php echo ($uri == '/edit' ? 'class="active"':'') ?>>Edit Documents</a>
            <a href="<?php echo $GLOBALS['vfou_baseurl'] ?>/types" <?php echo ($uri == '/types' ? 'class="active"':'') ?>>Types</a>
            <a href="<?php echo $GLOBALS['vfou_baseurl'] ?>/schemas" <?php echo ($uri == '/schemas' ? 'class="active"':'') ?>>Schemas</a>
        </div>
    </nav>


