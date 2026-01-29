<?php

beforeEach(function () {
    $this->md = new \F3\Markdown();
});

test('markdown', function ($case) {
    $txt = $this->f3->read('ui/markdown/'.$case.'.txt');
    expect(
        $this->md->convert($txt),
    )->toBe(
        $this->f3->read('ui/markdown/'.$case.'.htm'),
    );
})->with([
    'Code Blocks',
    'Blockquotes with code blocks',
    'Nested blockquotes',
    'Horizontal rules',
    'Ordered and unordered lists',
    'Code block in a list item',
    'Hard-wrapped paragraphs with list-like lines',
    'Tight blocks',
    'Tabs',
    'Tidyness',
    'Links, shortcut references',
    'Links, reference style',
    'Links, inline style',
    'Images',
    'Inline HTML (Simple)',
    'Inline HTML (Advanced)',
    'Inline HTML comments',
    'Code Spans',
    'Strong and em together',
    'Auto links',
    'Amps and angle encoding',
    'Backslash escapes',
    'Literal quotes in titles',
    'PHP-specific bugs',
    'Tricky combinations',
]);