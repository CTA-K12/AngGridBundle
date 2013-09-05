AngGridBundle
=============

A grid bundle written in AngularJS and Symfony

Example Code
------------

composer.json file
```js
    "repositories": [
        {
            "type" : "vcs",
            "url" : "https://github.com/MESD/AngGridBundle.git"
        }
    ],
    "require": {
        "mesd/ang-grid-bundle": "dev-master"
    }
```

app/AppKernel.php
```php
        $bundles = array(
            new Knp\Bundle\PaginatorBundle\KnpPaginatorBundle(),
            new Knp\Bundle\SnappyBundle\KnpSnappyBundle(),
            new MESD\Ang\GridBundle\MESDAngGridBundle(),
        );
```

app/config/config.yml
```yml
knp_snappy:
    pdf:
        enabled:    true
        binary:     "%kernel.root_dir%/../bin/wkhtmltopdf-amd64"
        options:    []
```

app/Resources/views/base.html.twig
```twig
{% extends 'MESDPresentationPresentationBundle::index.html.twig' %}
{% block javascripts %}
    {{parent()}}
    {% javascripts
        'bundles/mesdanggrid/js/angular-1.0.7.js'
        'bundles/mesdanggrid/js/angular-resource-1.0.7.js'
        'bundles/mesdanggrid/js/grid_config.js'
        'bundles/mesdanggrid/js/grid_controller.js'
        %}
        <script src="{{ asset_url }}"></script>
    {% endjavascripts %}
{% endblock javascripts %}
```

src/MESD/App/ChangeThisBundle/Resources/config/routing/example.yml
```yml
example:
    pattern:  /
    defaults: { _controller: "MESDAppChangeThisBundle:ChangeThis:index" }

example_grid:
    pattern:  /grid
    defaults: { _controller: "MESDAppChangeThisBundle:ChangeThis:grid" }

example_data:
    pattern: /data.json
    defaults: { _controller: "MESDAppChangeThisBundle:ChangeThis:data" }

example_export:
    pattern: /export.{exportType}
    defaults: { _controller: "MESDAppChangeThisBundle:ChangeThis:data" }
```

src/MESD/App/ChangeThisBundle/Resources/views/Example/index.html.twig
```twig
{% extends '::base.html.twig' %}
{% set subtitle = 'Grid' %}
{% set ngApp = 'gridModule' %}
{% block main %}
    <div data-ng-view>Loading...</div>
{% endblock main %}
{% block javascripts %}
    {{parent()}}
    <script type="text/javascript" src="export.js?perPage=10"></script>
{% endblock javascripts %}
```

src/MESD/App/ChangeThisBundle/Controller/ChangeThisController.php
```php
<?php

namespace MESD\App\ChangeThisBundle\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Symfony\Component\HttpFoundation\Request;

class ChangeThisController extends Controller
{
    public function indexAction()
    {
        return $this->render('MESDAppChangeThisBundle:Grid:index.html.twig');
    }

    public function gridAction()
    {
        return $this->render('MESDAngGridBundle:Grid:grid.html.twig', array('ngController' => 'GridController'));
    }

    public function dataAction(Request $request, $exportType = null)
    {
        $em = $this->getDoctrine()->getManager();
        $qb = $em->getRepository('MESDAppChangeThisBundle:Example')
            ->createQueryBuilder('example');
        $qb->leftJoin('example.notAssociated', 'notAssociated');

        $gm = new GridManager(
            $this->get('doctrine.orm.entity_manager')
            , $this->get('knp_paginator')
            , $this->get('request')
            , $this->get('router')
            , $this->get('templating')
            , $this->get('knp_snappy.pdf') //This can also be null, which will hide the pdf option from the export menu
        );

        $gm->setQueryBuilder($qb);
        $gm->setRoot('example', 'MESD\App\ChangeThisBundle\Entity\Example');
        $gm->setSelect('another');

        $gm->setExportType($exportType);
        $gm->setExportAlias('example_export');

        $gm->setPath( array(
                'alias'    => 'example_show',
                'icon'     => 'icon-search',
                'title'    => 'Show',
            )
        );

        $gm->setPath( array(
                'alias'    => 'example_edit',
                'icon'     => 'icon-pencil',
                'title'    => 'Edit',
            )
        );

        // this is set with a function because the action is based on the id of an associated entity
        $gm->setPath( array(
                'alias'    => 'associated_show',
                'icon'     => 'icon-file',
                'title'    => 'Show Associated',
                'function' => function($resultSet, $router) {
                    $class = 'MESD\ORMed\ORMedBundle\Entity\Associated';
                    if (isset($resultSet[$class]) && 0 < count($resultSet[$class])) {
                        return array(
                            'path' => $router->generate('associated_show', array('id' => $resultSet['root']->getAssociated()->getId())),
                        );
                    }
                    return array('path' => null);
                },
            )
        );

        // this is set with a function because the action is based on the id of a non associated entity
        $gm->setPath( array(
                'alias'    => 'not_associated_show',
                'icon'     => 'icon-file',
                'title'    => 'Show Not Associated',
                'function' => function($resultSet, $router) {
                    $class = 'MESD\ORMed\ORMedBundle\Entity\NotAssociated';
                    if (isset($resultSet[$class]) && 0 < count($resultSet[$class])) {
                        return array(
                            'path' => $router->generate('not_associated_show', array('id' => $resultSet[$class][0]->getId())),
                        );
                    }
                    return array('path' => null);
                },
            )
        );

        $gm->setButton( array(
                'alias'    => 'example_delete'
                'class'    => 'btn btn-danger btn-mini'
                'icon'     => 'icon-remove'
                'title'    => 'Delete'
            )
        );


        $gm->setHeader( array(
                'field'    => 'example.shortName'
                'title'    => 'Short Name'
            )
        );

        $gm->setHeader( array(
                'field'    => 'example.longName'
                'title'    => 'Long Name'
            )
        );

        $gm->setHeader( array(
                'field'    => 'example.another.shortName'
                'title'    => 'Another'
            )
        );

        // date and time fields have to be given a function or else you get [Object object]
        $gm->setHeader( array(
                'field'    => 'example.effective'
                'title'    => 'Effective Date'
                'function' => function($resultSet) {
                    return array('value' => $resultSet['root']->getDate() ? $resultSet['root']->getDate()->format( 'm/d/Y' ) : '-' );
                },
            )
        );

        if ($gm->isExport()) {
            $getItemOutput = function($resultSet) {
                    $items = $resultSet['root']->getItem()->toArray();
                    sort($items);
                    return array('value' => implode($items, ', ' ));
            };
        } else {
            $getItemOutput = function($resultSet) {
                    $items = $resultSet['root']->items()->toArray();
                    sort($items);
                    return array('value' => '<div>'.implode($items, '</div><div>' ).'</div>');
            };
        }

        $gm->setHeader(array(
                'field'    => 'example.item.shortName',
                'title'    => 'Items',
                'html'     => 'true',
                'function' => $getItemOutput,
            )
        );

        return $gm->getJsonResponse();
    }
}
```
