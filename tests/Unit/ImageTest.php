<?php

test($t1='extension loaded', function () {
    expect(extension_loaded('gd'))->toBeTrue();
});

describe('Image', function () {
    beforeEach(function () {
        $file = 'images/south-park.jpg';
        $this->f3->UI = 'ui/';
        $this->img = new \F3\Image($file, true);
    });

    test('image resource', function () {
        $res = $this->img->data();
        expect($res)
            ->toBeInstanceOf(\Gdimage::class)
            ->and(imagesx($this->img->data()))
            ->toBe($this->img->width());
    });

    test('rgb conversion', function ($value, $expected) {
        expect($this->img->rgb($value))
            ->toBe($expected);
    })->with([
        [0x000000, [0, 0, 0]],
        [0xFFFFFF, [255, 255, 255]],
        [0x00FF00, [0, 255, 0]],
        [0x999, [153, 153, 153]],
        ['FF3344', [255, 51, 68]],
        [0x336699A0, [51, 102, 153, 160]],
        ['336699A0', [51, 102, 153, 160]],
    ]);

    it('dumps image to base64-encoded data URI', function () {
        $src = $this->img->dump();
        expect($src)->toMatchSnapshot();
    });

    it('detects image width + height', function () {
        expect($this->img->width().'x'.$this->img->height())
            ->toMatchSnapshot();
    });

    test('horizontal flip', function () {
        $src = $this->img->hflip()->dump();
        expect($src)->toMatchSnapshot();
    });

    test('undo last action', function () {
        $state1 = $this->f3->base64($this->img->dump(), 'image/png');
        $state2 = $this->f3->base64($this->img->hflip()->undo()->dump(), 'image/png');
        expect($state1)->toBe($state2);
    });

    test('vertical flip', function () {
        $src = $this->img->vflip()->dump();
        expect($src)->toMatchSnapshot();
    });

    test('restore', function () {
        $state1 = $this->f3->base64($this->img->dump(), 'image/png');
        $state2 = $this->f3->base64($this->img->vflip()->undo()->invert()->sepia()->sketch()->restore()->dump(), 'image/png');
        expect($state1)->toBe($state2);
    });

    test('invert', function () {
        $src = $this->img->invert()->dump();
        expect($src)->toMatchSnapshot();
    });

    test('grayscale', function () {
        $src = $this->img->grayscale()->dump();
        expect($src)->toMatchSnapshot();
    });

    test('pixelate', function () {
        $src = $this->img->pixelate(10)->dump();
        expect($src)->toMatchSnapshot();
    });

    test('sketch', function () {
        $src = $this->img->sketch()->dump();
        expect($src)->toMatchSnapshot();
    });

    test('sepia', function () {
        $src = $this->img->sepia()->dump();
        expect($src)->toMatchSnapshot();
    });

    test('brightness', function () {
        $src = $this->img->brightness(150)->dump();
        expect($src)->toMatchSnapshot();
    });

    test('contrast', function () {
        $src = $this->img->contrast(-65)->dump();
        expect($src)->toMatchSnapshot();
    });

    test('smooth', function () {
        $src = $this->img->smooth(20)->dump();
        expect($src)->toMatchSnapshot();
    });

//    test('scatter', function () {
//        $src = $this->img->scatter(6, 10)->dump();
//        expect($src)->toMatchSnapshot();
//    });

    test('blur', function () {
        $src = $this->img->blur(false, 15)->dump();
        expect($src)->toMatchSnapshot();
    });

    test('emboss', function () {
        $src = $this->img->emboss()->dump();
        expect($src)->toMatchSnapshot();
    });

    test('crop', function () {
        $src = $this->img->crop(25, 25, 95, 95)->dump();
        expect($src)->toMatchSnapshot();
    });

    test('Resize (smaller)', function ($crop) {
        $src = $this->img->resize(120, 190, $crop)->dump();
        expect($src)->toMatchSnapshot();
    })->with([true, false]);

    test('Resize (larger)', function ($crop) {
        $src = $this->img->resize(200, 250, $crop)->dump();
        expect($src)->toMatchSnapshot();
    })->with([true, false]);

    test('Resize/crop horizontal', function () {
        $src = $this->img->resize(100, 90)->dump();
        expect($src)->toMatchSnapshot();
    });

    test('Resize/crop vertical', function () {
        $src = $this->img->resize(150, 90)->dump();
        expect($src)->toMatchSnapshot();
    });

    test('Rotate clockwise', function () {
        $src = $this->img->rotate(-90)->dump();
        expect($src)->toMatchSnapshot();
    });

    test('Rotate anti-clockwise', function () {
        $src = $this->img->rotate(90)->dump();
        expect($src)->toMatchSnapshot();
    });

    test('Image overlay', function () {
        $ovr = new \F3\Image('images/watermark.png');
        $ovr->resize(100, 38)->rotate(90);

        $src = $this->img->overlayImage($ovr, \F3\Image::POS_Right | \F3\Image::POS_Middle)->dump();
        expect($src)->toMatchSnapshot();
    });

    test('Image overlay, 50% transparency, manually aligned', function () {
        $ovr = new \F3\Image('images/watermark.png');
        $ovr->resize(100, 38)->rotate(45);

        $src = $this->img->overlayImage($ovr, [65, 25], 50)->dump();
        expect($src)->toMatchSnapshot();
    });

    test('Text overlay', function () {
        $src = $this->img->overlayText(
            string: 'FatFree',
            font: 'fonts/thunder.ttf',
        )->dump();
        expect($src)->toMatchSnapshot();
    });

    test('Text overlay, multiline', function () {
        $src = $this->img->overlayText(
            string: 'FatFree'."\n".'is awesome!',
            font: 'fonts/thunder.ttf',
            fontSize: 12,
            lineHeightRatio: 1.25,
            align: [20, 20],
            color: 'fff',
        )->dump();
        expect($src)->toMatchSnapshot();
    });

    test('Text overlay, options', function () {
        $src = $this->img->overlayText(
            string: 'FatFree',
            font: 'fonts/thunder.ttf',
            fontSize: 38,
            align: \F3\Image::POS_Left | \F3\Image::POS_Bottom,
            color: '250,250,250',
            shadow: 'FF0000',
            shadowArgs: [5, 5],
        )->dump();
        expect($src)->toMatchSnapshot();
    });

    test('convert format', function ($type) {
        $src = $this->img->dump($type);
        expect($src)->toMatchSnapshot();
    })->with(['png', 'jpeg', 'gif', 'wbmp']);

    test('dump with args', function () {
        $src = $this->img->dump('png', 7, PNG_ALL_FILTERS);
        expect($src)->toMatchSnapshot();
        $src = $this->img->dump('jpeg', 65);
        expect($src)->toMatchSnapshot();
    });

});

test('identicon', function ($nonce, $size, $blocks) {
    $img = new \F3\Image();
    expect($img->identicon($nonce, $size, $blocks)->dump())->toMatchSnapshot();
})->with([
    ['foo', 64, 4],
    ['bar', 64, 4],
    ['fatfree', 64, 4],
    ['foo', 64, 7],
    ['foo', 32, 4],
]);