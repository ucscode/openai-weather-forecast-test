<?php

namespace App\Controller;

use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use GuzzleHttp\Client;

class OpenAIResponse extends AbstractController
{
    public const VERSION = '1.0.0';
    public const WS_BASE = "http://api.weatherstack.com/";

    #[Route('/{location}/{liveliness}', name: 'responder', requirements: ['liveliness' => '.*'])]

    public function generateResponse(string $location, ?string $liveliness = null): Response
    {

        // Get weather data using weatherstack.com API

        $data = $this->getWeather($location);

        // Ask OpenAI (ChatGPT) to generate a dynamic response based on data & route

        $response = $this->askOpenAI($data, trim($liveliness));

        return $response;

    }

    public static function failedResponse(?int $error = null, string $text = ''): Response
    {

        $data = json_encode([
            "status" => $error ?? Response::HTTP_NO_CONTENT,
            "success" => false,
            "forecast" => $text,
        ], JSON_PRETTY_PRINT);

        return new Response($data);

    }

    private function getWeather(string $location): array
    {

        $data = array();

        // send request with guzzle
        $guzzle = new Client([
            "base_uri" => self::WS_BASE
        ]);

        $request = $guzzle->request('GET', '/current', [
            "query" => [
                "access_key" => $this->getParameter('weatherstack_key'),
                "query" => $location
            ]
        ]);

        // get status of the request
        $data['status'] = $request->getStatusCode();
        $data['success'] = ($data['status'] == Response::HTTP_OK);

        if($data['success']) {
            $data['context'] = $request->getBody()->getContents();
        } else {
            $data['context'] = null;
        }

        //The weather detail will not be rendered but passed to ChatGPT for a communicatable answer
        return $data;

    }

    private function askOpenAI(array $data, string $liveliness): Response
    {

        // Confirm that weatherstack API did not respond with an error;
        $data = $this->validateData($data);

        // if dataType changed, then an error occured within weatherstack API Response
        if($data instanceof Response) {
            return $data;
        }

        // Else, proceed to querying ChatGPT;

        $openAIKey = $this->getParameter("openai_key"); //

        if(!in_array($liveliness, ['serious', 'funny'])) {
            $reply = 'generic';
        } else {
            $reply = "really {$liveliness}";
        };

        // get a funny or serious description ("generic" if parameter does not match the mentioned type)
        $prompt = sprintf("%1\$s\r\nUsing the JSON data above, give me a %2\$s description about the location's weather", $data['context'], $reply);

        try {

            // Try to catch exceptions (if any)

            $client = \OpenAI::client($openAIKey);

            $result = $client->completions()->create([
                'model' => 'text-davinci-003',
                'prompt' => $prompt,
                'temperature' => 0.69, // allow OpenAI to choose varying answers
                'max_tokens' => 500, // default max_token = 16, which is very short
            ]);

            // In case of multiple choices, select a random one
            $key = array_rand($result['choices']);

            // Capture the OpenAI Text
            $data['forecast'] = $result['choices'][$key]['text'];

            // Remove the weatherstack.com data from the generated output
            unset($data['context']);

            // Create a new symfony response
            $response = new Response(json_encode($data, JSON_PRETTY_PRINT));

        } catch(Exception $e) {

            $response = self::failedResponse();

        }

        return $response;

    }

    private function validateData($data)
    {

        // The weather detail (JSON)
        $detail = json_decode($data['context'], true);

        $message = "Unknown Error";

        if(json_last_error() || empty($detail)) {

            $code = Response::HTTP_BAD_REQUEST;

        } elseif(isset($detail['error'])) {

            $code = $detail['error']['code'];
            if((int)substr($code, 0, 1) !== 1) {
                $message = $detail['error']['info'];
            }

        };

        // Return an error response
        if(isset($code)) {
            return self::failedResponse($code, $message);
        };

        return $data;

    }

}
