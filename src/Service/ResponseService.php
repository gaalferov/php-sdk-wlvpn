<?php

declare(strict_types=1);

namespace GAAlferov\WLVPN\Service;

use GAAlferov\WLVPN\Exception\InvalidApiStatusException;
use GAAlferov\WLVPN\Exception\InvalidResponseCodeException;
use Psr\Http\Message\ResponseInterface;

class ResponseService
{
    const SUCCESS = 200;
    const NO_CONTENT = 204;
    const BAD_REQUEST = 400;
    const UNAUTHORISED = 401;
    const NOT_FOUND = 404;
    const NO_LONGER_EXISTS = 410;
    const TOO_MANY_REQUESTS = 429;
    const INTERNAL_SERVER_ERROR = 500;
    const BAD_GATEWAY = 502;
    const SERVICE_UNAVAILABLE = 503;

    /**
     * @param ResponseInterface $response
     *
     * @return array
     *
     * @throws InvalidResponseCodeException
     * @throws InvalidApiStatusException
     */
    public static function getResponseData(ResponseInterface $response): array
    {
        if (!in_array($response->getStatusCode(), [self::SUCCESS, self::NO_CONTENT])) {
            throw new InvalidResponseCodeException('Unknown WLVPN response status code - ' . $response->getStatusCode());
        }

        if ($response->getStatusCode() === self::NO_CONTENT) {
            return [];
        }

        $data = json_decode($response->getBody()->getContents(), true);
        if ($data['api_status'] !== 1) {
            throw new InvalidApiStatusException($data['error']);
        }
        // left only really necessary information
        unset($data['api_status']);

        return $data;
    }
}
