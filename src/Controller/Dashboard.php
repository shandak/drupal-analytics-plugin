<?php

namespace Drupal\aesirx_analytics\Controller;

use Drupal\aesirx_analytics\Form\AdminConfig\AesirxAnalyticsAdminConfigForm;
use Drupal\Core\Controller\ControllerBase;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * @since 1.0.0
 */
class Dashboard extends ControllerBase
{
    /**
     * @var \Symfony\Component\HttpFoundation\Request
     */
    protected $requestStack;

    protected string $modulePath;

    /**
     * @param ContainerInterface $container
     * @return mixed
     */
    public static function create(ContainerInterface $container)
    {
        $instance = parent::create($container);
        $instance->requestStack = $container->get('request_stack')
            ->getCurrentRequest();
        $instance->modulePath = $container->get('extension.list.module')
            ->getPath('aesirx_analytics');
        return $instance;
    }

    /**
     * @return array
     */
    public function index()
    {
        $conf = $this->config(AesirxAnalyticsAdminConfigForm::SETTINGS);
        $host = $this->requestStack->getSchemeAndHttpHost();
        $protocols = ['http://', 'https://', 'http://www.', 'https://www.', 'www.'];
        $domain = str_replace($protocols, '', $host);
        $streams = [
            [
                'name' => $this->config('system.site')->get('name'),
                'domain' => $domain,
            ],
        ];
        $endpoint =
            ($conf->get('settings.1st_party_server') ?? 'internal') == 'internal'
                ? $host
                : rtrim($conf->get('settings.domain') ?? '', '/');

        return [
            '#theme' => 'bi-dashboard',
            '#content' => [
                'endpoint_url' => $endpoint,
                'data_stream' => json_encode($streams),
                'public_url' => $host . '/' . $this->modulePath,
            ],
        ];
    }
}
