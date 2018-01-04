<?php
/**
 * Created by PhpStorm.
 * User: usman
 * Date: 1/1/18
 * Time: 12:30 AM
 */

namespace Drupal\lite_speed_cache\Controller;


use Drupal\Core\Controller\ControllerBase;

class LSCacheController extends ControllerBase {

    /**
     * Display the markup.
     *
     * @return array
     */
    public function content() {
        return array(
            '#type' => 'markup',
            '#markup' => $this->t('Hello, LSCache!'),
        );
    }

}