<?php

namespace Drupal\aesirx_analytics\Form\AdminConfig;

use Drupal\aesirx_analytics\AesirxAnalyticsCli;
use Drupal\aesirx_analytics\AesirxAnalyticsInterface;
use Drupal\Core\Config\ConfigFactoryInterface;
use Drupal\Core\Form\ConfigFormBase;
use Drupal\Core\Form\FormStateInterface;
use Throwable;
use Symfony\Component\DependencyInjection\ContainerInterface;

class AesirxAnalyticsAdminConfigForm extends ConfigFormBase {

  public const SETTINGS = 'aesirx_analytics.settings';

  /**
   * @var \Drupal\aesirx_analytics\AesirxAnalyticsCli
   */
  private AesirxAnalyticsCli $cli;

  public function __construct(ConfigFactoryInterface $config_factory, AesirxAnalyticsCli $cli) {
    parent::__construct($config_factory);
    $this->cli = $cli;
  }

  public function getFormId() {
    return 'aesirx_analytics_admin_config_form';
  }

  public static function create(ContainerInterface $container) {
    return new static(
      $container->get('config.factory'),
      $container->get(AesirxAnalyticsCli::class)
    );
  }

  /**
   * Form constructor.
   *
   * @param array                                $form
   *   An associative array containing the structure of the form.
   * @param \Drupal\Core\Form\FormStateInterface $form_state
   *   The current state of the form.
   *
   * @return array
   *   The form structure.
   */
  public function buildForm(array $form, FormStateInterface $form_state) {
    $config = $this->config(self::SETTINGS);
    $settings = $config->get('settings');

    $form['help'] = [
      '#type' => 'item',
      //'#title' => t('Block title'),
      '#markup' => t('<p>Read more detail at <a target="_blank" href="https://github.com/aesirxio/analytics#in-ssr-site">https://github.com/aesirxio/analytics#in-ssr-site</a></p><p class= "description">
        <p>Note: Please set Permalink structure is NOT plain.</p></p>'),
    ];

    $form['1st_party_server'] = [
      '#type' => 'radios',
      '#title' => $this->t('Client ID'),
      '#default_value' => $settings['1st_party_server'],
      '#options' => [
        AesirxAnalyticsInterface::INTERNAL => $this->t('Internal'),
        AesirxAnalyticsInterface::EXTERNAL => $this->t('External'),
      ],
    ];

    if ($this->cli->analytics_cli_exists())
    {
      try
      {
        $this->cli->process_analytics(['--version']);
        $form['download_cli_button'] = [
          '#type' => 'item',
          '#markup' => '<b class="color-success">' . $this->t('CLI library check: Passed') . '</b>',
          '#states' => [
            'visible' => [
              ':input[name="1st_party_server"]' => ['value' => AesirxAnalyticsInterface::INTERNAL],
            ],
          ],
        ];
      }
      catch (Throwable $e)
      {
        $form['check_cli'] = [
          '#type' => 'item',
          '#markup' => '<b class="color-error">' . $this->t('You can\'t use internal server. Error: ' . $e->getMessage()) . '</b>',
          '#states' => [
            'visible' => [
              ':input[name="1st_party_server"]' => ['value' => AesirxAnalyticsInterface::INTERNAL],
            ],
          ],
        ];
      }
    }
    else
    {
      try
      {
        $this->cli->get_supported_arch();

        $form['download_cli_button'] = [
          '#type' => 'submit',
          '#value' => $this->t('Click to download CLI library! This plugin can\'t work without the library!'),
          '#submit' => [[$this, 'download_cli_button']],
          '#states' => [
            'visible' => [
              ':input[name="1st_party_server"]' => ['value' => AesirxAnalyticsInterface::INTERNAL],
            ],
          ],
        ];
      }
      catch (Throwable $e)
      {
        $form['check_cli'] = [
          '#type' => 'item',
          '#markup' => '<b class="color-error">' . $this->t('You can\'t use internal server. Error: ' . $e->getMessage()) . '</b>',
          '#states' => [
            'visible' => [
              ':input[name="1st_party_server"]' => ['value' => AesirxAnalyticsInterface::INTERNAL],
            ],
          ],
        ];
      }
    }

    $form['consent'] = [
      '#type' => 'radios',
      '#title' => $this->t('Consent'),
      '#default_value' => $settings['consent'],
      '#options' => [
        TRUE => $this->t('Yes'),
        FALSE => $this->t('No'),
      ],
    ];
    $form['domain'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Domain (Use next format: http://example.com:1000/)'),
      '#default_value' => $settings['domain'],
      '#states' => [
        'visible' => [
          ':input[name="1st_party_server"]' => ['value' => AesirxAnalyticsInterface::EXTERNAL],
        ],
      ],
      '#element_validate' => [
        [get_class($this), 'validateExternalIsRequired'],
        [get_class($this), 'validateDomain'],
      ],
      '#description' => $this->t("<p class= 'description'>
		You can setup 1st party server at <a target='_blank' href='https://github.com/aesirxio/analytics-1stparty'>https://github.com/aesirxio/analytics-1stparty</a>.</p>")
    ];
    $form['client_id'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Client ID'),
      '#default_value' => $settings['client_id'],
      '#required' => TRUE,
    ];
    $form['client_secret'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Client Secret'),
      '#default_value' => $settings['client_secret'],
      '#required' => TRUE,
    ];
    $form['license'] = [
      '#type' => 'textfield',
      '#title' => $this->t('License'),
      '#default_value' => $settings['license'],
      '#states' => [
        'visible' => [
          ':input[name="1st_party_server"]' => ['value' => AesirxAnalyticsInterface::INTERNAL],
        ],
      ],
      '#element_validate' => [[get_class($this), 'validateInternalIsRequired']],
      '#description' => $this->t("<p class= 'description'>
        Register to AesirX and get your client id, client secret and license here: <a target='_blank' href='https://web3id.aesirx.io'>https://web3id.aesirx.io</a>.</p>")
    ];

    return parent::buildForm($form, $form_state);
  }

  public function download_cli_button(array &$form, FormStateInterface $form_state) {
    try
    {
      $this->cli->download_analytics_cli();
      $this->messenger()
        ->addStatus($this->t('Library successfully downloaded.'));
    }
    catch (Throwable $e)
    {
      $this->messenger()
        ->addError($this->t('Downloading failed: ') . $e->getMessage());
    }
  }

  /**
   * Form submission handler.
   *
   * @param array                                $form
   *   An associative array containing the structure of the form.
   * @param \Drupal\Core\Form\FormStateInterface $form_state
   *   The current state of the form.
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
    $this->config(self::SETTINGS)
      ->set('settings', $form_state->cleanValues()->getValues())
      ->save();

    parent::submitForm($form, $form_state);
  }

  /**
   * Gets the configuration names that will be editable.
   *
   * @return array
   *   An array of configuration object names that are editable if called in
   *   conjunction with the trait's config() method.
   */
  protected function getEditableConfigNames() {
    return [self::SETTINGS];
  }

  public static function validateInternalIsRequired($element, FormStateInterface $form_state) {
    if (empty($element['#value']) && $form_state->getValue('1st_party_server') == AesirxAnalyticsInterface::INTERNAL)
    {
      $form_state->setError($element, t('The "@name" can not be empty.', ['@name' => $element['#title']]));
    }
  }

  public static function validateExternalIsRequired($element, FormStateInterface $form_state) {
    if (empty($element['#value']) && $form_state->getValue('1st_party_server') == AesirxAnalyticsInterface::EXTERNAL)
    {
      $form_state->setError($element, t('The "@name" can not be empty.', ['@name' => $element['#title']]));
    }
  }

  public static function validateDomain($element, FormStateInterface $form_state) {
    if (!empty($element['#value']) && filter_var($element['#value'], FILTER_VALIDATE_URL) === FALSE)
    {
      $form_state->setError($element, t('The "@name" has invalid domain format.', ['@name' => $element['#title']]));
    }
  }

}
