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
        7 => '18:00 - 19:45',
        8 => '20:00 - 21:30',
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

    public function pobierzKalendarzNaStroneGlowna()
    {
        $kalendarz = $this->bazaDanych->pobierzKalendarzNaStroneGlowna();

        $dzien = null;

        foreach ($kalendarz as $wpis) {

            if ($dzien === null) {
                $dzien = $wpis['dzien'];
                $wpisy[$dzien] = ['<div class="tab-pane fade in active" id="' . $dzien . '">'];
            }

            if (empty($wpisy[$dzien])) {
                $wpisy[$dzien] = [];
            }

            if ($dzien !== $wpis['dzien']) {
                $wpisy[$dzien][] = '</div>';
                $dzien = $wpis['dzien'];
                $wpisy[$dzien] = ['<div class="tab-pane" id="' . $dzien . '">'];
            }

            $wpis['godzina'] = self::GODZINY[$wpis['godzina']];

            $parametry = [
                '%{godzina}' => $wpis['godzina'],
                '%{dzien}' => $wpis['dzien'],
                '%{trener}' => $wpis['trener'],
                '%{nazwa}' => $wpis['nazwa'],
                '%{opis}' => $wpis['opis'],
                '%{obrazek}' => $wpis['obrazek'],
                '%{identyfikator}' => $wpis['identyfikator'],
            ];

            $wpisy[$dzien][] = $this->szablon->zwrocSzablon('stronaGlowna-kalendarz', $parametry);

        }

        foreach ($wpisy as $dzien => &$wpisyNaDzien) {
            $wpisyNaDzien = implode('', $wpisyNaDzien);
        }

        return implode('', $wpisy);
    }

    public function przygotujParametrySzablonuUzytkownika($dzien)
    {
        $daneKalendarza = $this->bazaDanych->pobierzKalendarzUzytkownika($this->uzytkownik->zwrocUzytkownika()['id']);
        $daneKalendarza = $this->przetworzKalendarzUzytkownika($daneKalendarza, $this->uzytkownik->zwrocUzytkownika()['id']);

        $parametry = [];
        $parametry['%{wpisy}'] = $this->przygotujWpisyTrenerowDlaUzytkownika($dzien, $daneKalendarza);

        $parametry['%{dni}'] = $this->przygotujListeDni('panelUzytkownika', $dzien);

        return $parametry;
    }

    public function przygotujParametrySzablonuUzytkownikaDietetyk()
    {
        $daneKalendarza = $this->bazaDanych->pobierzKalendarzUzytkownikaDietetyk($this->uzytkownik->zwrocUzytkownika()['id']);
        $parametry = $this->przetworzKalendarzUzytkownikaDietetyk($daneKalendarza);

        $parametry['%{dni}'] = $this->przygotujDniDietetyka();

        return $parametry;
    }

    public function przygotujParametrySzablonuTrenera()
    {
        $idTrenera = $this->uzytkownik->zwrocUzytkownika()['id_trenera'];
        $daneKalendarza = $this->bazaDanych->pobierzKalendarzTrenera($idTrenera);
        $daneKalendarza = $this->przetworzKalendarzTrenera($daneKalendarza);

        $parametry = [];

        $parametry['%{wpisy}'] = $this->przygotujWypisyUzytkownikowDlaTrenera($daneKalendarza, $idTrenera);

        return $parametry;
    }

    public function przygotujParametrySzablonuAdministratora($dzien)
    {
        $daneKalendarzy = $this->bazaDanych->pobierzKalendarzeTrenerow();
        $daneKalendarzy = $this->przetworzKalendarze($daneKalendarzy);

        $dietetykWpisy = $this->bazaDanych->pobierzDniDietetykaDlaAdministratora();



        $wpisy = '';

        $parametry = [];

        $parametry['%{dietetyk-wpisy}'] = implode('', $this->przygotujDietetykaDlaAdministratora($dietetykWpisy));

        $parametry['%{wpisy}'] = $this->przygotujWpisyTrenerowDlaAdministratora($dzien, $daneKalendarzy);

        $parametry['%{dni}'] = $this->przygotujListeDni('panelAdministratora', $dzien);

        return $parametry;
    }

    private function przygotujDietetykaDlaAdministratora($dietetykWpisy)
    {
        if (empty($dietetykWpisy)) {
            return ['Brak wpisow.'];
        }
        $rezultat = [];

        foreach ($dietetykWpisy as $wpis) {
            $rezultat[] = '<li>' . $wpis['data'] . ' - ' . $wpis['imie'] . ' ' . $wpis['nazwisko'] . '</li>';
        }

        return $rezultat;
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
            $uzytkownik = $uzytkownik['dane'] . ' <a href=" /?strona=edycja&id_uzytkownika='.$uzytkownik['id'].'">edytuj</a> <a href=" /?strona=usunUzytkownika&id='.$uzytkownik['id'].'" onclick="if(!confirm(\'Czy napewno usunąć użytkownika: '.$uzytkownik['dane'].'?\')) { return false; }">usuń</a>';
        }
        return '<ul><li>' . implode('</li><li>', $uzytkownicy) . '</li></ul>';
    }

    public function przygotujListeTrenerowAdministratora(array $uzytkownicy)
    {
        foreach ($uzytkownicy as &$uzytkownik) {
            $uzytkownik = $uzytkownik['dane'] . ' <a href=" /?strona=edycja&id_uzytkownika='.$uzytkownik['id'].'">edytuj</a> <a href=" /?strona=usunUzytkownika&id='.$uzytkownik['id'].'" onclick="if(!confirm(\'Czy napewno usunąć użytkownika: '.$uzytkownik['dane'].'?\')) { return false; }">usuń</a>';
        }
        return '<ul><li>' . implode('</li><li>', $uzytkownicy) . '</li></ul>';
    }

    public function usunWpisKalendarza($id) {
        $this->bazaDanych->usunWpisKalendarza($id);
    }

    public function przetworzKalendarzUzytkownika(array $daneKalendarza, int $idUzytkownika)
    {
        $rezultat = [];

        foreach ($daneKalendarza as $wpisKalendarza) {
            if (empty($rezultat[$wpisKalendarza['trener']])) {
                $rezultat[$wpisKalendarza['trener']] = [];
            }

            $rezultat[$wpisKalendarza['trener']][$wpisKalendarza['dzien']][$wpisKalendarza['godzina']][$wpisKalendarza['trening']] = $wpisKalendarza['uzytkownik'] == $idUzytkownika ? $wpisKalendarza['zapisany'] : 2;
        }

        return $rezultat;
    }

    public function przetworzKalendarzUzytkownikaDietetyk(array $daneKalendarza)
    {
        if (!empty($daneKalendarza)) {
            return ['%{statusFormularza}' => 'style="display: none;', '%{wpis}' => 'Jesteś zapisany do dietetyka na termin: ' . $daneKalendarza[0]['data'] . ' <a href="?strona=panelUzytkownikaDietetyk&akcja=usunRezerwacje">Usuń rezerwację terminu</a>'];
        }

        return [];
    }

    public function usunRezerwacjeDietetyka()
    {
        $this->bazaDanych->usunDniDietetyka($this->uzytkownik->zwrocUzytkownika()['id']);
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

            if (!empty($wpisKalendarza['zapisany'])) {
                $uzytkownik = $this->bazaDanych->pobierzNazweUzytkownikaPoId($wpisKalendarza['uzytkownik']);
                $rezultat[$trener][$wpisKalendarza['dzien']][$wpisKalendarza['godzina']][$wpisKalendarza['trening']][] = ['dane' => sprintf('%s %s', $uzytkownik['imie'], $uzytkownik['nazwisko']), 'id_kalendarza' => $wpisKalendarza['id']];
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

        $this->bazaDanych->usunWpisyWypisanychUzytkownikow();
    }

    public function zapiszDaneDietetyk(string $data)
    {
        $idUzytkownika = $this->uzytkownik->zwrocUzytkownika()['id'];

        $this->bazaDanych->dodajWpisDietetyka($data, $idUzytkownika);
    }

    private function przygotujDniDietetyka()
    {
        $daty = [];
        $d = 1;
        for ($i = 1; $i <= 7; $i++) {
            $data = new \DateTime('now +' . (string)$d . ' day');

            if ($data->format('N') >= 6) {
                $d += 8 - $data->format('N');
                $data = new \DateTime('now +' . (string)$d . ' day');
            }

            $daty[] = '<optgroup label="' . self::DNI[($d - 1) % 7] . ', ' . $data->format('d.m.Y') . '">';

            for ($g = 9; $g <= 17; $g++) {
                $dataSformatowana = (string)$g . ':00:00';
                $daty[] = sprintf('<option value="' . $data->format('Y-m-d') . ' ' . $dataSformatowana . '">' . $dataSformatowana . '</option>');
            }

            $daty[] = '</optgroup>';
            $d++;
        }

        return implode("\n", $daty);
    }

    private function przygotujListeDni($strona, $aktualny_dzien)
    {
        $wynik = [];

        foreach (self::DNI as $dzien) {
            if ($dzien == $aktualny_dzien) {
                $wynik[] = sprintf('<li class="active"><a href="?strona=%s&dzien=%s">%s</a>', $strona, $dzien, $dzien);
            } else {
                $wynik[] = sprintf('<li><a href="?strona=%s&dzien=%s">%s</a></li>', $strona, $dzien, $dzien);
            }
        }

        return implode('', $wynik);
    }

    private function przygotujWpisyTrenerowDlaUzytkownika($dzien, $daneKalendarza)
    {
        $rzedy = [];

        foreach (self::GODZINY as $idGodziny => $godzina) {
            $treningi = $this->bazaDanych->pobierzTreningiDlaGodzinyOrazDnia($idGodziny, $dzien);

            $rzadParametry = [
                '%{id-godziny}' => $idGodziny,
                '%{godzina}' => '<td rowspan="' . count($treningi) . '">' . $godzina . '</td>',
            ];

            $licznik = 0;

            $zablokowaneTreningi = null;

            foreach ($treningi as $trening) {
                $idTrenera = $trening['id_trenera'];

                if (isset($daneKalendarza[$idTrenera][$dzien][$idGodziny][$trening['id_treningu']]) && $daneKalendarza[$idTrenera][$dzien][$idGodziny][$trening['id_treningu']] == 1) {
                    $zablokowaneTreningi = serialize($trening);
                }
            }

            foreach ($treningi as $trening) {

                $idTrenera = $trening['id_trenera'];
                $imieTrenera = $trening['imie'];
                $nazwiskoTrenera = $trening['nazwisko'];

                $rzadParametry['%{trening}'] = '<td>' . $trening['nazwa'] . '</td>';
                $rzadParametry['%{trener}'] = '<td>' . sprintf('%s %s', $imieTrenera, $nazwiskoTrenera) . '</td>';
                $zapisy = [];

                $parametryLinii = [
                    '%{id}' => $idTrenera,
                    '%{trener}' => sprintf('%s %s', $imieTrenera, $nazwiskoTrenera),
                    '%{dzien}' => $dzien,
                    '%{godzina}' => $idGodziny,
                    '%{id_treningu}' => $trening['id_treningu'],
                    '%{przycisk}' => 'Zapisz się',
                    '%{wartosc}' => 1,
                ];

                if (isset($daneKalendarza[$idTrenera][$dzien][$idGodziny][$trening['id_treningu']])) {
                    $parametryLinii['%{wartosc}'] = 0;

                    switch (true) {
                        case $daneKalendarza[$idTrenera][$dzien][$idGodziny][$trening['id_treningu']] == 1 && serialize($trening) == $zablokowaneTreningi:
                            $parametryLinii['%{przycisk}'] = 'Wypisz się';
                            break;

                        case $daneKalendarza[$idTrenera][$dzien][$idGodziny][$trening['id_treningu']] == 2:
                            $parametryLinii['%{przycisk}'] = 'Zablokowany';
                            $parametryLinii['%{zablokowany}'] = 'disabled';
                            break;
                    }
                } elseif (!empty($zablokowaneTreningi) && serialize($trening) !== $zablokowaneTreningi) {
                    $parametryLinii['%{przycisk}'] = 'Zablokowany';
                    $parametryLinii['%{zablokowany}'] = 'disabled';
                }

                $zapisy[] = $this->szablon->zwrocSzablon('panelUzytkownika-linia', $parametryLinii);


                $rzadParametry['%{zapisy}'] = implode('', $zapisy);

                $licznik++;

                $rzedy[] = $this->szablon->zwrocSzablon('panelUzytkownika-rzad', $rzadParametry);

                unset($rzadParametry['%{godzina}']);
            }

            if (!isset($godzinaDoPokazania)) {
                $godzinaDoPokazania = $godzina;
            }
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
                    if ($trening['dzien'] != $dzien) {
                        $parametryLinii['%{' . $dzien . '}'] = '-----';
                    }
                    elseif (empty($daneKalendarza[$dzien][$idGodziny])) {
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
        }

        return implode('',$rzedy);
    }

    private function przygotujWpisyTrenerowDlaAdministratora($dzien, $daneKalendarza)
    {
        $rzedy = [];

        foreach (self::GODZINY as $idGodziny => $godzina) {
            $treningi = $this->bazaDanych->pobierzTreningiDlaGodzinyOrazDnia($idGodziny, $dzien);

            $rzadParametry = [
                '%{godzina}' => '<td rowspan="' . count($treningi) . '">' . $godzina . '</td>',
            ];

            $licznik = 0;

            foreach ($treningi as $trening) {
                $imieTrenera = $trening['imie'];
                $nazwiskoTrenera = $trening['nazwisko'];

                $trener = sprintf('%s %s', $imieTrenera, $nazwiskoTrenera);

                $rzadParametry['%{trening}'] = '<td>' . $trening['nazwa'] . '</td>';
                $rzadParametry['%{trener}'] = '<td>' . $trener . '</td>';
                $zapisy = [];

                $parametryLinii = [];

                if (empty($daneKalendarza[$trener][$dzien][$idGodziny])) {
                    $parametryLinii['%{zapisy}'] = 'Brak Zapisow';
                } else {
                    $parametryLinii['%{zapisy}'] = $this->przygotujListeUzytkownikowKalendarzaAdministratora($daneKalendarza[$trener][$dzien][$idGodziny][$trening['id_treningu']]);
                }

                $zapisy[] = $this->szablon->zwrocSzablon('panelAdministratora-linia', $parametryLinii);


                $rzadParametry['%{zapisy}'] = implode('', $zapisy);

                $licznik++;

                $rzedy[] = $this->szablon->zwrocSzablon('panelAdministratora-rzad', $rzadParametry);

                unset($rzadParametry['%{godzina}']);
            }

            if (!isset($godzinaDoPokazania)) {
                $godzinaDoPokazania = $godzina;
            }
        }

        return implode('', $rzedy);
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