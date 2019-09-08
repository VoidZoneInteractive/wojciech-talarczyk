<?php

namespace Moduly;

class Uzytkownik
{
    const NAZWA_SESJI_UZYTKOWNIKA = 'uzytkownik';

    private $bazaDanych;

    public function __construct()
    {
        if(session_status() == PHP_SESSION_NONE) {
            session_start();
        }

        $this->bazaDanych = new BazaDanych();
    }

    public function zwrocUzytkownika()
    {
        $uzytkownik = $this->pobierzUzytkownikaZSesji();

        if (!$uzytkownik) {
            return false;
        }

        return $uzytkownik;
    }

    public function zwrocUzytkownikaPoId($idUzytkownika)
    {
        $uzytkownik = $this->bazaDanych->pobierzUzytkownikaPoId($idUzytkownika);
        return $uzytkownik;
    }

    public function uzytkownikJestTrenerem() {
        $uzytkownik = $this->pobierzUzytkownikaZSesji();

        return $uzytkownik && $uzytkownik['id_trenera'];
    }

    public function uzytkownikJestAdministratorem() {
        $uzytkownik = $this->pobierzUzytkownikaZSesji();

        return $uzytkownik && !empty($uzytkownik['administrator']);
    }

    public function usunUzytkownika($id) {
        $this->bazaDanych->usunUzytkownika($id);
    }

    private function zapiszUzytkownikaWSesji(array $uzytkownik)
    {
        $_SESSION[self::NAZWA_SESJI_UZYTKOWNIKA] = $uzytkownik;
    }

    private function pobierzUzytkownikaZSesji()
    {
        return !empty($_SESSION[self::NAZWA_SESJI_UZYTKOWNIKA]) ? $_SESSION[self::NAZWA_SESJI_UZYTKOWNIKA] : false;
    }

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

    public function wylogujSie()
    {
        session_destroy();
        header('Location: /?strona=logowanie');
        exit();
    }

    public function sprawdzPoprawnoscDanych(string $imie = null, string $nazwisko, string $login = null, $haslo)
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

        if ($haslo !== false && $walidujHaslo = $this->sprawdzHaslo($haslo)) {
            $walidacja[] = $walidujHaslo;
        }

        return $walidacja;
    }

    private function sprawdzLogin(string $login = null)
    {
        return (!empty($login) && filter_var($login, FILTER_VALIDATE_EMAIL)) ? null : 'Niepoprawny email';
    }

    private function sprawdzImie(string $imie = null) {
        return !empty($imie) ? null : 'Niepoprawne imię';
    }

    private function sprawdzNazwisko(string $nazwisko = null) {
        return !empty($nazwisko) ? null : 'Niepoprawne imię';
    }

    private function sprawdzHaslo(string $haslo = null) {
        return (!empty($haslo) && strlen($haslo) > 4) ? null : 'Niepoprawne hasło (musi mieć przynajmniej 5 znaków)';
    }

    public function pobierzListeUzytkownikow()
    {
        return $this->bazaDanych->pobierzListeUzytkownikow();
    }

    public function pobierzListeTrenerow()
    {
        return $this->bazaDanych->pobierzListeTrenerow();
    }

    public function zarejestrujUzytkownika(string $imie, string $nazwisko, string $login, string $haslo)
    {
        $haslo = $this->szyfrujHaslo($haslo);

        try {
            $this->bazaDanych->dodajUzytkownikaDoBazy($imie, $nazwisko, $login, $haslo);
        } catch (\Exception $e) {
            throw $e;
        }
    }

    public function aktualizujUzytkownika(int $idUzytkownika, string $imie, string $nazwisko, string $login)
    {
        try {
            $this->bazaDanych->aktualizujUzytkownikaWBazie($idUzytkownika, $imie, $nazwisko, $login);
        } catch (\Exception $e) {
            throw $e;
        }
    }

    public function porownajHasloIHasz(string $haslo, string $hasz)
    {
        return password_verify($haslo, $hasz);
    }

    private function szyfrujHaslo(string $haslo)
    {
        return password_hash($haslo, PASSWORD_BCRYPT);
    }
}