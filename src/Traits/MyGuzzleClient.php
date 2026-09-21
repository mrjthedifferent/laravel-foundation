<?php

namespace Mrj\Foundation\Traits;

use GuzzleHttp\Client;
use GuzzleHttp\Exception\ConnectException;
use GuzzleHttp\Exception\GuzzleException;
use GuzzleHttp\Exception\RequestException;
use Illuminate\Support\Facades\Log;
use JsonException;

trait MyGuzzleClient
{
    private int $guzzleTimeout = 60;

    private int $guzzleConnectTimeout = 30;

    public function guzzle_get_call($url, $header_data, $params = null)
    {
        try {
            $client = new Client([
                'timeout' => $this->guzzleTimeout,
                'connect_timeout' => $this->guzzleConnectTimeout,
            ]);
            $request_data = ['headers' => $header_data];
            $request_data += ['verify' => false];
            if ($params) {
                $request_data['query'] = $params;
            }
            $response = $client->request('GET', $url, $request_data);
            if ($response->getStatusCode() === 200) {
                $body = $response->getBody();
                if ($body) {
                    return json_decode($body, false, 512, JSON_THROW_ON_ERROR);
                }
            }
        } catch (ConnectException $e) {
            Log::warning("Connection timeout for {$url}: ".$e->getMessage());

            return false;
        } catch (RequestException $e) {
            Log::warning("Request failed for {$url}: ".$e->getMessage());

            return false;
        } catch (\Exception $e) {
            Log::error("Guzzle GET call failed for {$url}: ".$e->getMessage());

            return false;
        }

        return false;
    }

    /**
     * @throws GuzzleException
     * @throws JsonException
     */
    public function guzzle_post_call($post_data, $url, $header_data = [], $query_params = [])
    {
        $client = new Client([
            'timeout' => $this->guzzleTimeout,
            'connect_timeout' => $this->guzzleConnectTimeout,
        ]);
        $request_data = ['form_params' => $post_data];
        if ($header_data) {
            $request_data += ['headers' => $header_data];
        }
        if ($query_params) {
            $request_data += ['query' => $query_params];
        }
        $request_data += ['verify' => false];
        $response = $client->request('POST', $url, $request_data);
        if ($response->getStatusCode() === 200) {
            $body = $response->getBody();
            if ($body) {
                return json_decode($body, false, 512, JSON_THROW_ON_ERROR);
            }
        }

        return false;
    }

    /**
     * @throws GuzzleException
     * @throws JsonException
     */
    public function guzzle_post_call_json($post_data, $url, $header_data = [])
    {
        $client = new Client([
            'timeout' => $this->guzzleTimeout,
            'connect_timeout' => $this->guzzleConnectTimeout,
        ]);
        $request_data = ['json' => $post_data];
        if ($header_data) {
            $request_data += ['headers' => $header_data];
        }
        $request_data += ['verify' => false];
        $response = $client->request('POST', $url, $request_data);

        if ($response->getStatusCode() === 200) {
            $body = $response->getBody();

            return json_decode($body, false, 512, JSON_THROW_ON_ERROR);
        }

        if ($response->getStatusCode() === 400) {
            $body = $response->getBody();

            return json_decode($body, false, 512, JSON_THROW_ON_ERROR);
        }

        return false;
    }

    public function guzzle_post_call_attachment($post_data, $url, $header_data = [])
    {
        $client = new Client([
            'timeout' => $this->guzzleTimeout,
            'connect_timeout' => $this->guzzleConnectTimeout,
        ]);
        $request_data = ['multipart' => $post_data];
        $request_data += ['headers' => $header_data];
        $request_data += ['verify' => false];
        $response = $client->request('POST', $url, $request_data);
        if ($response->getStatusCode() == 200) {
            $body = $response->getBody();
            if ($body) {
                // \Log::debug($body);
                return json_decode($body);
            }
        }

        return false;
    }
}
