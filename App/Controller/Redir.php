<?php

namespace App\Controller;

class Redir
{
    function get($f3): void
    {
        $tmp = $f3->get('TEMP');
        if (!is_dir($tmp))
            mkdir($tmp);
        $f3->write($tmp.'redir', microtime(true));
        $f3->reroute('/router');
    }

}
