<?php

/**
 * @file
 * Contains \Drupal\commerce_line_item\Entity\LineItem.
 */

namespace Drupal\commerce_line_item\Entity;

use Drupal\commerce_line_item\LineItemInterface;
use Drupal\Core\Entity\EntityStorageInterface;
use Drupal\Core\Field\BaseFieldDefinition;
use Drupal\Core\Entity\ContentEntityBase;
use Drupal\Core\Entity\EntityTypeInterface;
use Drupal\user\UserInterface;

/**
 * Defines the Commerce Line item entity.
 *
 * @ContentEntityType(
 *   id = "commerce_line_item",
 *   label = @Translation("Line Item"),
 *   handlers = {
 *     "list_builder" = "Drupal\commerce_line_item\LineItemListBuilder",
 *     "views_data" = "Drupal\views\EntityViewsData",
 *     "form" = {
 *       "add" = "Drupal\commerce_line_item\Form\LineItemForm",
 *       "edit" = "Drupal\commerce_line_item\Form\LineItemForm",
 *       "delete" = "Drupal\Core\Entity\ContentEntityDeleteForm"
 *     },
 *     "inline entity form" = "Drupal\commerce_line_item\Form\LineItemInlineEntityFormHandler",
 *   },
 *   base_table = "commerce_line_item",
 *   admin_permission = "administer line items",
 *   fieldable = TRUE,
 *   entity_keys = {
 *     "id" = "line_item_id",
 *     "uuid" = "uuid",
 *     "revision" = "revision_id",
 *     "bundle" = "type"
 *   },
 *   links = {
 *     "canonical" = "/admin/commerce/config/line-item/{commerce_line_item}",
 *     "edit-form" = "/admin/commerce/config/line-item/{commerce_line_item}/edit",
 *     "delete-form" = "/admin/commerce/config/line-item/{commerce_line_item}/delete",
 *     "collection" = "/admin/commerce/config/line-item"
 *   },
 *   bundle_entity_type = "commerce_line_item_type",
 *   field_ui_base_route = "entity.commerce_line_item_type.edit_form",
 * )
 */
class LineItem extends ContentEntityBase implements LineItemInterface {

  /**
   * {@inheritdoc}
   */
  public static function preCreate(EntityStorageInterface $storageController, array &$values) {
    parent::preCreate($storageController, $values);
    $values += array(
      'uid' => \Drupal::currentUser()->id(),
    );
  }

  /**
   * {@inheritdoc}
   */
  public function preSave(EntityStorageInterface $storage) {
    parent::preSave($storage);

    // If no owner has been set explicitly, make the current user the owner.
    if (!$this->getOwner()) {
      $this->setOwnerId(\Drupal::currentUser()->id());
    }

    // If no revision author has been set explicitly, make the line item owner the
    // revision author.
    if (!$this->getRevisionAuthor()) {
      $this->setRevisionAuthorId($this->getOwnerId());
    }
  }

  /**
   * {@inheritdoc}
   */
  public function preSaveRevision(EntityStorageInterface $storage, \stdClass $record) {
    parent::preSaveRevision($storage, $record);

    if (!$this->isNewRevision() && isset($this->original) && (!isset($record->revision_log) || $record->revision_log === '')) {
      // If we are updating an existing line item without adding a new revision, we
      // need to make sure $entity->revision_log is reset whenever it is empty.
      // Therefore, this code allows us to avoid clobbering an existing log
      // entry with an empty one.
      $record->revision_log = $this->original->revision_log->value;
    }
  }

  /**
   * {@inheritdoc}
   */
  public function getType() {
    return $this->bundle();
  }

  /**
   * {@inheritdoc}
   */
  public function getStatus() {
    return $this->get('status')->value;
  }

  /**
   * {@inheritdoc}
   */
  public function setStatus($status) {
    $this->set('status', $status);
    return $this;
  }

