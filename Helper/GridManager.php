<?php

namespace MESD\Ang\GridBundle\Helper;

use Doctrine\ORM\EntityManager;
use Knp\Component\Pager\Paginator;
use Knp\Bundle\SnappyBundle\Snappy\LoggableGenerator;
use Symfony\Bundle\TwigBundle\Debug\TimedTwigEngine;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Router;

use Symfony\Component\HttpFoundation\Response;

class GridManager {
    private $controller;
    private $export;
    private $exportAlias;
    private $grid;
    private $queryBuilder;
    private $root;
    private $router;
    private $rootClass;
    private $selects;
    private $templating;
    private $prepend;
    private $snappy;

    public function __construct( EntityManager $entityManager, Paginator $paginator, Request $request, Router $router, TimedTwigEngine $templating, LoggableGenerator $snappy = null ) {
        $this->entityManager = $entityManager;
        $this->paginator = $paginator;
        $this->request = $request;
        $this->router = $router;
        $this->templating = $templating;
        $this->snappy = $snappy;
        $this->prepend = '';

        $this->selects = array();
        $this->grid = array();

        $this->grid['paths'] = array();
        $this->grid['entities'] = array();
        $this->grid['buttons'] = array();
        $this->grid['entities'] = array();
        $this->grid['exportString'] = $request->query->get( 'exportString' );
        $this->grid['headers'] = array();
        $this->grid['page'] = $request->query->get( 'page' );
        $this->grid['perPage'] = $request->query->get( 'perPage' );
        $this->grid['requestCount'] = $request->query->get( 'requestCount' );
        $this->grid['search'] = $request->query->get( 'search' );
        $this->grid['sortsString'] = $request->query->get( 'sorts' );
        $this->grid['exportArray'] = is_null($snappy)
            ? array(
                array('label' => 'TSV', 'value' => 'tsv', 'exportLink' => '#'),
                array('label' => 'CSV', 'value' => 'csv', 'exportLink' => '#'),
                array('label' => 'Excel', 'value' => 'xls', 'exportLink' => '#') )
            : array(
                array('label' => 'TSV', 'value' => 'tsv', 'exportLink' => '#'),
                array('label' => 'CSV', 'value' => 'csv', 'exportLink' => '#'),
                array('label' => 'Excel', 'value' => 'xls', 'exportLink' => '#'),
                array('label' => 'PDF', 'value' => 'pdf', 'exportLink' => '#') );

        if ( is_null( $this->grid['exportString'] ) ) {
            $this->export = false;
        } else {
            $this->export = true;
        }
    }

    public function setRoot( $root, $rootClass ) {
        $this->root = $root;
        $this->rootClass = $rootClass;
    }

    public function setQueryBuilder( $queryBuilder ) {
        $this->queryBuilder = $queryBuilder;
    }

    public function setExportType( $exportType ) {
        if ( is_null( $exportType ) ) {
            $this->grid['exportType'] = $this->request->query->get( 'exportType' );
        } else {
            $this->grid['exportType'] = $exportType;
        }

    }

    public function setSelect( $select ) {
        $this->selects[$select] = $select;
    }

    public function setPath( $item ) {
        $alias = $item['alias'];

        if ( !isset( $item['class'] ) ) {
            $item['class'] = 'btn btn-mini btn-default action';
        }
        if ( !isset( $item['icon'] ) ) {
            $item['icon'] = 'icon-search';
        }
        if ( !isset( $item['title'] ) ) {
            $item['title'] = $alias;
        }
        $this->grid['paths'][$item['alias']] = $item;
    }

    public function setButton( $item ) {
        $alias = $item['alias'];

        if ( !isset( $item['class'] ) ) {
            $item['class'] = 'btn btn-mini btn-default action';
        }
        if ( !isset( $item['icon'] ) ) {
            $item['icon'] = 'icon-search';
        }
        if ( !isset( $item['title'] ) ) {
            $item['title'] = $alias;
        }
        $this->grid['buttons'][$item['alias']] = $item;
    }

    public function setExportAlias( $alias ) {
        $this->exportAlias = $alias;
    }

