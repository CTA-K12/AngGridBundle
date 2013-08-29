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

        $this->grid['actions'] = array();
        $this->grid['buttons'] = array();
        $this->grid['exportString'] = $request->query->get( 'exportString' );
        $this->grid['headers'] = array();
        $this->grid['page'] = $request->query->get( 'page' );
        $this->grid['perPage'] = $request->query->get( 'perPage' );
        $this->grid['requestCount'] = $request->query->get( 'requestCount' );
        $this->grid['search'] = $request->query->get( 'search' );
        $this->grid['sortsString'] = $request->query->get( 'sorts' );
        $this->grid['exportArray'] = is_null($snappy) 
            ? array(
                array('label' => 'CSV', 'value' => 'csv', 'exportLink' => '#'), 
                array('label' => 'TSV', 'value' => 'tsv', 'exportLink' => '#'), 
                array('label' => 'Excel', 'value' => 'xls', 'exportLink' => '#') )
            : array(
                array('label' => 'CSV', 'value' => 'csv', 'exportLink' => '#'), 
                array('label' => 'TSV', 'value' => 'tsv', 'exportLink' => '#'), 
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

    public function setAction( $item ) {
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
        $this->grid['actions'][$item['alias']] = $item;
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
        $qb = Query::search( $this->queryBuilder, $search, $this->grid['headers'] );
        $this->grid['filtered'] = $qb->getQuery()->getSingleScalarResult();
        $this->queryBuilder->select( $this->root );

        foreach ( $this->selects as $select ) {
            $this->queryBuilder->addSelect( $select );
        }

        if ( !$this->export ) {
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
            $qb->setFirstResult( $this->grid['perPage'] * ( $this->grid['page'] - 1 ) )
            ->setMaxResults( $this->grid['perPage'] );
        }

        if ( !is_null( $this->grid['sortsString'] ) ) {
            $this->grid['sorts'] = json_decode( $this->grid['sortsString'] );
            foreach ( $this->grid['sorts'] as $sort ) {
                $qb->addOrderBy( $this->grid['headers'][$sort->column]['column'], $sort->direction );
                if ( 'asc' == $sort->direction ) {
                    $this->grid['headers'][$sort->column]['sortIcon'] = 'icon-sort-up';
                } else {
                    $this->grid['headers'][$sort->column]['sortIcon'] = 'icon-sort-down';
                }
            }
        }

        $results = $this->paginator->paginate(
            $this->queryBuilder->getQuery()->setHint( 'knp_paginator.count', $this->grid['filtered'] ),
            $this->grid['page'],
            $this->grid['perPage'],
            array( 'distinct' => $distinct ) );

        $rootId = null;
        foreach ( $results as $result ) {
            if ( isset( $result ) && get_class( $result ) == $this->rootClass ) {
                $rootId = $result->getId();
            }
            $paths = array();
            foreach ( $this->grid['actions'] as $action ) {
                if ( isset( $action['function'] ) ) {
                    $function = $action['function'];
                    $path = $function( $result, $this->router );
                    if ( get_class( $result ) == $this->rootClass ) {
                        if ('' != $path['path']) {
                            $paths[$action['alias']] = $path['path'];
                        }
                    } else {
                        if ('' != $path['path']) {
                            $path['id'] = $rootId;
                            $paths[$action['alias']] = $path;
                        }
                    }
                } else {
                    $paths[$action['alias']] = $this->router->generate( $action['alias'], array( 'id' => $result->getId() ) );
                }
            }
            $buttons = array();
            foreach ( $this->grid['buttons'] as $button ) {
                if ( isset( $button['function'] ) ) {
                    $function = $button['function'];
                    $path = $function( $result, $this->router );
                    if ( get_class( $result ) == $this->rootClass ) {
                        if ('' != $path['path']) {
                            $buttons[$button['alias']] = $path['path'];
                        }
                    } else {
                        if ('' != $path['path']) {
                            $path['id'] = $rootId;
                            $buttons[$button['alias']] = $path;
                        }
                    }
                } else {
                    $buttons[$button['alias']] = $this->router->generate( $button['alias'], array( 'id' => $result->getId() ) );
                }
            }
            $values = array();
            foreach ( $this->grid['headers'] as $header ) {
                if ( 'boolean' == $header['type'] ) {
                    if ( isset( $header['function'] ) ) {
                        // not ready yet
                    } else {
                        if ( get_class( $result ) == $this->rootClass ) {
                            $columns = explode( '.', $header['field'] );
                            $value = $result;
                            foreach ( $columns as $key => $column ) {
                                if ( isset($value) && $key > 0 ) {
                                    $value = call_user_func( array( $value, 'get' . ucwords( $column ) ) );
                                }
                            }
                            $values[$header['column']] = $value;
                        }
                        $values[$header['column']] =
                            ( $value ? '<span class="icon-ok csuccess"></span>' : '<span class="icon-remove cdanger"></span>' );
                    }
                } else {
                    if ( isset( $header['function'] ) ) {
                        $function = $header['function'];
                        $value = $function( $result );
                        if ( get_class( $result ) == $this->rootClass ) {
                            $values[$header['column']] = $value['value'];
                        } else {
                            $values[$header['column']] = $value;
                        }
                    } else {
                        if ( get_class( $result ) == $this->rootClass ) {
                            $columns = explode( '.', $header['field'] );
                            $value = $result;
                            foreach ( $columns as $key => $column ) {
                                if ( isset($value) && $key > 0 ) {
                                    $value = call_user_func( array( $value, 'get' . ucwords( $column ) ) );
                                }
                            }
                            $values[$header['column']] = $value;
                        }
                    }
                }
            }
            if ( get_class( $result ) == $this->rootClass ) {
                $this->grid['entities']['id_' . $result->getId()] = array(
                    'id' => $result->getId(),
                    'paths' => $paths,
                    'buttons' => $buttons,
                    'values' => $values,
                );
            } else {
                foreach ( $values as $key => $value ) {
                    if ( isset( $value['id'] ) ) {
                        $this->grid['entities']['id_' . $value['id']]['values'][$key] = $value['value'];
                    }
                }
                foreach ( $buttons as $key => $button ) {
                    if ( isset( $button['id'] ) ) {
                        $this->grid['entities']['id_' . $button['id']]['buttons'][$key] = $button['button'];
                    }
                }
                foreach ( $paths as $key => $path ) {
                    if ( isset( $path['id'] ) ) {
                        $this->grid['entities']['id_' . $path['id']]['paths'][$key] = $path['path'];
                    }
                }
            }
        }
        if ( $this->export ) {
            if ($this->grid['exportType'] == 'pdf' && !is_null($this->snappy)) {
                $html = $this->templating->render( 'MESDAngGridBundle:Grid:export.pdf.twig',
                    array(
                        'entities' => $this->grid['entities'],
                        'headers' => $this->grid['headers'],
                    )
                );

                $response = new Response($this->snappy->getOutputFromHtml($html, array('orientation' => 'Landscape')),
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
            '&sorts=' . $this->grid['sortsString'] .
            '&page=' . $this->grid['page'] .
            '&perPage=' . $this->grid['perPage'];
            for($i = 0; $i < count($this->grid['exportArray']); $i++) {
                $this->grid['exportArray'][$i]['exportLink'] = $this->router->generate( $this->exportAlias, 
                array( 'exportType' => $this->grid['exportArray'][$i]['value'] ) ) . 
                '?exportString=true&search=' . $this->grid['search'] . 
                '&sorts=' . $this->grid['sortsString'] . 
                '&page=' . $this->grid['page'] . 
                '&perPage=' . $this->grid['perPage'];
            }
        }

        return new JsonResponse( $this->grid );
    }

    public function setFormUrl($url) {
        $this->grid['formUrl'] = $url;
    }

    public function prependSearch($search){
        $this->prepend = $search[0].' ';
    }
}
