<?php

namespace Tests\Feature;

use Tests\TestCase;

class ExampleTest extends TestCase
{
    public function test_the_home_page_redirects_to_login(): void
    {
        $this->get('/')->assertRedirect('/login');
    }

    public function test_the_login_page_returns_a_successful_response(): void
    {
        $this->get('/login')->assertOk();
    }
}