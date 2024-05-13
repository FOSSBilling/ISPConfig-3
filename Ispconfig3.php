<?php

/**
 * Copyright 2022-2024 FOSSBilling
 * Copyright 2011-2021 BoxBilling, Inc.
 * SPDX-License-Identifier: Apache-2.0.
 *
 * @copyright FOSSBilling (https://www.fossbilling.org)
 * @license   http://www.apache.org/licenses/LICENSE-2.0 Apache-2.0
 */
class Server_Manager_Ispconfig3 extends Server_Manager
{

    private $_session = null;

    public function __destruct()
    {
        $this->request('logout', []);
        unset($this->session);
    }

    /**
     * Returns server manager parameters.
     *
     * @return array returns an array with the label of the server manager
     */
    public static function getForm(): array
    {
        return [
            'label' => 'ISPConfig3',
        ];
    }

    /**
     * Returns the URL for account management.
     *
     * @param Server_Account|null $account the account for which the URL is generated
     *
     * @return string returns the URL as a string
     */
    public function getLoginUrl(Server_Account $account = null): string
    {
        $useSsl = $this->_config['secure'];
        $host = $this->_config['host'];
        $port = !empty($this->_config['port']) ? ':'.$this->_config['port'].'/' : '';
        $host = ($useSsl) ? 'https://'.$host : 'http://'.$host;

        return $host.$port;
    }

    /**
     * Returns the URL for reseller account management.
     *
     * @param Server_Account|null $account the account for which the URL is generated
     *
     * @return string returns the URL as a string
     */
    public function getResellerLoginUrl(Server_Account $account = null): string
    {
        $useSsl = $this->_config['secure'];
        $host = $this->_config['host'];
        $port = !empty($this->_config['port']) ? ':'.$this->_config['port'].'/' : '';
        $host = ($useSsl) ? 'https://'.$host : 'http://'.$host;

        return $host.$port;
    }

    /**
     * Tests the connection to the server.
     *
     * @return bool returns true if the connection is successful
     */
    public function testConnection(): bool
    {
        $this->_loadConfigAndLogin();

        return true;
    }

    /**
     * Synchronizes the account with the server.
     *
     * @param Server_Account $account the account to be synchronized
     *
     * @return Server_Account returns the synchronized account
     */
    public function synchronizeAccount(Server_Account $account): Server_Account
    {
        throw new Server_Exception(':type: does not support :action:', [':type:' => 'ISPConfig 3', ':action:' => __trans('account synchronization')]);
    }

    /**
     * Creates a new account on the server.
     *
     * @param Server_Account $account the account to be created
     *
     * @return bool returns true if the account is successfully created
     */
    public function createAccount(Server_Account $account)
    {
        $response = $this->getClientData($account);

        if ($account->getReseller()) {
            $isReseller = 1;
        } else {
            $isReseller = 0;
        }

        if(! $response['response']) {
            $request = $this->createClient($account, $isReseller);

            $id = $request['response'];
        } else {
            $id = $response['response'];
        }

        $client = $account->getClient();
        $client->setId($id);

        $this->createSite($account);
        $this->createDNSZone($account);
    }

    /**
     * Suspends an account on the server.
     *
     * @param Server_Account $account the account to be suspended
     *
     * @return bool returns true if the account is successfully suspended
     */
    public function suspendAccount(Server_Account $account)
    {
        $response = $this->request('sites_web_domain_set_status', [
            'primary_id' => $this->getPrimaryId($account),
            'status' => 'inactive'
        ]);

        return $response['response'];
    }

    /**
     * Unsuspends an account on the server.
     *
     * @param Server_Account $account the account to be unsuspended
     *
     * @return bool returns true if the account is successfully unsuspended
     */
    public function unsuspendAccount(Server_Account $account): bool
    {
        $response = $this->request('sites_web_domain_set_status', [
            'primary_id' => $this->getPrimaryId($account),
            'status' => 'active'
        ]);

        return $response['response'];
    }

    /**
     * Cancels an account on the server.
     *
     * @param Server_Account $account the account to be cancelled
     *
     * @return bool returns true if the account is successfully cancelled
     */
    public function cancelAccount(Server_Account $account): bool
    {
        $client = $this->getClientData($account);

        $response = $this->request('client_delete_everything', [
            'client_id' => $client['response']['client_id']
        ]);

        return $response['response'];
    }

