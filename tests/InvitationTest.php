<?php

namespace App\Tests;

use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;

class InvitationTest extends WebTestCase
{
    private $client;
    private $em;

    protected function setUp(): void
    {
        $this->client = static::createClient(['environment' => 'test']);
        $this->client->disableReboot();
        $this->em = $this->client->getContainer()->get('doctrine.orm.entity_manager');
        $this->em->beginTransaction();
    }

    protected function tearDown(): void
    {
        $this->em->rollback();
    }

    public function testSendInvitation()
    {
        // Test sending an invitation
        $this->client->request(
            'POST',
            '/api/invitation/send',
            [],
            [],
            ['CONTENT_TYPE' => 'application/json'],
            json_encode([
                'senderId' => 1,
                'invitedEmail' => 'test1@example.com',
                'invitedName' => 'Test User'
            ])
        );

        $this->assertEquals(200, $this->client->getResponse()->getStatusCode());
        $this->assertStringContainsStringIgnoringCase('Invitation sent successfully', $this->client->getResponse()->getContent());
    }

    public function testSendInvitationFailValidation()
    {
        // Test sending an invitation with missing data
        $this->client->request(
            'POST',
            '/api/invitation/send',
            [],
            [],
            ['CONTENT_TYPE' => 'application/json'],
            json_encode([
                'invitedEmail' => 'test2@example.com'
            ])
        );

        $this->assertEquals(400, $this->client->getResponse()->getStatusCode());
        $this->assertStringContainsStringIgnoringCase('Missing invitedEmail, invitedName or senderId', $this->client->getResponse()->getContent());
    }

    public function testSendInvitationFailInvitationExists()
    {
        // Test sending an invitation with an existing user
        $this->client->request(
            'POST',
            '/api/invitation/send',
            [],
            [],
            ['CONTENT_TYPE' => 'application/json'],
            json_encode([
                'senderId' => 1,
                'invitedEmail' => 'test3@example.com',
                'invitedName' => 'Test User'
            ])
        );

        // First response should be successful
        $this->assertEquals(200, $this->client->getResponse()->getStatusCode());
        $this->assertStringContainsStringIgnoringCase('Invitation sent successfully', $this->client->getResponse()->getContent());

        // Second response should fail because the invitation already exists
        $this->client->request(
            'POST',
            '/api/invitation/send',
            [],
            [],
            ['CONTENT_TYPE' => 'application/json'],
            json_encode([
                'senderId' => 1,
                'invitedEmail' => 'test3@example.com',
                'invitedName' => 'II. Test User'
            ])
        );

        $this->assertEquals(400, $this->client->getResponse()->getStatusCode());
        $this->assertStringContainsStringIgnoringCase('User already registered or invited', $this->client->getResponse()->getContent());
    }

    public function testSendInvitationFailUserExists()
    {
        // Test sending an invitation with an existing user
        $this->client->request(
            'POST',
            '/api/invitation/send',
            [],
            [],
            ['CONTENT_TYPE' => 'application/json'],
            json_encode([
                'senderId' => 1,
                'invitedEmail' => 'test4@example.com',
                'invitedName' => 'Test User'
            ])
        );

        // First response should be successful
        $this->assertEquals(200, $this->client->getResponse()->getStatusCode());
        $this->assertStringContainsStringIgnoringCase('Invitation sent successfully', $this->client->getResponse()->getContent());

        // get the invitation from the response
        $invitation = json_decode($this->client->getResponse()->getContent(), true)['invitation'];

        // Accepting invitation to create a user
        $this->client->request(
            'PUT',
            '/api/invitation/respond/' . $invitation['id'] . '/accepted'
        );

        // Second response should be successful
        $this->assertEquals(200, $this->client->getResponse()->getStatusCode());
        $this->assertStringContainsStringIgnoringCase('Invitation accepted successfully', $this->client->getResponse()->getContent());

        // Third response should fail because the user already exists
        $this->client->request(
            'POST',
            '/api/invitation/send',
            [],
            [],
            ['CONTENT_TYPE' => 'application/json'],
            json_encode([
                'senderId' => 1,
                'invitedEmail' => 'test4@example.com',
                'invitedName' => 'II. Test User'
            ])
        );

        $this->assertEquals(400, $this->client->getResponse()->getStatusCode());
        $this->assertStringContainsStringIgnoringCase('User already registered or invited', $this->client->getResponse()->getContent());
    }

