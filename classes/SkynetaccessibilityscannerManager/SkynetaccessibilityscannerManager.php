<?php

namespace Grav\Plugin\Skynetaccessibilityscanner\Classes\SkynetaccessibilityscannerManager;

use Grav\Common\Grav;
use Grav\Common\Utils;
use Grav\Common\Data\Data;
use Grav\Common\Data\Blueprints;
use Grav\Common\File\CompiledYamlFile;

/**
 * Skynetaccessibility scanner Plugin
 *
 */
class SkynetaccessibilityscannerManager extends Data {

    /**
     * Get the Skynetaccessibility Scanner data list from user/data/ yaml files
     *
     * @return array
     */
    public static function getSkynetaccessibilityscannerManagerData() {

        $scannerData = self::getYamlDataObjType(self::getCurrentSkynetaccessibilityscannerManagerPath());

        return $scannerData;
    }

    /**
     * Get the skynetaccessibilityscanner manager twig vars
     *
     * @return array
     */
    public static function getSkynetaccessibilityscannerManagerDataTwigVars() {

        $vars = [];

        $blueprints = self::getCurrentSkynetaccessibilityscannerManagerBlueprint();
        $content = self::getSkynetaccessibilityscannerManagerData();

        $scannerData  = new Data($content, $blueprints);

        $vars['scannerData'] = $scannerData;

        return $vars;
    }

    /**
     * get current skynetaccessibilityscanner manager blueprint
     *
     * @return \Grav\Common\Data\Blueprint
     */
    public static function getCurrentSkynetaccessibilityscannerManagerBlueprint() {

        $blueprints = new Blueprints;
        $currentSkynetaccessibilityscannerManagerBlueprint = $blueprints->get(self::getCurrentSkynetaccessibilityscannerManagerPath());

        return $currentSkynetaccessibilityscannerManagerBlueprint;
    }

    /**
     * get current path of skynetaccessibilityscanner manager for config info
     *
     * @return string
     */
    public static function getCurrentSkynetaccessibilityscannerManagerPath() {

        $uri = Grav::instance()['uri'];
        $currentSkynetaccessibilityscannerManagerPath = 'skynetaccessibilityscanner-manager';

        if(isset($uri->paths()[1])){
            $currentSkynetaccessibilityscannerManagerPath = $uri->paths()[1];
        }

        return $currentSkynetaccessibilityscannerManagerPath;
    }

    /**
     * get data object of given type
     *
     * @return array
     */
    public static function getYamlDataObjType($type) {

        //location of yaml files
        $dataStorage = 'user://data';

        return CompiledYamlFile::instance(Grav::instance()['locator']->findResource($dataStorage) . DS . $type . ".yaml")->content();
    }



}
