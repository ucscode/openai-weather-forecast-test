<?php

namespace App\Controller;

use GuzzleHttp\Client;
use Symfony\Component\Dotenv\Dotenv;
use Orhanerday\OpenAi\OpenAi;

class WeatherForecast
{
    protected string $weatherstackEndpoint = "http://api.weatherstack.com/current";
    protected string $openAIEndpoint = 'https://api.openai.com/v1/chat/completions';

    public function __construct($env = '.env')
    {

        $files = array_filter(array_map(function ($file) {
            $file = trim($file);
            $path = realpath(__DIR__ . "/../../") . "/{$file}";
            return is_file($path) ? $path : null;
        }, explode(",", $env)));

        /**
         * Load the .env variables
         * However, you can user a different env file such as .env.local / .env.test
         */
        (new Dotenv())->usePutenv()->load($files[0]);

    }

    # Get the weather from weatherstack API

    public function getWeather(string $location, array $config = []): array
    {

        $location = str_replace("-", " ", $location);

        $client = new Client($config); // Mockable for testing

        # Send a request to weatherstack
        $response = $client->request('GET', $this->weatherstackEndpoint, [
            "query" => [
                "access_key" => getenv('WEATHERSTACK_API_KEY'),
                "query" => $location
            ]
        ]);

        # Get the response
        $content = $response->getBody()->getContents();

        # Return array
        return json_decode($content, true);

    }

    # Get ChatGPT Response;

    public function getChat(OpenAi $openAI, array $data, string $tone): array
    {

        #  A successful weatherstack response contains the "request" key

        if(!isset($data['request'])) {
            $data['source'] = 'Weather Stack';
            $this->throwException("Oh no! We couldn't provide the information you are looking for", $data);
        };

        # Get response from OpenAI (ChatGPT) endpoint

        $request = [
            "model" => "gpt-3.5-turbo",
            "temperature" => 0.7,
            'max_tokens' => 500,
            'frequency_penalty' => 0,
            'presence_penalty' => 0,
            "messages" => $this->conversation($data, $tone)
        ];

        $chat = $openAI->chat($request);

        $response = json_decode($chat, true);

        if(isset($response['error'])) {
            $response['source'] = 'OpenAI';
            $this->throwException("Sorry! We are unable to give a response about the weather at this moment", $response);
        };

        return $response;

    }

    public function respondWith(int $code, string $message, array $data = [])
    {

        $JsonResponse = array(
            "name" => "OpenAI Weather Forecast Test",
            "time" => time(),
            "success" => ($code == 200),
            "status" => $code,
            "message" => $message,
            "info" => $data
        );

        return json_encode($JsonResponse, JSON_PRETTY_PRINT);

    }

    # Generate Conversation

    public function conversation(array $ws_data, string $tone)
    {

        /**
         * Gather only important information as the number of tokens are limited!
         * Since ChatGPT has sufficient information before 2021, it should be able to get basic information
         * By itself such as timezone of a location, language etc.
         * So to limit token usage, we will only compile information that ChatGPT cannot easily get
         */
        $data = [];

        $data['location'] = "{$ws_data['location']['name']}, {$ws_data['location']['country']}";
        $data['time'] = $ws_data['location']['localtime'];
        $data['lat'] = $ws_data['location']['lat'];
        $data['lon'] = $ws_data['location']['lon'];

        $data['weather'] = array();
        $replace_key = [
            "weather_code" => "code",
            "weather_descriptions" => "description"
        ];

        foreach($ws_data['current'] as $key => $value) {

            if(in_array($key, ['weather_icons', 'observation_time'])) {
                continue;
            };

            if(array_key_exists($key, $replace_key)) {
                $key = $replace_key[$key];
            }

            $data['weather'][$key] = $value;

        };

        $data['tone'] = $tone;

        $history = [
            [
                "role" => "system",
                "content" => "While considering the \"tone\" attribute, respond in a very funny or serious manner about weather using the following JSON data " . json_encode($data)
            ],
            [
                "role" => "user",
                "content" => "The weather seems strange today. I'm curious to know how it is elsewhere."
            ],
            [
                "role" => "assistant",
                "content" => "I can give you a {$data['tone']} detail if you tell me the location on your mind."
            ],
            [
                "role" => "user",
                "content" => "Okay, what the weather is like in {$data['location']}."
            ]
        ];

        return $history;

    }

    public function throwException(string $message, array $info = [], $statusCode = 400)
    {
        $exception = new \Exception($message);
        $exception->info = $info + ['source' => 'self'];
        $exception->statusCode = $statusCode;
        throw $exception;
    }

}
