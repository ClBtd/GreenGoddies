<?php

namespace App\Utils;

use Symfony\Component\String\Slugger\AsciiSlugger;

class Functions {

    function normalizeString(string $string): string
    {
        $slugger = new AsciiSlugger();
        return $slugger->slug($string)->lower()->toString();
    }

}

