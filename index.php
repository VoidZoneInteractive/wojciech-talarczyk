<?php

error_reporting(E_ALL);
ini_set('display_errors', 'On');

require 'autoloader.php';

try {
    $nazwaStrony = !empty($_GET['strona']) ? $_GET['strona'] : 'stronaGlowna';
    $kontroler = new \Moduly\Kontroler($nazwaStrony);
    echo $kontroler->akcja();
} catch (\Exception $e) {
    echo $e->getMessage();
}