    public function setHeader( $item ) {
        $name = $item['field'];
        if ( !isset( $item['column'] ) ) {
            $last = strrpos( $name, '.' );
            $nextLast = strrpos( $name, '.', $last - strlen( $name ) - 1 );
            if ( false == $nextLast ) {
                $item['column'] = $name;
            } else {
                $item['column'] = substr( $name, $nextLast + 1 );
            }
        }
        if ( !isset( $item['header'] ) ) {
            if ( isset( $item['title'] ) ) {
                $item['header'] = $item['title'];
            } else {
                $item['header'] = $name;
            }
        }
        if ( !isset( $item['id'] ) ) {
            $item['id'] = str_replace( '.', '-', $name );
        }
        if ( !isset( $item['searchable'] ) ) {
            $item['searchable'] = 'true';
        }
        if ( !isset( $item['sortIcon'] ) ) {
            $item['sortIcon'] = 'icon-sort';
        }
        if ( !isset( $item['title'] ) ) {
            if ( isset( $item['header'] ) ) {
                $item['title'] = $item['header'];
            } else {
                $item['title'] = $name;
            }
        }

        if ( !isset( $item['type'] ) ) {
            $item['type'] = 'text';
        }

        if ( 'boolean' == $item['type'] ) {
            $item['html'] = true;
        }
        $this->grid['headers'][$item['column']] = $item;
    }

    public function orderColumns( $columns ) {
        $this->grid['headers'] = Query::orderColumns( $this->grid['headers'], $columns );
    }

    public function hideColumns( $columns ) {
        $this->grid['headers'] = Query::hideColumns( $this->grid['headers'], $columns );
    }

    public function getJsonResponse($distinct = true) {
        $this->queryBuilder->select( $this->queryBuilder->expr()->count( 'distinct ' . $this->root . '.id' ) );
        $this->grid['total'] = $this->queryBuilder->getQuery()->getSingleScalarResult();
        $search = $this->prepend.$this->grid['search'];
        Query::search( $this->queryBuilder, $search, $this->grid['headers'] );
        $this->grid['filtered'] = $this->queryBuilder->getQuery()->getSingleScalarResult();
        $this->queryBuilder->select( $this->root );

        if (0 < $this->grid['filtered']) {

            foreach ( $this->selects as $select ) {
                $this->queryBuilder->addSelect( $select );
            }

            if (is_null($this->grid['page'])) {
                $this->grid['page'] = 1;
            }
            if (is_null($this->grid['perPage'])) {
                $this->grid['perPage'] = $this->grid['filtered'];
            }

            if ( !$this->export ) {
                $this->calculatePages();
            }

            $this->addSorts();

            $this->results = $this->paginator->paginate(
                $this->queryBuilder->getQuery()->setHint( 'knp_paginator.count', $this->grid['filtered'] ),
                $this->grid['page'],
                $this->grid['perPage'],
                array( 'distinct' => $distinct ) );

            $rootId = null;

            $this->processResults();
        }

        if ( $this->export ) {
            if ($this->grid['exportType'] == 'pdf' && !is_null($this->snappy)) {
                $html = $this->templating->render( 'MESDAngGridBundle:Grid:export.pdf.twig',
                    array(
                        'entities' => $this->grid['entities'],
                        'headers' => $this->grid['headers'],
                    )
                );

                $response = new Response($this->snappy->getOutputFromHtml($html, array('orientation' => 'Landscape',
                        'print-media-type' => true,
                        'footer-left'  => 'Exported on [date] at [time]',
                        'footer-right' => 'Page [page] of [toPage]')),
                    200,
                    array(
                        'Content-Type'          => 'application/pdf',
                        'Content-Disposition'   => 'attachment; filename="export.pdf"'
                    )
                );
            }
            else {
                $response = new Response($this->templating->render( 'MESDAngGridBundle:Grid:export.' . $this->grid['exportType'] . '.twig',
                    array(
                        'entities' => $this->grid['entities'],
                        'headers' => $this->grid['headers'],
                    )
                ));
                $response->headers->set( 'Content-Type', 'text/' . $this->grid['exportType'] );
                $response->headers->set( 'Content-Disposition', 'attachment; filename="export.' . $this->grid['exportType'] . '"' );
            }

            return $response;
        }

        if ( is_null( $this->grid['exportType'] ) ) {
            $this->grid['exportLink'] = '';
            foreach($this->grid['exportArray'] as $exType) {
                $exType['exportLink'] = '';
            }
        } else {
            $this->grid['exportLink'] = $this->router->generate( $this->exportAlias, array( 'exportType' => $this->grid['exportType'] ) ) .
            '?exportString=true&search=' . $this->grid['search'] .
            '&sorts=' . $this->grid['sortsString'];
            for($i = 0; $i < count($this->grid['exportArray']); $i++) {
                $this->grid['exportArray'][$i]['exportLink'] = $this->router->generate( $this->exportAlias,
                array( 'exportType' => $this->grid['exportArray'][$i]['value'] ) ) .
                '?exportString=true&search=' . $this->grid['search'] .
                '&sorts=' . $this->grid['sortsString'];
            }
        }

        if ('js' == $this->grid['exportType']) {
            $response = new JsonResponse( $this->grid );
            //$initData = 'var initData = ' . $response->getContent();
            /*
myApp.service('helloWorldFromService', function() {
    this.sayHello = function() {
        return "Hello, World!"
    };
});
*/
            //$initData = 'gridModule.provider(\'initData\', function() {this.initData = function() {return ' . $response->getContent() . '};});';

            $initData = <<<EOT
//provider style, full blown, configurable version
gridModule.provider('initData', function() {
    // In the provider function, you cannot inject any
    // service or factory. This can only be done at the
    // "\$get" method.

    this.name = 'Default';

    this.\$get = function() {
        var name = this.name;
        return {
            initData: function() {
EOT;
                $initData .= 'return ' . $response->getContent();
                $initData .= <<<EOT
;
            }
        }
    };

    this.setName = function(name) {
        this.name = name;
    };
});
EOT;

            return new Response($initData);
        } else {
            return new JsonResponse( $this->grid );
        }
    }

