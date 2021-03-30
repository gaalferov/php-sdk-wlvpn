<?php

declare(strict_types=1);

namespace GAAlferov\WLVPN;

use DateTime;
use GAAlferov\WLVPN\Exception\WLVPNException;
use GuzzleHttp\Client;
use GAAlferov\WLVPN\Service\ResponseService;
use GAAlferov\WLVPN\Exception\InvalidResponseCodeException;
use GuzzleHttp\Exception\GuzzleException;

/**
 * Class VPNClient
 */
class VPNClient
{
    /**
     * @var string
     */
    public const API_URL = 'https://api.wlvpn.com';

    /**
     * @var Client
     */
    private $guzzleClient;

    /**
     * @var int
     */
    private $defaultGroupId;

    /**
     * VPNClient constructor.
     *
     * @param string $apiKey
     * @param int    $defaultGroupId
     * @param array  $clientConfig
     */
    public function __construct(string $apiKey, int $defaultGroupId, array $clientConfig = [])
    {
        $this->defaultGroupId = $defaultGroupId;
        $defaultConfigs = [
            'base_uri' => self::API_URL,
            'auth' => ['api-key', $apiKey]
        ];
        $this->guzzleClient = new Client(array_merge($defaultConfigs, $clientConfig));
    }

    /**
     * Check Username is a get request
     *
     * @param string $username
     *
     * @return bool
     * @throws InvalidResponseCodeException|GuzzleException
     */
    public function isUsernameExists(string $username): bool
    {
        $result = $this->guzzleClient->request('GET', sprintf('/v2/username_exists/%s', $username));

        return ResponseService::getResponseData($result)['username_exists'] === 1;
    }

    /**
     * It allows retrieving account info by username.
     *
     * @param string|int $username
     *
     * @return array
     * @throws WLVPNException|GuzzleException
     */
    public function getAccountByUsername(string $username): array {
        $result = $this->guzzleClient->request('GET', sprintf('/v2/customers/username/%s', $username));

        return ResponseService::getResponseData($result)['customer'];
    }

    /**
     * It allows retrieving account info by customer ID.
     *
     * @param int $customerId
     *
     * @return array
     * @throws WLVPNException|GuzzleException
     */
    public function getAccountByCustomerId(int $customerId): array {
        $result = $this->guzzleClient->request('GET', sprintf('/v2/customers/%s', $customerId));

        return ResponseService::getResponseData($result)['customer'];
    }

    /**
     * When a request is successful and the account is created,
     * the cust_id will be returned in the response body with the ID that was created for the account
     *
     * @param string|int    $username
     * @param string        $password
     * @param int|null      $acctGroupId
     * @param DateTime|null $closeDate
     *
     * @return int
     * @throws WLVPNException|GuzzleException
     */
    public function createAccount(
        string $username,
        string $password,
        int $acctGroupId = null,
        DateTime $closeDate = null
    ): int {
        $data = [
            'cust_user_id' => $username,
            'cust_password' => $password,
            'acct_group_id' => $acctGroupId ?? $this->defaultGroupId,
        ];

        if (null !== $closeDate) {
            $data['close_date'] = $closeDate->format('Y-m-d');
        }

        $result = $this->guzzleClient->request('POST', '/v2/customers', ['json' => $data]);

        return ResponseService::getResponseData($result)['cust_id'];
    }

    /**
     * Updates information for an account in the VPN Platform by ID or username.
     * The only properties that we support updating are close_date, cust_password, acct_status_id and acct_group_id.
     * All other properties are considered read-only.
     * Statuses:
     * 1 - Active
     * 2 - Suspended
     * 3 - Closed
     *
     * @param int           $accountId
     * @param int           $accountStatus
     * @param string|null   $password
     * @param DateTime|null $closeDate
     * @param int|null      $acctGroupId
     *
     * @return bool
     * @throws WLVPNException|GuzzleException
     */
    public function updateAccount(
        int $accountId,
        int $accountStatus,
        string $password = null,
        DateTime $closeDate = null,
        int $acctGroupId = null
    ): bool {
        $data = [
            "acct_status_id" => $accountStatus,
            "acct_group_id" => $acctGroupId ?? $this->defaultGroupId,
        ];

        if (null !== $password) {
            $data['cust_password'] = $password;
        }

        if (null !== $closeDate) {
            $data['close_date'] = $closeDate->format('Y-m-d');
        }

        $result = $this->guzzleClient->request('PUT', sprintf('/v2/customers/%s', $accountId), ['json' => $data]);

        return is_array(ResponseService::getResponseData($result));
    }

    /**
     * @param int           $accountId
     * @param DateTime      $startDate
     * @param array         $metrics
     * @param DateTime|null $endDate
     *
     * @return array
     * @throws WLVPNException|GuzzleException
     */
    public function usageReportByAccount(
        int $accountId,
        DateTime $startDate,
        array $metrics,
        DateTime $endDate = null
    ): array {
        $data = [
            'start_date' => $startDate->format('Y-m-d'),
            'metrics' => $metrics,
        ];

        if (null !== $endDate) {
            $data['end_date'] = $endDate->format('Y-m-d');
        }

        $result = $this->guzzleClient->request(
            'POST',
            sprintf('/v2/customers/%s/usage-report', $accountId),
            ['json' => $data]
        );

        return ResponseService::getResponseData($result)['usage'];
    }

    /**
     * @param int        $accountId
     * @param string|int $value
     * @param string     $type
     *
     * @return bool
     * @throws WLVPNException|GuzzleException
     */
    public function createAccountLimitation(int $accountId, $value, string $type = 'rate-limit'): bool
    {
        $data = [
            'limitations' => [
                'type' => $type,
                'value' => $value,
            ],
        ];
        $result = $this->guzzleClient->request(
            'POST',
            sprintf('/v2/customers/%s/limitations', $accountId),
            ['json' => $data]
        );

        return is_array(ResponseService::getResponseData($result));
    }

    /**
     * @param int        $accountId
     * @param string|int $value
     * @param string     $type
     *
     * @return bool
     * @throws WLVPNException|GuzzleException
     */
    public function updateAccountLimitation(int $accountId, $value, string $type = 'rate-limit'): bool
    {
        $data = [
            'limitations' => [
                'type' => $type,
                'value' => $value,
            ],
        ];
        $result = $this->guzzleClient->request(
            'PUT',
            sprintf('/v2/customers/%s/limitations', $accountId),
            ['json' => $data]
        );

        return is_array(ResponseService::getResponseData($result));
    }

    /**
     * @param int $accountId
     *
     * @return bool
     * @throws WLVPNException|GuzzleException
     */
    public function deleteAccountLimitation(int $accountId): bool
    {
        $result = $this->guzzleClient->request('DELETE', sprintf('/v2/customers/%s/limitations', $accountId));

        return is_array(ResponseService::getResponseData($result));
    }

    /**
     * @return array
     * @throws WLVPNException|GuzzleException
     */
    public function getServers(): array
    {
        $result = $this->guzzleClient->request('GET', '/v2/servers');

        return ResponseService::getResponseData($result)['server'];
    }
}
