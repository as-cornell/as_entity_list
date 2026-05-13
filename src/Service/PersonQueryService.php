<?php

namespace Drupal\as_entity_list\Service;

use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\domain\DomainNegotiatorInterface;
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
  protected $domainNegotiator;

  /**
   * Constructs a PersonQueryService object.
   *
   * @param \Drupal\Core\Entity\EntityTypeManagerInterface $entity_type_manager
   *   The entity type manager.
   * @param \Psr\Log\LoggerInterface $logger
   *   The logger service.
   * @param \Drupal\domain\DomainNegotiatorInterface|null $domain_negotiator
   *   The domain negotiator, injected only when the Domain module is present.
   */
  public function __construct(EntityTypeManagerInterface $entity_type_manager, LoggerInterface $logger, ?DomainNegotiatorInterface $domain_negotiator = NULL) {
    $this->entityTypeManager = $entity_type_manager;
    $this->logger = $logger;
    $this->domainNegotiator = $domain_negotiator;
  }

  /**
   * Returns TRUE when the active domain is departments_as_cornell_edu.
   *
   * Always FALSE when the Domain module is not installed.
   */
  private function isOnDepartmentsDomain(): bool {
    if (!$this->domainNegotiator) {
      return FALSE;
    }
    $domain = $this->domainNegotiator->getActiveDomain();
    return $domain && $domain->id() === 'departments_as_cornell_edu';
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
    $on_dept = $this->isOnDepartmentsDomain();

    $query = $this->entityTypeManager->getStorage('node')->getQuery()
      ->accessCheck(!$on_dept)
      ->condition('type', 'person')
      ->condition('status', 1)
      ->range(0, $count)
      ->sort('field_person_last_name', 'ASC');

    if ($on_dept) {
      $query->condition('field_domain_access', 'departments_as_cornell_edu', '<>');
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
    $on_dept = $this->isOnDepartmentsDomain();

    if (isset($tags) && $tags != NULL) {
      $termvariables = $tags[0]['termvariables'];
      $operator = $tags[0]['operator'];
      $sort = $tags[0]['sort'];
    }

    $query = $this->entityTypeManager->getStorage('node')->getQuery()
      ->accessCheck(!$on_dept)
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

    // On the departments domain show only people from other domains.
    if ($on_dept) {
      $query->condition('field_domain_access', 'departments_as_cornell_edu', '<>');
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
    $on_dept = $this->isOnDepartmentsDomain();

    $query = $this->entityTypeManager->getStorage('node')->getQuery()
      ->accessCheck(!$on_dept)
      ->condition('type', 'person')
      ->condition('status', 1)
      ->condition('field_research_areas.entity', $tags)
      ->range(0, $count)
      ->sort('field_person_last_name', 'ASC');

    if ($on_dept) {
      $query->condition('field_domain_access', 'departments_as_cornell_edu', '<>');
    }

    $nids = $query->execute();
    return $nids;
  }

}
