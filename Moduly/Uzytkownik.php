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

    public function uzytkownikJestTrenerem() {
        $uzytkownik = $this->pobierzUzytkownikaZSesji();

        return $uzytkownik && $uzytkownik['id_trenera'];
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
            if (!empty($uzytkownik['id_trenera'])) {
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

    public function zarejestrujUzytkownika(string $imie, string $nazwisko, string $login, string $haslo)
    {
        $haslo = $this->szyfrujHaslo($haslo);

        try {
            $this->bazaDanych->dodajUzytkownikaDoBazy($imie, $nazwisko, $login, $haslo);
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
        return password_hash($haslo, PASSWORD_ARGON2I);
    }
}