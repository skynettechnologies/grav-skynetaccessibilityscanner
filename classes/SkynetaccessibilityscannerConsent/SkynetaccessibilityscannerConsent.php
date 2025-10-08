<?php

namespace Grav\Plugin\Skynetaccessibilityscanner\Classes\SkynetaccessibilityscannerConsent;

use Grav\Common\Grav;
use Grav\Common\Utils;
use Grav\Common\Data\Data;
use Grav\Common\File\CompiledYamlFile;


/**
 * Skynetaccessibility scanner Plugin Class
 *
 */
class SkynetaccessibilityscannerConsent extends Data {

    /**
     * get data object of given type
     *
     * @return object
     */
    public static function getYamlDataByType($type) {

        //location of yaml files
        $dataStorage = 'user://data';

        return CompiledYamlFile::instance(Grav::instance()['locator']->findResource($dataStorage) . DS . $type . ".yaml")->content();
    }

}
