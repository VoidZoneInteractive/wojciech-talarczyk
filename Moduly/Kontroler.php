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
        $dzien = $this->pobierzDzienZUrl();
        switch ($this->nazwa_strony) {
            case 'logowanie':
                return $this->logowanie();
            case 'wylogowywanie':
                return $this->wylogowywanie();
            case 'rejestracja':
                return $this->rejestracja();
            case 'edycja':
                $idUzytkownika = $this->pobierzIdUzytkownikaZUrl();
                return $this->edycja($idUzytkownika);
            case 'rejestracjaPodziekowanie':
                return $this->rejestracjaPodziekowanie();
            case 'stronaGlowna':
                return $this->stronaGlowna();
            case 'panelUzytkownika':
                return $this->panelUzytkownika($dzien);
            case 'panelUzytkownikaDietetyk':
                return $this->panelUzytkownikaDietetyk();
            case 'panelTrenera':
                return $this->panelTrenera($dzien);
            case 'panelAdministratora':
                return $this->panelAdministratora($dzien);
            case 'panelAdministratora-uzytkownicy':
                return $this->panelAdministratora($dzien, 'uzytkownicy');
            case 'panelAdministratora-trenerzy':
                return $this->panelAdministratora($dzien, 'trenerzy');
            case 'panelAdministratora-dietetyk':
                return $this->panelAdministratora($dzien, 'dietetyk');
            case 'usunWpisKalendarza':
                $this->usunWpisKalendarza();
                break;
            case 'usunUzytkownika':
                $this->usunUzytkownika();
                break;
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

        $kalendarz = new Kalendarz();

        $parametry = [
            '%{wpisy}' => $kalendarz->pobierzKalendarzNaStroneGlowna()
        ];

        $szablon = $this->szablon->zwrocSzablon($this->nazwa_strony, $parametry);

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
            $walidacja = $this->uzytkownik->sprawdzPoprawnoscDanych($imie, $nazwisko, $login, $haslo);

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

    public function edycja($idUzytkownika)
    {
        if (!$this->uzytkownik->uzytkownikJestAdministratorem() && !$this->uzytkownik->zwrocUzytkownika()) {
            // Przekierowujemy na stronę logowania, bo nie znaleziono zalogowanego użytkownika albo uzytkownik nie jest adminem
            header('Location: /?strona=logowanie');
            exit();
        }

        // Sprawdzamy czy ktos wyslal dane formularza
        if ($_SERVER['REQUEST_METHOD'] == 'POST') {
            $imie = $_POST['imie'];
            $nazwisko = $_POST['nazwisko'];
            $login = $_POST['login'];

            // Ta metoda sprawdza poprawnosc danych do edycji
            $walidacja = $this->uzytkownik->sprawdzPoprawnoscDanych($imie, $nazwisko, $login, false);

            // Sprawdz poprawnosc danych - jeżeli funkcja zwrocila cos innego niz null, wyswietl sformatowana wiadomosc
            if ($walidacja) {
                $szablon = $this->szablon->zwrocSzablon(
                    $this->nazwa_strony,
                    [
                        '%{imie}' => 'value="' . $imie . '"',
                        '%{nazwisko}' => 'value="' . $nazwisko . '"',
                        '%{login}' => 'value="' . $login . '"',
                        '%{wiadomosc}' => 'Wystapił błąd przy edycji:<br /><ul><li>' . implode('</li><li>', $walidacja) . '</li></ul>',
                        '%{styl_wiadomosci}' => 'display: block;',
                    ]
                );

                return $szablon;
            }

            $this->uzytkownik->aktualizujUzytkownika($idUzytkownika, $imie, $nazwisko, $login);

            // Przekierowujemy uzytkownika na strone z podziekowaniami za rejestracje
            if ($this->uzytkownik->uzytkownikJestAdministratorem()) {
                header('Location: /?strona=panelAdministratora');
                exit();
            } else {
                header('Location: /?strona=panelUzytkownika');
                exit();
            }
        } else {
            $uzytkownik = $this->uzytkownik->zwrocUzytkownikaPoId($idUzytkownika);
            $parametry = [
                '%{imie}' => 'value="' . $uzytkownik['imie'] . '"',
                '%{nazwisko}' => 'value="' . $uzytkownik['nazwisko'] . '"',
                '%{login}' => 'value="' . $uzytkownik['login'] . '"',
            ];
            $szablon = $this->szablon->zwrocSzablon($this->nazwa_strony, $parametry);
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

    public function panelUzytkownika($dzien)
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

        $parametry = array_merge($parametry, $kalendarz->przygotujParametrySzablonuUzytkownika($dzien));

        $szablon = $this->szablon->zwrocSzablon($this->nazwa_strony, $parametry);

        return $szablon;
    }

    public function panelUzytkownikaDietetyk()
    {
        if (!$this->uzytkownik->zwrocUzytkownika()) {
            // Przekierowujemy na stronę logowania, bo nie znaleziono zalogowanego użytkownika
            header('Location: /?strona=logowanie');
            exit();
        }

        $parametry = [];

        $kalendarz = new Kalendarz();

        if ($_SERVER['REQUEST_METHOD'] == 'GET' && !empty($_GET['akcja']) && $_GET['akcja'] == 'usunRezerwacje') {
            $kalendarz->usunRezerwacjeDietetyka();

            // Przekieruj na stronę dietetyka
            header('Location: /?strona=panelUzytkownikaDietetyk');
            exit();
        }

        if ($_SERVER['REQUEST_METHOD'] == 'POST') {
            $kalendarz->usunRezerwacjeDietetyka();
            $kalendarz->zapiszDaneDietetyk($_POST['data']);

            $parametry = [
                '%{wiadomosc}' => 'Zapisano kalendarz',
                '%{styl_wiadomosci}' => 'display: block;',
            ];
        }

        $parametry = array_merge($parametry, $kalendarz->przygotujParametrySzablonuUzytkownikaDietetyk());

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

    public function panelAdministratora($dzien, $modul = null)
    {
        if (!$this->uzytkownik->uzytkownikJestAdministratorem() && (!$this->uzytkownik->uzytkownikJestDietetykiem() && $modul == 'dietetyk') && (!$this->uzytkownik->uzytkownikJestPracownikiem() && (is_null($modul) || $modul == 'dietetyk'))) {
            // Przekierowujemy na stronę logowania, bo nie znaleziono zalogowanego użytkownika albo uzytkownik nie jest trenerem
            header('Location: /?strona=logowanie');
            exit();
        }

        $kalendarz = new Kalendarz();

        $parametry = [];

        if ($modul === 'uzytkownicy') {
            $uzytkownicy = $this->uzytkownik->pobierzListeUzytkownikow();

            foreach ($uzytkownicy as &$uzytkownik) {
                $uzytkownik = ['dane' => sprintf('%s %s', $uzytkownik['imie'], $uzytkownik['nazwisko']),
                               'id' => $uzytkownik['id']];
            }

            $parametry = [
                '%{lista-uzytkownikow}' => $kalendarz->przygotujListeUzytkownikowAdministratora($uzytkownicy),
            ];
        }

        if ($modul === 'trenerzy') {
            $trenerzy = $this->uzytkownik->pobierzListeTrenerow();

            foreach ($trenerzy as &$trener) {
                $trener = ['dane' => sprintf('%s %s', $trener['imie'], $trener['nazwisko']), 'id' => $trener['id']];
            }

            $parametry = [
                '%{lista-trenerow}' => $kalendarz->przygotujListeTrenerowAdministratora($trenerzy),
            ];
        }

        if ($modul === 'dietetyk') {
            $parametry = $kalendarz->przygotujDietetykaDlaAdministratora();

            if ($this->uzytkownik->uzytkownikJestDietetykiem()) {
                $parametry['%{ukryj-start}'] = '<!--';
                $parametry['%{ukryj-koniec}'] = '--!>';
            }

            if ($this->uzytkownik->uzytkownikJestPracownikiem()) {
                $parametry['%{ukryj-start-pracownik}'] = '<!--';
                $parametry['%{ukryj-koniec}'] = '--!>';
            }
        }

        if (is_null($modul)) {
            $parametry = array_merge($parametry, $kalendarz->przygotujParametrySzablonuAdministratora($dzien));

            if ($this->uzytkownik->uzytkownikJestPracownikiem()) {
                $parametry['%{ukryj-start}'] = '<!--';
                $parametry['%{ukryj-koniec}'] = '--!>';
            }
        }

        $szablon = $this->szablon->zwrocSzablon($this->nazwa_strony, $parametry);

        return $szablon;
    }

    /**
     * Akcja usunięcia wpisu kalendarza dla konkretnego użytkownika/dnia/trenera.
     * @throws \Exception
     */
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

    /**
     * Akcja usunięcia użytkownika z bazy danych wraz z jego wpisami kalendarza.
     * @throws \Exception
     */
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
        if ($this->uzytkownik->uzytkownikJestAdministratorem() || $this->uzytkownik->uzytkownikJestPracownikiem()) {
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

    private function pobierzDzienZUrl()
    {
        if (!empty($_GET['dzien']) && in_array($_GET['dzien'], Kalendarz::DNI)) {
            return $_GET['dzien'];
        } else {
            return Kalendarz::DNI[0];
        }
    }

    private function pobierzIdUzytkownikaZUrl()
    {
        if (!empty($_GET['id_uzytkownika'])) {
            return $_GET['id_uzytkownika'];
        } elseif ($uzytkownik = $this->uzytkownik->zwrocUzytkownika()) {
            return $uzytkownik['id'];
        } else {
            return null;
        }
    }
}