<?php

namespace MESD\Ang\GridBundle\Helper;

use Symfony\Component\HttpFoundation\JsonResponse;

class GridManager
{
    private $controller;
    private $export;
    private $exportAlias;
    private $grid;
    private $queryBuilder;
    private $root;
    private $rootClass;
    private $selects;

    public function __construct($root, $rootClass, $queryBuilder, $controller, $exportType = null)
    {
        $this->controller = $controller;
        $this->queryBuilder = $queryBuilder;
        $this->root = $root;
        $this->rootClass = $rootClass;
        $this->selects = array();

        $request = $this->controller->get('request');

        $this->grid['actions'] = array();
        $this->grid['exportString'] = $request->query->get( 'exportString' );
        $this->grid['headers'] = array();
        $this->grid['page'] = $request->query->get( 'page' );
        $this->grid['perPage'] = $request->query->get( 'perPage' );
        $this->grid['requestCount'] = $request->query->get( 'requestCount' );
        $this->grid['search'] = $request->query->get( 'search' );
        $this->grid['sortsString'] = $request->query->get( 'sorts' );

        if ( is_null( $exportType ) ) {
            $this->grid['exportType'] = $request->query->get( 'exportType' );
        } else {
            $this->grid['exportType'] = $exportType;
        }

        if ( is_null( $this->grid['exportString'] ) ) {
            $this->export = false;
        } else {
            $this->export = true;
        }
    }

    public function setSelect($select) {
        $this->selects[$select] = $select;
    }

    public function setAction($item)
    {
        $alias = $item['alias'];
        if (!isset($item['class'])) {
            $item['class'] = 'btn-mini action btn-default';
        }
        if (!isset($item['icon'])) {
            $item['icon'] = 'icon-search';
        }
        if (!isset($item['title'])) {
            $item['title'] = $alias;
        }
        $this->grid['actions'][$item['alias']] = $item;
    }

    public function setExportAlias($alias) {
        $this->exportAlias = $alias;
    }

    public function setHeader($item)
    {
        $name = $item['field'];
        if (!isset($item['column'])) {
            $last = strrpos($name, '.');
            $nextLast = strrpos($name, '.', $last - strlen($name) - 1);
            if (false == $nextLast) {
                $item['column'] = $name;
            } else {
                $item['column'] = substr($name, $nextLast + 1);
            }
        }
        if (!isset($item['header'])) {
            if (isset($item['title'])) {
                $item['header'] = $item['title'];
            } else {
                $item['header'] = $name;
            }
        }
        if (!isset($item['id'])) {
            $item['id'] = str_replace('.', '-', $name);
        }
        if (!isset($item['searchable'])) {
            $item['searchable'] = 'true';
        }
        if (!isset($item['sortIcon'])) {
            $item['sortIcon'] = 'icon-sort';
        }
        if (!isset($item['title'])) {
            if (isset($item['header'])) {
                $item['title'] = $item['header'];
            } else {
                $item['title'] = $name;
            }
        }
        if (!isset($item['type'])) {
            $item['type'] = 'text';
        }
        $this->grid['headers'][$item['column']] = $item;
    }

    public function getJsonResponse()
    {
        $this->queryBuilder->select($this->queryBuilder->expr()->count('distinct ' . $this->root . '.id'));
        $this->grid['total'] = $this->queryBuilder->getQuery()->getSingleScalarResult();
        $qb = Query::search( $this->queryBuilder, $this->grid['search'], $this->grid['headers'] );
        $this->grid['filtered'] = $qb->getQuery()->getSingleScalarResult();
        $this->queryBuilder->select($this->root);

        foreach($this->selects as $select) {
            $this->queryBuilder->addSelect($select);
        }

        if (!$this->export) {
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

        if (!is_null( $this->grid['sortsString'])) {
            $this->grid['sorts'] = json_decode( $this->grid['sortsString'] );
            foreach ($this->grid['sorts'] as $sort) {
                $qb->addOrderBy( $this->grid['headers'][$sort->column]['column'], $sort->direction );
                if ('asc' == $sort->direction) {
                    $this->grid['headers'][$sort->column]['sortIcon'] = 'icon-sort-up';
                } else {
                    $this->grid['headers'][$sort->column]['sortIcon'] = 'icon-sort-down';
                }
            }
        }
        $paginator = $this->controller->get('knp_paginator');
        $results = $paginator->paginate(
            $this->queryBuilder->getQuery()->setHint('knp_paginator.count', $this->grid['filtered']),
            $this->grid['page'],
            $this->grid['perPage'],
            array('distinct' => false));

        foreach($results as $result) {
            $paths = array();
            foreach($this->grid['actions'] as $action) {
                if (isset($action['function'])) {
                    $function = $action['function'];
                    $path = $function($result, $this->controller);
                    if (get_class($result) == $this->rootClass) {
                        $paths[$action['alias']] = $path['path'];
                    } else {
                        $paths[$action['alias']] = $path;
                    }
                } else {
                    $paths[$action['alias']] = $this->controller->generateUrl($action['alias'], array( 'id' => $result->getId()));
                }
            }
            $values = array();
            foreach($this->grid['headers'] as $header) {
                if (isset($header['function'])) {
                    $function = $header['function'];
                    $value = $function($result);
                    if (get_class($result) == $this->rootClass) {
                        $values[$header['column']] = $value['value'];
                    } else {
                        $values[$header['column']] = $value;
                    }
                } else {
                    if (get_class($result) == $this->rootClass) {
                        $columns = explode('.', $header['field']);
                        $value = $result;
                        foreach($columns as $key => $column){
                            if ($key > 0) {
                                $value = call_user_func(array($value,'get' . ucwords($column)));
                            }
                        }
                        $values[$header['column']] = $value;
                    }
                }
            }
            if (get_class($result) == $this->rootClass) {
                $this->grid['entities']['id_' . $result->getId()] = array(
                    'id' => $result->getId(),
                    'paths' => $paths,
                    'values' => $values,
                );
            } else {
                foreach($values as $key => $value) {
                    if (isset($value['id'])) {
                        $this->grid['entities']['id_' . $value['id']]['values'][$key] = $value['value'];
                    }
                }
                foreach($paths as $key => $path) {
                    if (isset($path['id'])) {
                        $this->grid['entities']['id_' . $path['id']]['paths'][$key] = $path['path'];
                    }
                }
            }
        }

        if ( $this->export ) {
            $response = $this->controller->render('MESDAngGridBundle:Grid:export.' . $this->grid['exportType'] . '.twig',
                array(
                    'entities' => $this->grid['entities'],
                    'headers' => $this->grid['headers'],
                )
            );
            $response->headers->set('Content-Type', 'text/' . $this->grid['exportType']);
            $response->headers->set('Content-Disposition', 'attachment; filename="export.' . $this->grid['exportType'] . '"');

            return $response;
        }

        if ( is_null( $this->grid['exportType'] ) ) {
            $this->grid['exportLink'] = '';
        } else {
            $this->grid['exportLink'] = $this->controller->generateUrl($this->exportAlias, array( 'exportType' => $this->grid['exportType'] ) ) . '?exportString=true&search=' . $this->grid['search'] . '&sorts=' . $this->grid['sortsString'];
        }

        return new JsonResponse($this->grid);
    }
}