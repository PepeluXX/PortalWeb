<?php
/**
 * @file
 * Contains \Drupal\article\Plugin\Block\ContactBlock.
 */
namespace Drupal\article\Plugin\Block;
use Drupal\Core\Block\BlockBase;
use Drupal\Core\Form\FormStateInterface;  
/**
 * Provides a 'contact' block.
 *
 * @Block(
 *   id = "contact_block",
 *   admin_label = @Translation("Contact Us"),
 *   category = @Translation("Custom contact us block")
 * )
 */
class ContactBlock extends BlockBase {
  /**
   * {@inheritdoc}
   */
  public function blockForm($form, FormStateInterface $form_state) {
    $form = parent::blockForm($form, $form_state);
    // Retrieve existing configuration for this block.
    $config = $this->getConfiguration();
    // Add a form field to the existing block configuration form.
    $form['org_name'] = array(
      '#type' => 'textfield',
      '#title' => t('Organization:'),
      '#default_value' => isset($config['org_name'])? $config['org_name'] : '',
    );
    $form['org_loca'] = array(
      '#type' => 'textfield',
      '#title' => t('Location:'),
      '#default_value' => isset($config['org_loca'])? $config['org_loca'] : '',
    );
    $form['org_mail'] = array(
      '#type' => 'email',
      '#title'=> t('Email ID:'),
      '#default_value' => isset($config['org_mail'])? $config['org_mail'] : '',
    );
    $form['org_phn'] = array(
      '#type' => 'number',
      '#title'=> t('Contact:'),
      '#default_value' => isset($config['org_phn'])? $config['org_phn'] : '',
    );
    $form['org_add'] = array(
      '#type' => 'textfield',
      '#title'=> t('Address:'),
      '#default_value' => isset($config['org_add'])? $config['org_add'] : '',
    );
    return $form;
  }
  /**
   * {@inheritdoc}
   */
  public function blockSubmit($form, FormStateInterface $form_state) {
    // Save our custom settings when the form is submitted.
    $this->setConfigurationValue('org_name', $form_state->getValue('org_name'));
    $this->setConfigurationValue('org_loca', $form_state->getValue('org_loca'));
    $this->setConfigurationValue('org_mail', $form_state->getValue('org_mail'));
    $this->setConfigurationValue('org_phn', $form_state->getValue('org_phn'));
    $this->setConfigurationValue('org_add', $form_state->getValue('org_add'));
  }
  /**
   * {@inheritdoc}
   */
  public function build() {
    $config = $this->getConfiguration();
    $org_name = isset($config['org_name']) ? $config['org_name'] : '';
    $org_loca = isset($config['org_loca']) ? $config['org_loca'] : '';
    $org_mail = isset($config['org_mail']) ? $config['org_mail'] : '';
    $org_phn = isset($config['org_phn']) ? $config['org_phn'] : '';
    $org_add = isset($config['org_add']) ? $config['org_add'] : '';
    return array(
      '#markup' => $this->t('@org, @loc. Email id : @mail Phn: @phn. Address: @add', array('@add'=> $org_add,'@phn'=> $org_phn,'@mail'=> $org_mail,'@org'=> $org_name,'@loc' => $org_loca)),
    );
  }
  /**
   * {@inheritdoc}
   */
  public function blockValidate($form, FormStateInterface $form_state) {
    $org_name = $form_state->getValue('org_name');
    if (is_numeric($org_name)) {
      drupal_set_message('needs to be an integer', 'error');
      $form_state->setErrorByName('org_name', t('Organization name should not be numeric'));
    }
  }
}
