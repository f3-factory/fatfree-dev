<?php

namespace App\Controller;

use F3\Base;

class Globals extends BaseController
{

    function get(Base $f3)
    {
        $test = new \F3\Test;
        $test->expect(
            is_null($f3->get('ERROR')),
            'No errors expected at this point',
        );
        $test->expect(
            $package = $f3->get('PACKAGE'),
            'PACKAGE: '.$package,
        );
        $test->expect(
            PHP_SAPI,
            'PHP_SAPI: '.PHP_SAPI,
        );
        $test->expect(
            $version = $f3->get('VERSION'),
            'VERSION: '.$version,
        );
        $test->expect(
            is_dir($root = $f3->get('ROOT')),
            'ROOT (document root): '.$f3->stringify($root),
        );
        $test->expect(
            $ip = $f3->IP,
            'IP (Remote IP address): '.$f3->stringify($ip),
        );
        $test->expect(
            $realm = $f3->get('REALM'),
            'REALM (Full canonical URI): '.$f3->stringify($realm),
        );
        $test->expect(
            ($verb = $f3->get('VERB')) == $_SERVER['REQUEST_METHOD'],
            'VERB (request method): '.$f3->stringify($verb),
        );
        $test->expect(
            $scheme = $f3->get('SCHEME'),
            'SCHEME (Web protocol): '.$f3->stringify($scheme),
        );
        $test->expect(
            $scheme = $f3->get('HOST'),
            'HOST (Web host/domain): '.$f3->stringify($scheme),
        );
        $test->expect(
            $port = $f3->get('PORT'),
            'PORT (HTTP port): '.$port,
        );
        $test->expect(
            is_string($base = $f3->get('BASE')),
            'BASE (path to index.php relative to ROOT): '.
            $f3->stringify($base),
        );
        $test->expect(
            ($uri = $f3->get('URI')) == $f3->SERVER['REQUEST_URI'],
            'URI (request URI): '.$f3->stringify($uri).' - '.$f3->SERVER['REQUEST_URI'],
        );
        $test->expect(
            $agent = $f3->get('AGENT'),
            'AGENT (user agent): '.$f3->stringify($agent),
        );
        $test->expect(
            $pattern = $f3->get('PATTERN'),
            'PATTERN (matching route): '.$f3->stringify($pattern),
        );
        $test->expect(
            ($language = $f3->get('LANGUAGE')),
            'LANGUAGE: '.$f3->stringify($language),
        );
        $test->expect(
            true,
            'Locale loaded: '.$f3->stringify(setlocale(LC_ALL, 0)),
        );
        $test->expect(
            $tz = $f3->get('TZ'),
            'TZ (time zone): '.$f3->stringify($tz),
        );
        $test->expect(
            $serializer = $f3->get('SERIALIZER'),
            'SERIALIZER: '.$f3->stringify($serializer),
        );
        $f3->clear('SESSION');
        $test->expect(
            !session_id(),
            'No active session',
        );
        $f3->set('SESSION[hello]', 'world');
        $test->expect(
            session_id() && $_SESSION['hello'] == 'world',
            'Session auto-started by set()',
        );
        $f3->clear('SESSION');
        $test->expect(
            !session_id() && empty($_SESSION),
            'Session destroyed by clear()',
        );
        $result = $f3->get('SESSION[hello]');
        $test->expect(
            empty(session_id())
            && empty($_SESSION['hello'])
            && is_null($result)
            ,
            'Session not restarted when no cookie present !!!',
        );
        // restart session with custom id
        $sId = session_create_id();
        $f3->set('COOKIE.'.session_name(), $sId);
        session_id($sId);

        $pre = \session_status() === PHP_SESSION_NONE;
        $result = $f3->get('SESSION[hello]');
        $post = \session_status() === PHP_SESSION_ACTIVE;
        $test->expect(
            $pre && $post && session_id() === $sId && empty($_SESSION['hello']) && is_null($result),
            'Session restarted by get() when cookie is present',
        );
        $f3->clear('SESSION');
        $result = $f3->exists('SESSION.hello');
        $test->expect(
            empty(session_id()) && empty($_SESSION['hello']) && $result === false,
            'No session variable instantiated by exists()',
        );
        $f3->set('SESSION.foo', 'bar');
        $f3->set('SESSION.baz', 'qux');
        $f3->clear('SESSION.foo');
        $result = $f3->exists('SESSION.foo');
        $test->expect(
            session_id()
            && empty($f3->SESSION['foo'])
            && $result === false
            && !empty($f3->SESSION['baz']),
            'Specific session variable created/erased',
        );
        $f3->clear('SESSION');
        $test->expect(
            !session_id() && empty($_SESSION) && empty($f3->SESSION),
            'Session cleared',
        );
        $test->expect(
            true,
            'ReactorMode: '.var_export($f3->NONBLOCKING, true),
        );
        if ($f3->NONBLOCKING) {
            $ok = true;
            $list = '';
            foreach (explode('|', $f3::GLOBALS) as $global) {
                if ($global === 'SESSION')
                    continue;
                $bak = $GLOBALS['_'.$global];
                $f3->set($global.'.foo2.0', 'bar123');
                $GLOBALS['_'.$global]['ccc1'] = 'c';
                if ($GLOBALS['_'.$global] === $f3->get($global)) {
                    $ok = false;
                    $list .= ($list ? ',' : '').$global;
                }
                $GLOBALS['_'.$global] = $bak;
            }
            $test->expect(
                $ok,
                'ReactorMode: PHP globals are de-sync\'d'.
                ($list ? (': '.$list) : ''),
            );
        }

        if (!$f3->NONBLOCKING) {
            $ok = true;
            foreach ($f3->get('HEADERS') as $hdr => $val) {
                $key = 'HTTP_'.strtoupper(str_replace('-', '_', $hdr));
                if ($_SERVER[$key] != $val)
                    $ok = false;
            }
            $test->expect(
                $ok,
                'HTTP headers match HEADERS variable',
            );
        }

        if ($f3->exists('COOKIE.baz')) {
            $test->message('HTTP cookie retrieved');
            $f3->clear('COOKIE.baz');
        } else
            $test->expect(
                $f3->set('COOKIE.baz', 'qux'),
                'HTTP cookie sent',
            );
        $test->expect(
            $f3->rel(substr($f3->SERVER['REQUEST_URI'], 0, -strlen($f3->PATH)).'/hello/world') ==
            '/hello/world' &&
            $f3->rel($f3->get('BASE').'/hello/world') == '/hello/world',
            'Relative links',
        );
        $f3->set('results', $test->results());
    }

}
