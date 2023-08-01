<?php

namespace Drupal\charts\Plugin\views\field;

use Drupal\charts\Element\BaseSettings;
use Drupal\charts\Plugin\views\style\ChartsPluginStyleChart;
use Drupal\views\Plugin\views\field\FieldPluginBase;
use Drupal\Core\Form\FormStateInterface;

/**
 * Provides a Views handler that exposes a Chart Type field.
 *
 * @ingroup views_field_handlers
 *
 * @ViewsField("field_exposed_chart_type")
 */
class ExposedChartType extends FieldPluginBase {

  /**
   * {@inheritdoc}
   */
  public function canExpose() {
    return TRUE;
  }

  /**
   * {@inheritdoc}
   */
  public function isExposed() {
    return TRUE;
  }

  /**
   * {@inheritdoc}
   */
  public function buildExposedForm(&$form, FormStateInterface $form_state) {
    $label = $this->options['label'] ? $this->options['label'] : 'Chart Type';
    $selected_options = $this->options['chart_types'];
    $all_types = BaseSettings::getChartTypes();
    $options = array_filter($all_types, function ($key) use ($selected_options) {
      return in_array($key, $selected_options, TRUE);
    }, ARRAY_FILTER_USE_KEY);

    $style_plugin = $this->view->style_plugin;
    $settings = $style_plugin->options['chart_settings'] ?? [];
    $chart_plugin_selected_type = $settings['type'] ?? '';
    if ($chart_plugin_selected_type) {
      // Move the selected.
      if (isset($options[$chart_plugin_selected_type])) {
        $options = [
          $chart_plugin_selected_type => $options[$chart_plugin_selected_type],
        ] + $options;
      }
      else {
        $options = [
          $chart_plugin_selected_type => $all_types[$chart_plugin_selected_type],
        ] + $options;
      }
    }

    $form['ct'] = [
      '#title' => $this->t('@value', ['@value' => $label]),
      '#type' => $this->options['exposed_select_type'],
      '#options' => $options,
      '#weight' => -20,
    ];

    if ($this->options['exposed_select_type'] == 'radios') {
      $form['ct']['#attributes']['class'] = [
        'chart-type-radios',
        'container-inline',
      ];
    }

    $form['ect'] = [
      '#type' => 'hidden',
      '#default_value' => 1,
    ];
  }

  /**
   * {@inheritdoc}
   */
  protected function defineOptions() {
    $options = parent::defineOptions();

    $options['chart_types'] = ['default' => []];
    $options['exposed_select_type'] = ['default' => 'checkboxes'];

    return $options;
  }

  /**
   * {@inheritdoc}
   */
  public function buildOptionsForm(&$form, FormStateInterface $form_state) {
    parent::buildOptionsForm($form, $form_state);

    $form['chart_types'] = [
      '#type' => 'checkboxes',
      '#title' => $this->t('Chart Type Options'),
      '#description' => $this->t('Pick the chart type options to be exposed. You may need to disable your Views cache.'),
      '#options' => BaseSettings::getChartTypes(),
      '#default_value' => $this->options['chart_types'],
    ];

    $style_plugin = $this->view->style_plugin;
    $settings = $style_plugin->options['chart_settings'] ?? [];
    if (!empty($settings['type'])) {
      $form['chart_types'][$settings['type']] = [
        '#default_value' => $settings['type'],
        '#disabled' => TRUE,
      ];
    }

    $form['exposed_select_type'] = [
      '#type' => 'radios',
      '#title' => $this->t('Exposed Selection Type'),
      '#description' => $this->t('Choose your options widget.'),
      '#options' => [
        'radios' => $this->t('Radios'),
        'select' => $this->t('Single select'),
      ],
      '#default_value' => $this->options['exposed_select_type'],
    ];
  }

  /**
   * {@inheritdoc}
   */
  public function validateOptionsForm(&$form, FormStateInterface $form_state) {
    parent::validateOptionsForm($form, $form_state);

    $style_plugin = $this->view->style_plugin;
    if (!($style_plugin instanceof ChartsPluginStyleChart)) {
      $form_state->setError($form['chart_types'], $this->t('You can only use this field type when the selected views style is chart!'));
    }
  }

  /**
   * {@inheritdoc}
   */
  public function query() {
    // This is not a real field and it does not affect the query. But Views
    // won't render if the query() method is not present. This doesn't do
    // anything, but it has to be here. This function is a void so it doesn't
    // return anything.
  }

}
