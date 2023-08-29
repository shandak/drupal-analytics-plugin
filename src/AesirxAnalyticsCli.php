<?php

namespace Drupal\aesirx_analytics;

use Drupal\aesirx_analytics\Exception\ExceptionWithErrorType;
use Drupal\Core\Config\ConfigFactoryInterface;
use Drupal\Core\Database\Database;
use RuntimeException;
use Symfony\Component\Process\Process;

class AesirxAnalyticsCli {

  private string $connection_key;

  /**
   * @var mixed|string
   */
  private mixed $connection_target;

  private string $cliPath;

  /**
   * @var \Drupal\Core\Config\ConfigFactoryInterface
   */
  private ConfigFactoryInterface $config_factory;

  public function __construct(
    ConfigFactoryInterface $config_factory,
    string $cliPath,
    string $connection_key = 'default',
    string $connection_target = 'default',
  ) {
    $this->connection_key = $connection_key;
    $this->connection_target = $connection_target;
    $this->cliPath = $cliPath;
    $this->config_factory = $config_factory;
  }

  public function analytics_cli_exists(): bool {
    return file_exists($this->cliPath);
  }

  public function get_supported_arch(): string {
    $arch = NULL;

    if (PHP_OS === 'Linux')
    {
      $uname = php_uname('m');
      if (strpos($uname, 'aarch64') !== FALSE)
      {
        $arch = 'aarch64';
      }
      else
      {
        if (strpos($uname, 'x86_64') !== FALSE)
        {
          $arch = 'x86_64';
        }
      }
    }

    if (is_null($arch))
    {
      throw new \DomainException("Unsupported architecture " . PHP_OS . " " . PHP_INT_SIZE);
    }

    return $arch;
  }

  /**
   * @throws \Drupal\aesirx_analytics\Exception\ExceptionWithErrorType
   */
  public function download_analytics_cli(): void {
    $arch = $this->get_supported_arch();
    file_put_contents($this->cliPath, fopen("https://github.com/aesirxio/analytics/releases/download/2.0.1/analytics-cli-linux-" . $arch, 'r'));
    chmod($this->cliPath, 0755);

    $this->process_analytics(['migrate']);
  }

  /**
   * @param array $command
   * @param bool  $makeExecutable
   *
   * @return Process
   * @throws \Drupal\aesirx_analytics\Exception\ExceptionWithErrorType
   * @global wpdb $wpdb WordPress database abstraction object.
   *
   */
  public function process_analytics(array $command, bool $makeExecutable = TRUE): Process {
    $info = Database::getConnectionInfo($this->connection_key)[$this->connection_target] ?? NULL;

    if (!is_array($info))
    {
      throw new RuntimeException('Database connection not found');
    }

    if (!$this->analytics_cli_exists())
    {
      throw new RuntimeException('CLI analytics library not found');
    }

    $env = [
      'DBUSER' => $info['username'],
      'DBPASS' => urlencode($info['password']),
      'DBNAME' => $info['database'],
      'DBTYPE' => 'mysql',
      'LICENSE' => $this->config_factory->get('aesirx_analytics.settings')
          ->get('settings.license') ?? '',
      'DBPREFIX' => $info['prefix'],
      'DBPORT' => $info['port'],
      'DBHOST' => $info['host'],
    ];

    // Plugin probably updated, we need to make sure it's executable and database is up-to-date
    if ($makeExecutable && 0755 !== (fileperms($this->cliPath) & 0777))
    {
      chmod($this->cliPath, 0755);

      if ($command != ['migrate'])
      {
        $this->process_analytics(['migrate'], FALSE);
      }
    }

    $process = new Process(array_merge([$this->cliPath], $command), NULL, $env);
    $process->run();

    if (!$process->isSuccessful())
    {
      $message = $process->getErrorOutput();
      $decoded = json_decode($message);
      $type = NULL;

      if (json_last_error() === JSON_ERROR_NONE
        && $process->getExitCode() == 65)
      {
        if (!empty($decoded->message))
        {
          $message = $decoded->message;
        }
        if (!empty($decoded->error_type))
        {
          $type = $decoded->error_type;
        }
      }
      throw new ExceptionWithErrorType($message, $type);
    }

    return $process;
  }

}
