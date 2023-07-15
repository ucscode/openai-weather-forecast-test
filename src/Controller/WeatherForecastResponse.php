<?php

namespace App\Controller;

# Import Symfony Resources
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\HttpFoundation\Response;

# Import Guzzle Resources
use GuzzleHttp\Exception\ClientException;
use GuzzleHttp\Psr7;

# Import OpenAI;
use Orhanerday\OpenAi\OpenAi;

class WeatherForecastResponse extends AbstractController
{
    protected $wfcast;

    #[Route('/{region}/{tone}', name: 'forecast')]

    public function ForecastInterface(string $region = '', string $tone = ''): Response
    {

        $this->wfcast = new WeatherForecast(".env.local, .env");

        try {

            $this->testParameters($region, $tone);

            # Get Weather

            $weather = $this->wfcast->getWeather($region);

            # Get Reply

            $openAI = new OpenAI(getenv('OPENAI_API_KEY'));

            $chat = $this->wfcast->getChat($openAI, $weather, $tone);

            $message = trim($chat['choices'][0]['message']['content']);

            # Output Response

            $responseAPI = $this->wfcast->respondWith(200, $message);

        } catch(ClientException $e) {

            if($e->hasResponse()) {

                $response = $e->getResponse()->getBody(); // will cast to string

                $responseAPI = $this->wfcast->respondWith($e->getStatusCode(), $message);

            } else {

                $responseAPI = $this->wfcast->respondWith(400, "The request was not successful");

            }

        } catch(\Exception $e) {

            $message = "Service Unavailable: The request cannot be process due to semantic errors";
            $status = 503;
            $info = [];

            if(!empty($e->info)) {
                $message = $e->getMessage();
                $info = $e->info;
                $status = $e->statusCode ?? 422;
            };

            //$message = "{$e->getMessage()} on line {$e->getLine()} @ {$e->getFile()}";

            # Bad Request!
            $responseAPI = $this->wfcast->respondWith($status, $message, $info);

        };

        return new Response($responseAPI);

    }

    private function testParameters(string $region, string $tone)
    {

        if(empty($region)) {

            $this->wfcast->throwException("This endpoint requires a location", [
                "error" => "No location specified"
            ]);

        } elseif(empty($tone)) {

            $this->wfcast->throwException("This endpoint required a funny or serious tone", [
                "error" => "No tone specified"
            ]);

        } elseif(!in_array(trim($tone), ['funny', 'serious'])) {

            $this->wfcast->throwException("Tone should either be funny or serious, \"{$tone}\" given instead", [
                "error" => "Invalid tone specification"
            ]);

        }

    }

}
