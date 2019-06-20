<?php

namespace Moduly;

/**
 * Class Uzytkownik
 *
 * @package Moduly
 *
 * Klasa użytkownika służy do zarządzania logowaniem, rejestracją i zarządzaniem zalogowanym użytkownikiem.
 */
class Uzytkownik
{
    const NAZWA_SESJI_UZYTKOWNIKA = 'uzytkownik';

    private $bazaDanych;

    /**
     * Uzytkownik constructor.
     *
     * @throws \Exception
     *
     * Konstruktor klasy - inicjuje sesję (jeżeli ta nie została jeszcze zainicjowana)
     * Oraz nawiązuje połączenie z bazą danych i zachowuje ją do zmiennej $bazaDanych
     */
    public function __construct()
    {
        if(session_status() == PHP_SESSION_NONE) {
            session_start();
        }

        $this->bazaDanych = new BazaDanych();
    }

    /**
     * @return bool
     *
     * Sprawdza czy w sesji zapisany jest użytkownik i pobiera na jego podstawie dane użytkownika
     * Zwraca null w przypadku gdy nie ma sesji bądź nie znaleziono użytkownika (jest niezalogowany)
     */
    public function zwrocUzytkownika()
    {
        $uzytkownik = $this->pobierzUzytkownikaZSesji();

        if (!$uzytkownik) {
            return false;
        }

        return $uzytkownik;
    }

    /**
     * @return bool
     *
     * Sprawdza czy zalogowany użytkownik jest trenerem
     */
    public function uzytkownikJestTrenerem() {
        $uzytkownik = $this->pobierzUzytkownikaZSesji();

        return $uzytkownik && $uzytkownik['id_trenera'];
    }

    /**
     * Funkcja sprawdzająca czy użytkownik ma uprawnienia administratora
     * @return bool
     */
    public function uzytkownikJestAdministratorem() {
        $uzytkownik = $this->pobierzUzytkownikaZSesji();

        return $uzytkownik && !empty($uzytkownik['administrator']);
    }

    /**
     * Metoda odwołująca się do funkcji bazodanowej usuwającej użytkownika
     * @param $id
     */
    public function usunUzytkownika($id) {
        $this->bazaDanych->usunUzytkownika($id);
    }

    /**
     * @param array $uzytkownik
     *
     * Zapisuje dane użytkownika w sesji
     */
    private function zapiszUzytkownikaWSesji(array $uzytkownik)
    {
        $_SESSION[self::NAZWA_SESJI_UZYTKOWNIKA] = $uzytkownik;
    }

    /**
     * @return bool
     *
     * Pobiera dane użytkownika z sesji
     */
    private function pobierzUzytkownikaZSesji()
    {
        return !empty($_SESSION[self::NAZWA_SESJI_UZYTKOWNIKA]) ? $_SESSION[self::NAZWA_SESJI_UZYTKOWNIKA] : false;
    }

    /**
     * @param string $login
     * @param string $haslo
     *
     * Loguje użytkownika - najpierw pobiera dane na podstawie loginu, potem porównuje zakodowane hasło
     * i jeżeli wszystko się zgadza to przekierowuje odpowiednio na stronę użytkownika bądź trenera
     */
    public function zalogujUzytkownika(string $login, string $haslo)
    {
        $uzytkownik = $this->bazaDanych->pobierzUzytkownika($login);

        if (!empty($uzytkownik) && $this->porownajHasloIHasz($haslo, $uzytkownik['haslo'])) {
            $this->zapiszUzytkownikaWSesji($uzytkownik);
            // Przekieruj na panel administracyjny
            if (!empty($uzytkownik['administrator'])) {
                header('Location: /?strona=panelAdministratora');
            } elseif (!empty($uzytkownik['id_trenera'])) {
                header('Location: /?strona=panelTrenera');
            } else {
                header('Location: /?strona=panelUzytkownika');
            }
            exit();
        }
    }

