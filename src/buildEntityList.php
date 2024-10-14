<?php

namespace Drupal\as_entity_list;


/**
 * extend Drupal's Twig_Extension class
 */
class buildEntityList extends \Twig\Extension\AbstractExtension
{

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
   * Gets filtered lists of node and term ids via entity query and parses into array for theming
   *
   *
   * @return array $article_record
   *   data in array for theming
   */
  public function build_entity_list($type,$count,$tags,$nid)
  {
    if ($type == 'academic_interests'){
        $entity_list = as_entity_list_get_terms($type,$count);
      }
    if ($type == 'research_areas'){
        $entity_list = as_entity_list_get_terms($type,$count);
      }
    //article nids filtered by tags
    if ($type == 'article'){
        $entity_list = as_entity_list_get_articles($type,$count,$tags);
      }
    // article nids to display in footer of article, excludes current nid
    if ($type == 'article_footer'){
        $entity_list = as_entity_list_get_articles_footer($type,$count,$nid);
      }
    // article nids to display in footer of person, filtered by person nid
    if ($type == 'article_person'){
        $entity_list = as_entity_list_get_articles_person($count,$nid);
      }
    // people nids filtered by tags, type is used to filter results by vocabulary
    if ($type == 'person'){
        $entity_list = as_entity_list_get_people($type,$count,$tags);
      }
    if ($type == 'work'){
        $entity_list = as_entity_list_get_works($type,$count,$tags);
      }
    if ($type == 'cinevent'){
        $entity_list = as_entity_list_get_cinevents($type,$count,$tags);
      }
    return $entity_list;
  }
}
