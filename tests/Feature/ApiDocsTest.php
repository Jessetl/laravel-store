<?php

namespace Tests\Feature;

use Tests\TestCase;

class ApiDocsTest extends TestCase
{
    public function test_swagger_is_not_exposed_outside_local(): void
    {
        $this->get('/api/v1/swagger')->assertNotFound();
        $this->get('/api/v1/swagger.json')->assertNotFound();
    }
}
