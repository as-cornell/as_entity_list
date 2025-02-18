<?php

namespace Drupal\as_entity_list;

class checkTid extends \Twig\Extension\AbstractExtension
{

    public function getName()
    {
        return 'check_tid';
    }

    public function getTests()
    {
        return [
            new \Twig\TwigTest('numeric', function ($value) { return  is_numeric($value); }),
        ];
    }
}