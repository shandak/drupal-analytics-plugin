<?php

/**
 * @package     AesirX_Analytics_Library
 *
 * @copyright   Copyright (C) 2016 - 2023 Aesir. All rights reserved.
 * @license     GNU General Public License version 2 or later; see LICENSE
 */

namespace Drupal\aesirx_analytics;

use AesirxAnalyticsLib\Cli\Env;
use RuntimeException;
use Drupal\Core\Config\ConfigFactoryInterface;
use Drupal\Core\Database\Database;

/**
 * @since 1.0.0
 */
class CliEnvFactory
{
    public static function getEnv(
        ConfigFactoryInterface $config_factory,
        string $connection_key = 'default',
        string $connection_target = 'default',
    ): Env {
        $info = Database::getConnectionInfo($connection_key)[$connection_target] ?? null;

        if (!is_array($info)) {
            throw new RuntimeException('Database connection not found');
        }

        return new Env(
            $config_factory->get('aesirx_analytics.settings')
                ->get('settings.license') ?? '',
            $info['username'],
            urlencode($info['password']),
            $info['database'],
            $info['prefix'],
            $info['host'],
            $info['port'],
        );
    }
}
