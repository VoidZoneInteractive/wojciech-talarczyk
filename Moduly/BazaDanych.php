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
                self::$polaczenie = new \mysqli($host, $uzytkownik, $haslo, $baza_danych);
            }
        } catch (\Exception $e) {
            throw new \Exception('Nie można było połączyć się z bazą danych. Sprawdź konfigurację danych oraz serwera Mysql.');
        }
    }

    public function dodajUzytkownikaDoBazy(string $imie, string $nazwisko, string $login, string $haslo)
    {
        $trescZapytania = 'INSERT INTO uzytkownik (imie, nazwisko, login, haslo) VALUES (?, ?, ?, ?)';
        $parametry = ['ssss', &$imie, &$nazwisko, &$login, &$haslo];

        $this->wykonajZapytanieDoBazy($trescZapytania, $parametry);
    }

    public function pobierzUzytkownika(string $login)
    {
        $trescZapytania = 'SELECT id, imie, nazwisko, login, haslo, id_trenera, administrator FROM uzytkownik WHERE login = ?';

        $parametry = ['s', &$login];

        return $this->wykonajZapytanieDoBazy($trescZapytania, $parametry, true);
    }

    public function pobierzNazweUzytkownikaPoId(int $idUzytkownika)
    {
        $trescZapytania = 'SELECT imie, nazwisko FROM uzytkownik WHERE id = ?';

        $parametry = ['i', &$idUzytkownika];

        return $this->wykonajZapytanieDoBazy($trescZapytania, $parametry, true);
    }

    public function pobierzKalendarzUzytkownika($idUzytkownika)
    {
        $trescZapytania = 'SELECT trener, dzien, godzina, trening, zapisany FROM kalendarz WHERE uzytkownik = ?';

        $parametry = ['i', &$idUzytkownika];

        return $this->wykonajZapytanieDoBazy($trescZapytania, $parametry, true, false);
    }

    public function pobierzKalendarzTrenera($idTrenera)
    {
        $trescZapytania = 'SELECT uzytkownik, dzien, godzina, trening, zapisany FROM kalendarz WHERE trener = ?';

        $parametry = ['i', &$idTrenera];

        return $this->wykonajZapytanieDoBazy($trescZapytania, $parametry, true, false);
    }

    public function pobierzKalendarzeTrenerow()
    {
        $trescZapytania = 'SELECT k.id, k.uzytkownik, k.dzien, k.zapisany, u.imie, u.nazwisko FROM kalendarz k JOIN uzytkownik u ON (k.trener = u.id_trenera)';

        return $this->wykonajZapytanieDoBazy($trescZapytania, null, true, false);
    }

    public function pobierzListeUzytkownikow()
    {
        $trescZapytania = 'SELECT id, imie, nazwisko FROM uzytkownik WHERE id_trenera IS NULL AND administrator = 0';

        $parametry = null;

        return $this->wykonajZapytanieDoBazy($trescZapytania, $parametry, true, false);
    }

    public function usunUzytkownika($id)
    {
        $trescZapytania = 'DELETE FROM uzytkownik WHERE id = ?';

        $parametry = ['i', &$id];

        return $this->wykonajZapytanieDoBazy($trescZapytania, $parametry);
    }

    public function usunWpisyUzytkownika($id)
    {
        $trescZapytania = 'DELETE FROM kalendarz WHERE uzytkownik = ?';

        $parametry = ['i', &$id];

        return $this->wykonajZapytanieDoBazy($trescZapytania, $parametry);
    }

    public function usunWpisKalendarza($id)
    {
        $trescZapytania = 'DELETE FROM kalendarz WHERE id = ?';

        $parametry = ['i', &$id];

        return $this->wykonajZapytanieDoBazy($trescZapytania, $parametry);
    }

    public function aktualizujKalendarz(int $trener, string $dzien, int $godzina, int $trening, int $zapisany, int $idUzytkownika)
    {
        $trescZapytania = 'REPLACE INTO kalendarz (trener, dzien, godzina, trening, zapisany, uzytkownik) VALUES (?, ?, ?, ?, ?, ?)';

        $parametry = ['isiiii', &$trener, &$dzien, &$godzina, &$trening, &$zapisany, &$idUzytkownika];

        return $this->wykonajZapytanieDoBazy($trescZapytania, $parametry);
    }

    public function pobierzTrenerowZTreningami()
    {
        $trescZapytania = 'SELECT DISTINCT id, imie, nazwisko, id_trenera FROM uzytkownik LEFT JOIN trening_trener ON (trening_trener.trener = uzytkownik.id_trenera) WHERE id_trenera IS NOT NULL AND administrator = 0 AND trening_trener.trening IS NOT NULL';

        $parametry = null;

        return $this->wykonajZapytanieDoBazy($trescZapytania, $parametry, true, false);
    }

    public function pobierzTreningiDlaGodziny($idGodziny)
    {
        $trescZapytania = 'SELECT trening.nazwa, trening.id AS id_treningu, id_trenera, imie, nazwisko FROM trening_trener JOIN trening ON (trening_trener.trening = trening.id) JOIN uzytkownik ON (trening_trener.trener = uzytkownik.id_trenera) WHERE trening_trener.godzina = ?';

        $parametry = ['i', &$idGodziny];

        return $this->wykonajZapytanieDoBazy($trescZapytania, $parametry, true, false);
    }

    public function pobierzTreningiTrenera($idGodziny, $idTrenera)
    {
        $trescZapytania = 'SELECT trening.nazwa, trening.id AS id_treningu FROM trening_trener JOIN trening ON (trening_trener.trening = trening.id) WHERE trening_trener.godzina = ? AND trening_trener.trener = ?';

        $parametry = ['ii', &$idGodziny, &$idTrenera];

        return $this->wykonajZapytanieDoBazy($trescZapytania, $parametry, true, false);
    }

    public function pobierzTreningiDlaTrenera($idTrenera)
    {
        $trescZapytania = 'SELECT trening.nazwa, trening.id AS id_treningu, id_trenera, CONCAT(imie, \' \', nazwisko) AS `uzytkownik` FROM kalendarz JOIN trening ON (kalendarz.trening = trening.id) JOIN uzytkownik ON (kalendarz.uzytkownik = uzytkownik.id) AND kalendarz.trener = ?';

        $parametry = ['i', &$idTrenera];

        return $this->wykonajZapytanieDoBazy($trescZapytania, $parametry, true, false);
    }

    private function wykonajZapytanieDoBazy(
        string $trescZapytania,
        ?array $parametry = null,
        bool $zwrocRezultat = false,
        bool $pojedynczyRezultat = true
    )
    {
        $zapytanie = self::$polaczenie->prepare($trescZapytania);

        if (!is_null($parametry)) {
            call_user_func_array([$zapytanie, 'bind_param'], $parametry);
        }

        $zapytanie->execute();

        if ($zwrocRezultat) {

            $wynikZapytania = $zapytanie->get_result();

            if ($pojedynczyRezultat) {
                return $wynikZapytania->fetch_assoc();
            }

            $rezultat = [];

            while ($wiersz = $wynikZapytania->fetch_assoc()) {
                $rezultat[] = $wiersz;
            }

            return $rezultat;
        }
    }
}