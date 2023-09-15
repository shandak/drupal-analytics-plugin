<?php

namespace Drupal\aesirx_analytics\Form\AdminConfig;

use AesirxAnalyticsLib\Cli\AesirxAnalyticsCli;
use Drupal\aesirx_analytics\AesirxAnalyticsInterface;
use Drupal\Core\Config\ConfigFactoryInterface;
use Drupal\Core\Form\ConfigFormBase;
use Drupal\Core\Form\FormStateInterface;
use Throwable;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * @since 1.0.0
 */
class AesirxAnalyticsAdminConfigForm extends ConfigFormBase
{
    public const SETTINGS = 'aesirx_analytics.settings';

    private AesirxAnalyticsCli $cli;

    public function __construct(ConfigFactoryInterface $config_factory, AesirxAnalyticsCli $cli)
    {
        parent::__construct($config_factory);
        $this->cli = $cli;
    }

    /**
     * @return string
     */
    public function getFormId()
    {
        return 'aesirx_analytics_admin_config_form';
    }

    /**
     * @param ContainerInterface $container
     * @return static
     */
    public static function create(ContainerInterface $container)
    {
        return new static(
            $container->get('config.factory'),
            $container->get(AesirxAnalyticsCli::class)
        );
    }

    /**
     * Form constructor.
     *
     * @param array $form
     *   An associative array containing the structure of the form.
     * @param \Drupal\Core\Form\FormStateInterface $form_state
     *   The current state of the form.
     *
     * @return array
     *   The form structure.
     */
    public function buildForm(array $form, FormStateInterface $form_state)
    {
        $config = $this->config(self::SETTINGS);
        $settings = $config->get('settings');

        $form['info'] = [
            '#type' => 'item',
            '#markup' => $this->t(
                '<p>Read more detail at <a target="_blank" href="https://github.com/aesirxio/analytics#in-ssr-site">https://github.com/aesirxio/analytics#in-ssr-site</a></p><p class= "description">
        <p>Note: Please set Permalink structure is NOT plain.</p></p>'
            ),
        ];

        $form['1st_party_server'] = [
            '#type' => 'radios',
            '#title' => $this->t('1st party server'),
            '#default_value' => $settings['1st_party_server'],
            '#options' => [
                AesirxAnalyticsInterface::INTERNAL => $this->t('Internal'),
                AesirxAnalyticsInterface::EXTERNAL => $this->t('External'),
            ],
        ];

        if ($this->cli->analyticsCliExists()) {
            $hidden = 'exists';
            try {
                $this->cli->processAnalytics(['--version']);
                $form['download_cli_button'] = [
                    '#type' => 'item',
                    '#markup' => '<b class="color-success">' . $this->t('CLI library check: Passed') . '</b>',
                    '#states' => [
                        'visible' => [
                            ':input[name="1st_party_server"]' => ['value' => AesirxAnalyticsInterface::INTERNAL],
                        ],
                    ],
                ];
            } catch (Throwable $e) {
                $hidden = 'error';
                $form['check_cli'] = [
                    '#type' => 'item',
                    '#markup' => '<b class="color-error">' . $this->t(
                        'You can\'t use internal server. Error: ' . $e->getMessage()
                    ) . '</b>',
                    '#states' => [
                        'visible' => [
                            ':input[name="1st_party_server"]' => ['value' => AesirxAnalyticsInterface::INTERNAL],
                        ],
                    ],
                ];
            }
        } else {
            $hidden = 'not_exists';
            try {
                $this->cli->getSupportedArch();

                $form['download_cli_button'] = [
                    '#type' => 'submit',
                    '#value' => $this->t('Click to download CLI library! This plugin can\'t work without the library!'),
                    '#submit' => [[$this, 'downloadCliButton']],
                    '#states' => [
                        'visible' => [
                            ':input[name="1st_party_server"]' => ['value' => AesirxAnalyticsInterface::INTERNAL],
                        ],
                    ],
                ];
            } catch (Throwable $e) {
                $hidden = 'error';
                $form['check_cli'] = [
                    '#type' => 'item',
                    '#markup' => '<b class="color-error">' . $this->t(
                        'You can\'t use internal server. Error: ' . $e->getMessage()
                    ) . '</b>',
                    '#states' => [
                        'visible' => [
                            ':input[name="1st_party_server"]' => ['value' => AesirxAnalyticsInterface::INTERNAL],
                        ],
                    ],
                ];
            }
        }

        $form['cli'] = [
            '#type' => 'hidden',
            '#value' => $hidden,
        ];

        $form['consent'] = [
            '#type' => 'checkbox',
            '#title' => $this->t('Consent'),
            '#return_value' => true,
            '#default_value' => $settings['consent'],
        ];

        $form['domain'] = [
            '#type' => 'textfield',
            '#title' => $this->t('Domain (Use next format: http://example.com:1000/)'),
            '#default_value' => $settings['domain'],
            '#states' => [
                'visible' => [
                    ':input[name="1st_party_server"]' => ['value' => AesirxAnalyticsInterface::EXTERNAL],
                ],
                'required' => [
                    ':input[name="1st_party_server"]' => ['value' => AesirxAnalyticsInterface::EXTERNAL],
                ],
            ],
            '#element_validate' => [
                [get_class($this), 'validateExternalIsRequired'],
                [get_class($this), 'validateDomain'],
            ],
            '#description' => $this->t(
                "<p class= 'description'>
		You can setup 1st party server at <a target='_blank' href='https://github.com/aesirxio/analytics-1stparty'>https://github.com/aesirxio/analytics-1stparty</a>.</p>"
            ),
        ];
        $form['client_id'] = [
            '#type' => 'textfield',
            '#title' => $this->t('Client ID'),
            '#default_value' => $settings['client_id'],
            '#required' => true,
        ];
        $form['client_secret'] = [
            '#type' => 'textfield',
            '#title' => $this->t('Client Secret'),
            '#default_value' => $settings['client_secret'],
            '#required' => true,
        ];
        $form['license'] = [
            '#type' => 'textfield',
            '#title' => $this->t('License'),
            '#default_value' => $settings['license'],
            '#states' => [
                'visible' => [
                    ':input[name="1st_party_server"]' => ['value' => AesirxAnalyticsInterface::INTERNAL],
                ],
                'required' => [
                    ':input[name="1st_party_server"]' => ['value' => AesirxAnalyticsInterface::INTERNAL],
                ],
            ],
            '#element_validate' => [[get_class($this), 'validateInternalIsRequired']],
            '#description' => $this->t(
                "<p class= 'description'>
        Register to AesirX and get your client id, client secret and license here: <a target='_blank' href='https://web3id.aesirx.io'>https://web3id.aesirx.io</a>.</p>"
            ),
        ];

        // Visibility settings.
        $form['tracking_scope'] = [
            '#type' => 'vertical_tabs',
        ];

        $visibility_request_path_pages = $config->get('visibility.request_path_pages');
        $form['tracking']['page_visibility_settings'] = [
            '#type' => 'details',
            '#title' => $this->t('Pages'),
            '#group' => 'tracking_scope',
        ];

        if ($config->get('visibility.request_path_mode') == 2) {
            $form['tracking']['page_visibility_settings'] = [];
            $form['tracking']['page_visibility_settings']['visibility_request_path_mode'] = [
                '#type' => 'value',
                '#value' => 2,
            ];
            $form['tracking']['page_visibility_settings']['visibility_request_path_pages'] = [
                '#type' => 'value',
                '#value' => $visibility_request_path_pages,
            ];
        } else {
            $form['tracking']['page_visibility_settings']['visibility_request_path_mode'] = [
                '#type' => 'radios',
                '#title' => $this->t('Add tracking to specific pages'),
                '#options' => [
                    $this->t('Every page except the listed pages'),
                    $this->t('The listed pages only'),
                ],
                '#default_value' => $config->get('visibility.request_path_mode'),
            ];
            $form['tracking']['page_visibility_settings']['visibility_request_path_pages'] = [
                '#type' => 'textarea',
                '#title' => $this->t('Pages'),
                '#title_display' => 'invisible',
                '#default_value' => !empty($visibility_request_path_pages) ? $visibility_request_path_pages : '',
                '#description' => $this->t(
                    "Specify pages by using their paths. Enter one path per line. The '*' character is a wildcard. Example paths are %blog for the blog page and %blog-wildcard for every personal blog. %front is the front page.",
                    [
                        '%blog' => '/blog',
                        '%blog-wildcard' => '/blog/*',
                        '%front' => '<front>',
                    ]
                ),
                '#rows' => 10,
            ];
        }

        $visibility_user_role_roles = $config->get('visibility.user_role_roles');

        $form['tracking']['role_visibility_settings'] = [
            '#type' => 'details',
            '#title' => $this->t('Roles'),
            '#group' => 'tracking_scope',
        ];

        $form['tracking']['role_visibility_settings']['visibility_user_role_mode'] = [
            '#type' => 'radios',
            '#title' => $this->t('Add tracking for specific roles'),
            '#options' => [
                $this->t('Add to the selected roles only'),
                $this->t('Add to every role except the selected ones'),
            ],
            '#default_value' => $config->get('visibility.user_role_mode'),
        ];
        $form['tracking']['role_visibility_settings']['visibility_user_role_roles'] = [
            '#type' => 'checkboxes',
            '#title' => $this->t('Roles'),
            '#default_value' => !empty($visibility_user_role_roles) ? $visibility_user_role_roles : [],
            '#options' => array_map('\Drupal\Component\Utility\Html::escape', user_role_names()),
            '#description' => $this->t(
                'If none of the roles are selected, all users will be tracked. If a user has any of the roles checked, that user will be tracked (or excluded, depending on the setting above).'
            ),
        ];

        $form = parent::buildForm($form, $form_state);

        $form['#theme'] = 'aesirx_config_form';

        return $form;
    }

