<?php

namespace Drupal\as_entity_list;

use Drupal\as_entity_list\Service\ArticleQueryService;
use Drupal\as_entity_list\Service\PersonQueryService;
use Drupal\as_entity_list\Service\TermQueryService;

/**
 * Twig extension for building entity lists.
 *
 * Extends Drupal's Twig_Extension class to provide build_entity_list() function.
 *
 * @package Drupal\as_entity_list
 */
class buildEntityList extends \Twig\Extension\AbstractExtension
{

  /**
   * The article query service.
   *
   * @var \Drupal\as_entity_list\Service\ArticleQueryService
   */
  protected $articleQuery;

  /**
   * The person query service.
   *
   * @var \Drupal\as_entity_list\Service\PersonQueryService
   */
  protected $personQuery;

  /**
   * The term query service.
   *
   * @var \Drupal\as_entity_list\Service\TermQueryService
   */
  protected $termQuery;

  /**
   * Constructs a buildEntityList object.
   *
   * @param \Drupal\as_entity_list\Service\ArticleQueryService $article_query
   *   The article query service.
   * @param \Drupal\as_entity_list\Service\PersonQueryService $person_query
   *   The person query service.
   * @param \Drupal\as_entity_list\Service\TermQueryService $term_query
   *   The term query service.
   */
  public function __construct(
    ArticleQueryService $article_query,
    PersonQueryService $person_query,
    TermQueryService $term_query
  ) {
    $this->articleQuery = $article_query;
    $this->personQuery = $person_query;
    $this->termQuery = $term_query;
  }

  /**
   * {@inheritdoc}
   * Let Drupal know the name of custom extension
   */
  public function getName()
  {
    return 'as_entity_list.build_entity_list';
  }


  /**
   * {@inheritdoc}
   * Return custom twig function to Drupal
   */
  public function getFunctions()
  {
    return [
      new \Twig\TwigFunction('build_entity_list', [$this, 'build_entity_list']),
    ];
  }

  /**
   * Gets filtered lists of node and term ids via entity query.
   *
   * @param string $type
   *   The type of entity list to build.
   * @param int $count
   *   Number of entities to retrieve.
   * @param mixed $tags
   *   Optional filtering tags or parameters.
   * @param int|null $nid
   *   Optional node ID for context-specific queries.
   *
   * @return array
   *   Array of entity IDs for theming.
   */
  public function build_entity_list($type, $count, $tags, $nid)
  {
    $entity_list = [];

    // Article nids filtered by tags
    if ($type == 'article') {
      $entity_list = $this->articleQuery->getArticles($count, $tags);
    }
    // Article nids with advanced filtering
    if ($type == 'article_as') {
      $entity_list = $this->articleQuery->getArticlesFiltered($count, $tags);
    }
    // Article nids to display in footer of article, excludes current nid
    if ($type == 'article_footer') {
      $entity_list = $this->articleQuery->getArticlesFooter($count, $nid);
    }
    // Article nids to display in footer of person, filtered by person nid
    if ($type == 'article_person') {
      $entity_list = $this->articleQuery->getArticlesPerson($count, $nid);
    }
    if ($type == 'article_person_as') {
      $entity_list = $this->articleQuery->getArticlesPersonAs($count, $nid);
    }
    if ($type == 'article_department') {
      $entity_list = $this->articleQuery->getArticlesDepartment($count, $tags);
    }
    // People nids filtered by tags, type is used to filter results by vocabulary
    if ($type == 'person') {
      $entity_list = $this->personQuery->getPeople($count, $tags);
    }
    if ($type == 'person_department') {
      $entity_list = $this->personQuery->getPeopleDepartment($count, $tags);
    }
    if ($type == 'person_research') {
      $entity_list = $this->personQuery->getPeopleResearch($count, $tags);
    }
    if ($type == 'dpc_as') {
      $entity_list = $this->termQuery->getTermsFiltered($type, $count, $tags);
    }
    if ($type == 'mmg_as') {
      $entity_list = $this->termQuery->getTermsFiltered($type, $count, $tags);
    }
    if ($type == 'academic_interests') {
      $entity_list = $this->termQuery->getTerms($type, $count);
    }
    if ($type == 'research_areas') {
      $entity_list = $this->termQuery->getTerms($type, $count);
    }

    return $entity_list;
  }
}