    /**
     * Wylogowuje użytkownika z sesji i z systemu
     */
    public function wylogujSie()
    {
        session_destroy();
        header('Location: /?strona=logowanie');
        exit();
    }

    /**
     * Sprawdzenie, czy wszystkie dane rejestracji są poprawne.
     * @param string|null $imie
     * @param string $nazwisko
     * @param string|null $login
     * @param string $haslo
     * @return array
     */
    public function sprawdzPoprawnoscDanychRejestracji(string $imie = null, string $nazwisko, string $login = null, string $haslo)
    {
        $walidacja = [];

        if ($walidujImie = $this->sprawdzImie($imie)) {
            $walidacja[] = $walidujImie;
        }

        if ($walidujNazwisko = $this->sprawdzNazwisko($nazwisko)) {
            $walidacja[] = $walidujNazwisko;
        }

        if ($walidujLogin = $this->sprawdzLogin($login)) {
            $walidacja[] = $walidujLogin;
        }

        if ($walidujHaslo = $this->sprawdzHaslo($haslo)) {
            $walidacja[] = $walidujHaslo;
        }

        return $walidacja;
    }

    /**
     * Metoda sprawdzająca czy login użyty do rejestracji jest poprawny (nie jest pusty i jest prawidłowym adresem e-mail)
     * @param string|null $login
     * @return null|string
     */
    private function sprawdzLogin(string $login = null)
    {
        return (!empty($login) && filter_var($login, FILTER_VALIDATE_EMAIL)) ? null : 'Niepoprawny email';
    }

    /**
     * Metoda sprawdzająca czy imię użyte do rejestracji jest poprawne (nie jest puste)
     * @param string|null $imie
     * @return null|string
     */
    private function sprawdzImie(string $imie = null) {
        return !empty($imie) ? null : 'Niepoprawne imię';
    }

    /**
     * Metoda sprawdzająca czy nazwisko użyte do rejestracji jest poprawne (nie jest puste)
     * @param string|null $nazwisko
     * @return null|string
     */
    private function sprawdzNazwisko(string $nazwisko = null) {
        return !empty($nazwisko) ? null : 'Niepoprawne imię';
    }

    /**
     * Metoda sprawdzająca czy hasło użyte do rejestracji jest poprawne (nie jest puste i ma co najmniej 5 znaków)
     * @param string|null $haslo
     * @return null|string
     */
    private function sprawdzHaslo(string $haslo = null) {
        return (!empty($haslo) && strlen($haslo) > 4) ? null : 'Niepoprawne hasło (musi mieć przynajmniej 5 znaków)';
    }

    /**
     * Metoda wykorzystująca pobranie danych użytkowników do panelu administracyjnego
     * @return array
     */
    public function pobierzListeUzytkownikow()
    {
        return $this->bazaDanych->pobierzListeUzytkownikow();
    }

    /**
     * @param string $imie
     * @param string $nazwisko
     * @param string $login
     * @param string $haslo
     * @throws \Exception
     *
     * Rejestruje nowego użytkonika. Przed zapisaniem do bazy danych jednostronnie szyfruje jego hasło
     */
    public function zarejestrujUzytkownika(string $imie, string $nazwisko, string $login, string $haslo)
    {
        $haslo = $this->szyfrujHaslo($haslo);

        try {
            $this->bazaDanych->dodajUzytkownikaDoBazy($imie, $nazwisko, $login, $haslo);
        } catch (\Exception $e) {
            throw $e;
        }
    }

    /**
     * @param string $haslo
     * @param string $hasz
     * @return bool
     *
     * Porównuje zaszyfrowane hasło z bazy z niezaszyfrowanym hasłem (przy logowaniu)
     */
    public function porownajHasloIHasz(string $haslo, string $hasz)
    {
        return password_verify($haslo, $hasz);
    }

    /**
     * @param string $haslo
     * @return bool|string
     *
     * Szyfruje hasło za pomocą algorytmu szyfrującego Argon2i
     */
    private function szyfrujHaslo(string $haslo)
    {
        return password_hash($haslo, PASSWORD_ARGON2I);
    }
}