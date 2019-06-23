<?php

namespace Moduly;

class Kalendarz
{
    const DNI = ['poniedziałek', 'wtorek', 'środa', 'czwartek', 'piątek'];
    const GODZINY = [
        1 => '08:00 - 09:30',
        2 => '10:00 - 11:30',
        3 => '11:45 - 13:15',
        4 => '13:30 - 15:00',
        5 => '16:15 - 17:45',
        6 => '18:00 - 19:30',
        7 => '20:00 - 21:30',
    ];
    const ID_TRENEROW = [1, 2];

    private $bazaDanych;
    private $uzytkownik;
    private $szablon;

    public function __construct()
    {
        if(session_status() == PHP_SESSION_NONE) {
            session_start();
        }

        $this->bazaDanych = new BazaDanych();
        $this->uzytkownik = new Uzytkownik();
        $this->szablon = new Szablon();
    }

    public function przygotujParametrySzablonuUzytkownika($dzien)
    {
        $daneKalendarza = $this->bazaDanych->pobierzKalendarzUzytkownika($this->uzytkownik->zwrocUzytkownika()['id']);
        $daneKalendarza = $this->przetworzKalendarzUzytkownika($daneKalendarza);

        $parametry = [];
        $parametry['%{wpisy}'] = $this->przygotujWpisyTrenerowDlaUzytkownika($dzien, $daneKalendarza);

        $parametry['%{dni}'] = $this->przygotujListeDni('panelUzytkownika', $dzien);

        return $parametry;
    }

    public function przygotujParametrySzablonuTrenera()
    {
        $idTrenera = $this->uzytkownik->zwrocUzytkownika()['id_trenera'];
        $daneKalendarza = $this->bazaDanych->pobierzKalendarzTrenera($idTrenera);
        $daneKalendarza = $this->przetworzKalendarzTrenera($daneKalendarza);

        $parametry = [];

        $parametry['%{wpisy}'] = $this->przygotujWypisyUzytkownikowDlaTrenera($daneKalendarza, $idTrenera);

//        foreach (self::GODZINY as $godzina) {
//            foreach (self::GODZINY as $godzina) {
//
//            }
//            $klucz = sprintf('%%{uzytkownicy-%s}', $godzina);
//
//            $parametry[$klucz] = !empty($daneKalendarza[$dzien]) ? $this->przygotujListeUzytkownikow($daneKalendarza[$dzien]) : 'Brak zapisanych.';
//        }

        return $parametry;
    }

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

    public function przygotujListeUzytkownikow(array $uzytkownicy)
    {
        return '<ul><li>' . implode('</li><li>', $uzytkownicy) . '</li></ul>';
    }

    public function przygotujListeUzytkownikowKalendarzaAdministratora(array $uzytkownicy)
    {
        foreach ($uzytkownicy as &$uzytkownik) {
            $uzytkownik = $uzytkownik['dane'] . ' <a href=" /?strona=usunWpisKalendarza&id='.$uzytkownik['id_kalendarza'].'" onclick="if(!confirm(\'Czy napewno usunąć wpis kalendarza użytkownika?\')) { return false; }">usuń</a>';
        }
        return '<ul><li>' . implode('</li><li>', $uzytkownicy) . '</li></ul>';
    }

    public function przygotujListeUzytkownikowAdministratora(array $uzytkownicy)
    {
        foreach ($uzytkownicy as &$uzytkownik) {
            $uzytkownik = $uzytkownik['dane'] . ' <a href=" /?strona=usunUzytkownika&id='.$uzytkownik['id'].'" onclick="if(!confirm(\'Czy napewno usunąć użytkownika: '.$uzytkownik['dane'].'?\')) { return false; }">usuń</a>';
        }
        return '<ul><li>' . implode('</li><li>', $uzytkownicy) . '</li></ul>';
    }

    public function usunWpisKalendarza($id) {
        $this->bazaDanych->usunWpisKalendarza($id);
    }

    public function przetworzKalendarzUzytkownika(array $daneKalendarza)
    {
        $rezultat = [];

        foreach ($daneKalendarza as $wpisKalendarza) {
            if (empty($rezultat[$wpisKalendarza['trener']])) {
                $rezultat[$wpisKalendarza['trener']] = [];
            }

            $rezultat[$wpisKalendarza['trener']][$wpisKalendarza['dzien']][$wpisKalendarza['godzina']][$wpisKalendarza['trening']] = $wpisKalendarza['zapisany'];
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
                $rezultat[$wpisKalendarza['dzien']][$wpisKalendarza['godzina']][$wpisKalendarza['trening']][] = sprintf('%s %s', $uzytkownik['imie'], $uzytkownik['nazwisko']);
            }
        }

