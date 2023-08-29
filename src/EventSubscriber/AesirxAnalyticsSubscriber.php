<?php

namespace Drupal\aesirx_analytics\EventSubscriber;

use Drupal\aesirx_analytics\AesirxAnalyticsCli;
use Drupal\aesirx_analytics\Exception\ExceptionWithErrorType;
use Drupal\aesirx_analytics\Exception\ExceptionWithResponseCode;
use Drupal\aesirx_analytics\RouterFactory;
use Drupal\Core\Config\ConfigFactoryInterface;
use Pecee\SimpleRouter\Exceptions\NotFoundHttpException;
use Pecee\SimpleRouter\SimpleRouter;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Symfony\Component\HttpKernel\Event\RequestEvent;
use Symfony\Component\HttpKernel\KernelEvents;
use Throwable;

class AesirxAnalyticsSubscriber implements EventSubscriberInterface {

	/**
	 * @var ConfigFactoryInterface
	 */
	private ConfigFactoryInterface $config_factory;

	private AesirxAnalyticsCli $cli;

	public function __construct(
		ConfigFactoryInterface $config_factory,
		AesirxAnalyticsCli $cli,
	) {
		$this->config_factory = $config_factory;
		$this->cli = $cli;
	}

	public function onRequest(RequestEvent $event) {
		if (($this->config_factory->get('aesirx_analytics.settings')
				->get('settings.1st_party_server') ?? 'internal') != 'internal')
		{
			return;
		}

		$callCommand = function(array $command): string {
			try
			{
				$process = $this->cli->process_analytics($command);
			}
			catch (Throwable $e)
			{
				$code = 500;

				if ($e instanceof ExceptionWithErrorType)
				{
					switch ($decoded->error_type ?? NULL)
					{
						case "NotFoundError":
							$code = 404;
							break;
						case "ValidationError":
							$code = 400;
							break;
						case "Rejected":
							$code = 406;
							break;
					}
				}

				throw new ExceptionWithResponseCode($e->getMessage(), $code, $e->getCode(), $e);
			}

			if (!headers_sent())
			{
				header('Content-Type: application/json; charset=utf-8');
			}
			return $process->getOutput();
		};

		try
		{
			echo (new RouterFactory($callCommand, $event->getRequest()->getBaseUrl()))
				->getSimpleRouter()
				->start();
		}
		catch (Throwable $e)
		{
			if ($e instanceof NotFoundHttpException)
			{
				return;
			}

			if ($e instanceof ExceptionWithResponseCode)
			{
				$code = $e->getResponseCode();
			}
			else
			{
				$code = 500;
			}

			if (!headers_sent())
			{
				header('Content-Type: application/json; charset=utf-8');
			}
			http_response_code($code);
			echo json_encode([
				'error' => $e->getMessage(),
			]);
		}

		die();
	}

	/**
	 * Returns an array of event names this subscriber wants to listen to.
	 *
	 * The array keys are event names and the value can be:
	 *
	 *  * The method name to call (priority defaults to 0)
	 *  * An array composed of the method name to call and the priority
	 *  * An array of arrays composed of the method names to call and respective
	 *    priorities, or 0 if unset
	 *
	 * For instance:
	 *
	 *  * ['eventName' => 'methodName']
	 *  * ['eventName' => ['methodName', $priority]]
	 *  * ['eventName' => [['methodName1', $priority], ['methodName2']]]
	 *
	 * The code must not depend on runtime state as it will only be called at
	 * compile time. All logic depending on runtime state must be put into the
	 * individual methods handling the events.
	 *
	 * @return array<string, string|array{0: string, 1: int}|list<array{0:
	 *                       string, 1?: int}>>
	 */
	public static function getSubscribedEvents() {
		//$events[KernelEvents::RESPONSE][] = ['onResponse', 100];
		$events[KernelEvents::REQUEST][] = ['onRequest', 100];

		return $events;
	}

}
