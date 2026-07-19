<?php

/**
 * The microphone test vocabulary this plugin registers on the FakeBridge
 * (withRecording / withMicrophoneStatus / assertRecordingStarted /
 * assertRecordingStopped / assertNothingRecorded) — the sugar app
 * developers use instead of raw bridge method strings.
 *
 * Skipped on cores whose FakeBridge predates macro support.
 */

use Native\Mobile\Microphone;
use Native\Mobile\Testing\FakeBridge;
use Native\Mobile\Testing\Native;
use PHPUnit\Framework\AssertionFailedError;
use Tests\TestCase;

uses(TestCase::class);

beforeEach(function () {
    if (! method_exists(FakeBridge::class, 'macro')) {
        $this->markTestSkipped('This core\'s FakeBridge does not support macros.');
    }

    $this->bridge = Native::fakeBridge();
});

describe('withRecording()', function () {
    it('reports an idle status and the given path as the last recording', function () {
        $this->bridge->withRecording('/storage/emulated/0/audio/note.m4a');

        expect((new Microphone)->getStatus())->toBe('idle')
            ->and((new Microphone)->getRecording())->toBe('/storage/emulated/0/audio/note.m4a');
    });

    it('defaults to a generic .m4a path when none is given', function () {
        $this->bridge->withRecording();

        expect((new Microphone)->getRecording())->toBe('recording.m4a');
    });
});

describe('withMicrophoneStatus()', function () {
    it('scripts getStatus() to report "recording"', function () {
        $this->bridge->withMicrophoneStatus('recording');

        expect((new Microphone)->getStatus())->toBe('recording');
    });

    it('scripts getStatus() to report "paused"', function () {
        $this->bridge->withMicrophoneStatus('paused');

        expect((new Microphone)->getStatus())->toBe('paused');
    });

    it('does not script getRecording()', function () {
        $this->bridge->withMicrophoneStatus('recording');

        expect((new Microphone)->getRecording())->toBeNull();
    });
});

describe('assertRecordingStarted()', function () {
    it('passes after record()->start()', function () {
        (new Microphone)->record()->id('rec-1')->start();

        $this->bridge->assertRecordingStarted();
    });

    it('fails when nothing was started', function () {
        expect(fn () => $this->bridge->assertRecordingStarted())
            ->toThrow(AssertionFailedError::class);
    });
});

describe('assertRecordingStopped()', function () {
    it('passes after stop()', function () {
        (new Microphone)->stop();

        $this->bridge->assertRecordingStopped();
    });

    it('fails when stop() was never called', function () {
        expect(fn () => $this->bridge->assertRecordingStopped())
            ->toThrow(AssertionFailedError::class);
    });
});

describe('assertNothingRecorded()', function () {
    it('passes when no recording was started', function () {
        (new Microphone)->getStatus();

        $this->bridge->assertNothingRecorded();
    });

    it('fails once a recording was started', function () {
        (new Microphone)->record()->id('rec-2')->start();

        expect(fn () => $this->bridge->assertNothingRecorded())
            ->toThrow(AssertionFailedError::class);
    });
});
