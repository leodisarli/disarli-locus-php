<?php

namespace Locus;

use AwsServiceDiscovery\AwsServiceDiscovery;
use Predis\Client;

class Locus
{
    private $redisConfig;
    private $envConfig;
    private $serviceDiscoveryConfig;

    /**
     * method __construct
     * @param array $redisConfig
     * @param array $envConfig
     * @param array $serviceDiscoveryConfig
     * @return void
     */
    public function __construct(
        array $redisConfig = [],
        array $envConfig = [],
        array $serviceDiscoveryConfig = []
    ) {
        $this->redisConfig = $redisConfig;
        $this->envConfig = $envConfig;
        $this->serviceDiscoveryConfig = $serviceDiscoveryConfig;
    }

    /**
     * method getUrl
     * @param string $namespace
     * @param string $service
     * @return string|null
     */
    public function getUrl(
        string $namespace,
        string $service
    ): ?string {
        $url = $this->getUrlFromEnv(
            $service
        );
        if (!empty($url)) {
            return $url;
        }

        $urls = $this->getUrlsFromCache(
            $service
        );
        if (!empty($urls)) {
            $index = $this->getRandomIndex(
                $urls
            );
            return $urls[$index];
        }

        $urls = $this->getUrlsFromServiceDiscovery(
            $namespace,
            $service
        );
        if (!empty($urls)) {
            $index = $this->getRandomIndex(
                $urls
            );
            return $urls[$index];
        }

        return null;
    }

    /**
     * method getRandomIndex
     * @param array $urls
     * @return int
     */
    public function getRandomIndex(
        array $urls
    ): int {
        return rand(0, count($urls)-1);
    }

    /**
     * method getUrlFromEnv
     * @param string $service
     * @return string|null
     */
    public function getUrlFromEnv(
        string $service
    ): ?string {
        return $this->envConfig[$service]['url'] ?? null;
    }

    /**
     * method getUrlFromCache
     * @param string $service
     * @return array|null
     */
    public function getUrlsFromCache(
        string $service
    ): ?array {
        $redis = $this->newRedis();
        $urlList = $redis->get("locus-{$service}");
        return json_decode($urlList, true) ?? null;
    }

    /**
     * method setUrlToCache
     * @param string $service
     * @param array $urlList
     * @return string
     */
    public function setUrlToCache(
        string $service,
        array $urlList
    ) : bool {
        $redis = $this->newRedis();
        $redis->set("locus-{$service}", json_encode($urlList));
        return true;
    }

    /**
     * method getUrlFromCache
     * @param string $namespace
     * @param string $service
     * @return string
     */
    public function getUrlsFromServiceDiscovery(
        string $namespace,
        string $service
    ) : ?array {
        $serviceDiscovery = $this->newServiceDiscovery();
        $urls = $serviceDiscovery->getServiceAllHealthUrl(
            $namespace,
            $service
        );
        if (empty($urls)) {
            return null;
        }
        
        $this->setUrlToCache(
            $service,
            $urls
        );
        return $urls;
    }

    /**
     * method getUrlFromCache
     * @param string $service
     * @return string
     */
    public function clearCache(
        string $service
    ) : bool {
        $redis = $this->newRedis();
        $redis->del("locus-{$service}");
        return true;
    }

    /**
     * return new predis client object
     * @return Client
     */
    private function newRedis(): Client
    {
        $defaultConfig = [
            'host'   => 'localhost',
            'port'   => 6379,
        ];

        $this->redisConfig = array_merge($defaultConfig, $this->redisConfig);
        return new Client($this->redisConfig);
    }

    /**
     * return new predis client object
     * @return AwsServiceDiscovery
     */
    private function newServiceDiscovery(): AwsServiceDiscovery
    {
        return new AwsServiceDiscovery(
            $this->serviceDiscoveryConfig
        );
    }
}
