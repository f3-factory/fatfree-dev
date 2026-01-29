<?php

describe('Log', function () {
    
    beforeEach(function () {
        $tmp = $this->f3->get('TEMP');
        $this->f3->LOGS = $tmp;
        $name = 'test.log';
        $this->file = $tmp.$name;
        $this->log = new \F3\Log($name);
    });

    it('creates a log file and writes to it', function () {
        $this->log->write('foo');
        $contents = file($this->file);
        expect(count($contents))->toBe(1)
            ->and($contents[0])->toContain('foo');

        $this->log->write('bar');
        $contents = file($this->file);
        expect(count($contents))->toBe(2)
            ->and($contents[1])->toContain('bar');

        $this->log->write('baz');
        $contents = file($this->file);
        expect(count($contents))->toBe(3)
            ->and($contents[2])->toContain('baz');
    });
    
    it('logs forwarded-IP', function() {
        $this->f3->SERVER['REMOTE_ADDR'] = '127.0.0.1';
        $this->f3->set('HEADERS.X-Forwarded-For', '123.123.123.123');
        $this->log->write('narf');
        $contents = file($this->file);
        expect(array_last($contents))
            ->toContain('123.123.123.123');
    });

    afterEach(function () {
        $this->log->erase();
        expect(is_file($this->file))->toBeFalse();
    });
});
