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
            if (!empty($uzytkownik['id_trenera'])) {
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