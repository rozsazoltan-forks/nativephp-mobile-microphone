<?php

namespace Native\Mobile\Providers\Testing;

use Native\Mobile\Testing\FakeBridge;

/**
 * Microphone test vocabulary for the NativePHP testing suite, registered as
 * FakeBridge macros so app tests read in recording terms instead of raw
 * bridge method strings:
 *
 *     Native::fakeBridge()->withRecording('/storage/emulated/0/audio/note.m4a');
 *
 *     Native::test(VoiceMemo::class)
 *         ->tap('record')
 *         ->assertRecordingStarted();
 *
 * Registered by MicrophoneServiceProvider when the app is running unit
 * tests on a core whose FakeBridge supports macros.
 */
class MicrophoneMacros
{
    public static function register(): void
    {
        /**
         * Fake a completed recording: Microphone.GetStatus reports "idle"
         * (recording finished, nothing in progress) and
         * Microphone.GetRecording reports $path — so getStatus() and
         * getRecording() read exactly as they would right after a real
         * recording session ends. $path defaults to a generic .m4a path
         * when omitted.
         */
        FakeBridge::macro('withRecording', function (?string $path = null) {
            $path ??= 'recording.m4a';

            $this->respondTo('Microphone.GetStatus', ['status' => 'idle']);

            return $this->respondTo('Microphone.GetRecording', ['path' => $path]);
        });

        /**
         * Script Microphone.GetStatus directly — for asserting on
         * "recording" or "paused" states without a completed recording on
         * disk. Use withRecording() instead when the test also needs
         * getRecording() to resolve a path.
         */
        FakeBridge::macro('withMicrophoneStatus', function (string $status) {
            return $this->respondTo('Microphone.GetStatus', ['status' => $status]);
        });

        /** Assert a recording was started (record()->start(), explicit or via auto-start). */
        FakeBridge::macro('assertRecordingStarted', function () {
            return $this->assertCalled('Microphone.Start');
        });

        /** Assert the recording was stopped. */
        FakeBridge::macro('assertRecordingStopped', function () {
            return $this->assertCalled('Microphone.Stop');
        });

        /** Assert no recording was ever started. */
        FakeBridge::macro('assertNothingRecorded', function () {
            return $this->assertNotCalled('Microphone.Start');
        });
    }
}
