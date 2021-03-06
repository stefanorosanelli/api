<?php
/**
 * BEdita, API-first content management framework
 * Copyright 2017 ChannelWeb Srl, Chialab Srl
 *
 * This file is part of BEdita: you can redistribute it and/or modify
 * it under the terms of the GNU Lesser General Public License as published
 * by the Free Software Foundation, either version 3 of the License, or
 * (at your option) any later version.
 *
 * See LICENSE.LGPL or <http://gnu.org/licenses/lgpl-3.0.html> for more details.
 */
namespace BEdita\API\Test\TestCase\Controller;

use BEdita\API\TestSuite\IntegrationTestCase;
use Cake\ORM\TableRegistry;

/**
 * {@see \BEdita\API\Controller\AdminController} Test Case
 *
 * @coversDefaultClass \BEdita\API\Controller\AdminController
 */
class AdminControllerTest extends IntegrationTestCase
{

    /**
     * Test index method.
     *
     * @return void
     *
     * @covers ::initialize()
     * @covers ::resourceUrl()
     */
    public function testIndex()
    {
        $expected = [
            [
                'id' => '1',
                'type' => 'applications',
                'attributes' => [
                    'name' => 'First app',
                    'description' => 'Lorem ipsum dolor sit amet, aliquet feugiat.',
                    'enabled' => 1,
                ],
                'meta' => [
                    'api_key' => API_KEY,
                    'created' => '2016-10-28T07:10:57+00:00',
                    'modified' => '2016-10-28T07:10:57+00:00',
                ],
                'links' => [
                    'self' => 'http://api.example.com/admin/applications/1'
                ],
            ],
            [
                'id' => '2',
                'type' => 'applications',
                'attributes' => [
                    'name' => 'Disabled app',
                    'description' => 'This app has been disabled',
                    'enabled' => 0,
                ],
                'meta' => [
                    'api_key' => 'abcdef12345',
                    'created' => '2017-02-17T15:51:29+00:00',
                    'modified' => '2017-02-17T15:51:29+00:00',
                ],
                'links' => [
                    'self' => 'http://api.example.com/admin/applications/2'
                ],
            ]
        ];
        // GET /admin with anonymous user
        $this->configRequestHeaders('GET');
        $this->get('/admin/applications');
        static::assertResponseCode(401);
        static::assertContentType('application/vnd.api+json');
        // GET /admin with user without administrator role
        $authHeader = $this->getUserAuthHeader('second user', 'password2');
        $this->configRequestHeaders('GET', $authHeader);
        $this->get('/admin/applications');
        static::assertResponseCode(403);
        static::assertContentType('application/vnd.api+json');
        // GET /admin with authenticated user
        $authHeader = $this->getUserAuthHeader();
        $this->configRequestHeaders('GET', $authHeader);
        $this->get('/admin/applications');
        $result = json_decode((string)$this->_response->getBody(), true);
        static::assertResponseCode(200);
        static::assertContentType('application/vnd.api+json');
        static::assertEquals($expected, $result['data']);
    }

    /**
     * Test route fail.
     *
     * @return void
     *
     * @covers ::initialize()
     */
    public function testRouteFail()
    {
        $this->configRequestHeaders();
        $this->get('/admin/objects');

        static::assertResponseCode(404);
        static::assertContentType('application/vnd.api+json');
    }

    /**
     * Test add.
     *
     * @return void
     *
     * @covers ::initialize()
     * @covers ::resourceUrl()
     */
    public function testAdd()
    {
        $data = [
            'type' => 'applications',
            'attributes' => [
                'name' => 'another-app',
                'description' => 'Another app',
            ],
        ];

        $this->configRequestHeaders('POST', $this->getUserAuthHeader());
        $this->post('/admin/applications', json_encode(compact('data')));

        static::assertResponseCode(201);
        static::assertContentType('application/vnd.api+json');
        static::assertHeader('Location', 'http://api.example.com/admin/applications/3');

        $entity = TableRegistry::get('Applications')->get(3);
        TableRegistry::get('Applications')->delete($entity);
    }
}
