[![Latest Stable Version](https://poser.pugx.org/as-cornell/as_entity_list/v)](https://packagist.org/packages/as-cornell/as_entity_list)

# AS ENTITY LIST (as_entity_list)

## INTRODUCTION

Lists of people, articles or taxonomy term with selectors for number of items, tag filter, view mode.  Returns entities as a list via a twig filter for inclusion in twig templates using twig tweak.

## MAINTAINERS

Current maintainers for Drupal 10:

- Mark Wilson (markewilson)

## ARCHITECTURE

This module uses a service-oriented architecture with three query service classes and a Twig extension for template integration.

### Service Classes

All entity query logic is organized into service classes under `src/Service/`:

**ArticleQueryService** (`as_entity_list.article_query`)
- `getArticles($count, $tags)` - Get articles filtered by tags
- `getArticlesFiltered($count, $tags)` - Advanced filtering with operator, sort options
- `getArticlesFooter($count, $nid)` - Get articles excluding current NID (for footer display)
- `getArticlesPerson($count, $nid)` - Get articles related to person (field_related_people)
- `getArticlesPersonAs($count, $nid)` - Get articles related to person (field_related_people_nodes)
- `getArticlesDepartment($count, $tags)` - Get articles filtered by department/program

**PersonQueryService** (`as_entity_list.person_query`)
- `getPeople($count, $tags)` - Get people sorted alphabetically by last name
- `getPeopleDepartment($count, $tags)` - Get people filtered by department with advanced options
- `getPeopleResearch($count, $tags)` - Get people filtered by research area

**TermQueryService** (`as_entity_list.term_query`)
- `getTerms($type, $count)` - Get terms from a vocabulary
- `getTermsFiltered($type, $count, $tags)` - Advanced term filtering for DPC/MMG lists

### Twig Extension

**buildEntityList** (`as_entity_list.buildEntityList`)

Extends Twig with the `build_entity_list()` function for use in templates:

```twig
{{ build_entity_list(type, count, tags, nid) }}
```

**Supported Types:**
- `article` - Articles filtered by tags
- `article_as` - Articles with advanced filtering
- `article_footer` - Articles for footer (excludes current)
- `article_person` - Articles by person (field_related_people)
- `article_person_as` - Articles by person (field_related_people_nodes)
- `article_department` - Articles by department/program
- `person` - People alphabetically
- `person_department` - People by department
- `person_research` - People by research area
- `dpc_as` - Department/Program/Center terms filtered
- `mmg_as` - Major/Minor/Gradfield terms filtered
- `academic_interests` - Academic interest terms
- `research_areas` - Research area terms

### Usage Example

```twig
{# Get 5 latest articles #}
{% set articles = build_entity_list('article', 5, null, null) %}

{# Get 10 people from a specific department #}
{% set department_tags = [{'termvariables': [...], 'operator': 'and', 'sort': 'alphabetical'}] %}
{% set people = build_entity_list('person_department', 10, department_tags, null) %}

{# Get related articles for footer (exclude current article) #}
{% set related = build_entity_list('article_footer', 3, null, node.id) %}
```
