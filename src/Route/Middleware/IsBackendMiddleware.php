<?php
namespace Drupal\aesirx_analytics\Route\Middleware;

use Pecee\Http\Middleware\IMiddleware;
use Pecee\Http\Request;
use Pecee\SimpleRouter\Exceptions\HttpException;

class IsBackendMiddleware implements IMiddleware
{
  /**
   * @param Request $request
   */
  public function handle(Request $request): void
  {
    if (!\Drupal::currentUser()->hasPermission('administer aesirx_analytics')) {
      throw new HttpException('Permission denied!', 403);
    }
  }
}
