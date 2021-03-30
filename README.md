# sdk-wlvpn
Library for White Label VPN API (WLVPN) v2


# Usage
```
composer require gaalferov/php-sdk-wlvpn
```

# Available actions
```
** Accounts
isUsernameExists
getAccountByUsername
getAccountByCustomerId
createAccount
updateAccount
usageReportByAccount
createAccountLimitation
updateAccountLimitation
deleteAccountLimitation
** Servers
getServers
```

Init new aplication with your secret and default group ID
```php
<?php

use GAAlferov\WLVPN\Exception\WLVPNException;
use GAAlferov\WLVPN\VPNClient;
use GuzzleHttp\Exception\GuzzleException;

require __DIR__ . '/vendor/autoload.php';

$VPNClient = new VPNClient('your_secret', 1111);
try {
    $res = $VPNClient->getServers();
} catch (GuzzleException $e) {
    $response = $e->getResponse();
    var_dump($e->getMessage());
    var_dump($response->getStatusCode());
    var_dump($response->getBody()->getContents());
} catch (WLVPNException $e) {
    var_dump($e->getMessage());
}

var_dump($res);
```
