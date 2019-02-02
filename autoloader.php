<?php

/**
 * Autoloader to metoda która służy do automatycznego ładowania pliku z klasą kiedy skrypt zażąda danej klasy
 * W tym przypadku jeżeli zażądamy klasy 'BazaDanych' to skrypt sprawdzi czy istnieje plik w katalogu Moduły o nazwie
 * BazaDanych.php i jeżeli istnieje to dołączy go do skryptu. Jeżeli nie to wyrzuci błąd.
 */

spl_autoload_register(function ($klasa) {
    if (file_exists(str_replace('\\', '/', $klasa) . '.php')) {

        require_once str_replace('\\', '/', $klasa) . '.php';
    } else {
        throw new \Exception(sprintf('Nie znaleziono modulu %s', $klasa));
    }
});