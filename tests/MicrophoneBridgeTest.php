<?php

/**
 * Contract tests for the Microphone facade (which ships in nativephp/mobile)
 * against THIS plugin's bridge functions, driven through the NativePHP
 * FakeBridge. They pin the PHP → native contract: calling the facade fires
 * the right bridge function with the right payload and decodes native
 * responses as callers expect. Requires nativephp/mobile, so this file is
 * bound to the Testbench TestCase.
 *
 * Run with: ./test-plugins.sh --element microphone
 */

use Native\Mobile\Events\Microphone\MicrophoneCancelled;
use Native\Mobile\Events\Microphone\MicrophoneRecorded;
use Native\Mobile\Microphone;
use Native\Mobile\Testing\Native;
use Tests\TestCase;

uses(TestCase::class);

beforeEach(function () {
    $this->bridge = Native::fakeBridge();
});

describe('record()/start()', function () {
    it('fires Microphone.Start with the id and default MicrophoneRecorded event', function () {
        (new Microphone)->record()->id('rec-1')->start();

        $this->bridge->assertCalled('Microphone.Start', function (array $p) {
            expect($p['id'])->toBe('rec-1');
            expect($p['event'])->toBe(MicrophoneRecorded::class);

            return true;
        });
    });

    it('fires Microphone.Start with a custom event class via ->event()', function () {
        (new Microphone)->record()->id('rec-2')->event(MicrophoneCancelled::class)->start();

        $this->bridge->assertCalled('Microphone.Start', function (array $p) {
            expect($p['id'])->toBe('rec-2');
            expect($p['event'])->toBe(MicrophoneCancelled::class);

            return true;
        });
    });

    it('generates an id when none is supplied', function () {
        (new Microphone)->record()->start();

        $calls = $this->bridge->callsTo('Microphone.Start');
        expect($calls)->toHaveCount(1);
        expect($calls[0]['params']['id'])->not->toBeEmpty();
    });

    it('uses the supplied id verbatim', function () {
        (new Microphone)->record()->id('my-custom-id')->start();

        $this->bridge->assertCalled('Microphone.Start', function (array $p) {
            expect($p['id'])->toBe('my-custom-id');

            return true;
        });
    });

    it('throws when a non-existent event class is passed to ->event()', function () {
        expect(fn () => (new Microphone)->record()->event('Not\\A\\Real\\EventClass'))
            ->toThrow(InvalidArgumentException::class);
    });

    it('sends only the id and event keys in the payload', function () {
        (new Microphone)->record()->id('rec-3')->start();

        $this->bridge->assertCalled('Microphone.Start', function (array $p) {
            expect(array_keys($p))->toEqualCanonicalizing(['id', 'event']);

            return true;
        });
    });

    it('returns true when the bridge reports success with no status key', function () {
        $this->bridge->respondTo('Microphone.Start', ['id' => 'rec-4']);

        $result = (new Microphone)->record()->id('rec-4')->start();

        expect($result)->toBeTrue();
    });

    it('returns true when the bridge response decodes to an empty array', function () {
        $this->bridge->respondTo('Microphone.Start', []);

        $result = (new Microphone)->record()->id('rec-5')->start();

        expect($result)->toBeTrue();
    });

    it('returns false when the bridge reports status error', function () {
        $this->bridge->respondTo('Microphone.Start', ['status' => 'error', 'message' => 'permission denied']);

        $result = (new Microphone)->record()->id('rec-6')->start();

        expect($result)->toBeFalse();
    });

    it('returns false and does not re-call the bridge when start() is called twice', function () {
        $this->bridge->respondTo('Microphone.Start', []);

        $pending = (new Microphone)->record()->id('rec-7');

        expect($pending->start())->toBeTrue();
        expect($pending->start())->toBeFalse();

        $this->bridge->assertCalledTimes('Microphone.Start', 1);
    });

    it('auto-starts via destructor when start() was never called explicitly', function () {
        $this->bridge->respondTo('Microphone.Start', []);

        $pending = (new Microphone)->record()->id('rec-auto');
        unset($pending);

        $this->bridge->assertCalled('Microphone.Start', function (array $p) {
            expect($p['id'])->toBe('rec-auto');

            return true;
        });
    });

    it('does not double-call the bridge when start() was already called explicitly before destruction', function () {
        $this->bridge->respondTo('Microphone.Start', []);

        $pending = (new Microphone)->record()->id('rec-explicit');
        $pending->start();
        unset($pending);

        $this->bridge->assertCalledTimes('Microphone.Start', 1);
    });
});

describe('stop()', function () {
    it('fires Microphone.Stop with an empty payload', function () {
        (new Microphone)->stop();

        $this->bridge->assertCalled('Microphone.Stop', function (array $p) {
            expect($p)->toBe([]);

            return true;
        });
    });

    it('does not throw and returns void', function () {
        expect((new Microphone)->stop())->toBeNull();
    });
});

