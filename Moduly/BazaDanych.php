<?php

namespace Moduly;

class BazaDanych
{
    private static $polaczenie = false;

    public function __construct()
    {
        $konfiguracja = (new \Konfiguracja(__CLASS__))->zwrocDaneKonfiguracyjne();

        $host = $konfiguracja['host'] ?? ini_get('mysql.default_host');
        $uzytkownik = $konfiguracja['uzytkownik'] ?? ini_get('mysql.default_user');
        $haslo = $konfiguracja['haslo'] ?? ini_get('mysql.default_password');
        $baza_danych = $konfiguracja['baza_danych'];

        try {
            if (self::$polaczenie === false) {
                self::$polaczenie = mysqli_connect($host, $uzytkownik, $haslo);
                self::$polaczenie->select_db($baza_danych);
            }
        } catch (\Exception $e) {
            throw new \Exception('Nie można było połączyć się z bazą danych. Sprawdź konfigurację danych oraz serwera Mysql.');
        }
    }

    public function dodajUzytkownikaDoBazy($login, $haslo)
    {
        $trescZapytania = 'INSERT INTO uzytkownik (login, haslo) VALUES (?, ?)';
        $parametry = ['ss', &$login, &$haslo];

        $this->wykonajZapytanieDoBazy($trescZapytania, $parametry);
    }

    private function wykonajZapytanieDoBazy($trescZapytania, $parametry)
    {
        $zapytanie = self::$polaczenie->prepare($trescZapytania);

        call_user_func_array([$zapytanie, 'bind_param'], $parametry);

        $zapytanie->execute();
    }
}