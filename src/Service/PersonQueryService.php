<?php

namespace Drupal\as_entity_list\Service;

use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\Routing\RouteMatchInterface;
use Psr\Log\LoggerInterface;

/**
 * Service for querying person entities.
 *
 * @package Drupal\as_entity_list\Service
 */
class PersonQueryService {

  /**
   * The entity type manager.
   *
   * @var \Drupal\Core\Entity\EntityTypeManagerInterface
   */
  protected $entityTypeManager;

  /**
   * The logger service.
   *
   * @var \Psr\Log\LoggerInterface
   */
  protected $logger;

  /**
   * The domain negotiator, or NULL when the Domain module is not installed.
   *
   * @var \Drupal\domain\DomainNegotiatorInterface|null
   */
  protected mixed $domainNegotiator;

  /**
   * The route match service.
   *
   * @var \Drupal\Core\Routing\RouteMatchInterface|null
   */
  protected $routeMatch;

  /**
   * Constructs a PersonQueryService object.
   *
   * @param \Drupal\Core\Entity\EntityTypeManagerInterface $entity_type_manager
   *   The entity type manager.
   * @param \Psr\Log\LoggerInterface $logger
   *   The logger service.
   * @param \Drupal\domain\DomainNegotiatorInterface|null $domain_negotiator
   *   The domain negotiator, injected only when the Domain module is present.
   * @param \Drupal\Core\Routing\RouteMatchInterface|null $route_match
   *   The route match service.
   */
  public function __construct(EntityTypeManagerInterface $entity_type_manager, LoggerInterface $logger, mixed $domain_negotiator = NULL, ?RouteMatchInterface $route_match = NULL) {
    $this->entityTypeManager = $entity_type_manager;
    $this->logger = $logger;
    $this->domainNegotiator = $domain_negotiator;
    $this->routeMatch = $route_match;
  }

  /**
   * Returns the active domain ID, or NULL when unavailable.
   */
  private function getActiveDomainId(): ?string {
    if (!$this->domainNegotiator) {
      return NULL;
    }
    $domain = $this->domainNegotiator->getActiveDomain();
    return $domain ? $domain->id() : NULL;
  }

  /**
   * Returns the first non-departments domain from the current route's node.
   */
  private function getCurrentNodeDomainId(): ?string {
    if (!$this->routeMatch) {
      return NULL;
    }
    $node = $this->routeMatch->getParameter('node');
    if (!$node || !$node->hasField('field_domain_access')) {
      return NULL;
    }
    foreach (array_column($node->get('field_domain_access')->getValue(), 'target_id') as $domain) {
      if ($domain !== 'departments_as_cornell_edu') {
        return $domain;
      }
    }
    return NULL;
  }

  /**
   * Returns the domain from the current route's taxonomy term.
   *
   * Used when building entity lists from a taxonomy term page, where there is
   * no node route parameter to derive the department domain from.
   */
  private function getCurrentTermDomainId(): ?string {
    if (!$this->routeMatch) {
      return NULL;
    }
    $term = $this->routeMatch->getParameter('taxonomy_term');
    if (!$term) {
      return NULL;
    }
    // departments_programs terms store their domain in field_domain_access_target_id.
    if ($term->hasField('field_domain_access_target_id')) {
      $domain = $term->get('field_domain_access_target_id')->value;
      if (!empty($domain)) {
        return $domain;
      }
    }
    // Fallback: check field_domain_access on the term itself.
    if ($term->hasField('field_domain_access')) {
      foreach (array_column($term->get('field_domain_access')->getValue(), 'target_id') as $domain) {
        if ($domain !== 'departments_as_cornell_edu') {
          return $domain;
        }
      }
    }
    return NULL;
  }

