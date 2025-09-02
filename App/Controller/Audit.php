<?php

namespace App\Controller;

class Audit extends BaseController
{

    function get($f3)
    {
        $test = new \F3\Test();
        $test->expect(
            is_null($f3->get('ERROR')),
            'No errors expected at this point',
        );
        $audit = new \F3\Audit();
        $test->expect(
            !$audit->url('http://www.example.com/space here.html') &&
            $audit->url('http://www.example.com/space%20here.html'),
            'URL',
        );
        $test->expect(
            !$audit->url('javascript://comment%0Aalert(1)') &&
            !$audit->url('php://foo/bar'),
            'URL XSS-check',
        );
        $test->expect(
            !$audit->email('Abc.google.com', false) &&
            !$audit->email('Abc.@google.com', false) &&
            !$audit->email('Abc..123@google.com', false) &&
            !$audit->email('A@b@c@google.com', false) &&
            !$audit->email('a"b(c)d,e:f;g<h>i[j\k]l@google.com', false) &&
            !$audit->email('just"not"right@google.com', false) &&
            !$audit->email('this is"not\allowed@google.com', false) &&
            !$audit->email('this\ still\"not\\allowed@google.com', false) &&
            $audit->email('niceandsimple@google.com', false) &&
            $audit->email('very.common@google.com', false) &&
            $audit->email('a.little.lengthy.but.fine@google.com', false) &&
            $audit->email('disposable.email.with+symbol@google.com', false) &&
            $audit->email('user@[IPv6:2001:db8:1ff::a0b:dbd0]', false) &&
            $audit->email('"very.unusual.@.unusual.com"@google.com', false) &&
            $audit->email('!#$%&\'*+-/=?^_`{}|~@google.com', false) &&
            $audit->email('""@google.com', false),
            'E-mail address',
        );
        $test->expect(
            !$audit->email('Abc.google.com') &&
            !$audit->email('Abc.@google.com') &&
            !$audit->email('Abc..123@google.com') &&
            !$audit->email('A@b@c@google.com') &&
            !$audit->email('a"b(c)d,e:f;g<h>i[j\k]l@google.com') &&
            !$audit->email('just"not"right@google.com') &&
            !$audit->email('this is"not\allowed@google.com') &&
            !$audit->email('this\ still\"not\\allowed@google.com') &&
            $audit->email('niceandsimple@google.com') &&
            $audit->email('very.common@google.com') &&
            $audit->email('a.little.lengthy.but.fine@google.com') &&
            $audit->email('disposable.email.with+symbol@google.com') &&
            $audit->email('user@[IPv6:2001:db8:1ff::a0b:dbd0]', false) &&
            $audit->email('"very.unusual.@.unusual.com"@google.com') &&
            $audit->email('!#$%&\'*+-/=?^_`{}|~@google.com') &&
            $audit->email('""@google.com'),
            'E-mail address (with domain verification)',
        );
        $test->expect(
            !$audit->ipv4('') &&
            !$audit->ipv4('...') &&
            !$audit->ipv4('hello, world') &&
            !$audit->ipv4('256.256.0.0') &&
            !$audit->ipv4('255.255.255.') &&
            !$audit->ipv4('.255.255.255') &&
            !$audit->ipv4('172.300.256.100') &&
            $audit->ipv4('30.88.29.1') &&
            $audit->ipv4('192.168.100.48'),
            'IPv4 address',
        );
        $test->expect(
            !$audit->ipv6('') &&
            !$audit->ipv6('FF01::101::2') &&
            !$audit->ipv6('::1.256.3.4') &&
            !$audit->ipv6('2001:DB8:0:0:8:800:200C:417A:221') &&
            !$audit->ipv6('FF02:0000:0000:0000:0000:0000:0000:0000:0001') &&
            $audit->ipv6('::') &&
            $audit->ipv6('::1') &&
            $audit->ipv6('2002::') &&
            $audit->ipv6('::ffff:192.0.2.128') &&
            $audit->ipv6('0:0:0:0:0:0:0:1') &&
            $audit->ipv6('2001:DB8:0:0:8:800:200C:417A'),
            'IPv6 address',
        );
        $test->expect(
            !$audit->isPrivate('0.1.2.3') &&
            !$audit->isPrivate('201.176.14.4') &&
            $audit->isPrivate('fc00::') &&
            $audit->isPrivate('10.10.10.10') &&
            $audit->isPrivate('172.16.93.7') &&
            $audit->isPrivate('192.168.3.5'),
            'Local IP range',
        );
        $test->expect(
            !$audit->isReserved('193.194.195.196') &&
            $audit->isReserved('::1') &&
            $audit->isReserved('127.0.0.1') &&
            $audit->isReserved('0.1.2.3') &&
            $audit->isReserved('169.254.1.2') &&
            !$audit->isReserved('192.0.2.1') &&
            !$audit->isReserved('224.225.226.227') &&
            $audit->isReserved('240.241.242.243'),
            'Reserved IP range',
        );
        $type = 'American Express';
        $test->expect(
            $audit->card('378282246310005') == $type &&
            $audit->card('371449635398431') == $type &&
            $audit->card('378734493671000') == $type,
            $type,
        );
        $type = 'Diners Club';
        $test->expect(
            $audit->card('30569309025904') == $type &&
            $audit->card('38520000023237') == $type,
            $type,
        );
        $type = 'Discover';
        $test->expect(
            $audit->card('6011111111111117') == $type &&
            $audit->card('6011000990139424') == $type,
            $type,
        );
        $type = 'JCB';
        $test->expect(
            $audit->card('3530111333300000') == $type &&
            $audit->card('3566002020360505') == $type,
            $type,
        );
        $type = 'MasterCard';
        $test->expect(
            $audit->card('5555555555554444') == $type &&
            $audit->card('2221000010000015') == $type &&
            $audit->card('5105105105105100') == $type,
            $type,
        );
        $type = 'Visa';
        $test->expect(
            $audit->card('4222222222222') == $type &&
            $audit->card('4111111111111111') == $type &&
            $audit->card('4012888888881881') == $type,
            $type,
        );
        $test->message('isdesktop: '.$f3->stringify($audit->isDesktop()));
        $test->message('ismobile: '.$f3->stringify($audit->isMobile()));
        $test->expect(
            $audit->mac('52:74:F2:B1:A8:7F') &&
            $audit->mac('3B:7C:9D:FF:FE:4E:8A:1C') &&
            $audit->mac('A3-56-78-9A-BC-DE') &&
            $audit->mac('4F5E.6D7C.8B9A') &&
            !$audit->mac('52:74:F2:B1:A8') &&
            !$audit->mac('6C:60:8C:D3:4F:EA:77') &&
            !$audit->mac('6C:60:8C:D3:4F:GA') &&
            !$audit->mac('52:74:F2:B1:A8:') &&
            !$audit->mac('52:74:F2:B1:A8:7F:ZZ') &&
            !$audit->mac('52:74:F2:B1:A8:7F:89:12') &&
            !$audit->mac('52:74::B1:A8:7F') &&
            !$audit->mac('52::F2:B1:A8:7F') &&
            !$audit->mac('00:14:22:ff:ef:01:23:45') &&
            !$audit->mac('00:14:ff:22:fe:01:23:45') &&
            !$audit->mac('00:22:14:ff:01:fe:23:45') &&
            !$audit->mac('6C:60:8C-D3:4F:EA') &&
            !$audit->mac('52:74:F2.B1:A8:7F'),
            'MAC address',
        );
        $f3->set('results', $test->results());
    }

}
