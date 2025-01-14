<?php

namespace App\Utils;

use Symfony\Component\String\Slugger\AsciiSlugger;

class Functions {

    // Enlever les accents et caractères spéciaux d'une chaine de caractère
    static function normalizeString(string $string): string
    {
        $slugger = new AsciiSlugger();
        return $slugger->slug($string)->lower()->toString();
    }

}

