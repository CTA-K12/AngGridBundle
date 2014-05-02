<?php

namespace MESD\Ang\GridBundle\Helper;
use Doctrine\ORM\Query\SqlWalker;

class QueryHelper
{
    public static function search($qb, $value, $headers, $negative=false)
    {
        if ( $negative && '' == $value ) {
            $value="WhatHaveYouBeenSmoking,Lighthart?";
        }

        if ('' != $value) {
            $values = explode( ' ', str_replace( array( ',', ';' ), ' ', $value ) );
            foreach ( $values as $k => $term ) {
                $oqb=array();
                foreach ( $headers as $headerKey => $header ) {
                    if ( false === $header['searchable'] ) {
                        continue;
                    }
                    if ( 'date' == $header['type'] ) {
                            $dateout=preg_replace( '/^(\d\d)\/(\d\d)\/(\d\d\d\d).*$/', '$3-$1-$2', $term );
                        $oqb[]=$qb->expr()->iLike( "CONCAT(" . $header['column'] . ", '')", ':date'.$k );
                    $qb->setParameter( 'date'.$k, "%".str_replace( '/', '-', $dateout )."%" );
                    } else {
                        $oqb[]=$qb->expr()
                        ->iLike( "CONCAT(" . $header['column'] . ", '')", ':term' . $k );
                        if (isset($header['addSort'])) {
                            foreach ($header['addSort'] as $newSort) {
                                $oqb[]=$qb->expr()
                                ->iLike( "CONCAT(" . $newSort . ", '')", ':term' . $k );
                            }
                        }
                    }
                    $qb->setParameter( 'term' . $k, "%" . $term ."%" );
                }
                $qb->andWhere( call_user_func_array( array( $qb->expr(), "orx" ), $oqb ) );
            }
        }
        return $qb;
    }

    public static function orderColumns($headers, $columns){
        $newHeaders = array();

        foreach($columns as $column){
            $newHeaders[$column] = $headers[$column];
        }

        $newHeaders = array_merge($newHeaders, $headers);
        return $newHeaders;
    }

    public static function hideColumns($headers, $columns){

        foreach($columns as $column){
            unset($headers[$column]);
        }
        return $headers;
    }
}