    public function validateForm(array &$form, FormStateInterface $form_state)
    {
        parent::validateForm($form, $form_state);
        $form_state->setValue(
            'visibility_request_path_pages',
            trim($form_state->getValue('visibility_request_path_pages'))
        );
        // Verify that every path is prefixed with a slash, but don't check PHP
        // code snippets and do not check for slashes if no paths configured.
        if (
            $form_state->getValue('visibility_request_path_mode') != 2
            && !empty($form_state->getValue('visibility_request_path_pages'))
        ) {
            $pages = preg_split('/(\r\n?|\n)/', $form_state->getValue('visibility_request_path_pages'));
            foreach ($pages as $page) {
                if (strpos($page, '/') !== 0 && $page !== '<front>') {
                    $form_state->setErrorByName(
                        'visibility_request_path_pages',
                        $this->t('Path "@page" not prefixed with slash.', ['@page' => $page])
                    );
                    // Drupal forms show one error only.
                    break;
                }
            }
        }
    }

    /**
     * @param array $form
     * @param FormStateInterface $form_state
     * @return void
     */
    public function downloadCliButton(array &$form, FormStateInterface $form_state)
    {
        try {
            $this->cli->downloadAnalyticsCli();
            $this->messenger()
                ->addStatus($this->t('Library successfully downloaded.'));
        } catch (Throwable $e) {
            $this->messenger()
                ->addError($this->t('Downloading failed: ') . $e->getMessage());
        }

        $form_state->setValue('cli', 'exists');

        $this->submitForm($form, $form_state);
    }

