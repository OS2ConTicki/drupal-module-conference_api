<?php

namespace Drupal\conference_api\PathProcessor;

use Drupal\Core\PathProcessor\InboundPathProcessorInterface;
use Symfony\Component\HttpFoundation\Request;

/**
 * Path processer to handle generic router paths.
 *
 * @see https://www.drupal.org/docs/8/api/routing-system/parameters-in-routes/using-parameters-in-routes#s-example
 *
 * While Symfony allows for more arbitrary use of slugs, Drupal has stricter
 * requirements. In fact, unlike generic Symfony routes, Drupal requires that
 * a slug occupies a complete path part - the portion between two slashes (or
 * everything after the last slash). If you must pass a parameter containing
 * slashes, apply the same trick as in PathProcessorFiles.
 */
class PathProcessor implements InboundPathProcessorInterface {

  /**
   * {@inheritdoc}
   */
  public function processInbound($path, Request $request) {
    if (0 === strpos($path, '/api')) {
      $request->query->set('api_path', $path);

      return '/api';
    }

    return $path;
  }

}
