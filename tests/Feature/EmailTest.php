<?php

namespace Tests\Feature;

use App\Http\Controllers\EmailController;
use App\Jobs\SendEmailJob;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Foundation\Testing\WithFaker;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Queue;
use Tests\TestCase;

class EmailTest extends TestCase
{
    use RefreshDatabase, WithFaker;

    public function test_send_emails_job()
    {
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
        $response->assertJson(['message' => 'Emails sent successfully!']);

        foreach ($data as $email) {
            Queue::assertPushedOn('emails',
                SendEmailJob::class, function ($job) use ($email) {
                    return $job->emailData['recipient'] === $email['recipient']
                        && $job->emailData['subject'] === $email['subject']
                        && $job->emailData['body'] === $email['body'];
            });
        }
    }

    public function test_validation_errors_are_handled()
    {
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
        sleep(1);

        $response->assertStatus(200);
        $response->assertJson(['message' => 'Emails sent successfully!']);

        $expected = [
            'hits' => [
                'hits' => [
                    [
                        '_source' => [
                            'body' => 'Test Body 1',
                            'subject' => 'Test Subject 1',
                            'to' => 'test1@example.com',
                        ],
                    ],
                    [
                        '_source' => [
                            'body' => 'Test Body 2',
                            'subject' => 'Test Subject 2',
                            'to' => 'test2@example.com',
                        ],
                    ],
                ],
            ],
        ];
        $expected = collect($expected['hits']['hits'])->map(function ($hit) {
            return $hit['_source'];
        });

        $response = resolve(EmailController::class)->list();

        $this->assertEquals(JsonResponse::HTTP_OK, $response->getStatusCode());
        $this->assertEquals(response()->json($expected), $response);
    }
}