    public function setFormUrl($url) {
        $this->grid['formUrl'] = $url;
    }

    public function prependSearch($search){
        $this->prepend = $search[0].' ';
    }

    public function calculatePages() {
            if ( 0 < $this->grid['filtered'] ) {
                $this->grid['last'] = ceil( $this->grid['filtered'] / $this->grid['perPage'] );
            } else {
                $this->grid['last'] = 1;
            }
            if ( 1 > $this->grid['page'] ) {
                $this->grid['page'] = 1;
            } elseif ( $this->grid['last'] < $this->grid['page'] ) {
                $this->grid['page'] = $this->grid['last'];
            }
            $this->queryBuilder->setFirstResult( $this->grid['perPage'] * ( $this->grid['page'] - 1 ) )
            ->setMaxResults( $this->grid['perPage'] );
    }

    public function addSorts() {
        if ( !is_null( $this->grid['sortsString'] ) ) {
            if ('' != $this->grid['sortsString']) {
                $this->grid['sorts'] = json_decode( $this->grid['sortsString'] );
                foreach ( $this->grid['sorts'] as $sort ) {
                    $this->queryBuilder->addOrderBy( $this->grid['headers'][$sort->column]['column'], $sort->direction );
                    if ( 'asc' == $sort->direction ) {
                        $this->grid['headers'][$sort->column]['sortIcon'] = 'icon-sort-up';
                    } else {
                        $this->grid['headers'][$sort->column]['sortIcon'] = 'icon-sort-down';
                    }
                }
            }
        }
    }

    public function isExport() {
        return $this->export;
    }

    public function processResults() {
        $this->resultSet = null;
        foreach ($this->results as $result) {
            if (isset($result)) {
                $class = get_class($result);
                if ($class == $this->rootClass) {
                    if (isset($this->resultSet)) {
                        $this->processResultSet();
                    }
                    $this->resultSet = array('root' => $result);
                } else {
                    $this->resultSet[$class][] = $result;
                }
            }
        }
        $this->processResultSet();
    }

    public function processResultSet() {
        $paths = $this->processActions('paths');
        $buttons = $this->processActions('buttons');
        $values = $this->processValues();
        $this->grid['entities']['id_' . $this->resultSet['root']->getId()] = array(
            'id' => $this->resultSet['root']->getId(),
            'paths' => $paths,
            'buttons' => $buttons,
            'values' => $values,
        );
    }

    public function processActions($name) {
        $actions = array();
        foreach($this->grid[$name] as $action) {
            if (isset($action['function'])) {
                $function = $action['function'];
                $path = $function($this->resultSet, $this->router);
                if (isset($path['path'])) {
                    $actions[$action['alias']] = $path['path'];
                }
            } else {
                $actions[$action['alias']] = $this->router->generate($action['alias'], array('id' => $this->resultSet['root']->getId()));
            }
        }
        return $actions;
    }

    public function processValues() {
        $values = array();
        foreach($this->grid['headers'] as $header) {
            if (isset($header['function'])) {
                $function = $header['function'];
                $value = $function($this->resultSet, $this->router, $this->templating);
                $values[$header['column']] = $value['value'];
            } else {
                $columns = explode( '.', $header['field'] );
                $value = $this->resultSet['root'];
                foreach ( $columns as $key => $column ) {
                    if ( isset($value) && $key > 0 ) {
                        $value = call_user_func( array( $value, 'get' . ucwords( $column ) ) );
                    }
                }
                if (is_null($value)) {
                    $value = '-';
                }
                $values[$header['column']] = $value;
            }
        }
        return $values;
    }
}
