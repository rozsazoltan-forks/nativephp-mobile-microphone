<?php

namespace Native\Mobile\Providers;

use Illuminate\Support\ServiceProvider;
use Native\Mobile\Microphone;
use Native\Mobile\Providers\Testing\MicrophoneMacros;
use Native\Mobile\Testing\FakeBridge;

class MicrophoneServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        $this->app->singleton(Microphone::class, function () {
            return new Microphone;
        });

        // Test sugar (assertRecordingStarted() etc.) — only under a test
        // runner, and only on a core whose FakeBridge is macroable (the
        // method_exists guard keeps older v4 and v3 cores fatal-free).
        if ($this->app->runningUnitTests()
            && class_exists(FakeBridge::class)
            && method_exists(FakeBridge::class, 'macro')) {
            MicrophoneMacros::register();
        }
    }
}
