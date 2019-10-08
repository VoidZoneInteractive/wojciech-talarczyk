<?php

namespace Moduly;

/**
 * Class BazaDanych
 *
 * Ten moduł służy do połączenia się z bazą danych i pobierania oraz zapisywania tam danych o użytkowniku i treningach
 *
 * @package Moduly
 */
class BazaDanych
{
    private static $polaczenie = false;

    /**
     * BazaDanych constructor.
     *
     * Konstruktor klasy - służy do nawiązania połączenia i zapisania tego połączenia w zmiennej $polaczenie
     * To pozwala, żeby za każdym razem jak odwołujemy się do tej klasy nie nawiązywać nowego połączenia tylko
     * wykorzystać to już istniejące
     *
     * @throws \Exception
     */
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
                self::$polaczenie->set_charset('utf8');
            }
        } catch (\Exception $e) {
            throw new \Exception('Nie można było połączyć się z bazą danych. Sprawdź konfigurację danych oraz serwera Mysql.');
        }
    }

    /**
     * @param string $imie
     * @param string $nazwisko
     * @param string $login
     * @param string $haslo
     *
     * Zapisanie danych użytkownika do bazy danych
     */
    public function dodajUzytkownikaDoBazy(string $imie, string $nazwisko, string $login, string $haslo)
    {
        $trescZapytania = 'INSERT INTO uzytkownik (imie, nazwisko, login, haslo) VALUES (?, ?, ?, ?)';
        $parametry = ['ssss', &$imie, &$nazwisko, &$login, &$haslo];

        $this->wykonajZapytanieDoBazy($trescZapytania, $parametry);
    }

    public function aktualizujUzytkownikaWBazie(int $idUzytkownika, string $imie, string $nazwisko, string $login)
    {
        $trescZapytania = 'UPDATE uzytkownik SET imie = ?, nazwisko = ?, login = ? WHERE id = ?;';
        $parametry = ['sssi', &$imie, &$nazwisko, &$login, &$idUzytkownika];

        $this->wykonajZapytanieDoBazy($trescZapytania, $parametry);
    }

    /**
     * @param string $login
     * @return array
     *
     * Pobranie danych użytkownika na podstawie jego loginu
     */
    public function pobierzUzytkownika(string $login)
    {
        $trescZapytania = 'SELECT id, imie, nazwisko, login, haslo, id_trenera, administrator, dietetyk, pracownik FROM uzytkownik WHERE login = ?';

        $parametry = ['s', &$login];

        return $this->wykonajZapytanieDoBazy($trescZapytania, $parametry, true);
    }

    public function pobierzUzytkownikaPoId(int $idUzytkownika)
    {
        $trescZapytania = 'SELECT id, imie, nazwisko, login, haslo, id_trenera, administrator FROM uzytkownik WHERE id = ?';

        $parametry = ['i', &$idUzytkownika];

        return $this->wykonajZapytanieDoBazy($trescZapytania, $parametry, true);
    }

    /**
     * @param int $idUzytkownika
     * @return array
     *
     * Pobranie imienia i nazwiska użytkownika na postawie unikalnego identyfikatora w bazie danych
     */
    public function pobierzNazweUzytkownikaPoId(int $idUzytkownika)
    {
        $trescZapytania = 'SELECT imie, nazwisko FROM uzytkownik WHERE id = ?';

        $parametry = ['i', &$idUzytkownika];

        return $this->wykonajZapytanieDoBazy($trescZapytania, $parametry, true);
    }

    /**
     * @param $idUzytkownika
     * @return array
     *
     * Pobranie danych o kalendarzu uzytkownika na podstawie unikalnego identyfikatora uzytkownika
     */
    public function pobierzKalendarzUzytkownika($idUzytkownika)
    {
        $trescZapytania = 'SELECT trener, dzien, godzina, trening, zapisany FROM kalendarz WHERE uzytkownik = ?';

        $parametry = ['i', &$idUzytkownika];

        return $this->wykonajZapytanieDoBazy($trescZapytania, $parametry, true, false);
    }

    public function pobierzIloscTreningowDlaDanegoPrzedzialu($dzien, $godzina, $trening)
    {
        $trescZapytania = 'SELECT COUNT(*) AS ilosc FROM kalendarz WHERE dzien = ? AND godzina = ? AND trening = ?';

        $parametry = ['sii', &$dzien, &$godzina, &$trening];

        return $this->wykonajZapytanieDoBazy($trescZapytania, $parametry, true, true);
    }

    public function pobierzKalendarzUzytkownikaDietetyk($idUzytkownika)
    {
        $trescZapytania = 'SELECT data FROM dietetyk WHERE uzytkownik = ?';

        $parametry = ['i', &$idUzytkownika];

        return $this->wykonajZapytanieDoBazy($trescZapytania, $parametry, true, false);
    }

    public function pobierzDniDietetyka($idUzytkownika)
    {
        $trescZapytania = 'SELECT data FROM dietetyk WHERE uzytkownik = ? AND data >= NOW()';

        $parametry = ['i', &$idUzytkownika];

        return $this->wykonajZapytanieDoBazy($trescZapytania, $parametry, true, false);
    }

    public function usunDniDietetyka($idUzytkownika)
    {
        $trescZapytania = 'DELETE FROM dietetyk WHERE uzytkownik = ?';

        $parametry = ['i', &$idUzytkownika];

        return $this->wykonajZapytanieDoBazy($trescZapytania, $parametry, false, false);
    }

    public function pobierzDniDietetykaDlaAdministratora()
    {
        $trescZapytania = 'SELECT d.data, u.imie, u.nazwisko FROM dietetyk d JOIN uzytkownik u ON (u.id = d.uzytkownik) WHERE d.data >= NOW() ORDER BY d.data ASC';

        $parametry = null;

        return $this->wykonajZapytanieDoBazy($trescZapytania, $parametry, true, false);
    }

    /**
     * @param $idTrenera
     * @return array
     *
     * Pobranie danych o kalendarzu trenera na podstawie unikalnego identyfikatora trenera
     */
    public function pobierzKalendarzTrenera($idTrenera)
    {
        $trescZapytania = 'SELECT uzytkownik, dzien, godzina, trening, zapisany FROM kalendarz WHERE trener = ?';

        $parametry = ['i', &$idTrenera];

        return $this->wykonajZapytanieDoBazy($trescZapytania, $parametry, true, false);
    }

    /**
     * Pobranie kalendarzy dla trenera - do zapytania dowiązujemy jeszcze imię i nazwisko trenera).
     * @return array
     */
    public function pobierzKalendarzeTrenerow()
    {
        $trescZapytania = 'SELECT k.id, k.uzytkownik, k.dzien, k.godzina, k.trening, k.zapisany, u.imie, u.nazwisko FROM kalendarz k JOIN uzytkownik u ON (k.trener = u.id_trenera)';

        return $this->wykonajZapytanieDoBazy($trescZapytania, null, true, false);
    }

    public function pobierzListeUzytkownikow()
    {
        $trescZapytania = 'SELECT id, imie, nazwisko FROM uzytkownik WHERE id_trenera IS NULL AND administrator = 0';

        $parametry = null;

        return $this->wykonajZapytanieDoBazy($trescZapytania, $parametry, true, false);
    }

    public function pobierzListeTrenerow()
    {
        $trescZapytania = 'SELECT id, imie, nazwisko FROM uzytkownik WHERE id_trenera IS NOT NULL AND administrator = 0';

        $parametry = null;

        return $this->wykonajZapytanieDoBazy($trescZapytania, $parametry, true, false);
    }

    /**
     * Usunięcie użytkownika przy wykorzystaniu jego identyfikatora (id).
     *
     * @param $id
     * @return array
     */
    public function usunUzytkownika($id)
    {
        $trescZapytania = 'DELETE FROM uzytkownik WHERE id = ?';

        $parametry = ['i', &$id];

        return $this->wykonajZapytanieDoBazy($trescZapytania, $parametry);
    }

    /**
     * Usunięcie wszystkich wpisów z kalendarza na podstawie id użytkwonika (używane przy usuwaniu użytkownika, żeby nie było problemów w bazie z niepowiązanymi danymi)
     * @param $id
     * @return array
     */
    public function usunWpisyUzytkownika($id)
    {
        $trescZapytania = 'DELETE FROM kalendarz WHERE uzytkownik = ?';

        $parametry = ['i', &$id];

        return $this->wykonajZapytanieDoBazy($trescZapytania, $parametry);
    }

    /**
     * Usunięcie wpisu z kalendarza na podstawie identyfikatora wpisu (id).
     *
     * @param $id
     * @return array
     */
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

    public function usunWpisyWypisanychUzytkownikow()
    {
        $trescZapytania = 'DELETE FROM kalendarz WHERE zapisany = 0';

        $parametry = null;

        return $this->wykonajZapytanieDoBazy($trescZapytania, $parametry);
    }

    public function dodajWpisDietetyka(string $data, int $idUzytkownika)
    {
        $trescZapytania = 'INSERT INTO dietetyk (data, uzytkownik) VALUES (?, ?)';

        $parametry = ['si', &$data, &$idUzytkownika];

        return $this->wykonajZapytanieDoBazy($trescZapytania, $parametry);
    }

    public function pobierzTrenerowZTreningami()
    {
        $trescZapytania = 'SELECT DISTINCT id, imie, nazwisko, id_trenera FROM uzytkownik LEFT JOIN trening_trener ON (trening_trener.trener = uzytkownik.id_trenera) WHERE id_trenera IS NOT NULL AND administrator = 0 AND trening_trener.trening IS NOT NULL';

        $parametry = null;

        return $this->wykonajZapytanieDoBazy($trescZapytania, $parametry, true, false);
    }

    public function pobierzTreningiDlaGodzinyOrazDnia($idGodziny, $dzien)
    {
        $trescZapytania = 'SELECT trening.nazwa, trening.id AS id_treningu, id_trenera, imie, nazwisko, trening_max FROM trening_trener JOIN trening ON (trening_trener.trening = trening.id) JOIN uzytkownik ON (trening_trener.trener = uzytkownik.id_trenera) WHERE trening_trener.godzina = ? AND trening_trener.dzien = ?';

        $parametry = ['is', &$idGodziny, &$dzien];

        return $this->wykonajZapytanieDoBazy($trescZapytania, $parametry, true, false);
    }

    public function pobierzTreningiTrenera($idGodziny, $idTrenera)
    {
        $trescZapytania = 'SELECT trening.nazwa, trening_trener.dzien, trening.id AS id_treningu FROM trening_trener JOIN trening ON (trening_trener.trening = trening.id) WHERE trening_trener.godzina = ? AND trening_trener.trener = ?';

        $parametry = ['ii', &$idGodziny, &$idTrenera];

        return $this->wykonajZapytanieDoBazy($trescZapytania, $parametry, true, false);
    }

    public function pobierzTreningiTrenerow($idGodziny)
    {
        $trescZapytania = 'SELECT trening.nazwa, trening.id AS id_treningu, trener FROM trening_trener JOIN trening ON (trening_trener.trening = trening.id) WHERE trening_trener.godzina = ?;';

        $parametry = ['i', &$idGodziny];

        return $this->wykonajZapytanieDoBazy($trescZapytania, $parametry, true, false);
    }

    public function pobierzTreningiDlaTrenera($idTrenera)
    {
        $trescZapytania = 'SELECT trening.nazwa, trening.id AS id_treningu, id_trenera, CONCAT(imie, \' \', nazwisko) AS `uzytkownik` FROM kalendarz JOIN trening ON (kalendarz.trening = trening.id) JOIN uzytkownik ON (kalendarz.uzytkownik = uzytkownik.id) AND kalendarz.trener = ?';

        $parametry = ['i', &$idTrenera];

        return $this->wykonajZapytanieDoBazy($trescZapytania, $parametry, true, false);
    }

    public function pobierzKalendarzNaStroneGlowna()
    {

        $trescZapytania = 'SELECT tt.godzina, tt.dzien, CONCAT(u.imie, \' \', u.nazwisko) AS trener, t.nazwa, t.opis, CONCAT(tt.dzien, tt.godzina, t.id) AS identyfikator, u.obrazek FROM trening_trener tt JOIN uzytkownik u ON (tt.trener = u.id_trenera) JOIN trening t ON (tt.trening = t.id) ORDER BY FIELD(tt.dzien, \'poniedzialek\', \'wtorek\', \'sroda\', \'czwartek\', \'piatek\') ASC, tt.godzina ASC;';

        $parametry = null;

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