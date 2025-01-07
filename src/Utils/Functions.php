<?php

namespace App\Utils;

use Symfony\Component\String\Slugger\AsciiSlugger;

class Functions {

    static function normalizeString(string $string): string
    {
        $slugger = new AsciiSlugger();
        return $slugger->slug($string)->lower()->toString();
    }

}

