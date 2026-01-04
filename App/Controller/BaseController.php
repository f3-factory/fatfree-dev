<?php

namespace App\Controller;

abstract class BaseController
{

    function beforeroute($f3)
    {
        $uri = $f3->PATH;
        if ($uri == '/router')
            $uri = '/redir';
        elseif (preg_match('/\/openid2\b/', $uri))
            $uri = '/openid';
        $f3->set('active', $f3->get('menu["'.$uri.'"]'));
    }

    function afterroute()
    {
        echo \F3\Preview::instance()->render('layout.htm');
    }

}
