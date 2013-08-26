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
            new MESD\Ang\GridBundle\MESDAngGridBundle(),
        );
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

src/MESD/App/ChangeThisBundle/ChangeThisController.php
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
        $qb->leftJoin('example.another', 'another');

        $gm = new GridManager(
            $this->get('doctrine.orm.entity_manager')
            , $this->get('knp_paginator')
            , $this->get('request')
            , $this->get('router')
            , $this->get('templating')
        );

        $gm->setQueryBuilder($qb);
        $gm->setRoot('example', 'MESD\App\ChangeThisBundle\Entity\Example');
        $gm->setSelect('another');

        $gm->setExportType($exportType);
        $gm->setExportAlias('example_export');

        $gm->setAction( array(
                'alias'   => 'example_show'
                , 'icon'  => 'icon-search'
                , 'title' => 'Show'
            )
        );

        $gm->setAction( array(
                'alias'   => 'example_edit'
                , 'icon'  => 'icon-pencil'
                , 'title' => 'Edit'
            )
        );

        // this is set with a function because the action is based on the id of an associated entity
        $gm->setAction( array(
                'alias'      => 'another_show'
                , 'icon'     => 'icon-file'
                , 'title'    => 'Show Another'
                , 'function' => function( $result, $router ) {
                    if ( isset($result) && get_class( $result ) == 'MESD\App\ChangeThisBundle\Entity\Another' ) {
                        return array(
                            'id' => $result->getExample()->getId(),
                            'path' => $router->generate( 'another_show', array( 'id' => $result->getId() ) ),
                        );
                    }
                    return array( 'path' => '' );
                }
            )
        );

        $gm->setButton( array(
                'alias'   => 'example_delete'
                , 'class' => 'btn btn-danger btn-mini'
                , 'icon'  => 'icon-remove'
                , 'title' => 'Delete'
            )
        );


        $gm->setHeader( array(
                'field'   => 'example.shortName'
                , 'title' => 'Short Name'
            )
        );

        $gm->setHeader( array(
                'field'   => 'example.longName'
                , 'title' => 'Long Name'
            )
        );

        $gm->setHeader( array(
                'field'   => 'example.another.shortName'
                , 'title' => 'Another'
            )
        );

        // date and time fields have to be given a function or else you get [Object object]
        $gm->setHeader( array(
                'field'      => 'example.effective'
                , 'title'    => 'Effective Date'
                , 'function' => function( $result ) {
                    return array( 'value' => $result ? $result->getDate()->format( 'm/d/Y' ) : '' );
                }
            )
        );

        return $gm->getJsonResponse();
    }
}
```
