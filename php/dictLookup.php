<?php
$term = $_GET['t'];
$lang = $_GET['l'];

$defs = include 'dictionary-'.$lang.'.php';

$defs = array_change_key_case($defs, CASE_LOWER);
$term = trim(strtolower($term));

if (array_key_exists($term, $defs))
  echo $defs[$term];
else
  echo "Sorry. The term wasn't found.";
die();
