<?php

namespace App\Console\Commands;

use Elasticsearch\Client;
use Elasticsearch\ClientBuilder;
use Illuminate\Console\Command;

class CreateEmailsIndex extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'elastic:create-index:emails {--fresh}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Create Elasticsearch index for emails.';

    public function handle()
    {
        $indexName = 'emails';
        $fresh = $this->option('fresh');
        $client = createElasticsearchClient();

        if ($fresh) {
            $this->deleteIndex($client, $indexName);
        }

        $indexParams = [
            'index' => $indexName,
            'body' => [
                'settings' => [
                    'number_of_shards' => 1,
                    'number_of_replicas' => 0,

                ],
                'mappings' => [
                    'properties' => [
                        'to' => [
                            'type' => 'keyword',
                        ],
                        'subject' => [
                            'type' => 'text',
                        ],
                        'body' => [
                            'type' => 'text',
                        ],
                    ],
                ],
            ],
        ];

        $indexExists = $client->indices()->exists(['index' => 'emails']);

        if (!$indexExists) {
            $response = $client->indices()->create($indexParams);
            $this->info('Index created successfully!');
            return;
        }

        $this->info('Index already exists!');
    }

    /**
     * @param $client
     * @param $indexName
     * @return void
     */
    private function deleteIndex($client, $indexName): void
    {
        $params = [
            'index' => $indexName,
        ];

        $response = $client->indices()->delete($params);

        $this->info('Existing index deleted!');
    }
}
