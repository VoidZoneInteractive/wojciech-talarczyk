<?php

namespace Moduly;

class Kontroler
{
    private $nazwa_strony = null;
    private $szablon = null;

    public function __construct($nazwa_strony = null)
    {
        $this->nazwa_strony = $nazwa_strony;
        $this->szablon = new Szablon();
    }

    public function akcja()
    {
        switch ($this->nazwa_strony) {
            case 'logowanie':
                break;
            case 'rejestracja':
                return $this->rejestracja();
            case 'rejestracjaPodziekowanie':
                return $this->rejestracjaPodziekowanie();
            case 'stronaGlowna':
                return $this->stronaGlowna();
        }

        throw new \Exception(sprintf('Nie znaleziono strony %s', $this->nazwa_strony));
    }

    public function stronaGlowna()
    {
        $szablon = $this->szablon->zwrocSzablon($this->nazwa_strony);

        return $szablon;
    }

    public function rejestracja()
    {
        // Sprawdzamy czy ktos wyslal dane formularza
        if ($_SERVER['REQUEST_METHOD'] == 'POST') {
            try {
                $bazaDanych = new BazaDanych();
                $bazaDanych->dodajUzytkownikaDoBazy($_POST['login'], $_POST['haslo']);
            } catch (\Exception $e) {
                throw $e;
            }

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

    }

    public function rejestracjaPodziekowanie()
    {
        $szablon = $this->szablon->zwrocSzablon($this->nazwa_strony);

        return $szablon;
    }
}