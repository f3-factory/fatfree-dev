<?php

namespace App\Controller;

class Pingback2
{
    public function get($f3): string
    {
        return \F3\View::instance()->render($f3->get('GET.page').'.htm');
    }
}
