<?php

namespace MESD\Ang\GridBundle\Helper;

use Doctrine\ORM\Tools\Pagination\Paginator;
use Symfony\Component\HttpFoundation\JsonResponse;

class GridManager
{
    private $export;
    private $grid;
    private $queryBuilder;

    public function __construct($queryBuilder, $request, $exportType)
    {
        $this->queryBuilder = $queryBuilder;

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

    public function setHeader($column)
    {
        $name = $column['column'];
        if (!isset($column['header'])) {
            $column['header'] = $name;
        }
        if (!isset($column['id'])) {
            $column['id'] = str_replace('.', '-', $name);
        }
        if (!isset($column['searchable'])) {
            $column['searchable'] = 'true';
        }
        if (!isset($column['sort-icon'])) {
            $column['sort-icon'] = 'icon-sort';
        }
        if (!isset($column['title'])) {
            $column['title'] = $name;
        }
        $this->grid['headers'][$column['column']] = $column;
    }

    public function getJsonResponse()
    {
        //$this->grid['total'] = $this->queryBuilder->getQuery()->getSingleScalarResult();
        $qb = Query::search( $this->queryBuilder, $this->grid['search'], $this->grid['headers'] );
        //$this->grid['filtered'] = $qb->getQuery()->getSingleScalarResult();
        $this->grid['filtered'] = 0;

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

        $results = new Paginator( $qb->getQuery(), $fetchJoinCollection = true );

        // entities

        if ( $this->export ) {
            $response = $this->render('MESDAngGridBundle:Grid:export.' . $this->grid['exportType'] . '.twig',
                array(
                    'entities' => $this->grid['entities'],
                    'headers' => $this->grid['headers'],
                )
            );
            $response->headers->set('Content-Type', 'text/' . $this->grid['exportType']);
            $response->headers->set('Content-Disposition', 'attachment; filename="export.' . $this->grid['exportType'] . '"');

            return $response;
        }

        // export

        return new JsonResponse($this->grid);
    }
}