<?php

namespace Drupal\as_entity_list\Service;

use Drupal\Core\Entity\EntityTypeManagerInterface;
use Psr\Log\LoggerInterface;

/**
 * Service for querying taxonomy term entities.
 *
 * @package Drupal\as_entity_list\Service
 */
class TermQueryService {

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
   * Constructs a TermQueryService object.
   *
   * @param \Drupal\Core\Entity\EntityTypeManagerInterface $entity_type_manager
   *   The entity type manager.
   * @param \Psr\Log\LoggerInterface $logger
   *   The logger service.
   */
  public function __construct(EntityTypeManagerInterface $entity_type_manager, LoggerInterface $logger) {
    $this->entityTypeManager = $entity_type_manager;
    $this->logger = $logger;
  }

  /**
   * Gets taxonomy terms from a vocabulary.
   *
   * @param string $type
   *   The vocabulary ID to query.
   * @param int $count
   *   Number of terms to retrieve.
   *
   * @return array
   *   Array of taxonomy term IDs.
   */
  public function getTerms($type, $count) {
    // Use entity query to look up all tids in a given vocabulary.
    $tids = $this->entityTypeManager->getStorage('taxonomy_term')->getQuery()
      ->accessCheck(TRUE)
      ->condition('vid', $type)
      ->condition('status', 1)
      // Exclude parent terms like department names.
      ->condition('parent', '0', '<>')
      ->range(0, $count)
      ->execute();

    return $tids;
  }

  /**
   * Gets taxonomy terms with advanced filtering.
   *
   * Used for department_program_center_list and major_minor_gradfield_list paragraph types.
   *
   * @param string $type
   *   The list type ('dpc_as' or 'mmg_as').
   * @param int $count
   *   Number of terms to retrieve.
   * @param array|null $tags
   *   Optional array containing termvariables, operator, and sort settings.
   *
   * @return array
   *   Array of taxonomy term IDs.
   */
  public function getTermsFiltered($type, $count, $tags) {
    // Use entity query to look up taxonomy term tids filtered by term references.
    if ($type == 'dpc_as') {
      $vname = 'departments_programs';
    }
    if ($type == 'mmg_as') {
      $vname = 'majors_minors_gradfields';
    }

    $operator = 'and';
    $sort = 'alphabetical';

    if (isset($tags) && $tags != NULL) {
      $termvariables = $tags[0]['termvariables'];
      $operator = $tags[0]['operator'];
      $sort = $tags[0]['sort'];
    }

    $query = $this->entityTypeManager->getStorage('taxonomy_term')->getQuery()
      ->accessCheck(TRUE)
      ->condition('vid', $vname)
      ->condition('status', 1);

    if ($sort == 'alphabetical') {
      $query->sort('name', 'ASC');
      $query->range(0, $count);
    }
    if ($sort == 'random') {
      // Get more for random, to make sure it's really random.
      $query->range(0, 500);
    }

    // If termvariables set field-specific queries.
    if (isset($termvariables) && $termvariables != NULL) {
      foreach ($termvariables as $term) {
        if ($term['vid'] == 'department_type') {
          if ($operator == 'and') {
            $query->condition($query->andConditionGroup()
              ->condition('field_type.entity', $term['tid']));
          }
          if ($operator == 'or') {
            $query->condition($query->orConditionGroup()
              ->condition('field_type.entity', $term['tid']));
          }
        }
        if ($term['vid'] == 'discipline') {
          if ($operator == 'and') {
            $query->condition($query->andConditionGroup()
              ->condition('field_discipline.entity', $term['tid']));
          }
          if ($operator == 'or') {
            $query->condition($query->orConditionGroup()
              ->condition('field_discipline.entity', $term['tid']));
          }
        }
        if ($term['vid'] == 'function') {
          if ($operator == 'and') {
            $query->condition($query->andConditionGroup()
              ->condition('field_related_functions.entity', $term['tid']));
          }
          if ($operator == 'or') {
            $query->condition($query->orConditionGroup()
              ->condition('field_related_functions.entity', $term['tid']));
          }
        }
        if ($term['vid'] == 'interests') {
          if ($operator == 'and') {
            $query->condition($query->andConditionGroup()
              ->condition('field_interests.entity', $term['tid']));
          }
          if ($operator == 'or') {
            $query->condition($query->orConditionGroup()
              ->condition('field_interests.entity', $term['tid']));
          }
        }
        if ($term['vid'] == 'level') {
          if ($operator == 'and') {
            $query->condition($query->andConditionGroup()
              ->condition('field_level.entity', $term['tid']));
          }
          if ($operator == 'or') {
            $query->condition($query->orConditionGroup()
              ->condition('field_level.entity', $term['tid']));
          }
        }
      }
    }

    $terms = $query->execute();

    if ($sort == 'random') {
      // Randomize array.
      shuffle($terms);
      // Chop back down to requested list size.
      $terms = array_slice($terms, 0, $count);
    }

    return $terms;
  }

}