  /**
   * {@inheritdoc}
   */
  public function getCreatedTime() {
    return $this->get('created')->value;
  }

  /**
   * {@inheritdoc}
   */
  public function setCreatedTime($timestamp) {
    $this->set('created', $timestamp);
    return $this;
  }

  /**
   * {@inheritdoc}
   */
  public function getChangedTime() {
    return $this->get('changed')->value;
  }

  /**
   * {@inheritdoc}
   */
  public function getOwner() {
    return $this->get('uid')->entity;
  }

  /**
   * {@inheritdoc}
   */
  public function getOwnerId() {
    return $this->get('uid')->target_id;
  }

  /**
   * {@inheritdoc}
   */
  public function setOwnerId($uid) {
    $this->set('uid', $uid);
    return $this;
  }

  /**
   * {@inheritdoc}
   */
  public function setOwner(UserInterface $account) {
    $this->set('uid', $account->id());
    return $this;
  }

  /**
   * {@inheritdoc}
   */
  public function getRevisionCreationTime() {
    return $this->get('revision_timestamp')->value;
  }

  /**
   * {@inheritdoc}
   */
  public function setRevisionCreationTime($timestamp) {
    $this->set('revision_timestamp', $timestamp);
    return $this;
  }

  /**
   * {@inheritdoc}
   */
  public function getRevisionAuthor() {
    return $this->get('revision_uid')->entity;
  }

  /**
   * {@inheritdoc}
   */
  public function setRevisionAuthorId($uid) {
    $this->set('revision_uid', $uid);
    return $this;
  }

  /**
   * {@inheritdoc}
   */
  public function getData() {
    return $this->get('data')->first()->getValue();
  }

  /**
   * {@inheritdoc}
   */
  public function setData($data) {
    $this->set('data', array($data));
    return $this;
  }

