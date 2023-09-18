<?php

namespace Drupal\aesirx_analytics\Helpers;

use Drupal\aesirx_analytics\Form\AdminConfig\AesirxAnalyticsAdminConfigForm;
use Drupal\Core\Config\ConfigFactoryInterface;
use Drupal\Core\Path\CurrentPathStack;
use Drupal\Core\Path\PathMatcherInterface;
use Drupal\path_alias\AliasManagerInterface;

/**
 * Defines the Path Matcher class.
 */
class VisibilityTracker {

  /**
   * @var \Drupal\path_alias\AliasManagerInterface
   */
  private $aliasManager;

  /**
   * @var \Drupal\Core\Path\PathMatcherInterface
   */
  private $pathMatcher;

  /**
   * @var \Drupal\Core\Config\ImmutableConfig
   */
  private $config;

  /**
   * @var \Drupal\Core\Path\CurrentPathStack
   */
  private $currentPath;

  private ?bool $pageMatch = NULL;

  /**
   * Constructor.
   *
   * @param \Drupal\Core\Config\ConfigFactoryInterface $config_factory
   *   The config factory service.
   * @param \Drupal\path_alias\AliasManagerInterface   $alias_manager
   *   The alias manager service.
   * @param \Drupal\Core\Path\PathMatcherInterface     $path_matcher
   *   The path matcher service.
   */
  public function __construct(ConfigFactoryInterface $config_factory, AliasManagerInterface $alias_manager, PathMatcherInterface $path_matcher, CurrentPathStack $current_path) {
    $this->config = $config_factory->get(AesirxAnalyticsAdminConfigForm::SETTINGS);
    $this->aliasManager = $alias_manager;
    $this->pathMatcher = $path_matcher;
    $this->currentPath = $current_path;
  }

  /**
   * Tracking visibility check for pages.
   *
   * Based on visibility setting this function returns TRUE if JS code should
   * be added to the current page and otherwise FALSE.
   */
  public function getVisibilityPages(): bool {
    // Cache visibility result if function is called more than once.
    if (is_null($this->pageMatch))
    {
      $visibility_request_path_mode = $this->config->get('visibility.request_path_mode');
      $visibility_request_path_pages = $this->config->get('visibility.request_path_pages');

      // Match path if necessary.
      if (!empty($visibility_request_path_pages))
      {
        // Convert path to lowercase. This allows comparison of the same path
        // with different case. Ex: /Page, /page, /PAGE.
        $pages = mb_strtolower($visibility_request_path_pages);
        if ($visibility_request_path_mode < 2)
        {
          // Compare the lowercase path alias (if any) and internal path.
          $path = $this->currentPath->getPath();
          $path_alias = mb_strtolower($this->aliasManager->getAliasByPath($path));
          $this->pageMatch = $this->pathMatcher->matchPath($path_alias, $pages) || (($path != $path_alias) && $this->pathMatcher->matchPath($path, $pages));
          // When $visibility_request_path_mode has a value of 0, the tracking
          // code is displayed on all pages except those listed in $pages. When
          // set to 1, it is displayed only on those pages listed in $pages.
          $this->pageMatch = !($visibility_request_path_mode xor $this->pageMatch);
        }
        else
        {
          $this->pageMatch = FALSE;
        }
      }
      else
      {
        $this->pageMatch = TRUE;
      }
    }
    return $this->pageMatch;
  }

  /**
   * Tracking visibility check for user roles.
   *
   * Based on visibility setting this function returns TRUE if JS code should
   * be added for the current role and otherwise FALSE.
   *
   * @param \Drupal\Core\Session\AccountProxyInterface $account
   *   A user object containing an array of roles to check.
   *
   * @return bool
   *   TRUE if JS code should be added for the current role and otherwise FALSE.
   */
  public function getVisibilityRoles($account): bool {
    $enabled = (bool) $visibility_user_role_mode = $this->config->get('visibility.user_role_mode');
    $visibility_user_role_roles = $this->config->get('visibility.user_role_roles');

    if (!empty($visibility_user_role_roles)) {
      // One or more roles are selected.
      foreach (array_values($account->getRoles()) as $user_role) {
        // Is the current user a member of one of these roles?
        if (in_array($user_role, $visibility_user_role_roles)) {
          // Current user is a member of a role that should be tracked/excluded
          // from tracking.
          $enabled = !$visibility_user_role_mode;
          break;
        }
      }
    }
    else {
      // No role is selected for tracking, therefore all roles should be tracked.
      $enabled = TRUE;
    }

    return $enabled;
  }
}
