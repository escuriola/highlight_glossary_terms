<?php

namespace Drupal\highlight_glossary_terms\Form;

use Drupal\Core\Form\ConfigFormBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\taxonomy\Entity\Vocabulary;

/**
 * Provides Highlight Glossary Terms Settings Form.
 */
class HighlightGlossaryTermsSettingsForm extends ConfigFormBase {

  /**
   * {@inheritdoc}
   */
  protected function getEditableConfigNames() {
    return [
      'highlight_glossary_terms.settings',
    ];
  }

  /**
   * {@inheritdoc}
   */
  public function getFormId() {
    return 'highlight_glossary_terms_settings_form';
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state) {
    $config = $this->config('highlight_glossary_terms.settings');

    $form['highlight_glossary_terms'] = [
      '#type' => 'fieldset',
      '#title' => $this->t('Highlight Glossary Terms settings'),
      '#collapsible' => FALSE,
    ];

    $form['highlight_glossary_terms']['vocabulary'] = [
      '#type' => 'select',
      '#title' => $this->t('Select a vocabulary'),
      '#options' => $this->getVocabularyList(),
      '#default_value' => $config->get('vocabulary') ?: '',
      '#required' => TRUE,
      '#description' => $this->t('Selects the vocabulary whose terms will be highlighted when the content is displayed.'),
    ];
    return parent::buildForm($form, $form_state);
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
    parent::submitForm($form, $form_state);

    $this->config('highlight_glossary_terms.settings')
      ->set('vocabulary', $form_state->getValue('vocabulary'))
      ->save();
  }

  /**
   * @return array
   */
  private function getVocabularyList(): array {
    $vocabularyList = [];
    $vocabularies = Vocabulary::loadMultiple();

    foreach ($vocabularies as $vocabulary) {
      $vocabularyList[$vocabulary->id()] = $vocabulary->label();
    }

    return $vocabularyList;
  }

}
