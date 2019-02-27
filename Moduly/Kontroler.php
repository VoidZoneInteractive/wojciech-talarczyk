<?php

namespace Moduly;

/**
 * Class Kontroler
 *
 * @package Moduly
 *
 * Klasa Kontroler służy do przekazywania danych z bazy danych oraz z przeglądarki (takich jak adres url) do
 * szablonów (w przypadku wyświetlania danych) i w drugą stronę (w przypadku zapisywania danych)
 */
class Kontroler
{
    private $nazwa_strony = null;
    private $szablon = null;
    private $uzytkownik = null;

    /**
     * Kontroler constructor.
     *
     * @param null|string $nazwa_strony
     *
     * Kontruktor klasy - zapisuje nazwę strony (informacja co przychodzi z adresu przeglądarki) w zmiennej $nazwa_strony,
     * przygotowuje klasę Szablon (do obsługi szablonów) i zapuje uchwyt w zmiennej $szablon oraz
     * klasę użytkownika dającą dostęp do danych zalogowanego użytkownika i zapisuje uchwyt do $uzytkownik
     */
    public function __construct(?string $nazwa_strony = null)
    {
        $this->nazwa_strony = $nazwa_strony;
        $this->szablon = new Szablon();
        $this->uzytkownik = new Uzytkownik();
    }