  /**
   * Gets people sorted alphabetically by last name.
   *
   * @param int $count
   *   Number of people to retrieve.
   * @param mixed $tags
   *   Optional tags parameter (not currently used in this method).
   *
   * @return array
   *   Array of person node IDs.
   */
  public function getPeople($count, $tags) {
    $domain = $this->getActiveDomainId();

    $query = $this->entityTypeManager->getStorage('node')->getQuery()
      ->accessCheck(FALSE)
      ->condition('type', 'person')
      ->condition('status', 1)
      ->range(0, $count)
      ->sort('field_person_last_name', 'ASC');

    if ($domain === 'departments_as_cornell_edu') {
      $page_domain = $this->getCurrentNodeDomainId() ?? $this->getCurrentTermDomainId();
      $query->condition('field_domain_access', $page_domain ?? 'departments_as_cornell_edu', $page_domain ? '=' : '<>');
    }
    elseif ($domain) {
      $query->condition('field_domain_access', $domain);
    }

    $nids = $query->execute();
    return $nids;
  }

  /**
   * Gets people filtered by department/program with advanced filtering.
   *
   * @param int $count
   *   Number of people to retrieve.
   * @param array|null $tags
   *   Optional array containing termvariables, operator, and sort settings.
   *
   * @return array
   *   Array of person node IDs.
   */
  public function getPeopleDepartment($count, $tags) {
    // Use entity query to look up people nids filtered by department/program.
    $operator = 'and';
    $sort = 'alphabetical';
    $domain = $this->getActiveDomainId();

    if (isset($tags) && $tags != NULL) {
      $termvariables = $tags[0]['termvariables'];
      $operator = $tags[0]['operator'];
      $sort = $tags[0]['sort'];
    }

    $query = $this->entityTypeManager->getStorage('node')->getQuery()
      ->accessCheck(FALSE)
      ->condition('type', 'person')
      ->condition('status', 1);

    if ($sort == 'alphabetical') {
      $query->sort('field_person_last_name', 'ASC');
      $query->range(0, $count);
    }
    if ($sort == 'random') {
      // Get more for random, to make sure it's really random.
      $query->range(0, 500);
    }

    // If termvariables set field-specific queries.
    if (isset($termvariables) && $termvariables != NULL) {
      foreach ($termvariables as $term) {
        if ($operator == 'and') {
          $query->condition($query->andConditionGroup()
            ->condition('field_departments_programs.entity', $term['tid']));
        }
        if ($operator == 'or') {
          $query->condition($query->orConditionGroup()
            ->condition('field_departments_programs.entity', $term['tid']));
        }
      }
    }

    // Optional field_person_type filter — pass label strings, e.g. ['Faculty', 'Other Faculty'].
    if (!empty($tags[0]['person_types'])) {
      $query->condition('field_person_type.entity:taxonomy_term.name', (array) $tags[0]['person_types'], 'IN');
    }

    if ($domain === 'departments_as_cornell_edu') {
      $page_domain = $this->getCurrentNodeDomainId() ?? $this->getCurrentTermDomainId();
      $query->condition('field_domain_access', $page_domain ?? 'departments_as_cornell_edu', $page_domain ? '=' : '<>');
    }
    elseif ($domain) {
      $query->condition('field_domain_access', $domain);
    }

    $nids = $query->execute();

    if ($sort == 'random') {
      // Randomize array.
      shuffle($nids);
      // Chop back down to requested list size.
      $nids = array_slice($nids, 0, $count);
    }

    return $nids;
  }

  /**
   * Gets people filtered by research area.
   *
   * @param int $count
   *   Number of people to retrieve.
   * @param mixed $tags
   *   Research area term ID(s) to filter by.
   *
   * @return array
   *   Array of person node IDs.
   */
  public function getPeopleResearch($count, $tags) {
    $domain = $this->getActiveDomainId();

    $query = $this->entityTypeManager->getStorage('node')->getQuery()
      ->accessCheck(FALSE)
      ->condition('type', 'person')
      ->condition('status', 1)
      ->condition('field_research_areas.entity', $tags)
      ->range(0, $count)
      ->sort('field_person_last_name', 'ASC');

    if ($domain === 'departments_as_cornell_edu') {
      $page_domain = $this->getCurrentNodeDomainId() ?? $this->getCurrentTermDomainId();
      $query->condition('field_domain_access', $page_domain ?? 'departments_as_cornell_edu', $page_domain ? '=' : '<>');
    }
    elseif ($domain) {
      $query->condition('field_domain_access', $domain);
    }

    $nids = $query->execute();
    return $nids;
  }

}
