<?php

namespace Drupal\conference_api\Controller;

use Exception;
use InvalidArgumentException;
use Drupal\Core\Controller\ControllerBase;
use Drupal\Core\DependencyInjection\ContainerInjectionInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Exception\BadRequestHttpException;
use Symfony\Component\HttpKernel\HttpKernelInterface;

/**
 * Api controller.
 */
class ApiController extends ControllerBase implements ContainerInjectionInterface {
  /**
   * Symfony\Component\HttpKernel\HttpKernelInterface definition.
   *
   * @var Symfony\Component\HttpKernel\HttpKernelInterface
   */
  protected $httpKernel;

  /**
   * Constructor.
   */
  public function __construct(HttpKernelInterface $httpKernel) {
    $this->httpKernel = $httpKernel;
  }

  /**
   * Kreator.
   */
  public static function create(ContainerInterface $container) {
    return new static(
      $container->get('http_kernel.basic')
    );
  }

  /**
   * Index method.
   *
   * Proxies to the underlying JSON:API and returns the modified response.
   */
  public function index(Request $request, string $path = NULL): Response {
    // Check if our path processor has set an api path.
    if (NULL === $path) {
      $path = $request->get('api_path');
    }

    try {
      $requestPath = $this->getJsonApiPath($path);

      if (NULL === $requestPath) {
        throw new BadRequestHttpException(sprintf('Invalid path: %s', $path));
      }
    }
    catch (Exception $exception) {
      throw new BadRequestHttpException($exception->getMessage());

    }

    $request = Request::create($requestPath);

    $response = $this->httpKernel->handle($request, HttpKernelInterface::SUB_REQUEST);

    if (Response::HTTP_OK === $response->getStatusCode()) {
      $response->setContent($this->convertContent($response->getContent()));
    }

    return $response;
  }

  /**
   * Converts JSON:API data to Conference API data.
   */
  private function convertContent(string $content): string {
    return $content;
  }

  /**
   * Get JSON:API path from a Conference API path.
   */
  private function getJsonApiPath(string $path = NULL): ?string {
    $parts = explode('/', $path);
    if ('api' !== ($parts[1] ?? NULL)) {
      return NULL;
    }
    array_splice($parts, 0, 2);

    $apiPath = '/jsonapi/node';

    if (!empty($parts)) {
      $apiPath .= '/' . $this->getNodeType(array_shift($parts));
    }

    if (!empty($parts)) {
      // Entity id.
      $apiPath .= '/' . array_shift($parts);
    }

    return $apiPath;
  }

  /**
   * Get Conference API path from JSON:API path.
   */
  private function getApiPath(string $jsonApiPath = NULL): ?string {
    throw new \RuntimeException(__METHOD__ . ' not implemented!');
  }

  /**
   * Get node type.
   */
  private function getNodeType(string $type): string {
    switch ($type) {
      case 'conference':
      case 'event':
        return 'conference_' . $type;
    }

    throw new InvalidArgumentException(sprintf('Invalid type: %s', $type));
  }

}
