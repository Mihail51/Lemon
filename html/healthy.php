<?php
$h1_prefix = 'Recipees';
$h1_title  = 'Healthy';

$page_title = $h1_prefix . ' ' . $h1_title; 

$content_file = __DIR__ . '/../content/categories/healthy.php';
include __DIR__ . '/../templates/category.php';