  /**
   * {@inheritdoc}
   */
  public static function baseFieldDefinitions(EntityTypeInterface $entityType) {
    $fields['line_item_id'] = BaseFieldDefinition::create('integer')
      ->setLabel(t('Line Item ID'))
      ->setDescription(t('The line item ID.'))
      ->setReadOnly(TRUE)
      ->setSetting('unsigned', TRUE);

    $fields['uuid'] = BaseFieldDefinition::create('uuid')
      ->setLabel(t('UUID'))
      ->setDescription(t('The line item UUID.'))
      ->setReadOnly(TRUE);

    $fields['revision_id'] = BaseFieldDefinition::create('integer')
      ->setLabel(t('Revision ID'))
      ->setDescription(t('The line item revision ID.'))
      ->setReadOnly(TRUE)
      ->setSetting('unsigned', TRUE);

    $fields['type'] = BaseFieldDefinition::create('entity_reference')
      ->setLabel(t('Type'))
      ->setDescription(t('The line item type.'))
      ->setSetting('target_type', 'commerce_line_item_type')
      ->setReadOnly(TRUE);

    $fields['uid'] = BaseFieldDefinition::create('entity_reference')
      ->setLabel(t('Owner'))
      ->setDescription(t('The user that owns this line item.'))
      ->setRevisionable(TRUE)
      ->setSetting('target_type', 'user')
      ->setSetting('handler', 'default')
      ->setDefaultValueCallback('Drupal\commerce_line_item\Entity\CommerceLineItem::getCurrentUserId')
      ->setTranslatable(TRUE)
      ->setDisplayOptions('view', array(
        'label' => 'hidden',
        'type' => 'author',
      ))
      ->setDisplayConfigurable('form', TRUE);

    $fields['status'] = BaseFieldDefinition::create('string')
      ->setLabel(t('Status'))
      ->setDescription(t('The status name of this line item.'))
      ->setRequired(TRUE)
      ->setRevisionable(TRUE)
      ->setDefaultValue('')
      ->setSetting('max_length', 255)
      ->setDisplayOptions('view', array(
        'label' => 'hidden',
        'type' => 'string',
        'weight' => 0,
      ))
      ->setDisplayOptions('form', array(
        'type' => 'hidden',
        'weight' => -1,
      ))
      ->setDisplayConfigurable('form', TRUE);

    $fields['created'] = BaseFieldDefinition::create('created')
      ->setLabel(t('Created'))
      ->setDescription(t('The time that the line item was created.'))
      ->setRequired(TRUE)
      ->setRevisionable(TRUE)
      ->setTranslatable(TRUE)
      ->setDisplayOptions('view', array(
        'label' => 'hidden',
        'type' => 'timestamp',
        'weight' => 0,
      ))
      ->setDisplayOptions('form', array(
        'type' => 'datetime_timestamp',
        'weight' => 10,
      ))
      ->setDisplayConfigurable('form', TRUE);

    $fields['changed'] = BaseFieldDefinition::create('changed')
      ->setLabel(t('Changed'))
      ->setDescription(t('The time that the line item was last edited.'))
      ->setRequired(TRUE)
      ->setRevisionable(TRUE);

    $fields['quantity'] = BaseFieldDefinition::create('decimal')
      ->setLabel(t('Quantity'))
      ->setDescription(t('The quantity of units.'))
      ->setReadOnly(TRUE)
      ->setSetting('unsigned', TRUE)
      ->setRevisionable(TRUE)
      ->setDisplayOptions('form', array(
        'type' => 'string',
        'weight' => 1,
      ))
      ->setDisplayConfigurable('form', TRUE)
      ->setDisplayConfigurable('view', TRUE);

    $fields['unit_price'] = BaseFieldDefinition::create('price')
      ->setLabel(t('Unit Price'))
      ->setDescription(t('Unit Price of the Line Item'))
      ->setRequired(TRUE)
      ->setRevisionable(TRUE)
      ->setDisplayOptions('form', array(
        'type' => 'price_simple',
        'weight' => 2,
      ))
      ->setDisplayConfigurable('form', TRUE)
      ->setDisplayConfigurable('view', TRUE);

    $fields['line_total'] = BaseFieldDefinition::create('price')
      ->setLabel(t('Line Item Total Price'))
      ->setDescription(t('The total price of the line item.'))
      ->setReadOnly(TRUE)
      ->setDisplayConfigurable('form', FALSE)
      ->setDisplayConfigurable('view', TRUE);

    $fields['data'] = BaseFieldDefinition::create('map')
      ->setLabel(t('Data'))
      ->setDescription(t('A serialized array of additional data.'));

    $fields['revision_timestamp'] = BaseFieldDefinition::create('created')
      ->setLabel(t('Revision timestamp'))
      ->setDescription(t('The time that the current revision was created.'))
      ->setQueryable(FALSE)
      ->setRevisionable(TRUE);

    $fields['revision_uid'] = BaseFieldDefinition::create('entity_reference')
      ->setLabel(t('Revision user ID'))
      ->setDescription(t('The user ID of the author of the current revision.'))
      ->setSetting('target_type', 'user')
      ->setQueryable(FALSE)
      ->setRevisionable(TRUE);

    $fields['revision_log'] = BaseFieldDefinition::create('string_long')
      ->setLabel(t('Revision log message'))
      ->setDescription(t('The log entry explaining the changes in this revision.'))
      ->setRevisionable(TRUE)
      ->setTranslatable(TRUE)
      ->setDisplayOptions('form', array(
        'type' => 'string_textarea',
        'weight' => 25,
        'settings' => array(
          'rows' => 4,
        ),
      ));

    return $fields;
  }

  /**
   * Default value callback for 'uid' base field definition.
   *
   * @see ::baseFieldDefinitions()
   *
   * @return array
   *   An array of default values.
   */
  public static function getCurrentUserId() {
    return array(\Drupal::currentUser()->id());
  }

}