describe('pause()', function () {
    it('fires Microphone.Pause with an empty payload', function () {
        (new Microphone)->pause();

        $this->bridge->assertCalled('Microphone.Pause', function (array $p) {
            expect($p)->toBe([]);

            return true;
        });
    });
});

describe('resume()', function () {
    it('fires Microphone.Resume with an empty payload', function () {
        (new Microphone)->resume();

        $this->bridge->assertCalled('Microphone.Resume', function (array $p) {
            expect($p)->toBe([]);

            return true;
        });
    });
});

describe('getStatus()', function () {
    it('fires Microphone.GetStatus with an empty payload', function () {
        (new Microphone)->getStatus();

        $this->bridge->assertCalled('Microphone.GetStatus', function (array $p) {
            expect($p)->toBe([]);

            return true;
        });
    });

    it('returns "recording" status from the bridge', function () {
        $this->bridge->respondTo('Microphone.GetStatus', ['status' => 'recording']);

        expect((new Microphone)->getStatus())->toBe('recording');
    });

    it('returns "paused" status from the bridge', function () {
        $this->bridge->respondTo('Microphone.GetStatus', ['status' => 'paused']);

        expect((new Microphone)->getStatus())->toBe('paused');
    });

    it('returns "idle" status from the bridge explicitly', function () {
        $this->bridge->respondTo('Microphone.GetStatus', ['status' => 'idle']);

        expect((new Microphone)->getStatus())->toBe('idle');
    });

    it('defaults to idle when nothing is scripted', function () {
        expect((new Microphone)->getStatus())->toBe('idle');
    });

    it('defaults to idle when the response has no status key', function () {
        $this->bridge->respondTo('Microphone.GetStatus', ['foo' => 'bar']);

        expect((new Microphone)->getStatus())->toBe('idle');
    });

    it('ignores extra fields such as duration in the response', function () {
        $this->bridge->respondTo('Microphone.GetStatus', ['status' => 'recording', 'duration' => 12.5]);

        expect((new Microphone)->getStatus())->toBe('recording');
    });

    it('falls back to idle when the bridge returns an error response', function () {
        $this->bridge->respondTo('Microphone.GetStatus', ['error' => 'not recording']);

        expect((new Microphone)->getStatus())->toBe('idle');
    });
});

describe('getRecording()', function () {
    it('fires Microphone.GetRecording with an empty payload', function () {
        (new Microphone)->getRecording();

        $this->bridge->assertCalled('Microphone.GetRecording', function (array $p) {
            expect($p)->toBe([]);

            return true;
        });
    });

    it('returns the recording path from the bridge', function () {
        $this->bridge->respondTo('Microphone.GetRecording', ['path' => '/storage/emulated/0/audio/recording-1.m4a']);

        expect((new Microphone)->getRecording())->toBe('/storage/emulated/0/audio/recording-1.m4a');
    });

    it('returns null when the path is an empty string', function () {
        $this->bridge->respondTo('Microphone.GetRecording', ['path' => '']);

        expect((new Microphone)->getRecording())->toBeNull();
    });

    it('returns null when the path key is missing', function () {
        $this->bridge->respondTo('Microphone.GetRecording', ['foo' => 'bar']);

        expect((new Microphone)->getRecording())->toBeNull();
    });

    it('returns null when the path is explicitly null', function () {
        $this->bridge->respondTo('Microphone.GetRecording', ['path' => null]);

        expect((new Microphone)->getRecording())->toBeNull();
    });

    it('returns null when nothing is scripted', function () {
        expect((new Microphone)->getRecording())->toBeNull();
    });

    it('ignores extra fields such as duration in the response', function () {
        $this->bridge->respondTo('Microphone.GetRecording', ['path' => '/tmp/rec.m4a', 'duration' => 42]);

        expect((new Microphone)->getRecording())->toBe('/tmp/rec.m4a');
    });

    it('returns null when the bridge returns an error response', function () {
        $this->bridge->respondTo('Microphone.GetRecording', ['error' => 'no recording found']);

        expect((new Microphone)->getRecording())->toBeNull();
    });
});

describe('recording lifecycle sequence', function () {
    it('fires Start, Pause, Resume, and Stop in order for a realistic recording session', function () {
        $this->bridge->respondTo('Microphone.Start', []);

        $microphone = new Microphone;
        $microphone->record()->id('session-1')->start();
        $microphone->pause();
        $microphone->resume();
        $microphone->stop();

        $this->bridge->assertCallOrder([
            'Microphone.Start',
            'Microphone.Pause',
            'Microphone.Resume',
            'Microphone.Stop',
        ]);
    });
});
