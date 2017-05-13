<?php

namespace Drupal\og\Form;

use Drupal\Core\Form\FormStateInterface;
use Drupal\og\Entity\OgRole;
use Drupal\og\OgRoleInterface;

/**
 * Provides a form to remove multiple OG roles from a membership.
 *
 * @see \Drupal\og\Plugin\Action\RemoveMultipleOgMembershipRoles
 */
class OgRemoveMultipleRolesForm extends OgChangeMultipleRolesFormBase {

  /**
   * {@inheritdoc}
   */
  protected $pluginId = 'og_membership_remove_multiple_roles_action';

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state) {
    $options = [];
    foreach ($this->getMemberships() as $membership) {
      foreach ($membership->getRoles() as $role) {
        // Only add the role to the list if it is not a default role, these
        // cannot be removed.
        if (!$role->isDefaultRole()) {
          $options[$role->id()] = $role->label();
        }
      }
    }

    $form['roles'] = [
      '#type' => 'select',
      '#title' => t('Remove roles'),
      '#multiple' => TRUE,
      '#required' => TRUE,
      '#options' => $options,
    ];

    return parent::buildForm($form, $form_state);
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
    $role_ids = array_keys($form_state->getValue('roles'));
    /** @var OgRoleInterface[] $roles */
    $roles = OgRole::loadMultiple($role_ids);
    foreach ($this->getMemberships() as $membership) {
      $changed = FALSE;
      foreach ($roles as $role) {
        $group = $membership->getGroup();
        if ($group->getEntityTypeId() === $role->getGroupType() && $group->bundle() === $role->getGroupBundle()) {
          if ($membership->hasRole($role->id())) {
            $changed = TRUE;
            $membership->revokeRole($role);
          }
        }
      }
      // Only save the membership if it has actually changed.
      if ($changed) {
        $membership->save();
      }
    }
  }

}
