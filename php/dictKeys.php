<?php
if (empty($_GET['l'])) die('No language defined');
$lang = $_GET['l'];

$defs = include 'dictionary-'.$lang.'.php';

$defs = array_change_key_case($defs, CASE_LOWER);

$keys = array_keys($defs);

echo json_encode($keys);
die();