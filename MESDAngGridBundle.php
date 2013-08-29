<?php

namespace MESD\Ang\GridBundle;

use Symfony\Component\HttpKernel\Bundle\Bundle;

class MESDAngGridBundle extends Bundle
{
    /*  !!!NOTE!!!
     * to get these to register, add the following snippet to the end of the AppKernel register bundles function
     */

    // $registeredBundles = [];
    // foreach($bundles as $bundle) {
    //     $registeredBundles[] = get_class($bundle);
    // }
    // foreach($bundles as $bundle) {
    //     if (method_exists($bundle, 'registerDependentBundles')) {
    //         $dependentBundles = $bundle->registerDependentBundles();
    //         foreach($dependentBundles as $dependentBundle) {
    //             if (!in_array(get_class($dependentBundle), $registeredBundles)) {
    //                 $bundles[] = $dependentBundle;
    //                 $registeredBundles[] = get_class($dependentBundle);
    //             }
    //         }
    //     }
    // }

    public function registerDependentBundles() {
        $dependentBundles = array(
            new \Knp\Bundle\SnappyBundle\KnpSnappyBundle()
        );

        return $dependentBundles;
    }
}
