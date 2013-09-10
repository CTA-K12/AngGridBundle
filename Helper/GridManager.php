<?php

namespace MESD\Ang\GridBundle\Helper;

use Knp\Component\Pager\Paginator;
use Knp\Bundle\SnappyBundle\Snappy\LoggableGenerator;
use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;

class GridManager {
    private $controller;
    private $export;
    private $exportAlias;
    private $grid;
    private $queryBuilder;
    private $root;
    private $rootClass;
    private $selects;
    private $prepend;
    private $snappy;

    public function __construct( $controller, Paginator $paginator, LoggableGenerator $snappy = null ) {
        $this->controller = $controller;
        $this->entityManager = $this->controller->getDoctrine()->getManager();
        $this->paginator = $paginator;
        $this->request = $this->controller->getRequest();
        $this->snappy = $snappy;
        $this->prepend = '';

        $this->selects = array();
        $this->grid = array();

        $this->grid['paths'] = array();
        $this->grid['entities'] = array();
        $this->grid['buttons'] = array();
        $this->grid['entities'] = array();
        $this->grid['exportString'] = $this->request->query->get( 'exportString' );
        $this->grid['headers'] = array();
        $this->grid['requestCount'] = $this->request->query->get( 'requestCount' );
        $this->grid['exportArray'] = is_null( $snappy )
            ? array(
            array( 'label' => 'TSV', 'value' => 'tsv', 'exportLink' => '#' ),
            array( 'label' => 'CSV', 'value' => 'csv', 'exportLink' => '#' ),
            array( 'label' => 'Excel', 'value' => 'xls', 'exportLink' => '#' ) )
            : array(
            array( 'label' => 'TSV', 'value' => 'tsv', 'exportLink' => '#' ),
            array( 'label' => 'CSV', 'value' => 'csv', 'exportLink' => '#' ),
            array( 'label' => 'Excel', 'value' => 'xls', 'exportLink' => '#' ),
            array( 'label' => 'PDF', 'value' => 'pdf', 'exportLink' => '#' ) );

        if ( is_null( $this->grid['exportString'] ) ) {
            $this->export = false;
        } else {
            $this->export = true;
        }

        $grid0 = $this->request->cookies->get( 'grid0' );
        if ( isset( $grid0 ) ) {
            $cookie =  json_decode( $grid0 );
        }
        $addView = $this->request->query->get( 'addView' );
        if ( isset( $addView ) ) {
            $this->grid['addView'] = $addView;
        } else {
            $this->grid['addView'] = json_decode( $this->request->cookies->get( 'addView' ) );
            if ( isset( $cookie->addView ) ) {
                $this->grid['addView'] = $cookie->addView;
            }
        }
        $page = $this->request->query->get( 'page' );
        if ( isset( $page ) ) {
            $this->grid['page'] = $page;
        } else {
            $this->grid['page'] = json_decode( $this->request->cookies->get( 'page' ) );
            if ( isset( $cookie->page ) ) {
                $this->grid['page'] = $cookie->page;
            }
        }
        $perPage = $this->request->query->get( 'perPage' );
        if ( isset( $perPage ) ) {
            $this->grid['perPage'] = $perPage;
        } else {
            $this->grid['perPage'] = json_decode( $this->request->cookies->get( 'perPage' ) );
            if ( isset( $cookie->perPage ) ) {
                $this->grid['perPage'] = $cookie->perPage;
            }
        }
        $search = $this->request->query->get( 'search' );
        if ( isset( $search ) ) {
            $this->grid['search'] = $search;
        } else {
            $this->grid['search'] = json_decode( $this->request->cookies->get( 'search' ) );
            if ( isset( $cookie->search ) ) {
                $this->grid['search'] = $cookie->search;
            }
        }
        $showControl = $this->request->query->get( 'showControl' );
        if ( isset( $showControl ) ) {
            $this->grid['showControl'] = $showControl;
        } else {
            $this->grid['showControl'] = json_decode( $this->request->cookies->get( 'showControl' ) );
            if ( isset( $cookie->showControl ) ) {
                $this->grid['showControl'] = $cookie->showControl;
            }
        }
        $sorts = json_decode( $this->request->query->get( 'sorts' ) );
        if ( isset( $sorts ) ) {
            $this->grid['sorts'] = $sorts;
        } else {
            $this->grid['sorts'] = json_decode( $this->request->cookies->get( 'sorts' ) );
            if ( isset( $cookie->sorts ) ) {
                $this->grid['sorts'] = $cookie->sorts;
            }
        }

        if ( !isset( $this->grid['addView'] ) ) {
            $this->grid['addView'] = false;
        }

        if ( !isset( $this->grid['showControl'] ) ) {
            $this->grid['showControl'] = true;
        }

        if ( !isset( $this->grid['sorts'] ) ) {
            $this->grid['sorts'] = null;
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
        if ( !isset( $item['field'] ) ) {
            $name = $item['title'];
            $item['field'] = $name;
            $item['column'] = $name;
        } else {
            $name = $item['field'];
            if ( !isset( $item['column'] ) ) {
                $last = strrpos( $name, '.' );
                if ( false == $last ) {
                    $item['column'] = $name;
                } else {
                    $nextLast = strrpos( $name, '.', $last - strlen( $name ) - 1 );
                    if ( false == $nextLast ) {
                        $item['column'] = $name;
                    } else {
                        $item['column'] = substr( $name, $nextLast + 1 );
                    }
                }
            }
        }

        if ( isset( $item['align'] ) && 'right' == $item['align'] ) {
            $item['align'] = 'td-align-right';
        }

        if ( isset( $item['align'] ) && 'center' == $item['align'] ) {
            $item['align'] = 'td-align-center';
        }

        // or for default case
        if ( !isset( $item['align'] ) || 'left' == $item['align'] ) {
            $item['align'] = 'td-align-left';
        }

        if ( !isset( $item['header'] ) ) {
            if ( isset( $item['title'] ) ) {
                $item['header'] = $item['title'];
            } else {
                $item['header'] = $name;
            }
        }

        if ( !isset( $item['hidden'] ) ) {
            $item['hidden'] = false;
        }

        if ( !isset( $item['id'] ) ) {
            $item['id'] = str_replace( '.', '-', $name );
        }

        if ( !isset( $item['searchable'] ) ) {
            $item['searchable'] = 'true';
        }

        if ( !isset( $item['sortable'] ) ) {
            $item['sortable'] = true;
        }

        if ( $item['sortable'] ) {
            $item['sortIcon'] = 'icon-sort';
        } else {
            $item['sortIcon'] = '';
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

    public function getJsonResponse( $distinct = true ) {
        $this->queryBuilder->select( $this->queryBuilder->expr()->count( 'distinct ' . $this->root . '.id' ) );
        $this->grid['total'] = $this->queryBuilder->getQuery()->getSingleScalarResult();
        $search = $this->prepend.$this->grid['search'];
        Query::search( $this->queryBuilder, $search, $this->grid['headers'] );
        $this->grid['filtered'] = $this->queryBuilder->getQuery()->getSingleScalarResult();
        $this->queryBuilder->select( $this->root );
        $this->removeHidden();
        foreach ( $this->selects as $select ) {
            $this->queryBuilder->addSelect( $select );
        }
        if ( 0 < $this->grid['filtered'] ) {
            if ( is_null( $this->grid['page'] ) ) {
                $this->grid['page'] = 1;
            }
            if ( is_null( $this->grid['perPage'] ) ) {
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
            if ( $this->grid['exportType'] == 'pdf' && !is_null( $this->snappy ) ) {
                $html = $this->controller->render( 'MESDAngGridBundle:Grid:export.pdf.twig',
                    array(
                        'entities' => $this->grid['entities'],
                        'headers' => $this->grid['headers'],
                    )
                );
                $response = new Response( $this->snappy->getOutputFromHtml( $html, array( 'orientation' => 'Landscape',
                            'print-media-type' => true,
                            'footer-left'  => 'Exported on [date] at [time]',
                            'footer-right' => 'Page [page] of [toPage]' ) ),
                    200,
                    array(
                        'Content-Type'          => 'application/pdf',
                        'Content-Disposition'   => 'attachment; filename="export.pdf"'
                    )
                );
            }
            else {
                $response = new Response( $this->controller->render( 'MESDAngGridBundle:Grid:export.' . $this->grid['exportType'] . '.twig',
                        array(
                            'entities' => $this->grid['entities'],
                            'headers' => $this->grid['headers'],
                        )
                    ) );
                $response->headers->set( 'Content-Type', 'text/' . $this->grid['exportType'] );
                $response->headers->set( 'Content-Disposition', 'attachment; filename="export.' . $this->grid['exportType'] . '"' );
            }

            return $response;
        }

        if ( is_null( $this->grid['exportType'] ) ) {
            $this->grid['exportLink'] = '';
            foreach ( $this->grid['exportArray'] as $exType ) {
                $exType['exportLink'] = '';
            }
        } else {
            $this->grid['exportLink'] = $this->controller->generateUrl( $this->exportAlias, array( 'exportType' => $this->grid['exportType'] ) ) .
                '?exportString=true&search=' . $this->grid['search'] .
                '&sorts=' . json_encode( $this->grid['sorts'] );
            for ( $i = 0; $i < count( $this->grid['exportArray'] ); $i++ ) {
                $this->grid['exportArray'][$i]['exportLink'] = $this->controller->generateUrl( $this->exportAlias,
                    array( 'exportType' => $this->grid['exportArray'][$i]['value'] ) ) .
                    '?exportString=true&search=' . $this->grid['search'] .
                    '&sorts=' . json_encode( $this->grid['sorts'] );
            }
        }

        if ( 'js' == $this->grid['exportType'] ) {
            $response = new JsonResponse( $this->grid );

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

            return new Response( $initData );
        } else {
            return new JsonResponse( $this->grid );
        }
    }

    public function setFormUrl( $url ) {
        $this->grid['formUrl'] = $url;
    }

    public function prependSearch( $search ) {
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
        if ( isset( $this->grid['sorts'] ) && '[]' != $this->grid['sorts'] ) {
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

    public function isExport() {
        return $this->export;
    }

    public function processResults() {
        $this->resultSet = null;
        foreach ( $this->results as $result ) {
            if ( isset( $result ) ) {
                $class = get_class( $result );
                if ( $class == $this->rootClass ) {
                    if ( isset( $this->resultSet ) ) {
                        $this->processResultSet();
                    }
                    $this->resultSet = array( 'root' => $result );
                } else {
                    $this->resultSet[$class][] = $result;
                }
            }
        }
        $this->processResultSet();
    }

    public function processResultSet() {
        $paths = $this->processActions( 'paths' );
        $buttons = $this->processActions( 'buttons' );
        $values = $this->processValues();
        $this->grid['entities']['id_' . $this->resultSet['root']->getId()] = array(
            'id' => $this->resultSet['root']->getId(),
            'paths' => $paths,
            'buttons' => $buttons,
            'values' => $values,
        );
    }

    public function processActions( $name ) {
        $actions = array();
        foreach ( $this->grid[$name] as $action ) {
            if ( isset( $action['function'] ) ) {
                $function = $action['function'];
                $path = $function( $this->resultSet, $this->controller );
                if ( isset( $path['path'] ) ) {
                    $actions[$action['alias']] = $path['path'];
                }
            } else {
                $actions[$action['alias']] = $this->controller->generateUrl( $action['alias'], array( 'id' => $this->resultSet['root']->getId() ) );
            }
        }
        return $actions;
    }

    public function processValues() {
        $values = array();
        foreach ( $this->grid['headers'] as $header ) {
            if ( isset( $header['function'] ) ) {
                $function = $header['function'];
                $value = $function( $this->resultSet, $this->controller );
                $values[$header['column']] = $value['value'];
            } else {
                $columns = explode( '.', $header['field'] );
                $value = $this->resultSet['root'];
                foreach ( $columns as $key => $column ) {
                    if ( isset( $value ) && $key > 0 ) {
                        $value = call_user_func( array( $value, 'get' . ucwords( $column ) ) );
                    }
                }
                if ( is_null( $value ) ) {
                    $value = '-';
                }
                $values[$header['column']] = $value;
            }
        }
        return $values;
    }

    public function removeHidden() {
        $columns = array();
        foreach ( $this->grid['headers'] as $headerKey => $header ) {
            if ( $header['hidden'] ) {
                $columns[] = $headerKey;
            }
        }
        $this->hideColumns( $columns );
    }
}
