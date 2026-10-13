<?php

namespace Vendor\LaravelMonitor\Tests\Unit;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Bus;
use Illuminate\Support\Facades\Config;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Queue;
use Vendor\LaravelMonitor\Jobs\SendHeartbeat;
use Tests\TestCase;

class SendHeartbeatJobTest extends TestCase
{
    
    public function test_send_heartbeat_job_calls_monitor_manager()
    {
        Config::set('monitor.enabled', true);
        Config::set('monitor.endpoint', 'https://monitor.example.com/api/v1/applications/heartbeat');
        Config::set('monitor.api_token', 'test-token');

        // Mock MonitorManager to verify it's called
        $mockManager = $this->mock(\Vendor\LaravelMonitor\MonitorManager::class, function ($mock) {
            $mock->shouldReceive('sendHeartbeat')->once();
            return $mock;
        });
        $this->app->instance('monitor', $mockManager);

        // Manually trigger the job
        $job = new SendHeartbeat();
        $job->handle();

        // Dispatch the job
        Queue::fake();

        SendHeartbeat::dispatch();

        // Assert the job was processed
        Queue::assertPushed(SendHeartbeat::class);
    }

    public function test_send_heartbeat_job_handles_exceptions_gracefully()
    {
        Config::set('monitor.enabled', true);
        Config::set('monitor.endpoint', 'https://monitor.example.com/api/v1/applications/heartbeat');
        Config::set('monitor.api_token', 'test-token');

        // Mock MonitorManager to throw an exception
        $mockManager = $this->mock(\Vendor\LaravelMonitor\MonitorManager::class, function ($mock) {
            $mock->shouldReceive('sendHeartbeat')->once()->andThrow(new \Exception('Test exception'));
            return $mock;
        });

        $this->app->instance('monitor', $mockManager);

        // Manually trigger the job
        $job = new SendHeartbeat();
        $job->handle();
    }

    public function test_send_heartbeat_job_has_correct_retry_settings()
    {
        $job = new SendHeartbeat();

        $this->assertEquals(3, $job->tries);
        $this->assertEquals(10, $job->backoff);
    }
}