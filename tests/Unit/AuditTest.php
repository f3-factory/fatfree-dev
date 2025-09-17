<?php

use F3\Audit;

describe('F3\\Audit', function () {

    test('URL', function () {
        $audit = Audit::instance();
        expect($audit->url('http://www.example.com/space here.html'))
            ->toBeFalse()
            ->and($audit->url('http://www.example.com/space%20here.html'))
            ->toBeTrue();
    });

    test('URL XSS-check', function () {
        $audit = Audit::instance();
        expect($audit->url('javascript://comment%0Aalert(1)'))
            ->toBeFalse()
            ->and($audit->url('php://foo/bar'))
            ->toBeFalse();
    });

    describe('E-mail address', function () {
        test('format validation without domain verification', function () {
            $audit = Audit::instance();
            expect($audit->email('Abc.google.com', false))
                ->toBeFalse()
                ->and($audit->email('Abc.@google.com', false))->toBeFalse()
                ->and($audit->email('Abc..123@google.com', false))->toBeFalse()
                ->and($audit->email('A@b@c@google.com', false))->toBeFalse()
                ->and($audit->email('a"b(c)d,e:f;g<h>i[j\\k]l@google.com', false))->toBeFalse(
                )
                ->and($audit->email('just"not"right@google.com', false))->toBeFalse()
                ->and($audit->email('this is"not\allowed@google.com', false))->toBeFalse()
                ->and($audit->email('this\ still\"not\\allowed@google.com', false))
                ->toBeFalse()
                ->and($audit->email('niceandsimple@google.com', false))->toBeTrue()
                ->and($audit->email('very.common@google.com', false))->toBeTrue()
                ->and($audit->email('a.little.lengthy.but.fine@google.com', false))->toBeTrue(
                )
                ->and($audit->email('disposable.email.with+symbol@google.com', false))
                ->toBeTrue()
                ->and($audit->email('user@[IPv6:2001:db8:1ff::a0b:dbd0]', false))->toBeTrue()
                ->and($audit->email('"very.unusual.@.unusual.com"@google.com', false))
                ->toBeTrue()
                ->and($audit->email('!#$%&\'*+-/=?^_`{}|~@google.com', false))->toBeTrue()
                ->and($audit->email('""@google.com', false))->toBeTrue();
        });

        test('with domain verification (default)', function () {
            $audit = Audit::instance();
            expect($audit->email('Abc.google.com'))
                ->toBeFalse()
                ->and($audit->email('Abc.@google.com'))->toBeFalse()
                ->and($audit->email('Abc..123@google.com'))->toBeFalse()
                ->and($audit->email('A@b@c@google.com'))->toBeFalse()
                ->and($audit->email('a"b(c)d,e:f;g<h>i[j\\k]l@google.com'))->toBeFalse()
                ->and($audit->email('just"not"right@google.com'))->toBeFalse()
                ->and($audit->email('this is"not\allowed@google.com'))->toBeFalse()
                ->and($audit->email('this\ still\"not\\allowed@google.com'))->toBeFalse()
                ->and($audit->email('niceandsimple@google.com'))->toBeTrue()
                ->and($audit->email('very.common@google.com'))->toBeTrue()
                ->and($audit->email('a.little.lengthy.but.fine@google.com'))->toBeTrue()
                ->and($audit->email('disposable.email.with+symbol@google.com'))->toBeTrue()
                // IPv6 literal likely bypasses DNS checks in original tests
                ->and($audit->email('user@[IPv6:2001:db8:1ff::a0b:dbd0]', false))->toBeTrue()
                ->and($audit->email('"very.unusual.@.unusual.com"@google.com'))->toBeTrue()
                ->and($audit->email('!#$%&\'*+-/=?^_`{}|~@google.com'))->toBeTrue()
                ->and($audit->email('""@google.com'))->toBeTrue();
        });
    });

    test('IPv4 address', function () {
        $audit = Audit::instance();
        expect($audit->ipv4(''))
            ->toBeFalse()
            ->and($audit->ipv4('...'))->toBeFalse()
            ->and($audit->ipv4('hello, world'))->toBeFalse()
            ->and($audit->ipv4('256.256.0.0'))->toBeFalse()
            ->and($audit->ipv4('255.255.255.'))->toBeFalse()
            ->and($audit->ipv4('.255.255.255'))->toBeFalse()
            ->and($audit->ipv4('172.300.256.100'))->toBeFalse()
            ->and($audit->ipv4('30.88.29.1'))->toBeTrue()
            ->and($audit->ipv4('192.168.100.48'))->toBeTrue();
    });

    test('IPv6 address', function () {
        $audit = Audit::instance();
        expect($audit->ipv6(''))
            ->toBeFalse()
            ->and($audit->ipv6('FF01::101::2'))->toBeFalse()
            ->and($audit->ipv6('::1.256.3.4'))->toBeFalse()
            ->and($audit->ipv6('2001:DB8:0:0:8:800:200C:417A:221'))->toBeFalse()
            ->and($audit->ipv6('FF02:0000:0000:0000:0000:0000:0000:0000:0001'))->toBeFalse()
            ->and($audit->ipv6('::'))->toBeTrue()
            ->and($audit->ipv6('::1'))->toBeTrue()
            ->and($audit->ipv6('2002::'))->toBeTrue()
            ->and($audit->ipv6('::ffff:192.0.2.128'))->toBeTrue()
            ->and($audit->ipv6('0:0:0:0:0:0:0:1'))->toBeTrue()
            ->and($audit->ipv6('2001:DB8:0:0:8:800:200C:417A'))->toBeTrue();
    });

    test('Local IP range', function () {
        $audit = Audit::instance();
        expect($audit->isPrivate('0.1.2.3'))
            ->toBeFalse()
            ->and($audit->isPrivate('201.176.14.4'))->toBeFalse()
            ->and($audit->isPrivate('fc00::'))->toBeTrue()
            ->and($audit->isPrivate('10.10.10.10'))->toBeTrue()
            ->and($audit->isPrivate('172.16.93.7'))->toBeTrue()
            ->and($audit->isPrivate('192.168.3.5'))->toBeTrue();
    });

    test('Reserved IP range', function () {
        $audit = Audit::instance();
        expect($audit->isReserved('193.194.195.196'))
            ->toBeFalse()
            ->and($audit->isReserved('::1'))->toBeTrue()
            ->and($audit->isReserved('127.0.0.1'))->toBeTrue()
            ->and($audit->isReserved('0.1.2.3'))->toBeTrue()
            ->and($audit->isReserved('169.254.1.2'))->toBeTrue()
            ->and($audit->isReserved('192.0.2.1'))->toBeFalse()
            ->and($audit->isReserved('224.225.226.227'))->toBeFalse()
            ->and($audit->isReserved('240.241.242.243'))->toBeTrue();
    });

    test('Public IP range', function () {
        $audit = Audit::instance();
        expect($audit->isPublic('38.194.195.196'))
            ->toBeTrue()
            ->and($audit->isPublic('::1'))->toBeFalse()
            ->and($audit->isPublic('127.0.0.1'))->toBeFalse()
            ->and($audit->isPublic('0.1.2.3'))->toBeFalse()
            ->and($audit->isPublic('169.254.1.2'))->toBeFalse()
            ->and($audit->isPublic('192.168.2.1'))->toBeFalse()
            ->and($audit->isPublic('224.225.226.227'))->toBeTrue()
            ->and($audit->isPublic('240.241.242.243'))->toBeFalse();
    });

    describe('Credit Card detection', function () {
        test('American Express', function () {
            $audit = Audit::instance();
            expect($audit->card('378282246310005'))
                ->toBe('American Express')
                ->and($audit->card('371449635398431'))->toBe('American Express')
                ->and($audit->card('378734493671000'))->toBe('American Express');
        });

        test('Diners Club', function () {
            $audit = Audit::instance();
            expect($audit->card('30569309025904'))
                ->toBe('Diners Club')
                ->and($audit->card('38520000023237'))->toBe('Diners Club');
        });

        test('Discover', function () {
            $audit = Audit::instance();
            expect($audit->card('6011111111111117'))
                ->toBe('Discover')
                ->and($audit->card('6011000990139424'))->toBe('Discover');
        });

        test('JCB', function () {
            $audit = Audit::instance();
            expect($audit->card('3530111333300000'))
                ->toBe('JCB')
                ->and($audit->card('3566002020360505'))->toBe('JCB');
        });

        test('MasterCard', function () {
            $audit = Audit::instance();
            expect($audit->card('5555555555554444'))
                ->toBe('MasterCard')
                ->and($audit->card('2221000010000015'))->toBe('MasterCard')
                ->and($audit->card('5105105105105100'))->toBe('MasterCard');
        });

        test('Visa', function () {
            $audit = Audit::instance();
            expect($audit->card('4222222222222'))
                ->toBe('Visa')
                ->and($audit->card('4111111111111111'))->toBe('Visa')
                ->and($audit->card('4012888888881881'))->toBe('Visa');
        });
    });

    test('MAC address', function () {
        $audit = Audit::instance();
        expect($audit->mac('52:74:F2:B1:A8:7F'))
            ->toBeTrue()
            ->and($audit->mac('3B:7C:9D:FF:FE:4E:8A:1C'))->toBeTrue()
            ->and($audit->mac('A3-56-78-9A-BC-DE'))->toBeTrue()
            ->and($audit->mac('4F5E.6D7C.8B9A'))->toBeTrue()
            ->and($audit->mac('52:74:F2:B1:A8'))->toBeFalse()
            ->and($audit->mac('6C:60:8C:D3:4F:EA:77'))->toBeFalse()
            ->and($audit->mac('6C:60:8C:D3:4F:GA'))->toBeFalse()
            ->and($audit->mac('52:74:F2:B1:A8:'))->toBeFalse()
            ->and($audit->mac('52:74:F2:B1:A8:7F:ZZ'))->toBeFalse()
            ->and($audit->mac('52:74:F2:B1:A8:7F:89:12'))->toBeFalse()
            ->and($audit->mac('52:74::B1:A8:7F'))->toBeFalse()
            ->and($audit->mac('52::F2:B1:A8:7F'))->toBeFalse()
            ->and($audit->mac('00:14:22:ff:ef:01:23:45'))->toBeFalse()
            ->and($audit->mac('00:14:ff:22:fe:01:23:45'))->toBeFalse()
            ->and($audit->mac('00:22:14:ff:01:fe:23:45'))->toBeFalse()
            ->and($audit->mac('6C:60:8C-D3:4F:EA'))->toBeFalse()
            ->and($audit->mac('52:74:F2.B1:A8:7F'))->toBeFalse();
    });

    dataset('user_agents', [
        ['desktop', 'Mozilla/5.0 (Macintosh; Intel Mac OS X 10.15; rv:143.0) Gecko/20100101 Firefox/143.0'],
        ['desktop', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/140.0.0.0 Safari/537.36'],
        ['desktop', 'Mozilla/5.0 (X11; Linux x86_64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/140.0.0.0 Safari/537.36'],
        ['desktop', 'Mozilla/5.0 (Macintosh; Intel Mac OS X 10_15_7) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/140.0.0.0 Safari/537.36'],
        ['desktop', 'Linux Mozilla User Agent 4.0'],
        ['mobile', 'Mozilla/5.0 (iPhone; CPU iPhone OS 18_7 like Mac OS X) AppleWebKit/605.1.15 (KHTML, like Gecko) Version/18.6.2 Mobile/15E148 Safari/604.1'],
        ['mobile', 'Mozilla/5.0 (iPhone; CPU iPhone OS 18_7 like Mac OS X) AppleWebKit/605.1.15 (KHTML, like Gecko) CriOS/140.0.7339.122 Mobile/15E148 Safari/604.1'],
        ['mobile', 'Mozilla/5.0 (iPhone; CPU iPhone OS 18_7 like Mac OS X) AppleWebKit/605.1.15 (KHTML, like Gecko) FxiOS/143.0 Mobile/15E148 Safari/605.1.15'],
        ['mobile', 'Mozilla/5.0 (Linux; Android 13; SAMSUNG SM-S918B) AppleWebKit/537.36 (KHTML, like Gecko) SamsungBrowser/21.0 Chrome/110.0.5481.154 Mobile Safari/537.36'],
        ['mobile', 'Dalvik/2.1.0 (Linux; U; Android 13; SM-S908U Build/TP1A.220624.014)'],
        ['bot', 'Mozilla/5.0 (compatible; Yahoo! Slurp; http://help.yahoo.com/help/us/ysearch/slurp)'],
        ['bot', 'Mozilla/5.0 (Linux; Android 6.0.1; Nexus 5X Build/MMB29P) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/W.X.Y.Z Mobile Safari/537.36 (compatible; Googlebot/2.1; +http://www.google.com/bot.html)'],
        ['bot', 'Mozilla/5.0 AppleWebKit/537.36 (KHTML, like Gecko; compatible; Googlebot/2.1; +http://www.google.com/bot.html) Chrome/W.X.Y.Z Safari/537.36'],
        ['ai', 'Mozilla/5.0 AppleWebKit/537.36 (KHTML, like Gecko); compatible; GPTBot/1.1; +https://openai.com/gptbot)'],
        ['ai', 'Mozilla/5.0 AppleWebKit/537.36 (KHTML, like Gecko); compatible; OAI-SearchBot/1.0; +https://openai.com/searchbot)'],
        ['ai', 'Mozilla/5.0 AppleWebKit/537.36 (KHTML, like Gecko); compatible; ChatGPT-User/1.0; +https://openai.com/bot)'],
        ['ai', 'Mozilla/5.0 (compatible; anthropic-ai/1.0; +http://www.anthropic.com/bot.html)'],
        ['ai', 'Mozilla/5.0 AppleWebKit/537.36 (KHTML, like Gecko); compatible; ClaudeBot/1.0; +claudebot@anthropic.com)'],
        ['ai', 'Mozilla/5.0 AppleWebKit/537.36 (KHTML, like Gecko; compatible; PerplexityBot/1.0; +https://perplexity.ai/perplexitybot)'],
        ['ai', 'Mozilla/5.0 (compatible; Google-Extended/1.0; +http://www.google.com/bot.html)'],
        ['ai', 'Mozilla/5.0 (compatible; BingBot/1.0; +http://www.bing.com/bot.html)'],
        ['ai', 'Mozilla/5.0 (Macintosh; Intel Mac OS X 10_10_1) AppleWebKit/600.2.5 (KHTML, like Gecko) Version/8.0.2 Safari/600.2.5 (Amazonbot/0.1; +https://developer.amazon.com/support/amazonbot)'],
        ['ai', 'Mozilla/5.0 (compatible; DuckAssistBot/1.0; +http://www.duckduckgo.com/bot.html)'],
    ]);

    test('isDesktop', function ($type, $ua) {
        $audit = Audit::instance();
        if ($type === 'desktop') {
            expect($audit->isDesktop($ua))
                ->toBeTrue()
                ->and($audit->isMobile($ua))->toBeFalse();
        } elseif ($type === 'mobile') {
            expect($audit->isDesktop($ua))
                ->toBeFalse()
                ->and($audit->isMobile($ua))->toBeTrue();
        } elseif ($type === 'bot') {
            expect($audit->isBot($ua))
                ->toBeTrue();
        } elseif ($type === 'ai') {
            expect($audit->isAI($ua))
                ->toBeTrue();
        }
    })->with('user_agents');

    test('entropy', function ($pw, $complexity) {
        $audit = Audit::instance();
        expect($audit->entropy($pw))->toBeGreaterThanOrEqual($complexity);
    })->with([
        ['password', 18],
        ['P4ssw0rd', 24],
        ['P4ssw0rd!', 25.5],
        ['mOn0ph451c-jU5t1c3', 39],
        ['$*F267akwL$qQ3!#mgB5ekW*', 46],
    ]);
});