    /**
     * Form submission handler.
     *
     * @param array $form
     *   An associative array containing the structure of the form.
     * @param \Drupal\Core\Form\FormStateInterface $form_state
     *   The current state of the form.
     */
    public function submitForm(array &$form, FormStateInterface $form_state)
    {
        $this->config(self::SETTINGS)
            ->set('settings', $form_state->cleanValues()->getValues())
            ->set('visibility.request_path_mode', $form_state->getValue('visibility_request_path_mode'))
            ->set('visibility.request_path_pages', $form_state->getValue('visibility_request_path_pages'))
            ->set('visibility.user_role_mode', $form_state->getValue('visibility_user_role_mode'))
            ->set('visibility.user_role_roles', $form_state->getValue('visibility_user_role_roles'))
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
    protected function getEditableConfigNames()
    {
        return [self::SETTINGS];
    }

    /**
     * @param $element
     * @param FormStateInterface $form_state
     * @return void
     */
    public static function validateInternalIsRequired($element, FormStateInterface $form_state)
    {
        if (
            empty($element['#value']) && $form_state->getValue(
                '1st_party_server'
            ) == AesirxAnalyticsInterface::INTERNAL
        ) {
            $form_state->setError($element, t('The "@name" can not be empty.', ['@name' => $element['#title']]));
        }
    }

    /**
     * @param $element
     * @param FormStateInterface $form_state
     * @return void
     */
    public static function validateExternalIsRequired($element, FormStateInterface $form_state)
    {
        if (
            empty($element['#value']) && $form_state->getValue(
                '1st_party_server'
            ) == AesirxAnalyticsInterface::EXTERNAL
        ) {
            $form_state->setError($element, t('The "@name" can not be empty.', ['@name' => $element['#title']]));
        }
    }

    /**
     * @param $element
     * @param FormStateInterface $form_state
     * @return void
     */
    public static function validateDomain($element, FormStateInterface $form_state)
    {
        if (!empty($element['#value']) && filter_var($element['#value'], FILTER_VALIDATE_URL) === false) {
            $form_state->setError(
                $element,
                t('The "@name" has invalid domain format.', ['@name' => $element['#title']])
            );
        }
    }
}
