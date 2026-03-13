<?php

namespace App\Core\WeatherApi\Contracts;

use Carbon\CarbonInterface;

interface WeatherApiContract
{
    public function getCurrentWeather(string $location): ?array;

    public function getHistoricalWeather(string $location, CarbonInterface $date): ?array;

    public function getForecastWeather(string $location, CarbonInterface $date): ?array;

    public function getWeatherByIp(string $ipAddress, ?CarbonInterface $date = null): ?array;

    public function getLocationFromIp(string $ipAddress): ?array;

    /**
     * @param  array<string, mixed>  $weatherData
     * @return array<string, mixed>
     */
    public function extractWeatherForDailyReport(array $weatherData): array;

    public function hasStoredWeatherData(string $location, CarbonInterface $date, string $type = 'forecast'): bool;
}
