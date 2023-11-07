<?php

namespace App\Http\Controllers;

use App\Http\Requests\EmailSendRequest;
use App\Jobs\SendEmailJob;
use App\Models\User;
use App\Utilities\Contracts\ElasticsearchHelperInterface;
use App\Utilities\Contracts\RedisHelperInterface;
use Illuminate\Http\JsonResponse;
use Throwable;

class EmailController extends Controller
{
    public function __construct(
        private ElasticsearchHelperInterface $elasticsearchHelper,
        private RedisHelperInterface $redisHelper
    ) {}

    /**
     * @param User $user
     * @param EmailSendRequest $request
     * @return JsonResponse
     */
    public function send(User $user, EmailSendRequest $request): JsonResponse
    {
        $emails = $request->input('emails');

        foreach ($emails as $emailData) {

            $messageBody = $emailData['body'];
            $messageSubject = $emailData['subject'];
            $toEmailAddress = $emailData['recipient'];

            $docId = $this->elasticsearchHelper->storeEmail($messageBody, $messageSubject, $toEmailAddress);

            $this->redisHelper->storeRecentMessage($docId, $messageSubject, $toEmailAddress);

            SendEmailJob::dispatch($emailData, $user);
        }
        return response()->json(['message' => 'Emails sent successfully!'], 200);
    }


    /**
     * @return JsonResponse
     */
    public function list(): JsonResponse
    {
        $client = createElasticsearchClient();

        $params = [
            'index' => 'emails',
            'body' => [
                'query' => [
                    'match_all' => new \stdClass(),
                ],
            ],
        ];

        $response = $client->search($params);

        $emails = collect($response['hits']['hits'])->map(function ($hit) {
            return $hit['_source'];
        });

        return response()->json($emails);
    }
}
