<?php

namespace Drupal\conference_api\Controller;

use Drupal\Core\Routing\UrlGeneratorInterface;
use Exception;
use InvalidArgumentException;
use Drupal\Core\Controller\ControllerBase;
use Drupal\Core\DependencyInjection\ContainerInjectionInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\HttpFoundation\JsonResponse;
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
  private $httpKernel;

  /**
   * @var \Drupal\Core\Routing\UrlGeneratorInterface*/
  private $urlGenerator;

  /**
   * Constructor.
   */
  public function __construct(HttpKernelInterface $httpKernel, UrlGeneratorInterface $urlGenerator) {
    $this->httpKernel = $httpKernel;
    $this->urlGenerator = $urlGenerator;
  }

  /**
   * Kreator.
   */
  public static function create(ContainerInterface $container) {
    return new static(
      $container->get('http_kernel.basic'),
      $container->get('url_generator')
    );
  }

  /**
   * Index method.
   *
   * Proxies to the underlying JSON:API and returns the modified response.
   */
  public function index(Request $request, string $path = NULL): Response {
    // Check if our path processor has set an api path.
    $path = $request->get('api_path', $path);

    try {
      $requestPath = $this->getJsonApiPath($path);

      if (NULL === $requestPath) {
        throw new BadRequestHttpException(sprintf('Invalid path: %s', $path));
      }

      if (empty($requestPath)) {
        return $this->generateIndex();
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
   *
   */
  private function generateIndex(): Response {
    return new JsonResponse([
      'conference' => $this->urlGenerator->generate(
        'conference_api.api_controller_index',
        [
          'path' => 'conference',
        ],
        UrlGeneratorInterface::ABSOLUTE_URL),
      'event' => $this->urlGenerator->generate(
        'conference_api.api_controller_index',
        [
          'path' => 'event',
        ],
        UrlGeneratorInterface::ABSOLUTE_URL),
    ]);
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
    $parts = array_values(array_filter(explode('/', $path)));
    if (empty($parts) || 'api' !== $parts[0]) {
      return NULL;
    }
    array_shift($parts);

    if (empty($parts)) {
      return '';
    }

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
