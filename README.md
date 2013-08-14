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
        'bundles/mesdanggrid/js/grid_filters.js'
        %}
        <script src="{{ asset_url }}"></script>
    {% endjavascripts %}
{% endblock javascripts %}
```

src/MESD/App/ExampleBundle/Resources/config/routing/example.yml
```yml
example:
    pattern:  /
    defaults: { _controller: "MESDAngGridBundle:Grid:index" }

example_list:
    pattern:  /list
    defaults: { _controller: "MESDAngGridBundle:Grid:list" }

example_data:
    pattern: /data.json
    defaults: { _controller: "MESDAngGridBundle:Grid:data" }

example_export:
    pattern: /export.{exportType}
    defaults: { _controller: "MESDAngGridBundle:Grid:data" }
```

src/MESD/App/ExampleBundle/ExampleController.php
```php
<?php

namespace MESD\Ang\GridBundle\Controller;

use Doctrine\ORM\Tools\Pagination\Paginator;
use MESD\Ang\GridBundle\Helper\Query;
use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Symfony\Component\HttpFoundation\Request;

class ExampleController extends Controller
{
    public function indexAction()
    {
        return $this->render('MESDAngGridBundle:Grid:index.html.twig');
    }

    public function listAction()
    {
        return $this->render('MESDAngGridBundle:Grid:list.html.twig', array('ngController' => 'GridController'));
    }

    public function dataAction(Request $request, $exportType = null)
    {
        $grid = array();

        $grid['exportString'] = $request->query->get('exportString');
        $grid['page'] = $request->query->get('page');
        $grid['perPage'] = $request->query->get('perPage');
        $grid['search'] = $request->query->get('search');
        $grid['sortsString'] = $request->query->get('sorts');

        if (is_null($exportType)) {
            $grid['exportType'] = $request->query->get('exportType');
        } else {
            $grid['exportType'] = $exportType;
        }

        if (is_null($grid['exportString'])) {
            $export = false;
        } else {
            $export = true;
        }

        $grid['actions'] = array(
                'example_show' => array(
                    'alias' => 'example_show',
                    'class' => 'btn-mini btn-default action',
                    'icon' => 'icon-search',
                    'title' => 'Show',
                ),
                'example_edit' => array(
                    'alias' => 'example_edit',
                    'class' => 'btn-mini btn-default action',
                    'icon' => 'icon-pencil',
                    'title' => 'Edit',
                )
            )
        ;

        $em = $this->getDoctrine()->getManager();
        $qb= $em->getRepository( 'MESDAngGridBundle:Example' )
            ->createQueryBuilder( 'example' );

        $grid['headers'] = array(
                'example.shortName' => array(
                    'column' => 'example.shortName',
                    'searchable' => 'true',
                    'sortIcon' => 'icon-sort',
                    'title' => 'Short Name',
                    'type' => 'text',
                ),
                'example.longName' => array(
                    'column' => 'example.longName',
                    'searchable' => 'true',
                    'sortIcon' => 'icon-sort',
                    'title' => 'Long Name',
                    'type' => 'text',
                ),
                'example.description' => array(
                    'column' => 'example.description',
                    'searchable' => 'true',
                    'sortIcon' => 'icon-sort',
                    'title' => 'Description',
                    'type' => 'text',
                ),
                'example.modified' => array(
                    'column' => 'example.modified',
                    'searchable' => 'true',
                    'sortIcon' => 'icon-sort',
                    'title' => 'Modified',
                    'type' => 'date',
                ),
                'example.active' => array(
                    'column' => 'example.active',
                    'searchable' => 'false',
                    'sortIcon' => 'icon-sort',
                    'title' => 'Active',
                    'type' => 'boolean',
                ),
            )
        ;

        $qb->select('count(example)');
        $grid['total'] = $qb->getQuery()->getSingleScalarResult();
        $grid['headers'] = Query::setOrder($grid['headers'],array('example.shortName','example.longName','example.description','example.modified'));
        $grid['headers'] = Query::hideColumns($grid['headers'],array('example.id'));
        $qb = Query::search($qb, $grid['search'], $grid['headers']);
        $grid['filtered'] = $qb->getQuery()->getSingleScalarResult();

        if (!$export) {
            if (0 < $grid['filtered']) {
                $grid['last'] = ceil($grid['filtered'] / $grid['perPage']);
            } else {
                $grid['last'] = 1;
            }
            if (1 > $grid['page']) {
                $grid['page'] = 1;
            } elseif ($grid['last'] < $grid['page']) {
                $grid['page'] = $grid['last'];
            }
            $qb->setFirstResult($grid['perPage'] * ($grid['page'] - 1))
            ->setMaxResults($grid['perPage']);
        }

        $qb->select('example')
        ;

        if (!is_null($grid['sortsString'])) {
            $grid['sorts'] = json_decode($grid['sortsString']);
            foreach($grid['sorts'] as $sort) {
                $qb->addOrderBy($grid['headers'][intval($sort->column)]['column'], $sort->direction);
                if ('asc' == $sort->direction) {
                    $grid['headers'][$sort->column]['sortIcon'] = 'icon-sort-up';
                } else {
                    $grid['headers'][$sort->column]['sortIcon'] = 'icon-sort-down';
                }
            }
        }

        $results = new Paginator($qb->getQuery(), $fetchJoinCollection = true);

        $grid['entities'] = array();
        foreach($results as $result) {
            $grid['entities'][] = array(
                'paths' => array(
                    'example_show' => $this->generateUrl('example_show', array('id' => $result->getId())),
                    'example_edit' => $this->generateUrl('example_edit', array('id' => $result->getId())),
                ),
                'values' => array(
                    'example.shortName' => $result->getShortName(),
                    'example.longName' => $result->getLongName(),
                    'example.description' => $result->getDescription(),
                    'example.modified' => $result->getModified()->format('Y-m-d H:i:s'),
                    'example.active' => $result->getActive() ? '✔' : '✘',
                ),
            );
        }

        if ($export) {
            $response = $this->render('MESDAngGridBundle:Grid:export.' . $grid['exportType'] . '.twig',
                array(
                    'entities' => $grid['entities'],
                    'headers' => $grid['headers'],
                )
            );
            $response->headers->set('Content-Type', 'text/' . $grid['exportType']);
            $response->headers->set('Content-Disposition', 'attachment; filename="export.' . $grid['exportType'] . '"');

            return $response;
        }

        if (is_null($grid['exportType'])) {
            $grid['exportLink'] = '';
        } else {
            $grid['exportLink'] = $this->generateUrl('caseload_export', array('exportType' => $grid['exportType'])) . '?exportString=true&search=' . $grid['search'] . '&sorts=' . $grid['sortsString'];
        }

        return new JsonResponse($grid);
    }
}
```
