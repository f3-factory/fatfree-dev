<?php


beforeEach(function () {
    $this->web = \F3\Web::instance();
    $this->f3->set('UI', 'ui/');
});

it('converts string to URL-friendly slug', function () {
    $text = 'Ñõw is the tîme~for all good mên. to cóme! to the aid 0f-thëir_côuntry';
    $expected = 'now-is-the-time-for-all-good-men-to-come-to-the-aid-0f-their-country';
    expect($this->web->slug($text))->toBe($expected);
});

it('auto-detects MIME type using file extension', function ($file, $mime) {
    expect($this->web->mime($file))->toBe($mime);
})->with([
    ['test.html', 'text/html'],
    ['xyz.htm', 'text/html'],
    ['nude.jpeg', 'image/jpeg'],
    ['sexy.jpg', 'image/jpeg'],
]);

it('sends file with rate limiting', function () {
    $file = $this->f3->get('UI').'images/wallpaper.jpg';
    $kbps = 256;
    $now = microtime(true);
    ob_start();
    $this->web->send($file, null, $kbps, false, null, false);
    $out = ob_get_clean();
    header_remove('Content-Type');

    $elapsed = microtime(true) - $now;
    $size = filesize($file) / 1024;
    expect($elapsed)->toBeGreaterThan($size / $kbps);
});

it('receives file upload via PUT', function () {
    $this->f3->set('UPLOADS', $this->f3->get('TEMP'));
    $file = $this->f3->get('UI').'images/wallpaper.jpg';

    $this->f3->route('PUT /upload/@filename', function () {
        $this->web->receive();
    });

    $this->f3->mock('PUT /upload/'.basename($file), null, null, $this->f3->read($file));
    $target = $this->f3->get('UPLOADS').basename($file);

    expect(is_file($target))->toBeTrue();
    @unlink($target);
});

it('determines acceptable MIME types', function () {
    $_SERVER['HTTP_ACCEPT'] =
        'application/xml;q=0.1, text/html; q=0.5, text/*; q=0.01 , application/json;q=0, text/html;level=2, text/html;level=1;q=0.5,application/xhtml+xml ; q=0.1';

    expect($this->web->acceptable())
        ->toEqual([
            'text/html;level=2' => 1,
            'text/html;level=1' => 0.5,
            'text/html' => 0.5,
            'application/xml' => 0.1,
            'application/xhtml+xml' => 0.1,
            'text/*' => 0.01,
            'application/json' => 0,
        ])
        ->and(
            $this->web->acceptable(
                ['text/html', 'text/html;level=1', 'text/html;level=2', 'text/html;level=3'],
            ),
        )->toBe('text/html;level=2')
        ->and($this->web->acceptable('image/jpeg'))->toBeFalse()
        ->and($this->web->acceptable('application/json'))->toBeFalse()
        ->and($this->web->acceptable('text/javascript'))->toBe('text/javascript');
});

it('minifies CSS', function () {
    $this->f3->set('CACHE', true);
    $expected =
        'html,body,div,span,applet,object,iframe,h1,h2,h3,h4,h5,h6,p,blockquote,pre,a,abbr,acronym,address,big,cite,code,del,dfn,em,img,ins,kbd,q,s,samp,small,strike,strong,sub,sup,tt,var,b,u,i,center,dl,dt,dd,ol,ul,li,fieldset,form,label,legend,table,caption,tbody,tfoot,thead,tr,th,td,article,aside,canvas,details,embed,figure,figcaption,footer,header,hgroup,menu,nav,output,ruby,section,summary,time,mark,audio,video{margin:0;padding:0;border:0;font-size:100%;font:inherit;vertical-align:baseline}article,aside,details,figcaption,figure,footer,header,hgroup,menu,nav,section{display:block}body{line-height:1}ol,ul{list-style:none}blockquote,q{quotes:none}blockquote:before,blockquote:after,q:before,q:after{content:\'\';content:none}table{border-collapse:collapse;border-spacing:0}div *{text-align:center}#content{border:1px #000 solid;text-shadow:#ccc -1px -1px 0px}tr:nth-child(odd) td{line-height:1.2em}h1[name] span{font-size:12pt}.sprite{background:url(./test.jpg) no-repeat}@media(min-width:768px) and (max-width:979px){body{background:green}}.widget>div :first-child{margin-top:0}.widget>div:first-child{margin-top:0}';
    expect($this->web->minify('css/simple.css', null, false))->toBe($expected);
});

it('minifies Javascript', function () {
    $this->f3->set('CACHE', true);
    $expected = \F3\View::instance()->render('js/underscore.min.js');
    expect($this->web->minify('js/underscore.js', null, false))->toBe($expected);
});

it('minifies tricky JS', function () {
    $this->f3->set('CACHE', true);
    $expected =
        '(this.id="ui-id-"+ ++a);var a=5;var b="test"+ ++a;var tmpl=`xxx ${firstName} ${lastName} xxx`;';
    expect($this->web->minify('js/operators.js', null, false))->toBe($expected);
});

it('minifies from multiple UI paths', function () {
    $this->f3->set('CACHE', true);
    $this->f3->UI = 'ui2/,ui/';
    $min = $this->web->minify('css/theme.css,css/simple.css', null, false);
    expect($min)
        ->toContain('html{height:100vh;')
        ->and($min)->not->toContain('html{height:100%;');
});

it('performs HTTP requests using different engines', function ($wrapper) {
    if ($wrapper === 'curl' && extension_loaded('curl') ||
        $wrapper === 'stream' && ini_get('allow_url_fopen') ||
        $wrapper === 'socket' && function_exists('fsockopen'))
    {
        $this->web->engine($wrapper);
        $url = 'http://www.google.com/';
        $req = $this->web->request($url);

        expect($req)->not
            ->toBeFalse()
            ->and(strtolower($req['engine']))->toBe(strtolower($wrapper));

        // local resource
        $reqLocal = $this->web->request('pingback2?page=pingback/client');
        expect($reqLocal)->not->toBeFalse();

        // RSS feed
        $rssUrl = 'https://wordpress.org/news/feed/';
        $rss = $this->web->rss($rssUrl);
        expect($rss)->toBeArray();
    } else {
        $this->markTestSkipped("Wrapper $wrapper not supported");
    }
})->with(['curl', 'stream', 'socket']);

it('performs WHOIS lookups', function () {
    $whois = $this->web->whois('sourceforge.net');
    expect($whois)->not->toBeFalse();
});

it('generates filler text', function () {
    $fillerStd = $this->web->filler(3);
    expect($fillerStd)
        ->toBeString()
        ->and(strlen($fillerStd))->toBeGreaterThan(0);

    $fillerRandom = $this->web->filler(5, 20, false);
    expect($fillerRandom)
        ->toBeString()
        ->and(strlen($fillerRandom))->toBeGreaterThan(0);
});

afterEach(function () {
    $this->f3->clear('CACHE');
});