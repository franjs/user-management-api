<?php

namespace Tests\AppBundle\Controller;

use Tests\AppBundle\ApiTestCase;


class SecurityControllerTest extends ApiTestCase
{
    /**
     * @test
     */
    public function testLogin()
    {
        $this->createUser();

        $response = $this->client->post('/api/login', [
            'body' => json_encode(['username' => 'test', 'password' => 'test-password'])
        ]);

        $this->assertEquals(200, $response->getStatusCode());
        $this->asserter()->assertResponsePropertyExists(
            $response,
            'token'
        );
    }

    /**
     * @test
     */
    public function testLoginInvalidCredentials()
    {
        $this->createUser();

        $response = $this->client->post('/api/login', [
            'body' => json_encode(['username' => 'test', 'password' => 'invalid_password'])
        ]);

        $this->assertEquals(401, $response->getStatusCode());
        $this->assertEquals('application/problem+json', $response->getHeader('Content-Type')[0]);
        $this->asserter()->assertResponsePropertyEquals($response, 'type', 'about:blank');
        $this->asserter()->assertResponsePropertyEquals($response, 'title', 'Unauthorized');
        $this->asserter()->assertResponsePropertyEquals(
            $response, 'detail',
            'Invalid credentials.'
        );
    }

    /**
     * @test
     */
    public function testLoginUserNotFound()
    {
        $response = $this->client->post('/api/login', [
            'body' => json_encode(['username' => 'test-user', 'password' => 'invalid_password'])
        ]);

        $this->assertEquals(404, $response->getStatusCode());
        $this->assertEquals('application/problem+json', $response->getHeader('Content-Type')[0]);
        $this->asserter()->assertResponsePropertyEquals($response, 'type', 'about:blank');
        $this->asserter()->assertResponsePropertyEquals($response, 'title', 'Not Found');
        $this->asserter()->assertResponsePropertyEquals(
            $response, 'detail',
            'Not Found'
        );
    }
}
