<?php
/**
 * Created by PhpStorm.
 * @author     	LiteSpeed Technologies <info@litespeedtech.com>
 * Date: 1/1/18
 * Time: 9:41 PM
 */

namespace Drupal\lite_speed_cache\Form;

use Drupal\Core\Form\ConfigFormBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Messenger\MessengerInterface;


class LSCacheForm extends ConfigFormBase
{
    /**
     * Purge all status variable
     */
    public static $purgeALL;

    /**
     * Purge this site status variable
     */
    public static $purgeThisSite;

    /**
    * crawlear this site variable
    */
    public static $crawlerTheSite;

    /**
     * {@inheritdoc}
     */
    public function getFormId() {
        return 'lite_speed_cache_form';
    }

    /**
     * {@inheritdoc}
     */
    protected function getEditableConfigNames() {
        return [
            'lite_speed_cache.settings','system.performance',
        ];
    }

    /**
     * {@inheritdoc}
     */
    public function buildForm(array $form, FormStateInterface $form_state) {
        // Form constructor.
        $form = parent::buildForm($form, $form_state);
        // Default settings.
        $config = $this->config('lite_speed_cache.settings');
        // esi_on field.


        $form['clear_cache'] = [
            '#type' => 'details',
            '#title' => t('Clear cache!'),
            '#open' => TRUE,
        ];

        $form['clear_cache']['clear_this'] = [
            '#type' => 'submit',
            '#value' => t('Clear this site'),
            '#submit' => ['::submitThisCache'],
        ];

        $form['clear_cache']['clear_all'] = [
            '#type' => 'submit',
            '#value' => t('Clear all'),
            '#submit' => ['::submitAllCache'],
        ];

        $form['cache_settings'] = [
            '#type' => 'details',
            '#title' => t('LSCache Settings!'),
            '#open' => TRUE,
        ];

        $options = ['On','Off'];

        $form['cache_settings']['cache_status'] = array(
            '#type' => 'select',
            '#title' => $this->t('Cache Status'),
            '#options' => $options,
            '#default_value' => $config->get('lite_speed_cache.cache_status'),
            '#description' => $this->t('Disable or enable LiteSpeed Cache completely!'),
        );

        $options = ['On','Off'];

        $form['cache_settings']['debug'] = array(
            '#type' => 'select',
            '#title' => $this->t('Debug'),
            '#options' => $options,
            '#default_value' => $config->get('lite_speed_cache.debug'),
            '#description' => $this->t('Weather to send or not the debug headers!'),
        );

        $options = ['On','Off'];

        // max_age field.
        $form['cache_settings']['max_age'] = array(
            '#type' => 'textfield',
            '#title' => $this->t('Public Cache TTL'),
            '#default_value' => $config->get('lite_speed_cache.max_age'),
            '#description' => $this->t('Amount of time for which page should be cached by LiteSpeed Webserver public cache (Seconds).'),
        );

        return $form;
    }


    /**
     * {@inheritdoc}
     */
    public function validateForm(array &$form, FormStateInterface $form_state) {

    }

    /**
     * {@inheritdoc}
     */
    public function submitForm(array &$form, FormStateInterface $form_state) {
        $cacheStatus = $form_state->getValue('cache_status');

        $config = $this->config('lite_speed_cache.settings');
        //$config->set('lite_speed_cache.esi_on', $form_state->getValue('esi_on'));
        $config->set('lite_speed_cache.max_age', $form_state->getValue('max_age'));
        //$config->set('lite_speed_cache.max_age_private', $form_state->getValue('max_age_private'));
        $config->set('lite_speed_cache.cache_status', $form_state->getValue('cache_status'));
        $config->set('lite_speed_cache.debug', $form_state->getValue('debug'));
        $config->save();

        // Prevent gzip cause broken website layout
        $config = $this->config('system.performance');
        $config->set('css.preprocess', '0');
        $config->set('js.preprocess', '0');
        $config->save();
        return parent::submitForm($form, $form_state);
    }

    /**
     * Clears All caches.
     */
    public function submitAllCache(array &$form, FormStateInterface $form_state) {
        LSCacheForm::$purgeALL = 1;
        \Drupal::messenger()->addMessage(t('Instructed LiteSpeed Web Server to clear all cache!'));
    }

    /**
     * Clears this caches.
     */
    public function submitThisCache(array &$form, FormStateInterface $form_state) {
        LSCacheForm::$purgeThisSite = 1;
        \Drupal::messenger()->addMessage(t('Instructed LiteSpeed Web Server to clear this site cache!'));
    }

}