    public function testCancelInvitation()
    {
        // Test sending an invitation
        $this->client->request(
            'POST',
            '/api/invitation/send',
            [],
            [],
            ['CONTENT_TYPE' => 'application/json'],
            json_encode([
                'senderId' => 1,
                'invitedEmail' => 'test5@example.com',
                'invitedName' => 'Test User'
            ])
        );

        // First response should be successful
        $this->assertEquals(200, $this->client->getResponse()->getStatusCode());
        $this->assertStringContainsStringIgnoringCase('Invitation sent successfully', $this->client->getResponse()->getContent());

        // Get the invitation from the response
        $invitation = json_decode($this->client->getResponse()->getContent(), true)['invitation'];
        $this->client->request(
            'DELETE',
            '/api/invitation/cancel/' . $invitation['id']
        );

        // Second response should be successful
        $this->assertEquals(200, $this->client->getResponse()->getStatusCode());
        $this->assertStringContainsStringIgnoringCase('Invitation cancelled successfully', $this->client->getResponse()->getContent());
    }

    public function testCancelInvitationFailNotFound()
    {
        // Test sending an invitation
        $this->client->request(
            'DELETE',
            '/api/invitation/cancel/999'
        );

        $this->assertEquals(404, $this->client->getResponse()->getStatusCode());
    }

    public function testAcceptInvitation()
    {
        // Test sending an invitation
        $this->client->request(
            'POST',
            '/api/invitation/send',
            [],
            [],
            ['CONTENT_TYPE' => 'application/json'],
            json_encode([
                'senderId' => 1,
                'invitedEmail' => 'test6@example.com',
                'invitedName' => 'Test User'
            ])
        );
        $this->assertEquals(200, $this->client->getResponse()->getStatusCode());
        $this->assertStringContainsStringIgnoringCase('Invitation sent successfully', $this->client->getResponse()->getContent());

        // Get the invitation from the response
        $invitation = json_decode($this->client->getResponse()->getContent(), true)['invitation'];

        // Test accepting the invitation
        $this->client->request(
            'PUT',
            '/api/invitation/respond/' . $invitation['id'] . '/accepted'
        );

        // Get the user from the response
        $user = json_decode($this->client->getResponse()->getContent(), true)['user'];

        $this->assertEquals(200, $this->client->getResponse()->getStatusCode());
        $this->assertStringContainsStringIgnoringCase('Invitation accepted successfully', $this->client->getResponse()->getContent());
        $this->assertEquals('test6@example.com', $user['email']);
    }

    public function testDeclineInvitation()
    {
        // Test sending an invitation
        $this->client->request(
            'POST',
            '/api/invitation/send',
            [],
            [],
            ['CONTENT_TYPE' => 'application/json'],
            json_encode([
                'senderId' => 1,
                'invitedEmail' => 'test7@example.com',
                'invitedName' => 'Test User'
            ])
        );

        // First response should be successful
        $this->assertEquals(200, $this->client->getResponse()->getStatusCode());
        $this->assertStringContainsStringIgnoringCase('Invitation sent successfully', $this->client->getResponse()->getContent());

        // Get the invitation from the response
        $invitation = json_decode($this->client->getResponse()->getContent(), true)['invitation'];

        // Test declining the invitation
        $this->client->request(
            'PUT',
            '/api/invitation/respond/' . $invitation['id'] . '/declined'
        );

        $this->assertEquals(200, $this->client->getResponse()->getStatusCode());
        $this->assertStringContainsStringIgnoringCase('Invitation declined successfully', $this->client->getResponse()->getContent());
    }
}
