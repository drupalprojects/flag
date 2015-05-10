<?php
/**
 * @file
 * Contains the \Drupal\flag\Controller\FieldEntryFormController class.
 */

namespace Drupal\flag\Controller;

use Drupal\Core\Controller\ControllerBase;
use Drupal\flag\FlagInterface;
use Drupal\flag\FlaggingInterface;
use Drupal\flag\Entity\Flag;

/**
 * Provides a controller for the Field Entry link type.
 */
class FieldEntryFormController extends ControllerBase {

  /**
   * Performs a flagging when called via a route.
   *
   * @param \Drupal\flag\FlagInterface $flag
   *   The flag entity.
   * @param int $entity_id
   *   The flaggable ID.
   *
   * @return AjaxResponse
   *   The response object.
   *
   * @see \Drupal\flag\Plugin\ActionLink\AJAXactionLink
   */
  public function flag(FlagInterface $flag, $entity_id) {
    $flag_id = $flag->id();

    $account = $this->currentUser();

    $flagging = $this->entityManager()->getStorage('flagging')->create([
      'fid' => $flag->id(),
      'entity_type' => $flag->getFlaggableEntityTypeId(),
      'entity_id' => $entity_id,
      'type' => $flag->id(),
      'uid' => $account->id(),
    ]);

    return $this->getForm($flagging, 'add');
  }

  /**
   * Return the flagging edit form.
   *
   * @param \Drupal\flag\FlagInterface $flag
   *   The flag entity.
   * @param mixed $entity_id
   *   The entity ID.
   *
   * @return array
   *   The processed edit form for the given flagging.
   */
  public function edit(FlagInterface $flag, $entity_id) {
    $flag_service = \Drupal::service('flag');
    $entity = $flag_service->getFlaggableById($flag, $entity_id);
    $flagging = \Drupal::service('flag')->getFlagging($flag, $entity);
    return $this->getForm($flagging, 'edit');
  }

  /**
   * Performs an unflagging when called via a route.
   *
   * @param \Drupal\flag\FlagInterface $flag
   *   The flag entity.
   * @param int $entity_id
   *   The entity ID to unflag.
   *
   * @return array
   *   The processed delete form for the given flagging.
   *
   * @see \Drupal\flag\Plugin\ActionLink\AJAXactionLink
   */
  public function unflag(FlagInterface $flag, $entity_id) {
    $flag_service = \Drupal::service('flag');
    $entity = $flag_service->getFlaggableById($flag, $entity_id);
    $flagging = \Drupal::service('flag')->getFlagging($flag, $entity);
    return $this->getForm($flagging, 'delete');
  }

  /**
   * Title callback when creating a new flagging.
   *
   * @param \Drupal\flag\FlagInterface $flag
   *   The flag entity.
   * @param int $entity_id
   *   The entity ID to unflag.
   *
   * @return string
   *   The flag field entry form title.
   */
  public function flagTitle(FlagInterface $flag, $entity_id) {
    $link_type = $flag->getLinkTypePlugin();
    return $link_type->getFlagQuestion();
  }

  /**
   * Title callback when editing an existing flagging.
   *
   * @param \Drupal\flag\FlagInterface $flag
   *   The flag entity.
   * @param int $entity_id
   *   The entity ID to unflag.
   *
   * @return string
   *   The flag field entry form title.
   */
  public function editTitle(FlagInterface $flag, $entity_id) {
    $link_type = $flag->getLinkTypePlugin();
    return $link_type->getEditFlaggingTitle();
  }

  /**
   * Get the flag's field entry form.
   *
   * @param FlaggingInterface $flagging
   *   The flagging from which to get the form.
   * @param string|null $operation
   *   (optional) The operation identifying the form variant to return.
   *   If no operation is specified then 'default' is used.
   *
   * @return array
   *   The processed form for the given flagging and operation.
   */
  protected function getForm(FlaggingInterface $flagging, $operation = 'default') {
    return $this->entityFormBuilder()->getForm($flagging, $operation);
  }

}
