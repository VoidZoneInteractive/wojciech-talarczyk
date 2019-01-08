<?php

class Konfiguracja
{
    private $nazwa_modulu = null;

    private static $konfiguracja = [
        \Moduly\BazaDanych::class => [
            'host' => 'localhost',
            'uzytkownik' => 'root',
            'haslo' => 'root',
            'baza_danych' => 'wojtek_talarczyk',
        ],
    ];

    public function __construct($nazwa_modulu)
    {
        $this->nazwa_modulu = $nazwa_modulu;

        return $this;
    }

    public function zwrocDaneKonfiguracyjne() {
        if (!empty(self::$konfiguracja[$this->nazwa_modulu])) {
            return self::$konfiguracja[$this->nazwa_modulu];
        }

        throw new \Exception('Nie znaleziono konfiguracji dla podanego modulu.');
    }
}