    /**
     * Changes the package of an account on the server.
     *
     * @param Server_Account $account the account for which the package is to be changed
     * @param Server_Package $package the new package
     *
     * @return bool returns true if the package is successfully changed
     */
    public function changeAccountPackage(Server_Account $account, Server_Package $package): bool
    {
        throw new Server_Exception(':type: does not support :action:', [':type:' => 'ISPConfig 3', ':action:' => __trans('changing the account IP')]);

    }

    /**
     * Changes the username of an account on the server.
     *
     * @param Server_Account $account     the account for which the username is to be changed
     * @param string         $newUsername the new username
     *
     * @return bool returns true if the username is successfully changed
     */
    public function changeAccountUsername(Server_Account $account, string $newUsername): bool
    {
        throw new Server_Exception(':type: does not support :action:', [':type:' => 'ISPConfig 3', ':action:' => __trans('username changes')]);

    }

    /**
     * Changes the domain of an account on the server.
     *
     * @param Server_Account $account   the account for which the domain is to be changed
     * @param string         $newDomain the new domain
     *
     * @return bool returns true if the domain is successfully changed
     */
    public function changeAccountDomain(Server_Account $account, string $newDomain): bool
    {
        throw new Server_Exception(':type: does not support :action:', [':type:' => 'ISPConfig 3', ':action:' => __trans('changing the account domain')]);

    }

    /**
     * Changes the password of an account on the server.
     *
     * @param Server_Account $account     the account for which the password is to be changed
     * @param string         $newPassword the new password
     *
     * @return bool returns true if the password is successfully changed
     */
    public function changeAccountPassword(Server_Account $account, string $newPassword): bool
    {
        $client = $this->getClientData($account);

        $response = $this->request('client_change_password', [
            'client_id' => $client['response']['client_id'],
            'password' => $newPassword
        ]);

        return $response['response'];
    }

    /**
     * Changes the IP of an account on the server.
     *
     * @param Server_Account $account the account for which the IP is to be changed
     * @param string         $newIp   the new IP
     *
     * @return bool returns true if the IP is successfully changed
     */
    public function changeAccountIp(Server_Account $account, string $newIp): bool
    {
        throw new Server_Exception(':type: does not support :action:', [':type:' => 'ISPConfig 3', ':action:' => __trans('changing the account IP')]);
    }

    /**
     * Private Functions
     */

    private function createClient(Server_Account $account, int $isReseller)
    {
        $client  = $account->getClient();
        $package = $account->getPackage();

        // Will need to sort through this, see what exactly we need.
        $payload = [
            'params' => [
                'server_id' => 1,
                'company_name' => $client->getCompany(),
                'contact_name' => $client->getFullName(),
                'vat_id' => $package->getCustomValue('vat_id'),
                'street' => $client->getStreet(),
                'zip' => $client->getZip(),
                'city' => $client->getCity(),
                'state' => $client->getState(),
                'country' => $client->getCountry(),
                'telephone' => $client->getTelephone(),
                'mobile' => $client->getTelephone(),
                'fax' => $client->getTelephone(),
                'email' => $client->getEmail(),
                'internet' => $client->getWww(),
                'icq' => '',
                'notes' => $account->getNote(),
                'default_mailserver' => 1,
                'limit_maildomain' => -1,
                'limit_mailbox' => -1,
                'limit_mailalias' => -1,
                'limit_mailaliasdomain' => -1,
                'limit_mailforward' => -1,
                'limit_mailcatchall' => -1,
                'limit_mailrouting' => 0,
                'limit_mail_wblist' => 0,
                'limit_mailfilter' => -1,
                'limit_fetchmail' => -1,
                'limit_mailquota' => -1,
                'limit_spamfilter_wblist' => 0,
                'limit_spamfilter_user' => 0,
                'limit_spamfilter_policy' => 1,
                'default_webserver' => 1,
                'limit_web_ip' => '',
                'limit_web_domain' => -1,
                'limit_web_quota' => -1,
                'web_php_options' => $package->getCustomValue('web_php_options') ?? 'no,fast-cgi,cgi,mod,suphp,php-fpm',
                'limit_web_subdomain' => -1,
                'limit_web_aliasdomain' => -1,
                'limit_ftp_user' => -1,
                'limit_shell_user' => $package->getCustomValue('limit_shell_user') ?? 1,
                'ssh_chroot' => $package->getCustomValue('ssh_chroot') ?? 'no,jailkit,ssh-chroot',
                'limit_webdav_user' => 0,
                'default_dnsserver' => 1,
                'limit_dns_zone' => -1,
                'limit_dns_slave_zone' => -1,
                'limit_dns_record' => -1,
                'default_dbserver' => 1,
                'limit_database' => -1,
                'limit_cron' => 0,
                'limit_cron_type' => 'url',
                'limit_cron_frequency' => 5,
                'limit_traffic_quota' => -1,
                'limit_client' => 0, // If this value is > 0, then the client is a reseller
                'parent_client_id' => 0,
                'username' => $account->getUsername(),
                'password' => $account->getPassword(),
                'language' => $package->getCustomValue('language') ?? 'en',
                'usertheme' => 'default',
                'template_master' => 0,
                'template_additional' => '',
                'created_at' => 0
            ]
        ];

        $response = $this->request('client_add', $payload);

        return $response;
    }


