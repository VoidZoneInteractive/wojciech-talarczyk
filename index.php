<?php

/**
 * Wejściowy skrypt dla każdego wywołania strony
 * Najpierw inicjuje autoloader aby automatycznie rejestrować żadane przez skrypt klasy a potem inicjuje
 * Kontroler który wykonuje akcje.
 * W przypadku przechwyconego błędu wyświetla go.
 */

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