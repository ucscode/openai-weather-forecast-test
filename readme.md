# OpenAI Weather Forecast Backend Development Test

This repository contains the backend development test for the OpenAI Weather Forecast API.

## API Description

The API provides weather forecast data based on the specified location and liveliness type.

### API Endpoint

```
GET /location/liveliness
```

### Parameters

- `location`: Specifies the place, city, or country for which weather forecast is requested.
- `liveliness`: Specifies the type of response from the OpenAI API. It can be either "funny" or "serious".

### Example Usage

```
GET /Paris/funny
```

This will retrieve a weather forecast for Paris with a funny response.

```
GET /NewYork/serious
```

This will retrieve a weather forecast for New York with a serious response.

> If the liveness is not specified or does not match funny / serious, it will give a generic response.

### Response

The API response will contain weather forecast information based on the provided location and liveliness type.

Example Response (JSON):
```json
{
  "status": "200",
  "success": true,
  "forecast": "The weather in Paris today is sunny with a temperature of 25°C.",
}
```

## Installation

To set up the OpenAI Weather Forecast backend, follow these steps:

1. Clone the repository:

```
git clone https://github.com/your-repository.git
```

2. Install the dependencies using Composer:

```
composer install
```

3. Configure the environment variables:

   - Rename the `.env.example` file to `.env`.
   - Open the `.env` file and provide the necessary values for the environment variables.

4. Start the development server:

```
php bin/console server:start
```

The backend server will start running on the default Symfony development server.

## Development

The API implementation is built using the Symfony framework. The main files and directories to consider for development are:

- `src/Controller/WeatherForecastController.php`: Contains the controller logic for the weather forecast endpoint.
- `templates/weather_forecast/index.html.twig`: Provides the HTML template for the weather forecast response.
- `config/routes.yaml`: Defines the API routes and maps them to the appropriate controller methods.

## Testing

The API can be tested using the provided endpoint and parameters. You can use tools like cURL or API testing tools (e.g., Postman) to send requests to the API endpoint and validate the responses.

## License

This project is licensed under the [MIT License](LICENSE). Feel free to use, modify, and distribute the code as per the license terms.

## Contributing

Contributions are welcome! If you find any issues or want to enhance the functionality of the API, please feel free to submit a pull request.

If you have any questions or need assistance, please reach out to the project maintainers.

## Acknowledgements

This project is developed as part of the backend development test for the OpenAI Weather Forecast API. We acknowledge the contributions and support from the OpenAI team in creating this test.