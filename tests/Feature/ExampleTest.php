<?php

namespace Tests\Feature;

// use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ExampleTest extends TestCase
{
    /**
     * A basic test example.
     */
    public function test_the_application_boots(): void
    {
        $this->assertTrue(class_exists(\App\Http\Controllers\ViajesController::class));
    }
}
