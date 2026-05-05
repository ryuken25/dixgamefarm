<?php

namespace Config;

use CodeIgniter\Config\BaseConfig;

class App extends BaseConfig
{
    public string $baseURL = 'http://localhost:8080/';

    /**
     * Allowed Hostnames in the Site URL other than the hostname in the baseURL.
     *
     * @var list<string>
     */
    public array $allowedHostnames = [];

    public string $indexPage = '';

    public string $uriProtocol = 'REQUEST_URI';

    public string $permittedURIChars = 'a-z 0-9~%.:_\-';

    public string $defaultLocale = 'id';

    public bool $negotiateLocale = false;

    /**
     * @var list<string>
     */
    public array $supportedLocales = ['id'];

    public string $appTimezone = 'Asia/Makassar';

    public string $charset = 'UTF-8';

    public bool $forceGlobalSecureRequests = false;

    /**
     * @var array<string, string>
     */
    public array $proxyIPs = [];

    public bool $CSPEnabled = false;
}
