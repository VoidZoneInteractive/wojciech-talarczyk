<?php

namespace Moduly;

class Kalendarz
{
    const DNI = ['poniedzialek', 'wtorek', 'sroda', 'czwartek', 'piatek'];
    const ID_TRENEROW = [1, 2];

    private $bazaDanych;
    private $uzytkownik;

    public function __construct()
    {
        if(session_status() == PHP_SESSION_NONE) {
            session_start();
        }

        $this->bazaDanych = new BazaDanych();
        $this->uzytkownik = new Uzytkownik();
    }

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

    public function przygotujListeUzytkownikow(array $uzytkownicy)
    {
        return '<ul><li>' . implode('</li><li>', $uzytkownicy) . '</li></ul>';
    }

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

    public function zapiszDane(array $dane)
    {
        $dane = $this->przetworzDane($dane);
        $idUzytkownika = $this->uzytkownik->zwrocUzytkownika()['id'];

        $this->bazaDanych->aktualizujKalendarz($dane['trener'], $dane['dzien'], (int)$dane['zapisany'], $idUzytkownika);
    }

    private function przetworzDane(array $dane)
    {
        $trener = key($dane);
        $dzien = key($dane[$trener]);
        $zapisany = $dane[$trener][$dzien];

        return ['trener' => $trener, 'dzien' => $dzien, 'zapisany' => $zapisany];
    }
}