<?php

namespace Libern\Rest;

use GuzzleHttp\Client;
use Illuminate\Http\Response;
use Psr\Http\Message\ResponseInterface;

class RestClient
{

    /**
     * @var Psr\Http\Message\ResponseInterface
     */
    protected $guzzle_response;

    /**
     * @var Response
     */
    protected $response;

    /**
     * @var GuzzleHttp\Client
     */
    protected $client;

    /**
     * @var array
     */
    protected $oauth_tokens = [];

    const TOKEN_TYPE_USER = 'user';
    const TOKEN_TYPE_CLIENT = 'client';

    protected $use_oauth_token = null;

    /**
     * @var bool
     */
    protected $debug_mode = false;

    /**
     * Create a new Skeleton Instance
     */
    public function __construct()
    {
        $this->setUp();
    }

    public function setUp()
    {
        $this->client = new Client([
            'base_uri' => 'http://someline-starter.app/api/',
            'exceptions' => false,
        ]);
    }

    protected function getClientData()
    {
        return [
            'client_id' => 'SomelineFvGXRmBv',
            'client_secret' => 'WFYBPbkOBv7hTby8vGL2SPOOq2GKYQdSIDGXcLsS',
        ];
    }

    protected function getOAuthUserCredentialsData()
    {
        return [
            'username' => 'libern@someline.com',
            'password' => 'Abc12345',
        ];
    }

    protected function postRequestAccessToken($grant_type, $data)
    {
        return $this->post('oauth/access_token', array_merge($data, [
            'grant_type' => $grant_type,
        ]));
    }

    private function configureOptions($options)
    {
        $headers = [
            'User-Agent' => 'someline-testing/1.0',
            'Accept' => 'application/x.someline.v1+json',
        ];

        if ($this->use_oauth_token) {
            $headers['Authorization'] = 'Bearer ' . $this->getOAuthToken($this->use_oauth_token);
        }

        if (isset($options['headers'])) {
            $headers = array_merge($headers, $options['headers']);
            unset($options['headers']);
        }

        return array_merge([
            'headers' => $headers,
        ], $options);
    }

    public function getResponse()
    {
        return $this->response;
    }

    public function getResponseAsJson($assoc = true)
    {
        return json_decode($this->getResponse()->getContent(), $assoc);
    }

    public function getResponseData()
    {
        return $this->getResponseAsJson();
    }

    private function getOAuthToken($type)
    {
        if (!isset($this->oauth_tokens[$type])) {
            if ($type == self::TOKEN_TYPE_CLIENT) {
                $this->postRequestAccessToken('client_credentials',
                    array_merge($this->getClientData(), []));
            } else if ($type == self::TOKEN_TYPE_USER) {
                $this->postRequestAccessToken('password',
                    array_merge($this->getClientData(), $this->getOAuthUserCredentialsData()));
            }
            $data = $this->getResponseData();
            $this->setOAuthToken($type, $data['access_token']);
        }
        return $this->oauth_tokens[$type];
    }

    public function setOAuthToken($type, $access_token)
    {
        echo "SET OAuthToken[$type]: $access_token\n\n";
        $this->oauth_tokens[$type] = $access_token;
    }

    public function withOAuthToken($type)
    {
        $this->getOAuthToken($type);
        $this->use_oauth_token = $type;
    }

    public function withOAuthTokenTypeUser()
    {
        $this->withOAuthToken(self::TOKEN_TYPE_USER);
    }

    public function withOAuthTokenTypeClient()
    {
        $this->withOAuthToken(self::TOKEN_TYPE_CLIENT);
    }

    public function withoutOAuthToken()
    {
        $this->use_oauth_token = null;
    }

    /**
     * @param string $uri
     * @param array $query
     * @param array $options
     * @return $this
     */
    public function get($uri, array $query = [], array $options = [])
    {
        $options = $this->configureOptions($options);
        $this->printArray($options);
        $response = $this->client->get($uri, array_merge($options, [
            'query' => $query,
        ]));
        $this->setGuzzleResponse($response);
        return $this;
    }

