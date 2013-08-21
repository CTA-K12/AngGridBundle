<?php

namespace MESD\Ang\GridBundle\Helper;

use Symfony\Component\HttpFoundation\JsonResponse;

class GridManager
{
    private $queryBuilder;
    private $grid;

    public function __construct($queryBuilder, $request)
    {
        $this->queryBuilder = $queryBuilder;

        $this->grid['exportString'] = $request->query->get( 'exportString' );
        $this->grid['headers'] = array();
        $this->grid['page'] = $request->query->get( 'page' );
        $this->grid['perPage'] = $request->query->get( 'perPage' );
        $this->grid['requestCount'] = $request->query->get( 'requestCount' );
        $this->grid['search'] = $request->query->get( 'search' );
        $this->grid['sortsString'] = $request->query->get( 'sorts' );
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
        $this->headers[$column['column']] = $column;
    }

    public function getJsonResponse()
    {
        return new JsonResponse($this->grid);
    }
}