[![Latest Stable Version](https://poser.pugx.org/as-cornell/as_entity_list/v)](https://packagist.org/packages/as-cornell/as_entity_list)

# AS ENTITY LIST (as_entity_list)

## INTRODUCTION

Lists of people, articles or taxonomy term with selectors for number of items, tag filter, view mode.  Returns entities as a list via a twig filter for inclusion in twig templates using twig tweak.

## MAINTAINERS

Current maintainers for Drupal 10:

- Mark Wilson (markewilson)

## FUNCTIONS
- as_entity_list_get_articles 
  //use entity query to look up article nids filtered by tags
- as_entity_list_get_articles_footer 
  //use entity query to look up article nids to display in footer of article, excludes current nid
- as_entity_list_get_articles_person 
  //use entity query to look up article nids with related people containing current nid
- as_entity_list_get_people 
  //use entity query to look up people nids filtered by research area
- as_entity_list_get_works 
  //use entity query to look up nids of work content type
- as_entity_list_get_cinevents 
  //use entity query to look up nids of cinevent content type 
- as_entity_list_get_terms 
  //use entity query to look up tids from a passed vocabulary
- as_entity_list_help
- build_entity_list($type,$count,$tags,$nid) 
  //buildEntityList extends Drupal's Twig_Extension class
