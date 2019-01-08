<?php

spl_autoload_register(function ($klasa) {
    if (file_exists(str_replace('\\', '/', $klasa) . '.php')) {

        require_once str_replace('\\', '/', $klasa) . '.php';
    } else {
        throw new \Exception(sprintf('Nie znaleziono modulu %s', $klasa));
    }
});