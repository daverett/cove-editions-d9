<?php

namespace Drupal\at_tool;

use Drupal\Core\Controller\TitleResolverInterface;
use Drupal\Core\Routing\RouteMatchInterface;
use Drupal\Core\Security\TrustedCallbackInterface;
use Symfony\Component\HttpFoundation\RequestStack;

/**
 * Lazy builders for the at_tool.
 */
class AtToolLazyBuilders implements TrustedCallbackInterface {

  /**
   * The request stack.
   *
   * @var \Symfony\Component\HttpFoundation\RequestStack
   */
  protected $requestStack;

  /**
   * The route match.
   *
   * @var \Drupal\Core\Routing\RouteMatchInterface
   */
  protected $routeMatch;

  /**
   * The title resolver.
   *
   * @var \Drupal\Core\Controller\TitleResolverInterface
   */
  protected $titleResolver;

  /**
   * Constructs a new LazyBuilders object.
   *
   * @param \Symfony\Component\HttpFoundation\RequestStack $request_stack
   *   The request stack.
   * @param \Drupal\Core\Routing\RouteMatchInterface $route_match
   *   The current route match.
   * @param \Drupal\Core\Controller\TitleResolverInterface $title_resolver
   *   The title resolver.
   */
  public function __construct(RequestStack $request_stack, RouteMatchInterface $route_match, TitleResolverInterface $title_resolver) {
    $this->requestStack = $request_stack;
    $this->routeMatch = $route_match;
    $this->titleResolver = $title_resolver;
  }

  /**
   * Return values for the breadcrumb title placeholder.
   *
   * @return array
   *   A renderable array of breadcrumb title.
   */
  public function breadcrumbTitle() {
    $request = $this->requestStack->getCurrentRequest();
    $route = $this->routeMatch->getRouteObject();
    $title = $this->titleResolver->getTitle($request, $route);
    $array = [
      '#theme' => 'page_title__breadcrumb',
      '#title' => $title,
    ];
    return $array;
  }

  /**
   * {@inheritdoc}
   */
  public static function trustedCallbacks() {
    return ['breadcrumbTitle'];
  }

}
