<?php

namespace Tests\AppBundle\Controller;

use GuzzleHttp\Psr7\Response;
use Tests\AppBundle\ApiTestCase;


class GroupControllerTest extends ApiTestCase
{
    public function setUp()
    {
        parent::setUp();

        $this->createUserAdmin();
    }

    /**
     * @test
     */
    public function testPostGroup()
    {
        $data = ['name' => 'test group'];

        /** @var Response $response */
        $response = $this->client->post('/api/groups', [
            'body' => json_encode($data),
            'headers' => $this->getAuthorizedHeaders('admin')
        ]);

        $responseData = json_decode($response->getBody(), true);

        $this->assertJsonResponse($response);
        $this->assertEquals(201, $response->getStatusCode());
        $this->assertTrue($response->hasHeader('Location'));
        $this->assertArrayHasKey('id', $responseData);
        $this->assertArrayHasKey('name', $responseData);

        $headerLocation = $response->getHeader('Location');
        $this->assertEquals(
            '/app_test.php/api/groups/'.$responseData['id'],
            $headerLocation[0]
        );
    }

    /**
     * @test
     */
    public function testGetGroup()
    {
        $group = $this->createGroup();
        /** @var Response $response */
        $response = $this->client->get('/api/groups/'.$group->getId(), [
            'headers' => $this->getAuthorizedHeaders('admin')
        ]);

        $this->assertEquals(200, $response->getStatusCode());
        $this->assertJsonResponse($response);
        $this->asserter()->assertResponsePropertiesExist($response, array(
            'id',
            'name'
        ));
        $this->asserter()->assertResponsePropertyEquals($response, 'id', $group->getId());
        $this->asserter()->assertResponsePropertyEquals($response, 'name', 'Group Test');
    }

    /**
     * @test
     */
    public function test404Exception()
    {
        $response = $this->client->get('/api/groups/fake', [
            'headers' => $this->getAuthorizedHeaders('admin')
        ]);

        $contentType = $response->getHeader('Content-Type');

        $this->assertEquals(404, $response->getStatusCode());
        $this->assertEquals('application/problem+json', $contentType[0]);
        $this->asserter()->assertResponsePropertyEquals($response, 'type', 'about:blank');
        $this->asserter()->assertResponsePropertyEquals($response, 'title', 'Not Found');
        $this->asserter()->assertResponsePropertyEquals($response, 'status', 404);
        $this->asserter()->assertResponsePropertyEquals(
            $response,
            'detail',
            'No group found by ID "fake"'
        );
    }

    /**
     * @test
     */
    public function testDeleteGroup()
    {
        $group = $this->createGroup();

        $response = $this->client->delete('/api/groups/'.$group->getId(), [
            'headers' => $this->getAuthorizedHeaders('admin')
        ]);
        $data = json_decode($response->getBody(), true);

        $this->assertEquals(204, $response->getStatusCode());
        $this->assertEquals(null, $data);
    }

    /**
     * @test
     */
    public function testDeleteGroupWithMembersThrowsBadRequestException()
    {
        $group = $this->createGroup();
        $user = $this->createUser();
        $this->assignUserToGroup($user, $group);

        $response = $this->client->delete('/api/groups/'.$group->getId(), [
            'headers' => $this->getAuthorizedHeaders('admin')
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
            'this group has members. It can not be deleted !'
        );
    }
}
