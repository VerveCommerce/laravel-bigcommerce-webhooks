<?php

namespace VerveCommerce\Bigcommerce;

use Bigcommerce\Api\Connection as ApiConnection;
use Bigcommerce\Api\Client as ApiClient;
use VerveCommerce\Bigcommerce\Exceptions\BigcommerceApiException;

class Bigcommerce
{
    public $connection;
    public $client;
    protected $clientId;
    protected $clientSecret;
    protected $storeHash;
    protected $accessToken;
    protected $version;
    protected $baseApiUrl = "https://api.bigcommerce.com/";
    protected $redirectUrl;

    public function __construct()
    {
        $this->connection = new ApiConnection();
        $this->version = config('bigcommerce.default_version', 'v3');

        if ($hash = config('bigcommerce.store_hash')) {
            $this->setStoreHash($hash);
        }

        if ($token = config('bigcommerce.access_token')) {
            $this->setAccessToken($token);
        }

        $this->clientId = config('bigcommerce.client_id');
        $this->clientSecret = config('bigcommerce.client_secret');

        $this->connection->addHeader("X-Auth-Client", $this->clientId);

        ApiClient::configure([
            'client_id' => $this->clientId,
            'auth_token' => $this->accessToken,
            'store_hash' => $this->storeHash
        ]);
    }

    /*
     * Set store hash;
     */
    public function setStoreHash($storeHash)
    {
        $this->storeHash = $storeHash;
        return $this;
    }

    public function setAccessToken($accessToken)
    {
        return tap($this, function ($bc) use ($accessToken) {
            $bc->accessToken = $accessToken;
            $bc->connection->addHeader("X-Auth-Token", $accessToken);
        });
    }

    public function setApiVersion($version)
    {
        $this->version = $version;
        return $this;
    }

    /**
     *  Proxy calls to the BigCommerce API.
     *
     * Generic methods (get, post, put, delete) get passed to the underlying
     * Connection class while all other methods are proxied to the Client
     * class.
     */
    public function __call($method, $args)
    {
        if (in_array($method, ['get', 'post', 'put', 'delete'])) {
            return $this->makeBasicRequest($method, $args[0], $args[1] ?? null);
        }

        return $this->proxyClientRequest($method, $args);
    }

    public function makeBasicRequest($httpVerb, $resource, $filters = null)
    {
        try {
            $data = $this->connection->$httpVerb($this->resourceUri($resource), $filters);

            // Recursively attempt to retry.
            if (($retryAfter = $this->connection->getHeader("X-Retry-After")) &&
                $retryAfter > 0) {
                sleep($retryAfter + 5);
                return $this->makeBasicRequest($httpVerb, $resource, $filters);
            }

            return $this->version == "v2" ?
                collect($data) : collect($data)->map(function ($value) {
                    return collect($value);
                });

        } catch (Exception $e) {
            throw new BigcommerceApiException($e->getMessage(), $e->getCode());
        }
    }

    public function resourceUri($resource)
    {
        return $this->baseApiUrl . "stores/" . $this->storeHash . "/{$this->version}/" . $resource;
    }

    public function proxyClientRequest($method, $args)
    {
        try {
            return call_user_func_array([ApiClient::class, $method], $args);
        } catch (Exception $e) {
            throw new BigcommerceApiException($e->getMessage(), $e->getCode());
        }
    }
    
}
