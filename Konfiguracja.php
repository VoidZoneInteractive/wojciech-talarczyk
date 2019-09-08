<?php

class Konfiguracja
{
    private $nazwa_modulu = null;

    private static $konfiguracja = [
        \Moduly\BazaDanych::class => [
            'host' => 'localhost',
            'uzytkownik' => 'wtalar_feateam', // wtalar_feateam
            'haslo' => '3~$4F^3bP1D&1f+', // 3~$4F^3bP1D&1f+
            'baza_danych' => 'wtalar_feateam', // wtalar_feateam
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