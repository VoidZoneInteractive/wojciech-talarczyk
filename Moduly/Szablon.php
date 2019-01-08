<?php

namespace Moduly;

class Szablon
{
    public function zwrocSzablon($nazwa_szablonu)
    {
        $szablon = file_get_contents(dirname(__FILE__) . '/../Szablony/' . $nazwa_szablonu . '.html');

        return $szablon;
    }
}