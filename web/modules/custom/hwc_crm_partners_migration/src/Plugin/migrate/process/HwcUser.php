<?php

namespace Drupal\hwc_crm_partners_migration\Plugin\migrate\process;

use Drupal\Core\Session\AccountInterface;
use Drupal\Core\Session\AccountProxy;
use Drupal\migrate\ProcessPluginBase;
use Drupal\Core\Plugin\ContainerFactoryPluginInterface;
use Drupal\migrate\MigrateExecutableInterface;
use Drupal\migrate\Row;
//use Drupal\Core\Session\UserSession\Ac;
use Drupal\workbench_access\Entity\AccessSchemeInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Drupal\migrate\MigrateSkipProcessException;
use Drupal\taxonomy\Entity\Term;

/**
 * This plugin find the term by name and vocabulary.
 * @code
 * process:
 *   destination_field:
 *     plugin: hwc_user
 *     source: source_field
 * @endcode
 * @MigrateProcessPlugin(
 *   id = "hwc_user",
 * )
 */
class HwcUser extends ProcessPluginBase implements ContainerFactoryPluginInterface {

  /**
   * The entity type manager.
   *
   * @var \Drupal\Core\Entity\EntityTypeManagerInterface
   */
  protected $entityTypeManager;

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container, array $configuration, $plugin_id, $plugin_definition) {
    $instance = new static(
      $configuration,
      $plugin_id,
      $plugin_definition
    );

    $instance->entityTypeManager = $container->get('entity_type.manager');

    return $instance;
  }

  /**
   * {@inheritdoc}
   */
  public function transform($value, MigrateExecutableInterface $migrate_executable, Row $row, $destination_property) {
    $user = $this->entityTypeManager->getStorage('user')->loadByProperties([
      'mail' => $row->getSourceProperty('field_main_contact_email'),
    ]);
    if (!$user) {
      $values = [
        'name' => $row->getSourceProperty('field_main_contact_email'),
        'mail' => $row->getSourceProperty('field_main_contact_email'),
        'field_crm_guid' => $row->getSourceProperty('field_guid_main_contact'),
        'field_user_partner_guid' => $row->getSourceProperty('field_guid_organisation'),
        'status' => 1,
        'roles' => ['campaign_partner'],
      ];
      $this->entityTypeManager->getStorage('user')->create($values)->save();
    }
    else {
      /** @var \Drupal\user\Entity\User $user */
      $user = reset($user);
      $user->set('field_crm_guid', $row->getSourceProperty('field_guid_main_contact'));
      $user->set('field_user_partner_guid', $row->getSourceProperty('field_guid_organisation'));
      $user->addRole('campaign_partner');
      $user->set('status', 1);
      $user->save();
    }



    return $value;


  }

}
