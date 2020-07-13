<?php

namespace Drupal\conference_api\Form;

use Drupal\Core\Form\ConfigFormBase;
use Drupal\Core\Form\FormStateInterface;

/**
 * Config form.
 */
class ConfigForm extends ConfigFormBase {

  /**
   * {@inheritdoc}
   */
  protected function getEditableConfigNames() {
    return [
      'conference_api.settings',
    ];
  }

  /**
   * {@inheritdoc}
   */
  public function getFormId() {
    return 'conference_api_settings_form';
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state) {
    $config = $this->config('conference_api.settings');

    $form['content_types'] = [
      '#title' => $this->t('Content types'),
      '#type' => 'fieldset',
      '#tree' => TRUE,
    ];

    /** @var \Drupal\node\Entity\NodeType[] $types */
    $types = \Drupal::entityTypeManager()
      ->getStorage('node_type')
      ->loadMultiple();
    $options = [];
    foreach ($types as $name => $type) {
      $options[$name] = $type->label();
    }

    foreach ([
      'conference' => [
        'title' => $this->t('Conference'),
      ],
      'event' => [
        'title' => $this->t('Event'),
      ],
      'speaker' => [
        'title' => $this->t('Speaker'),
      ],
      'organizer' => [
        'title' => $this->t('Organizer'),
      ],
      'tag' => [
        'title' => $this->t('Tag'),
      ],
      'theme' => [
        'title' => $this->t('Theme'),
      ],
      'location' => [
        'title' => $this->t('Location'),
      ],
      'sponsor' => [
        'title' => $this->t('Sponsor'),
      ],

    ] as $type => $info) {
      $form['content_types'][$type] = [
        '#title' => $info['title'],
        '#type' => 'select',
        '#options' => $options,
        '#default_value' => $config->get('content_types.' . $type) ?? NULL,
        '#empty_option' => $this->t('Select content type'),
      // '#required' => TRUE,
        '#description' => $info['description'] ?? NULL,
      ];
    }

    return parent::buildForm($form, $form_state);
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
    $this->config('conference_api.settings')
      ->set('content_types', $form_state->getValue('content_types'))
      ->save();
  }

}
