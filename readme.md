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

