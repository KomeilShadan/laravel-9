<?php

namespace Tests\Feature;

use App\Http\Controllers\EmailController;
use App\Jobs\SendEmailJob;
use App\Models\User;
use Elasticsearch\Client;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Foundation\Testing\WithFaker;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Queue;
use Mockery;
use Tests\TestCase;

class EmailTest extends TestCase
{
    use RefreshDatabase, WithFaker;

    public function test_send_emails_job()
    {
        Queue::fake();
        $user = User::factory()->create();

        $data = [
            [
                'recipient' => 'test1@example.com',
                'subject' => 'Test Subject 1',
                'body' => 'Test Body 1',
            ],
            [
                'recipient' => 'test2@example.com',
                'subject' => 'Test Subject 2',
                'body' => 'Test Body 2',
            ],
        ];

        $apiToken = 'token1234567';

        $response = $this->postJson("/api/{$user->id}/send?api_token=$apiToken", ['emails' => $data]);

        $response->assertStatus(200);
        $response->assertJson(['success' => true]);

        foreach ($data as $email) {
            Queue::assertPushed(SendEmailJob::class, function ($job) use ($email) {
                return $job->emailData['recipient'] === $email['recipient']
                    && $job->emailData['subject'] === $email['subject']
                    && $job->emailData['body'] === $email['body'];
            });
    }
}

public function test_validation_errors_are_handled()
{
    Queue::fake();
    $user = User::factory()->create();

    $data = [
        [
            'recipient' => 'recipient1@example.com',
            // 'subject' is missing
            'body' => 'Test Body 1',
        ],
    ];

    $apiToken = 'token1234567';

    $response = $this->postJson("/api/{$user->id}/send?api_token={$apiToken}", ['emails' => $data]);

    $response->assertStatus(422);
    $response->assertJsonValidationErrors(['emails.0.subject']);

    Queue::assertNothingPushed();
}

    public function test_list_sent_emails()
    {
        $client = Mockery::mock(Client::class);
        $this->app->instance(Client::class, $client);

        $response = [
            'hits' => [
                'hits' => [
                    [
                        '_source' => [
                            'email' => 'test1@example.com',
                            'subject' => 'Test Subject 1',
                            'body' => 'Test Body 1',
                        ],
                    ],
                    [
                        '_source' => [
                            'email' => 'test2@example.com',
                            'subject' => 'Test Subject 2',
                            'body' => 'Test Body 2',
                        ],
                    ],
                ],
            ],
        ];

        $client->shouldReceive('search')->once()->andReturn($response);

        $response = resolve(EmailController::class)->list();

        $this->assertEquals(JsonResponse::HTTP_OK, $response->getStatusCode());
        $this->assertJson($response->getContent(), json_encode([
            [
                'subject' => 'Test Subject 1',
                'body' => 'Test Body 1',
            ],
            [
                'subject' => 'Test Subject 2',
                'body' => 'Test Body 2',
            ],
        ], JSON_THROW_ON_ERROR));
    }
}
