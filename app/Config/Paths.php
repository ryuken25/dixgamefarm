<?php

namespace Config;

/**
 * Paths
 *
 * Holds the paths that are used by the system to
 * locate the main directories, app, system, etc.
 *
 * All paths are relative to the project's root folder (dxfarm/).
 */
class Paths
{
    /**
     * Path to the system directory (CI4 core framework)
     */
    public string $systemDirectory = __DIR__ . '/../../vendor/codeigniter4/framework/system';

    /**
     * Path to the application directory
     */
    public string $appDirectory = __DIR__ . '/..';

    /**
     * Path to the writable directory
     */
    public string $writableDirectory = __DIR__ . '/../../writable';

    /**
     * Path to the tests directory
     */
    public string $testsDirectory = __DIR__ . '/../../tests';

    /**
     * Path to the views directory
     */
    public string $viewDirectory = __DIR__ . '/../Views';

    /**
     * Path to the directory containing the .env file
     */
    public string $envDirectory = __DIR__ . '/../../';
}