    private function createSite(Server_Account $account)
    {
        //@TODO Check if site is already created.
        //@TODO Check these settings and see if we need to provide a custom input for them in the plan details.

        $client = $account->getClient();
        $package = $account->getPackage();

        $payload = [
            'params' => [
            'client_id' => $client->getId(),
            'domain' => $account->getDomain(),
            'type' => 'vhost',
            'vhost_type' => 'name',

            'client_group_id' => $client->getId() + 1,
            'server_id' => 1,

            'hd_quota' => $package->getQuota(),
            'traffic_quota' => $package->getBandwidth(),
            'traffic_quota_lock' => 'y',

            'allow_override' => 'ALL',
            'errordocs' => 1,


            'is_subdomainwww' => 1,
            'subdomain' => 'none',

            'php' => 'y',
            'cgi' => 'y',

            'php' => 'php-fpm',
            'ip_address' => '*',
            'active' => 'y',

            'ssl' => 'y',


            'pm' => 'ondemand',
            'pm_process_idle_timeout' => 30,
            'pm_max_requests' => 30,

            'http_port' => '80',
            'https_port' => '443'
            ]
        ];


        $response = $this->request('sites_web_domain_add', $payload);

        return $response;
    }

    private function createDNSZone(Server_Account $account)
    {
        $client = $account->getClient();

        // Setup DNSZone
        $zone = $this->request(
            'dns_zone_add',
            [
            'client_id' => $client->getId(),
            'params' => [
                'server_id' => 1,
                'origin' => $account->getDomain(),
                'ns' => $account->getNs1(),
                'zone' => $client->getId(),
                'name' => $account->getDomain(),
                'type' => 'A',
                'data' => $account->getIp(),
                'mbox' => 'mail.'.$account->getDomain().'.',
                'refresh' => '7200',
                'retry' => '540',
                'expire' => '604800',
                'minimum' => '86400',
                'ttl' => '3600',
                'active' => 'y'
            ]
        ]
        );

        $zoneId = $zone['response']; // Grab the zone ID to add the other records

        //Adding the DNS record A
        $testing = $this->request('dns_a_add', [
           'client_id' => $client->getid(),
           'params' => [
               'server_id' => 1,
               'zone' => $zoneId,
               'name' => $account->getDomain().'.',
               'type' => 'A',
               'data' => $account->getIp(),
               'ttl' => '3600',
               'active' => 'y',
               'stamp' => date('Y-m-d H:i:s')
               ]
           ]);


        //Adding the DNS record A
        $this->request('dns_a_add', [
           'client_id' => $client->getid(),
           'params' => [
               'server_id' => 1,
               'zone' => $zoneId,
               'name' => 'www',
               'type' => 'A',
               'data' => $account->getIp(),
               'ttl' => '3600',
               'active' => 'y',
           ]
           ]);

        //Adding the DNS record A
        $this->request('dns_a_add', [
        'client_id' => $client->getid(),
        'params' => [
        'server_id' => 1,
        'zone' => $zoneId,
        'name' => 'mail',
        'type' => 'A',
        'data' => $account->getIp(),
        'ttl' => '3600',
        'active' => 'y',
        'stamp' => date('Y-m-d H:i:s')
        ]
        ]);


        //Adding the DNS record NS1
        $this->request('dns_ns_add', [
           'client_id' => $client->getid(),
           'params' => [
            'server_id' => 1,
            'zone' => $zoneId,
            'name' => $account->getDomain().'.',
            'type' => 'ns',
            'data' => $account->getNs1().'.',
            'aux' => '0',
            'ttl' => '86400',
            'active' => 'Y',
            'stamp' => date('Y-m-d H:i:s'),
            'serial' => '1',
            ]
           ]);


        //Adding the DNS record NS1
        $this->request('dns_ns_add', [
           'client_id' => $client->getid(),
           'params' => [
            'server_id' => 1,
            'zone' => $zoneId,
            'name' => $account->getDomain().'.',
            'type' => 'ns',
            'data' => $account->getNs2().'.',
            'aux' => '0',
            'ttl' => '86400',
            'active' => 'Y',
            'stamp' => date('Y-m-d H:i:s'),
            'serial' => '1',
            ]
           ]);

        $this->request('mail_domain_add', [
           'client_id' => $client->getId(),
           'server_id' => 1,
           'domain' => $account->getDomain(),
           'active' => 'y'
        ]);
    }

