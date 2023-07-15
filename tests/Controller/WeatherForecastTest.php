<?php

declare(strict_types=1);

namespace App\Tests\Controller;

# Import PHPUnit;
use PHPUnit\Framework\TestCase;

# Import Guzzle & Mockery Attribute;
use GuzzleHttp\Client;
use GuzzleHttp\HandlerStack;
use GuzzleHttp\Handler\MockHandler;
use GuzzleHttp\Psr7\Response;

# Import the testable classes;
use App\Controller\WeatherForecast;
use Orhanerday\OpenAi\OpenAi;

class WeatherForecastTest extends TestCase
{
    public function setup(): void
    {
        $this->wfcast = new WeatherForecast(".env.test.local");
    }

    public function testGetWeather()
    {

        # get weather stack data;

        $ws_data = $this->wfcast->getWeather("New York", self::guzzleMock());

        # print_r( $data );

        $this->assertIsArray($ws_data);

        $valid = array_key_exists("request", $ws_data) || array_key_exists("success", $ws_data);

        $this->assertTrue($valid);

        return $ws_data;

    }

    /**
     * @depends testGetWeather
     */
    public function testGetChat($ws_data)
    {

        # Mock OpenAI Class

        $openAI = $this->createMock(OpenAI::class);
        $openAI->expects($this->atMost(1))
            ->method("chat")
            ->willReturn(self::chatMock());

        if(!isset($ws_data['request'])) {
            $this->expectException(\Exception::class);
        };

        $chat = $this->wfcast->getChat($openAI, $ws_data, "funny");

        $this->assertIsArray($chat);

    }

    public static function guzzleMock()
    {

        $mockery = [

            # First Response Test For Error JSON
            new Response(200, [], '{"success":false,"error":{"code":105,"type":"https_access_restricted","info":"Access Restricted - Your current Subscription Plan does not support HTTPS Encryption."}}'),

            # Second Response Test For Success JSON
            new Response(200, [], '{"request":{"type":"City","query":"New York, United States of America","language":"en","unit":"m"},"location":{"name":"New York","country":"United States of America","region":"New York","lat":"40.714","lon":"-74.006","timezone_id":"America\/New_York","localtime":"2023-07-11 01:06","localtime_epoch":1689037560,"utc_offset":"-4.0"},"current":{"observation_time":"05:06 AM","temperature":24,"weather_code":113,"weather_icons":["https:\/\/cdn.worldweatheronline.com\/images\/wsymbols01_png_64\/wsymbol_0008_clear_sky_night.png"],"weather_descriptions":["Clear"],"wind_speed":4,"wind_degree":333,"wind_dir":"NNW","pressure":1012,"precip":0,"humidity":50,"cloudcover":0,"feelslike":26,"uv_index":1,"visibility":16,"is_day":"no"}}'),

        ];

        shuffle($mockery);

        $mock = new MockHandler($mockery);

        # Return Mocked Data;

        return ["handler" => HandlerStack::create($mock)];

    }

    public static function chatMock()
    {

        $response = [
            '{
                "error": {
                    "message": "An error occurred during chat completion.",
                    "type": "invalid_request_error",
                    "code": null,
                    "param": null
                }
            }',
            '{
                "id":"chatcmpl-abc123",
                "object":"chat.completion",
                "created":1677858242,
                "model":"gpt-3.5-turbo-0301",
                "usage":{
                "prompt_tokens":13,
                "completion_tokens":7,
                "total_tokens":20
                },
                "choices":[
                {
                    "message":{
                        "role":"assistant",
                        "content":"\n\nThis is a test!"
                    },
                    "finish_reason":"stop",
                    "index":0
                }
                ]
            }'

        ];

        shuffle($response);

        return $response[0];

    }

}
