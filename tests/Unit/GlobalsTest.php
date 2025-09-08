<?php

describe('Session', function () {
    test('No Session active by default', function () {
        expect(session_id())->toBeEmpty('No active session');
    });

    test($t1 = 'Session auto-started by set()', function () {
        $this->f3->set('SESSION[hello]', 'world');
        expect(session_id())
            ->not()->toBeEmpty()
            ->and($this->f3->SESSION['hello'])
            ->tobe('world')
            ->and($_SESSION['hello'])
            ->tobe('world');
    });

    test('Session destroyed by clear()', function () {
        $this->f3->set('SESSION[hello]', 'world');
        $this->f3->clear('SESSION');
        expect(session_id())
            ->toBeEmpty()
            ->and($_SESSION)
            ->toBeEmpty();
    })->depends($t1);

    test('Session not restarted without cookie', function () {
        $result = $this->f3->get('SESSION[hello]');
        expect(session_id())
            ->toBeEmpty()
            ->and(empty($_SESSION['hello']))
            ->toBeTrue()
            ->and($result)
            ->toBeNull();
    })->depends($t1);

    test('Session restarted with cookie', function () {
        $sId = session_create_id();
        $this->f3->set('COOKIE.'.session_name(), $sId);
        session_id($sId);

        expect(\session_status())
            ->toBe(PHP_SESSION_NONE)
            ->and($this->f3->get('SESSION[hello]'))->toBeNull()
            ->and(\session_status())->toBe(PHP_SESSION_ACTIVE)
            ->and(\session_id())->toBe($sId)
            ->and(empty($_SESSION['hello']))->toBeTrue();
    });

    test('No session variable instantiated by exists()', function () {
        $result = $this->f3->exists('SESSION.hello');
        expect(\session_id())
            ->toBe('')
            ->and($result)->toBeFalse()
            ->and(empty($_SESSION['hello']))->toBeTrue();
    });

    test('Specific session variable created/erased', function () {
        $this->f3->set('SESSION.foo', 'bar');
        $this->f3->set('SESSION.baz', 'qux');
        $this->f3->clear('SESSION.foo');
        $result = $this->f3->exists('SESSION.foo');
        expect(session_id())
            ->not()->toBeEmpty()
            ->and(empty($this->f3->SESSION['foo']))->toBeTrue()
            ->and(empty($this->f3->SESSION['baz']))->toBeFalse()
            ->and($result)->toBeFalse();

        $this->f3->clear('SESSION');
        expect(!session_id())
            ->toBeTrue('Session cleared')
            ->and($_SESSION)->toBeEmpty()
            ->and(empty($this->f3->SESSION))->toBeTrue();
    });
});

describe('GLOBALS', function() {

    test('PHP globals same as hive globals', function () {
        $ok = true;
        $list = '';
        foreach (explode('|', $this->f3::GLOBALS) as $global) {
            if ($GLOBALS['_'.$global] != $this->f3->get($global)) {
                $ok = false;
                $list .= ($list ? ',' : '').$global;
            }
        }
        expect($ok)->toBeTrue(($list ? (': '.$list) : ''));
    });

    test('Altering hive globals affects PHP globals', function () {
        $ok = true;
        $list = '';
        foreach (explode('|', $this->f3::GLOBALS) as $global) {
            if (!$this->f3->NONBLOCKING) {
                $this->f3->sync($global);
            }
            $this->f3->set($global.'.foo', 'bar');
            if ($GLOBALS['_'.$global] !== $this->f3->get($global)) {
                $ok = false;
                $list .= ($list ? ',' : '').$global;
            }
        }
        expect($ok)->toBeTrue(($list ? (': '.$list) : ''));
    });

    test('Altering PHP globals affects hive globals', function () {
        $ok = true;
        $list = '';
        foreach (explode('|', $this->f3::GLOBALS) as $global) {
            $this->f3->sync($global);
            $GLOBALS['_'.$global]['bar'] = 'foo';
            if ($GLOBALS['_'.$global] !== $this->f3->get($global)) {
                $ok = false;
                $list .= ($list ? ',' : '').$global;
            }
        }
        expect($ok)->toBeTrue(($list ? (': '.$list) : ''));
    });
});
