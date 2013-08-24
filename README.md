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

app/config/config.yml
```yml
imports:
    - { resource: "@MESDAngGridBundle/Resources/config/services.yml" }
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
        %}
        <script src="{{ asset_url }}"></script>
    {% endjavascripts %}
{% endblock javascripts %}
```

src/MESD/App/ChangeThisBundle/Resources/config/routing/example.yml
```yml
example:
    pattern:  /
    defaults: { _controller: "MESDAppChangeThisBundle:ChangeThis:index" }

example_grid:
    pattern:  /grid
    defaults: { _controller: "MESDAppChangeThisBundle:ChangeThis:grid" }

example_data:
    pattern: /data.json
    defaults: { _controller: "MESDAppChangeThisBundle:ChangeThis:data" }

example_export:
    pattern: /export.{exportType}
    defaults: { _controller: "MESDAppChangeThisBundle:ChangeThis:data" }
```

src/MESD/App/ChangeThisBundle/ChangeThisController.php
```php
<?php

namespace MESD\App\ChangeThisBundle\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Symfony\Component\HttpFoundation\Request;

class ChangeThisController extends Controller
{
    public function indexAction()
    {
        return $this->render('MESDAppChangeThisBundle:Grid:index.html.twig');
    }

    public function gridAction()
    {
        return $this->render('MESDAngGridBundle:Grid:grid.html.twig', array('ngController' => 'GridController'));
    }

    public function dataAction(Request $request, $exportType = null)
    {
        $em = $this->getDoctrine()->getManager();
        $qb = $em->getRepository('MESDAppChangeThisBundle:Example')
            ->createQueryBuilder('example');
        $qb->leftJoin('example.another', 'another');

        $gm = $this->get('anggrid.gridmanager');

        $gm->setQueryBuilder($qb);
        $gm->setRoot('rate', 'MESD\App\ChangeThisBundle\Entity\Example');
        $gm->setSelect('another');

        $gm->setExportType($exportType);
        $gm->setExportAlias('example_export');

        $gm->setAction( array(
                'alias'   => 'example_show',
                'icon'  => 'icon-search',
                'title' => 'Show',
            )
        );

        $gm->setAction( array(
                'alias'   => 'example_edit'
                , 'icon'  => 'icon-pencil'
                , 'title' => 'Edit'
            )
        );

        $gm->setAction( array(
                'alias' => 'another_show',
                'icon' => 'icon-file',
                'title' => 'Show Another',
                'function' => function( $result, $router ) {
                    if ( get_class( $result ) == 'MESD\App\ChangeThisBundle\Entity\Another' ) {
                        return array(
                            'id' => $result->getExample()->getId(),
                            'path' => $router->generate( 'another_show', array( 'id' => $result->getId() ) ),
                        );
                    }
                    return array( 'path' => '' );
                },
            )
        );

        $gm->setHeader( array(
                'field'   => 'example.shortName'
                , 'title' => 'Short Name'
            )
        );

        $gm->setHeader( array(
                'field'   => 'example.longName'
                , 'title' => 'Long Name'
            )
        );

        $gm->setHeader( array(
                'field'   => 'example.another.shortName'
                , 'title' => 'Another'
            )
        );

        $gm->setHeader( array(
                'field'      => 'example.effective'
                , 'title'    => 'Effective Date'
                , 'function' => function( $result ) {
                    return array( 'value' => $result ? $result->getDate()->format( 'm/d/Y' ) : '' );
                }
            )
        );

        return $gm->getJsonResponse();
    }
}
```
