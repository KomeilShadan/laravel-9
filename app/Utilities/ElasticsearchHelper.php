<?php

namespace App\Utilities;

use App\Utilities\Contracts\ElasticsearchHelperInterface;
use Elasticsearch\Client;

class ElasticsearchHelper implements ElasticsearchHelperInterface
{
    private Client $client;

    public function __construct()
    {
        $this->client = createElasticsearchClient();
    }

    /**
     * @param string $messageBody
     * @param string $messageSubject
     * @param string $toEmailAddress
     * @return mixed
     */
    public function storeEmail(string $messageBody, string $messageSubject, string $toEmailAddress): mixed
    {
        $document = [
            'body' => $messageBody,
            'subject' => $messageSubject,
            'to' => $toEmailAddress,
        ];

        $params = [
            'index' => 'emails',
            'body' => $document,
        ];

        $response = $this->client->index($params);

        return $response['_id'];
    }
}
