<?php

namespace Moduly;

/**
 * Class Kalendarz
 *
 * @package Moduly
 *
 * Ten moduł służy do zarządzania kalendarzem, czyli tworzenie kalendarza użytkownika i trenera
 * oraz zarządzaniem zapisów na konkretne dni.
 */
class Kalendarz
{
    const DNI = ['poniedzialek', 'wtorek', 'sroda', 'czwartek', 'piatek'];
    const ID_TRENEROW = [1, 2];

    private $bazaDanych;
    private $uzytkownik;
    private $szablon;

    /**
     * Kalendarz constructor.
     *
     * @throws \Exception
     *
     * Konstruktor klasy. Do jego zadań należy ustanowienie sesji (jeżeli ta jeszcze nie została zainicjowana),
     * nawiązanie połączenia z bazą danych i zapisanie uchwytu do zmiennej $bazaDanych oraz inicjalizacja klasy
     * Uzytkownik - ktora daje dostęp do funkcji zarządzania zalogowanym użytkownikiem.
     */
    public function __construct()
    {
        if(session_status() == PHP_SESSION_NONE) {
            session_start();
        }

        $this->bazaDanych = new BazaDanych();
        $this->uzytkownik = new Uzytkownik();
        $this->szablon = new Szablon();
    }

    /**
     * @return array
     *
     * Funkcja przygotowuje zmienne, które są później używane przy budowaniu szablonu HTML kalendarza zalogowanego użytkownika
     */
    public function przygotujParametrySzablonuUzytkownika()
    {
        $daneKalendarza = $this->bazaDanych->pobierzKalendarzUzytkownika($this->uzytkownik->zwrocUzytkownika()['id']);
        $daneKalendarza = $this->przetworzKalendarzUzytkownika($daneKalendarza);

        $parametry = [];

        foreach (self::DNI as $dzien) {
            foreach (self::ID_TRENEROW as $trener) {
                $wartoscKlucz = sprintf('%%{wartosc-trener_%d-%s}', $trener, $dzien);
                $przyciskKlucz = sprintf('%%{przycisk-trener_%d-%s}', $trener, $dzien);
                $nowaWartosc = isset($daneKalendarza[$trener][$dzien]) ? (int)!$daneKalendarza[$trener][$dzien] : 1;

                $parametry[$wartoscKlucz] = $nowaWartosc;
                $parametry[$przyciskKlucz] = $nowaWartosc ? 'Zapisz się' : 'Wypisz się';
            }
        }

        return $parametry;
    }

    /**
     * @return array
     *
     * Podobnie jak funkcja powyżej, lecz dla zalogowanego trenera
     */
    public function przygotujParametrySzablonuTrenera()
    {
        $daneKalendarza = $this->bazaDanych->pobierzKalendarzTrenera($this->uzytkownik->zwrocUzytkownika()['id_trenera']);
        $daneKalendarza = $this->przetworzKalendarzTrenera($daneKalendarza);

        $parametry = [];

        foreach (self::DNI as $dzien) {
            $klucz = sprintf('%%{uzytkownicy-%s}', $dzien);

            $parametry[$klucz] = !empty($daneKalendarza[$dzien]) ? $this->przygotujListeUzytkownikow($daneKalendarza[$dzien]) : 'Brak zapisanych.';
        }

        return $parametry;
    }

    /**
     * Przygotowanie kalendarza wyświetlanego w panelu administratora.
     * @return string
     */
    public function przygotujParametrySzablonuAdministratora()
    {
        $daneKalendarzy = $this->bazaDanych->pobierzKalendarzeTrenerow();
        $daneKalendarzy = $this->przetworzKalendarze($daneKalendarzy);

        $wpisy = '';

        foreach ($daneKalendarzy as $trener => $daneKalendarza) {
            $parametry = ['%{trener}' => $trener];

            foreach (self::DNI as $dzien) {
                $klucz = sprintf('%%{uzytkownicy-%s}', $dzien);
                $parametry[$klucz] = !empty($daneKalendarza[$dzien]) ? $this->przygotujListeUzytkownikowKalendarzaAdministratora($daneKalendarza[$dzien]) : 'Brak zapisanych.';
            }

            $wpisy .= $this->szablon->zwrocSzablon('panelAdministratora-wpis', $parametry);
        }

        return $wpisy;
    }

    /**
     * @param array $uzytkownicy
     * @return string
     *
     * Funkcja przygotowuje listę użytkowników zapisanych dla konkretnego trenera
     */
    public function przygotujListeUzytkownikow(array $uzytkownicy)
    {
        return '<ul><li>' . implode('</li><li>', $uzytkownicy) . '</li></ul>';
    }

    /**
     * Przygotowanie listy kalendarzy trenerów wraz z użytkownikami wyświetlanej w panelu administratora (gdzie można je usunąć)
     *
     * @param array $uzytkownicy
     * @return string
     */
    public function przygotujListeUzytkownikowKalendarzaAdministratora(array $uzytkownicy)
    {
        foreach ($uzytkownicy as &$uzytkownik) {
            $uzytkownik = $uzytkownik['dane'] . ' <a href=" /?strona=usunWpisKalendarza&id='.$uzytkownik['id_kalendarza'].'" onclick="if(!confirm(\'Czy napewno usunąć wpis kalendarza użytkownika?\')) { return false; }">usuń</a>';
        }
        return '<ul><li>' . implode('</li><li>', $uzytkownicy) . '</li></ul>';
    }

