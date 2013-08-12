<?php

namespace MESD\Ang\GridBundle\Helper;

class Query
{
    public static function search($query, $value, $headers)
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