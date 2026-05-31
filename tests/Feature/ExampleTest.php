<?php

namespace Tests\Feature;

// use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ExampleTest extends TestCase
{
    /**
     * A basic test example.
     */
    public function test_the_application_returns_a_successful_response(): void
    {
        // Touch install lock just in case it doesn't exist in some environments
        @touch(storage_path('app/install.lock'));

        $response = $this->get('/login');

        $response->assertStatus(200);
    }
}
