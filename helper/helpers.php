<?php

use Elasticsearch\Client;
use Elasticsearch\ClientBuilder;

if (!function_exists('createElasticsearchClient')) {
    /**
     * @return Client
     */
    function createElasticsearchClient(): Client
    {
        return ClientBuilder::create()
            ->setHosts([env('ELASTICSEARCH_HOST', 'elasticsearch')])
            ->setBasicAuthentication(
                env('ELASTICSEARCH_USERNAME', 'elastic'),
                env('ELASTICSEARCH_PASSWORD', '')
            )
            ->build();
    }
}