    /**
     * @return bool|mixed|null|string|string[]
     * @throws \Exception
     *
     * Tutaj na podstawie przekazanej nazwy strony następuje wywołanie odpowiedniej funkcji która zajmie się
     * przetwarzaniem danych przychodzących z przeglądarki (oraz później z bazy danych) lub w drugą stronę w przypadku zapisu
     * Wyrzuca błąd w przypadku gdy nie poda się prawidłowej nazwy strony
     */
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
            case 'panelAdministratora':
                return $this->panelAdministratora();
            case 'usunWpisKalendarza':
                $this->usunWpisKalendarza();
            case 'usunUzytkownika':
                $this->usunUzytkownika();
        }

        throw new \Exception(sprintf('Nie znaleziono strony %s', $this->nazwa_strony));
    }

    /**
     * @return bool|mixed|null|string|string[]
     *
     * Przekazuje dane do szablonu strony głównej
     */
    public function stronaGlowna()
    {
        // Jezeli uzytkownik jest zalogowany to pokieruj go od razu do odpowiedniego panelu
        $this->przekierujZalogowanegoUzytkownika();

        $szablon = $this->szablon->zwrocSzablon($this->nazwa_strony);

        return $szablon;
    }

    /**
     * @return bool|mixed|null|string|string[]
     * @throws \Exception
     *
     * Przekazuje dane do szablonu rejestracja. Jeżeli przeglądarka wykonuje zapytanie typu POST wtedy uznajemy
     * że przychodzą dane do zapisania i obsługujemy je odpowiednio i później przekierowujemy na stronę podziękowania
     */
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

            // Ta metoda sprawdza poprawnosc danych do rejestracji
            $walidacja = $this->uzytkownik->sprawdzPoprawnoscDanychRejestracji($imie, $nazwisko, $login, $haslo);

            // Sprawdz poprawnosc danych - jeżeli funkcja zwrocila cos innego niz null, wyswietl sformatowana wiadomosc
            if ($walidacja) {
                $szablon = $this->szablon->zwrocSzablon(
                    $this->nazwa_strony,
                    [
                        '%{imie}' => 'value="' . $imie . '"',
                        '%{nazwisko}' => 'value="' . $nazwisko . '"',
                        '%{login}' => 'value="' . $login . '"',
                        '%{wiadomosc}' => 'Wystapił błąd przy rejestracji:<br /><ul><li>' . implode('</li><li>', $walidacja) . '</li></ul>',
                        '%{styl_wiadomosci}' => 'display: block;',
                    ]
                );

                return $szablon;
            }

            $this->uzytkownik->zarejestrujUzytkownika($imie, $nazwisko, $login, $haslo);

            // Przekierowujemy uzytkownika na strone z podziekowaniami za rejestracje
            header('Location: /?strona=rejestracjaPodziekowanie');
            exit();
        } else {
            $szablon = $this->szablon->zwrocSzablon($this->nazwa_strony);
        }

        return $szablon;
    }

    /**
     * @return bool|mixed|null|string|string[]
     * @throws \Exception
     *
     * Wyświetlamy stronę logowania. Jeżeli z przeglądarki przychodzi zapytanie typu POST wtedy
     * zaczynamy obsługę logowania użytkownika do systemu na podstawie wprowadzonych danych.
     * W przypadku błędnego logowania wyświetlamy błąd
     */
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

    /**
     * @return null
     *
     * Wylogowywanie się z serwisu
     */
    public function wylogowywanie()
    {
        $this->uzytkownik->wylogujSie();

        return null;
    }

    /**
     * @return bool|mixed|null|string|string[]
     *
     * Wyświetlamy stronę podziękowania za rejestrację.
     */
    public function rejestracjaPodziekowanie()
    {
        $szablon = $this->szablon->zwrocSzablon($this->nazwa_strony);

        return $szablon;
    }

    /**
     * @return bool|mixed|null|string|string[]
     * @throws \Exception
     *
     * Wyświetlamy stronę panelu użytkownika (z kalendarzem). Jeżeli użytkownik nie jest zalogowany przekierowujemy
     * go na stronę logowania. Jeżeli z przeglądarki przychodzi zapytanie typu POST zapisujemy w bazie
     * przychodzące dane dotyczące zmiany/zapisu do kalendarza.
     */
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

    /**
     * @return bool|mixed|null|string|string[]
     * @throws \Exception
     *
     * Jak wyżej, z tym, że strona dotyczy panelu trenera.
     */
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

    public function panelAdministratora()
    {
        if (!$this->uzytkownik->uzytkownikJestAdministratorem()) {
            // Przekierowujemy na stronę logowania, bo nie znaleziono zalogowanego użytkownika albo uzytkownik nie jest trenerem
            header('Location: /?strona=logowanie');
            exit();
        }

        $kalendarz = new Kalendarz();

        $uzytkownicy = $this->uzytkownik->pobierzListeUzytkownikow();

        foreach ($uzytkownicy as &$uzytkownik) {
            $uzytkownik = ['dane' => sprintf('%s %s', $uzytkownik['imie'], $uzytkownik['nazwisko']), 'id' => $uzytkownik['id']];
        }

        $parametry = [
            '%{wpisy}' => $kalendarz->przygotujParametrySzablonuAdministratora(),
            '%{lista-uzytkownikow}' => $kalendarz->przygotujListeUzytkownikowAdministratora($uzytkownicy),
        ];

        $szablon = $this->szablon->zwrocSzablon($this->nazwa_strony, $parametry);

        return $szablon;
    }

    public function usunWpisKalendarza()
    {
        if (!$this->uzytkownik->uzytkownikJestAdministratorem()) {
            // Przekierowujemy na stronę logowania, bo nie znaleziono zalogowanego użytkownika albo uzytkownik nie jest trenerem
            header('Location: /?strona=logowanie');
            exit();
        }

        $id = $_GET['id'];

        $kalendarz = new Kalendarz();

        $kalendarz->usunWpisKalendarza($id);

        // Przekierowujemy na stronę panelu administratora
        header('Location: /?strona=panelAdministratora');
        exit();
    }

    public function usunUzytkownika()
    {
        if (!$this->uzytkownik->uzytkownikJestAdministratorem()) {
            // Przekierowujemy na stronę logowania, bo nie znaleziono zalogowanego użytkownika albo uzytkownik nie jest trenerem
            header('Location: /?strona=logowanie');
            exit();
        }

        $id = $_GET['id'];

        $kalendarz = new Kalendarz();

        // Najpierw usuwamy wpisy uzytkownika z kalendarza
        $kalendarz->usunWpisyUzytkownika($id);

        // Potem usuwamy samego uzytkownika
        $this->uzytkownik->usunUzytkownika($id);

        // Przekierowujemy na stronę panelu administratora
        header('Location: /?strona=panelAdministratora');
        exit();
    }

    /**
     * Po zalogowaniu sprawdzamy rodzaj użytkownika i przekierowujemy go do odpowiedniego panelu.
     */
    private function przekierujZalogowanegoUzytkownika()
    {
        if ($this->uzytkownik->uzytkownikJestAdministratorem()) {
            // Przekierowujemy na stronę panelu administratora, bo znaleziono zalogowanego administratora
            header('Location: /?strona=panelAdministratora');
            exit();
        }
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