        return $rezultat;
    }

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

    public function usunWpisyUzytkownika($id)
    {
        $this->bazaDanych->usunWpisyUzytkownika($id);
    }

    public function zapiszDane(array $dane)
    {
        $dane = $this->przetworzDane($dane);
        $idUzytkownika = $this->uzytkownik->zwrocUzytkownika()['id'];

        $this->bazaDanych->aktualizujKalendarz($dane['trener'], $dane['dzien'], (int)$dane['godzina'], (int)$dane['trening'], (int)$dane['zapisany'], $idUzytkownika);
    }

    private function przygotujListeDni($strona, $aktualny_dzien)
    {
        $wynik = [];

        foreach (self::DNI as $dzien) {
            if ($dzien == $aktualny_dzien) {
                $wynik[] = $dzien;
            } else {
                $wynik[] = sprintf('<a href="?strona=%s&dzien=%s">%s</a>', $strona, $dzien, $dzien);
            }
        }

        return implode(' | ', $wynik);
    }

    private function przygotujWpisyTrenerowDlaUzytkownika($dzien, $daneKalendarza)
    {
        $rzedy = [];

        foreach (self::GODZINY as $idGodziny => $godzina) {
            $treningi = $this->bazaDanych->pobierzTreningiDlaGodziny($idGodziny);

            $rzadParametry = [
                '%{godzina}' => '<td rowspan="' . count($treningi) . '">' . $godzina . '</td>',
            ];

            $licznik = 0;

            foreach ($treningi as $trening) {

                $idTrenera = $trening['id_trenera'];
                $imieTrenera = $trening['imie'];
                $nazwiskoTrenera = $trening['nazwisko'];

                $rzadParametry['%{trening}'] = '<td>' . $trening['nazwa'] . '</td>';
                $rzadParametry['%{trener}'] = '<td>' . sprintf('%s %s', $imieTrenera, $nazwiskoTrenera) . '</td>';
                $zapisy = [];

                $zapisany = isset($daneKalendarza[$idTrenera][$dzien][$idGodziny][$trening['id_treningu']]) ? (int)!$daneKalendarza[$idTrenera][$dzien][$idGodziny][$trening['id_treningu']] : 1;

                $parametryLinii = [
                    '%{id}' => $idTrenera,
                    '%{trener}' => sprintf('%s %s', $imieTrenera, $nazwiskoTrenera),
                    '%{dzien}' => $dzien,
                    '%{godzina}' => $idGodziny,
                    '%{id_treningu}' => $trening['id_treningu'],
                    '%{przycisk}' => $zapisany ? 'Zapisz się' : 'Wypisz się',
                    '%{wartosc}' => $zapisany,
                ];

                $zapisy[] = $this->szablon->zwrocSzablon('panelUzytkownika-linia', $parametryLinii);


                $rzadParametry['%{zapisy}'] = implode('', $zapisy);

                $licznik++;

                $rzedy[] = $this->szablon->zwrocSzablon('panelUzytkownika-rzad', $rzadParametry);

                unset($rzadParametry['%{godzina}']);
            }

            if (!isset($godzinaDoPokazania)) {
                $godzinaDoPokazania = $godzina;
            }

            $licznik = 0;
        }

        return implode('', $rzedy);
    }

    private function przygotujWypisyUzytkownikowDlaTrenera($daneKalendarza, $idTrenera) {
        $rzedy = [];

        foreach (self::GODZINY as $idGodziny => $godzina) {

            $treningi = $this->bazaDanych->pobierzTreningiTrenera($idGodziny, $idTrenera);

            $rzadParametry = [
                '%{godzina}' => '<td rowspan="'.count($treningi).'">' . $godzina . '</td>',
            ];

            $licznik = 0;

            foreach ($treningi as $trening) {
                $rzadParametry['%{trening}'] = '<td>' . $trening['nazwa'] . '</td>';
                $zapisy = [];

                $parametryLinii = [];

                foreach (self::DNI AS $dzien)
                {
                    if (empty($daneKalendarza[$dzien][$idGodziny])) {
                        $parametryLinii['%{' . $dzien . '}'] = 'Brak Zapisow';
                    } else {
                        $parametryLinii['%{' . $dzien . '}'] = $this->przygotujListeUzytkownikow($daneKalendarza[$dzien][$idGodziny][$trening['id_treningu']]);
                    }
                }

                $rzadParametry['%{zapisy}'] = $this->szablon->zwrocSzablon('panelTrenera-linia', $parametryLinii);

                $licznik++;

                $rzedy[] = $this->szablon->zwrocSzablon('panelTrenera-rzad', $rzadParametry);

                unset($rzadParametry['%{godzina}']);
            }

            if (!isset($godzinaDoPokazania)) {
                $godzinaDoPokazania = $godzina;
            }

            $licznik = 0;
        }

        return implode('',$rzedy);
    }

    private function przetworzDane(array $dane)
    {
        $trener = key($dane);
        $dzien = key($dane[$trener]);
        $godzina = key($dane[$trener][$dzien]);
        $trening = key($dane[$trener][$dzien][$godzina]);
        $zapisany = $dane[$trener][$dzien][$godzina][$trening];

        return ['trener' => $trener, 'dzien' => $dzien, 'godzina' => $godzina, 'trening' => $trening, 'zapisany' => $zapisany];
    }
}