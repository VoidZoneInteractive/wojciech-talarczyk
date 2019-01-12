<?php

namespace Moduly;

class Kontroler
{
    private $nazwa_strony = null;
    private $szablon = null;
    private $uzytkownik = null;

    public function __construct(?string $nazwa_strony = null)
    {
        $this->nazwa_strony = $nazwa_strony;
        $this->szablon = new Szablon();
        $this->uzytkownik = new Uzytkownik();
    }

    public function akcja()
    {
        switch ($this->nazwa_strony) {
            case 'logowanie':
                return $this->logowanie();
            case 'wylogowywanie':
                return $this->wylogowywanie();
            case 'rejestracja':
                return $this->rejestracja();
            case 'rejestracjaPodziekowanie':
                return $this->rejestracjaPodziekowanie();
            case 'stronaGlowna':
                return $this->stronaGlowna();
            case 'panelUzytkownika':
                return $this->panelUzytkownika();
            case 'panelTrenera':
                return $this->panelTrenera();
        }

        throw new \Exception(sprintf('Nie znaleziono strony %s', $this->nazwa_strony));
    }

    public function stronaGlowna()
    {
        // Jezeli uzytkownik jest zalogowany to pokieruj go od razu do odpowiedniego panelu
        $this->przekierujZalogowanegoUzytkownika();

        $szablon = $this->szablon->zwrocSzablon($this->nazwa_strony);

        return $szablon;
    }

    public function rejestracja()
    {
        // Jezeli uzytkownik jest zalogowany to pokieruj go od razu do odpowiedniego panelu
        $this->przekierujZalogowanegoUzytkownika();

        // Sprawdzamy czy ktos wyslal dane formularza
        if ($_SERVER['REQUEST_METHOD'] == 'POST') {
            $imie = $_POST['imie'];
            $nazwisko = $_POST['nazwisko'];
            $login = $_POST['login'];
            $haslo = $_POST['haslo'];

            $this->uzytkownik->zarejestrujUzytkownika($imie, $nazwisko, $login, $haslo);

            // Przekierowujemy uzytkownika na strone z podziekowaniami za rejestracje
            header('Location: /?strona=rejestracjaPodziekowanie');
            exit();
        } else {
            $szablon = $this->szablon->zwrocSzablon($this->nazwa_strony);
        }

        return $szablon;
    }

    public function logowanie()
    {
        // Jezeli uzytkownik jest zalogowany to pokieruj go od razu do odpowiedniego panelu
        $this->przekierujZalogowanegoUzytkownika();

        if ($_SERVER['REQUEST_METHOD'] == 'POST') {
            try {
                $login = $_POST['login'];
                $haslo = $_POST['haslo'];


                $parametry = [
                    '%{wiadomosc}' => 'Nieprawidłowe dane logowania',
                    '%{styl_wiadomosci}' => 'display: block;',
                ];

                $this->uzytkownik->zalogujUzytkownika($login, $haslo);

                $szablon = $this->szablon->zwrocSzablon($this->nazwa_strony, $parametry);

                return $szablon;
            } catch (\Exception $e) {
                throw $e;
            }
        } else {
            $parametry = [
                '%{styl_wiadomosci}' => 'display: none;',
            ];

            $szablon = $this->szablon->zwrocSzablon($this->nazwa_strony, $parametry);

            return $szablon;
        }
    }

    public function wylogowywanie()
    {
        $this->uzytkownik->wylogujSie();

        return null;
    }

    public function rejestracjaPodziekowanie()
    {
        $szablon = $this->szablon->zwrocSzablon($this->nazwa_strony);

        return $szablon;
    }

    public function panelUzytkownika()
    {
        if (!$this->uzytkownik->zwrocUzytkownika()) {
            // Przekierowujemy na stronę logowania, bo nie znaleziono zalogowanego użytkownika
            header('Location: /?strona=logowanie');
            exit();
        }

        $parametry = [];

        $kalendarz = new Kalendarz();

        if ($_SERVER['REQUEST_METHOD'] == 'POST') {
            $kalendarz->zapiszDane($_POST['kalendarz']);

            $parametry = [
                '%{wiadomosc}' => 'Zapisano kalendarz',
                '%{styl_wiadomosci}' => 'display: block;',
            ];
        }

        $parametry = array_merge($parametry, $kalendarz->przygotujParametrySzablonuUzytkownika());

        $szablon = $this->szablon->zwrocSzablon($this->nazwa_strony, $parametry);

        return $szablon;
    }

    public function panelTrenera()
    {
        if (!$this->uzytkownik->uzytkownikJestTrenerem()) {
            // Przekierowujemy na stronę logowania, bo nie znaleziono zalogowanego użytkownika albo uzytkownik nie jest trenerem
            header('Location: /?strona=logowanie');
            exit();
        }

        $kalendarz = new Kalendarz();


        $parametry = $kalendarz->przygotujParametrySzablonuTrenera();

        $szablon = $this->szablon->zwrocSzablon($this->nazwa_strony, $parametry);

        return $szablon;

    }

    private function przekierujZalogowanegoUzytkownika()
    {
        if ($this->uzytkownik->uzytkownikJestTrenerem()) {
            // Przekierowujemy na stronę panelu trenera, bo znaleziono zalogowanego trenera
            header('Location: /?strona=panelTrenera');
            exit();
        }

        if ($this->uzytkownik->zwrocUzytkownika()) {

            // Przekierowujemy na stronę panelu użytkownika, bo znaleziono zalogowanego użytkownika
            header('Location: /?strona=panelUzytkownika');
            exit();
        }
    }
}