    private function getPrimaryId(Server_Account $account)
    {
        $sites = $this->getClientSites($account);

        if (is_array($sites['response'])) {
            foreach($sites['response'] as $key=>$domain) {
                if($account->getDomain() == $domain['domain']) {
                    return $domain['domain_id'];
                }
            }
        }

        return false;
    }

    private function getClientSites(Server_Account $account)
    {
        $client = $this->getClientData($account);

        $response = $this->request('client_get_sites_by_user', [
            'sys_userid' => $client['response']['userid'],
            'sys_groupid' => $client['response']['groups']
        ]);

        return $response;
    }


    private function getClientData(Server_Account $account)
    {
        $response = $this->request('client_get_by_username', [
            'username' => $account->getUsername()
        ]);

        return $response;
    }


    private function request($action, $data)
    {
        $useSsl = $this->_config['secure'];
        $host = $this->_config['host'];
        $username = $this->_config['username'];
        $password = $this->_config['password'];
        $port = !empty($this->_config['port']) ? ':'.$this->_config['port'].'/' : '';
        $host = ($useSsl) ? 'https://'.$host : 'http://'.$host;
        $restUrl = $host.$port.'remote/json.php';

        if(is_null($this->_session)) {
            $this->_loadConfigAndLogin();
        }

        $client = $this->getHttpClient()->withOptions([
            'verify_peer' => false,
            'verify_host' => false,
            'timeout' => 60,
        ]);

        $payload = array_merge(['session_id' => $this->_session], $data);

        $response = $client->request('POST', $restUrl .'?'. $action, [
            'json' => $payload
        ]);

        return json_decode($response->getContent(), true);


    }


    private function _loadConfigAndLogin()
    {
        $useSsl                                      = $this->_config['secure'];
        $host          = $this->_config['host'];
        $username      = $this->_config['username'];
        $password      = $this->_config['password'];
        $port          = !empty($this->_config['port']) ? ':'.$this->_config['port'].'/' : '';
        $host          = ($useSsl) ? 'https://'.$host : 'http://'.$host;
        $restUrl       = $host.$port.'remote/json.php';

        $client = $this->getHttpClient()->withOptions([
            'verify_peer' => false,
            'verify_host' => false,
            'timeout' => 60,
        ]);

        $response = $client->request('POST', $restUrl .'?login', [
            'json' => [ 'username' => $username, 'password' => $password ]
        ]);

        $body = json_decode($response->getContent(), true);

        $this->_session = $body['response'];


        if (! $this->_session) {
            throw new Server_Exception('Failed to login and gain a Session Token.');
        }
    }
}
