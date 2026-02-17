<?php

namespace Drupal\as_entity_list\Service;

use Drupal\Core\Entity\EntityTypeManagerInterface;
use Psr\Log\LoggerInterface;

/**
 * Service for querying article entities.
 *
 * @package Drupal\as_entity_list\Service
 */
class ArticleQueryService {

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
   * Constructs an ArticleQueryService object.
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
   * Gets articles filtered by tags.
   *
   * @param int $count
   *   Number of articles to retrieve.
   * @param array|null $tags
   *   Optional array of tag term IDs.
   *
   * @return array
   *   Array of article node IDs.
   */
  public function getArticles($count, $tags) {
    // Use entity query to look up article nids filtered by tags.
    $query = $this->entityTypeManager->getStorage('node')->getQuery()
      ->accessCheck(TRUE)
      ->condition('type', 'article')
      ->condition('status', 1)
      ->sort('created', 'DESC')
      ->range(0, $count);

    if (isset($tags) && $tags != NULL) {
      $query->condition('field_article_view_tags.entity', $tags);
    }

    $nids = $query->execute();
    return $nids;
  }

  /**
   * Gets articles with advanced filtering by multiple term vocabularies.
   *
   * @param int $count
   *   Number of articles to retrieve.
   * @param array|null $tags
   *   Optional array containing termvariables, operator, and sort settings.
   *
   * @return array
   *   Array of article node IDs.
   */
  public function getArticlesFiltered($count, $tags) {
    // Use entity query to look up a specific number article nids filtered by term references.
    $operator = 'and';
    $sort = 'latest';

    if (isset($tags) && $tags != NULL) {
      $termvariables = $tags[0]['termvariables'];
      $operator = $tags[0]['operator'];
      $sort = $tags[0]['sort'];
    }

    $query = $this->entityTypeManager->getStorage('node')->getQuery()
      ->accessCheck(TRUE)
      ->condition('type', 'article')
      ->condition('status', 1);

    if ($sort == 'latest') {
      $query->sort('created', 'DESC');
      $query->range(0, $count);
    }
    if ($sort == 'oldest') {
      $query->sort('created', 'ASC');
      $query->range(0, $count);
    }
    if ($sort == 'random') {
      // Get more for random, to make sure it's really random.
      $query->range(0, 500);
    }

    // If termvariables set field-specific queries.
    if (isset($termvariables) && $termvariables != NULL) {
      foreach ($termvariables as $term) {
        if ($term['vid'] == 'tags') {
          if ($operator == 'and') {
            $query->condition($query->andConditionGroup()
              ->condition('field_tags.entity', $term['tid']));
          }
          if ($operator == 'or') {
            $query->condition($query->orConditionGroup()
              ->condition('field_tags.entity', $term['tid']));
          }
        }
        if ($term['vid'] == 'article_view_tags') {
          if ($operator == 'and') {
            $query->condition($query->andConditionGroup()
              ->condition('field_article_view_tags.entity', $term['tid']));
          }
          if ($operator == 'or') {
            $query->condition($query->orConditionGroup()
              ->condition('field_article_view_tags.entity', $term['tid']));
          }
        }
        if ($term['vid'] == 'departments_programs') {
          if ($operator == 'and') {
            $query->condition($query->andConditionGroup()
              ->condition('field_department_program.entity', $term['tid']));
          }
          if ($operator == 'or') {
            $query->condition($query->orConditionGroup()
              ->condition('field_department_program.entity', $term['tid']));
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
        if ($term['vid'] == 'article_bylines') {
          if ($operator == 'and') {
            $query->condition($query->andConditionGroup()
              ->condition('field_byline_reference.entity', $term['tid']));
          }
          if ($operator == 'or') {
            $query->condition($query->orConditionGroup()
              ->condition('field_byline_reference.entity', $term['tid']));
          }
        }
        if ($term['vid'] == 'article_mediasources') {
          if ($operator == 'and') {
            $query->condition($query->andConditionGroup()
              ->condition('field_media_source_reference.entity', $term['tid']));
          }
          if ($operator == 'or') {
            $query->condition($query->orConditionGroup()
              ->condition('field_media_source_reference.entity', $term['tid']));
          }
        }
      }
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
   * Gets articles for footer display, excluding the current article.
   *
   * @param int $count
   *   Number of articles to retrieve.
   * @param int $nid
   *   Node ID to exclude from results (current article).
   *
   * @return array
   *   Array of article node IDs.
   */
  public function getArticlesFooter($count, $nid) {
    // Use entity query to look up article nids to display in footer of article, excludes current nid.
    $query = $this->entityTypeManager->getStorage('node')->getQuery()
      ->accessCheck(TRUE)
      ->condition('type', 'article')
      ->condition('status', 1)
      // Don't include this nid.
      ->condition('nid', $nid, '<>')
      ->sort('created', 'DESC')
      ->range(0, $count);

    $nids = $query->execute();
    return $nids;
  }

  /**
   * Gets articles related to a specific person (field_related_people).
   *
   * @param int $count
   *   Number of articles to retrieve.
   * @param int $nid
   *   Person node ID to filter by.
   *
   * @return array
   *   Array of article node IDs.
   */
  public function getArticlesPerson($count, $nid) {
    // Use entity query to look up article nids with related people containing current nid.
    $query = $this->entityTypeManager->getStorage('node')->getQuery()
      ->accessCheck(TRUE)
      ->condition('type', 'article')
      ->condition('status', 1)
      // Only if related people contains nid.
      ->condition('field_related_people.entity', $nid)
      ->sort('created', 'DESC')
      ->range(0, $count);

    $nids = $query->execute();
    return $nids;
  }

  /**
   * Gets articles related to a specific person (field_related_people_nodes).
   *
   * @param int $count
   *   Number of articles to retrieve.
   * @param int $nid
   *   Person node ID to filter by.
   *
   * @return array
   *   Array of article node IDs.
   */
  public function getArticlesPersonAs($count, $nid) {
    // Use entity query to look up article nids with related people containing current nid.
    $query = $this->entityTypeManager->getStorage('node')->getQuery()
      ->accessCheck(TRUE)
      ->condition('type', 'article')
      ->condition('status', 1)
      // Only if related people node contains nid.
      ->condition('field_related_people_nodes.entity', $nid)
      ->sort('created', 'DESC')
      ->range(0, $count);

    $nids = $query->execute();
    return $nids;
  }

  /**
   * Gets articles filtered by department/program term.
   *
   * @param int $count
   *   Number of articles to retrieve.
   * @param mixed $tags
   *   Department/program term ID(s) to filter by.
   *
   * @return array
   *   Array of article node IDs.
   */
  public function getArticlesDepartment($count, $tags) {
    // Use entity query to look up article nids filtered by related departments tid.
    $query = $this->entityTypeManager->getStorage('node')->getQuery()
      ->accessCheck(TRUE)
      ->condition('type', 'article')
      ->condition('status', 1)
      // Only if related departments contains tid.
      ->condition('field_department_program.entity', $tags)
      ->sort('created', 'DESC')
      ->range(0, $count);

    $nids = $query->execute();
    return $nids;
  }

}