    /**
     * @param string $uri
     * @param array $data
     * @param array $options
     * @return $this
     */
    public function post($uri, array $data = [], array $options = [])
    {
        $options = $this->configureOptions($options);
        $response = $this->client->post($uri, array_merge($options, [
            'form_params' => $data,
        ]));
        $this->setGuzzleResponse($response);
        return $this;
    }

    /**
     * @url http://docs.guzzlephp.org/en/latest/quickstart.html#sending-form-files
     * @param $uri
     * @param array $multipart
     * @param array $options
     * @return $this
     */
    public function postMultipart($uri, array $multipart = [], array $options = [])
    {
        $options = $this->configureOptions($options);
        $response = $this->client->post($uri, array_merge($options, [
            'multipart' => $multipart,
        ]));
        $this->setGuzzleResponse($response);
        return $this;
    }

    /**
     * @param $uri
     * @param array $data
     * @param array $options
     * @return $this
     */
    public function postMultipartSimple($uri, array $data = [], array $options = [])
    {
        $options = $this->configureOptions($options);
        $multipart = [];
        foreach ($data as $key => $value) {
            $multipart[] = [
                'name' => $key,
                'contents' => $value,
            ];
        }
        $response = $this->client->post($uri, array_merge($options, [
            'multipart' => $multipart,
        ]));
        $this->setGuzzleResponse($response);
        return $this;
    }

    /**
     * @param string $uri
     * @param array $data
     * @param array $options
     * @return $this
     */
    public function head($uri, array $data = [], array $options = [])
    {
        $response = $this->client->head($uri, array_merge($options, [
            'body' => $data,
        ]));
        $this->setGuzzleResponse($response);
        return $this;
    }

    /**
     * @param string $uri
     * @param array $data
     * @param array $options
     * @return $this
     */
    public function put($uri, array $data = [], array $options = [])
    {
        $options = $this->configureOptions($options);
        $response = $this->client->put($uri, array_merge($options, [
            'form_params' => $data,
        ]));
        $this->setGuzzleResponse($response);
        return $this;
    }

    /**
     * @param string $uri
     * @param array $data
     * @param array $options
     * @return $this
     */
    public function patch($uri, array $data = [], array $options = [])
    {
        $options = $this->configureOptions($options);
        $response = $this->client->patch($uri, array_merge($options, [
            'form_params' => $data,
        ]));
        $this->setGuzzleResponse($response);
        return $this;
    }

    /**
     * @param string $uri
     * @param array $data
     * @param array $options
     * @return $this
     */
    public function delete($uri, array $data = [], array $options = [])
    {
        $options = $this->configureOptions($options);
        $response = $this->client->delete($uri, array_merge($options, [
            'form_params' => $data,
        ]));
        $this->setGuzzleResponse($response);
        return $this;
    }

    /**
     * @param ResponseInterface $response
     */
    public function setGuzzleResponse(ResponseInterface $response)
    {
        $this->guzzle_response = $response;
        $this->setResponse(new Response($response->getBody(), $response->getStatusCode(), $response->getHeaders()));
    }

    /**
     * @param Response $response
     */
    public function setResponse(Response $response)
    {
        $this->response = $response;
        $statusCode = $this->response->getStatusCode();
        if ($statusCode >= 300 && $this->debug_mode) {
            echo "\nResponse STATUS CODE is $statusCode:\n";
            $responseData = $this->getResponseData();
            if ($responseData) {
                $this->printArray($responseData);
            } else {
                echo $this->getResponse();
            }
        }
    }

    /**
     * @param $user_id
     * @return mixed
     */
    public function loginUsingId($user_id)
    {
        return \Auth::loginUsingId($user_id);
    }

    public function printResponseData()
    {
        $this->printArray($this->getResponseData());
        return $this;
    }

    public function printResponseOriginContent()
    {
        $this->printArray((string)$this->response->getOriginalContent());
        return $this;
    }

    protected function printArray($array)
    {
        print_r($array);
    }

}