    /**
     * Przygotowanie listy użytkowników wyświetlanej w panelu administratora (gdzie można ich usunąć)
     * @param array $uzytkownicy
     * @return string
     */
    public function przygotujListeUzytkownikowAdministratora(array $uzytkownicy)
    {
        foreach ($uzytkownicy as &$uzytkownik) {
            $uzytkownik = $uzytkownik['dane'] . ' <a href=" /?strona=usunUzytkownika&id='.$uzytkownik['id'].'" onclick="if(!confirm(\'Czy napewno usunąć użytkownika: '.$uzytkownik['dane'].'?\')) { return false; }">usuń</a>';
        }
        return '<ul><li>' . implode('</li><li>', $uzytkownicy) . '</li></ul>';
    }

    /**
     * Wywołanie funkcji bazodanowej usuwającej wpis kalendarza w panelu administracyjnym.
     * @param $id
     */
    public function usunWpisKalendarza($id) {
        $this->bazaDanych->usunWpisKalendarza($id);
    }

    /**
     * @param array $daneKalendarza
     * @return array
     *
     * Funkcja przygotowuje dane dla kalendarza użykownika które później są używane przy budowaniu zmiennych do szablonu
     */
    public function przetworzKalendarzUzytkownika(array $daneKalendarza)
    {
        $rezultat = [];

        foreach ($daneKalendarza as $wpisKalendarza) {
            if (empty($rezultat[$wpisKalendarza['trener']])) {
                $rezultat[$wpisKalendarza['trener']] = [];
            }

            $rezultat[$wpisKalendarza['trener']][$wpisKalendarza['dzien']] = $wpisKalendarza['zapisany'];
        }

        return $rezultat;
    }

    /**
     * @param array $daneKalendarza
     * @return array
     *
     * Podobnie jak powyższa funkcja, tylko dla kalendarza trenera
     */
    public function przetworzKalendarzTrenera(array $daneKalendarza)
    {
        $rezultat = [];

        foreach ($daneKalendarza as $wpisKalendarza) {
            if (empty($rezultat[$wpisKalendarza['dzien']])) {
                $rezultat[$wpisKalendarza['dzien']] = [];
            }

            if (!empty($wpisKalendarza['zapisany'])) {
                $uzytkownik = $this->bazaDanych->pobierzNazweUzytkownikaPoId($wpisKalendarza['uzytkownik']);
                $rezultat[$wpisKalendarza['dzien']][] = sprintf('%s %s', $uzytkownik['imie'], $uzytkownik['nazwisko']);
            }
        }

        return $rezultat;
    }

    /**
     * Sformatowanie danych kalendarzy trenerów wykorzystywane w panelu administratora.
     * @param array $daneKalendarzy
     * @return array
     */
    public function przetworzKalendarze(array $daneKalendarzy)
    {
        $rezultat = [];

        foreach ($daneKalendarzy as $wpisKalendarza) {
            $trener = sprintf('%s %s', $wpisKalendarza['imie'], $wpisKalendarza['nazwisko']);
            if (empty($rezultat[$trener])) {
                $rezultat[$trener] = [];
            }

            if (empty($rezultat[$trener][$wpisKalendarza['dzien']])) {
                $rezultat[$trener][$wpisKalendarza['dzien']] = [];
            }

            if (!empty($wpisKalendarza['zapisany'])) {
                $uzytkownik = $this->bazaDanych->pobierzNazweUzytkownikaPoId($wpisKalendarza['uzytkownik']);
                $rezultat[$trener][$wpisKalendarza['dzien']][] = ['dane' => sprintf('%s %s', $uzytkownik['imie'], $uzytkownik['nazwisko']), 'id_kalendarza' => $wpisKalendarza['id']];
            }
        }

        return $rezultat;
    }

    /**
     * Metoda wywołująca funkcję bazodanową usuwającą wpisy użytkownika.
     * @param $id
     */
    public function usunWpisyUzytkownika($id)
    {
        $this->bazaDanych->usunWpisyUzytkownika($id);
    }

    /**
     * @param array $dane
     *
     * Zapisuje kalendarz użytkownika/trenera
     */
    public function zapiszDane(array $dane)
    {
        $dane = $this->przetworzDane($dane);
        $idUzytkownika = $this->uzytkownik->zwrocUzytkownika()['id'];

        $this->bazaDanych->aktualizujKalendarz($dane['trener'], $dane['dzien'], (int)$dane['zapisany'], $idUzytkownika);
    }

    /**
     * @param array $dane
     * @return array
     *
     * Przetwarza dane pochodzące z bazy danych
     */
    private function przetworzDane(array $dane)
    {
        $trener = key($dane);
        $dzien = key($dane[$trener]);
        $zapisany = $dane[$trener][$dzien];

        return ['trener' => $trener, 'dzien' => $dzien, 'zapisany' => $zapisany];
    }
}