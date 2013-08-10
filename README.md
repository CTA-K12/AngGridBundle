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
        }

        if (is_null($grid['exportString'])) {
            $export = false;
        } else {
            $export = true;
        }

        $grid['actions'] = array(
                'example_show' => array(
                    'alias' => 'example_show',
                    'title' => '',
                    'class' => 'btn-info',
                    'icon' => 'icon-search',
                ),
                'example_edit' => array(
                    'alias' => 'example_edit',
                    'title' => '',
                    'class' => 'btn-primary',
                    'icon' => 'icon-pencil',
                )
            )
        ;

        $em = $this->getDoctrine()->getManager();
        $qb= $em->getRepository( 'MESDAngGridBundle:Example' )
            ->createQueryBuilder( 'e' );

        $grid['idColumn'] = 'e.id';

        $grid['headers'] = array(
                'e.id' => array(
                    'column' => 't.id',
                    'show' => 'false',
                    'search' => 'false',
                    'sortIcon' => 'icon-sort',
                    'title' => 'ID',
                    'type' => 'text',
                ),
                'e.shortName' => array(
                    'column' => 'e.shortName',
                    'search' => 'true',
                    'show' => 'true',
                    'sortIcon' => 'icon-sort',
                    'title' => 'Short Name',
                    'type' => 'text',
                ),
                'e.longName' => array(
                    'column' => 'e.longName',
                    'search' => 'true',
                    'show' => 'true',
                    'sortIcon' => 'icon-sort',
                    'title' => 'Long Name',
                    'type' => 'text',
                ),
                'e.description' => array(
                    'column' => 'e.description',
                    'search' => 'true',
                    'show' => 'true',
                    'sortIcon' => 'icon-sort',
                    'title' => 'Description',
                    'type' => 'text',
                ),
                'e.modified' => array(
                    'column' => 'e.modified',
                    'search' => 'true',
                    'show' => 'true',
                    'sortIcon' => 'icon-sort',
                    'title' => 'Modified',
                    'type' => 'date',
                ),
                ),
                'e.active' => array(
                    'column' => 'e.modified',
                    'search' => 'false',
                    'show' => 'true',
                    'sortIcon' => 'icon-sort',
                    'title' => 'Modified',
                    'type' => 'boolean',
                ),
            )
        ;

        $qb->select('count(e)');
        $grid['total'] = $qb->getQuery()->getSingleScalarResult();
        $qb = $this->search($qb, $grid['search'], $grid['headers']);
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

        $qb->select( 'e' )
        ;

        if (!is_null($grid['sortsString'])) {
            $grid['sorts'] = json_decode($grid['sortsString']);
            foreach($grid['sorts'] as $sort) {
                $qb->addOrderBy($sort->column, $sort->direction);
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
                    'e.id'          => $result->getId(),
                    'e.shortName'   => $result->getShortName(),
                    'e.longName'    => $result->getLongName(),
                    'e.description' => $result->getDescription(),
                    'e.modified'    => $result->getModified(),
                )
            ;
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
            $grid['exportAlias'] = '';
        } else {
            $grid['exportAlias'] = 'caseload_export';
        }

        return $this->render('MESDAngGridBundle:Grid:data.json.twig',
            array(
                'grid' => $grid,
            )
        );
    }

    public function search($query, $value, $headers)
    {
        if ( isset( $value ) ) {
            $values = explode( ' ', str_replace( array( ',', ';' ), ' ', $value ) );
            foreach ( $values as $k => $term ) {
                $oqb=array();
                foreach ( $headers as $headerKey => $header ) {
                    if ('false' == $header['search']) {
                        continue;
                    }
                    if ( 'text' == $header['type'] ) {
                        $oqb[]=$query->expr()
                        ->like( "LOWER(CONCAT(" . $header['column'] . ", ''))", ':term' . $k );
                    } elseif ( 'date' == $header['type'] ) {
                            $dateout=preg_replace( '/^(\d\d)\/(\d\d)\/(\d\d\d\d).*$/', '$3-$1-$2', $term );
                        $oqb[]=$query->expr()->like( "CONCAT(" . $header['column'] . ", '')", ':date'.$k );
                    $query->setParameter( 'date'.$k, "%".strtolower( str_replace( '/', '-', $dateout ) )."%" );
                    } else {
                        $oqb[]=$query->expr()
                        ->like( "CONCAT(" . $header['column'] . ", '')", ':term' . $k );
                    }
                    $query->setParameter( 'term' . $k, "%" . strtolower( $term )."%" );
                }
                $query->andWhere( call_user_func_array( array( $query->expr(), "orx" ), $oqb ) );
            }
        }
        return $query;
    }
}
```