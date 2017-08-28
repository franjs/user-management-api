<?php

namespace Tests\AppBundle\Controller;

use GuzzleHttp\Psr7\Response;
use Tests\AppBundle\ApiTestCase;


class UserControllerTest extends ApiTestCase
{
    /**
     * @test
     */
    public function testPostUser()
    {
        $data = ['name' => 'test name'];

        /** @var Response $response */
        $response = $this->client->post('/api/users', [
            'body' => json_encode($data)
        ]);

        $responseData = json_decode($response->getBody(), true);

        $this->assertJsonResponse($response);
        $this->assertEquals(201, $response->getStatusCode());
        $this->assertTrue($response->hasHeader('Location'));
        $this->assertArrayHasKey('id', $responseData);
        $this->assertArrayHasKey('name', $responseData);

        $headerLocation = $response->getHeader('Location');
        $this->assertEquals(
            '/app_test.php/api/users/'.$responseData['id'],
            $headerLocation[0]
        );
    }

    /**
     * @test
     */
    public function testGetUser()
    {
        $user = $this->createUser();
        /** @var Response $response */
        $response = $this->client->get('/api/users/'.$user->getId());

        $this->assertEquals(200, $response->getStatusCode());
        $this->assertJsonResponse($response);
        $this->asserter()->assertResponsePropertiesExist($response, array(
            'id',
            'name'
        ));
        $this->asserter()->assertResponsePropertyEquals($response, 'id', $user->getId());
        $this->asserter()->assertResponsePropertyEquals($response, 'name', 'User Test');
    }

    /**
     * @test
     */
    public function test404Exception()
    {
        $response = $this->client->get('/api/users/fake');

        $contentType = $response->getHeader('Content-Type');

        $this->assertEquals(404, $response->getStatusCode());
        $this->assertEquals('application/problem+json', $contentType[0]);
        $this->asserter()->assertResponsePropertyEquals($response, 'type', 'about:blank');
        $this->asserter()->assertResponsePropertyEquals($response, 'title', 'Not Found');
        $this->asserter()->assertResponsePropertyEquals($response, 'status', 404);
        $this->asserter()->assertResponsePropertyEquals(
            $response,
            'detail',
            'No user found by ID "fake"'
        );
    }

    /**
     * @test
     */
    public function testDeleteUser()
    {
        $user = $this->createUser();

        $response = $this->client->delete('/api/users/'.$user->getId());
        $data = json_decode($response->getBody(), true);

        $this->assertEquals(204, $response->getStatusCode());
        $this->assertEquals(null, $data);
    }

    /**
     * @test
     */
    public function testAssignUserToGroup()
    {
        $user = $this->createUser();
        $group = $this->createGroup();

        /** @var Response $response */
        $response = $this->client->post(
            '/api/users/' . $user->getId() . '/assign-to-group', [
            'body' => json_encode(['group_id' => $group->getId()])
        ]);

        $this->assertEquals(200, $response->getStatusCode());
    }

    /**
     * @test
     */
    public function testAssignUserToGroupAlreadyAssignedThrowsAnException()
    {
        $user = $this->createUser();
        $group = $this->createGroup();
        $this->assignUserToGroup($user, $group);

        /** @var Response $response */
        $response = $this->client->post(
            '/api/users/' . $user->getId() . '/assign-to-group', [
            'body' => json_encode(['group_id' => $group->getId()])
        ]);

        $contentType = $response->getHeader('Content-Type');

        $this->assertEquals(400, $response->getStatusCode());
        $this->assertEquals('application/problem+json', $contentType[0]);
        $this->asserter()->assertResponsePropertyEquals($response, 'type', 'about:blank');
        $this->asserter()->assertResponsePropertyEquals($response, 'title', 'Bad Request');
        $this->asserter()->assertResponsePropertyEquals($response, 'status', 400);
        $this->asserter()->assertResponsePropertyEquals(
            $response,
            'detail',
            'The user is already assigned to the group given'
        );
    }
}