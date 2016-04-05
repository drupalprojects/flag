<?php
/**
 * @file
 * Contains the \Drupal\flag\Form\FlagFormBase class.
 */

namespace Drupal\flag\Form;

use Drupal\Core\Entity\EntityForm;
use Drupal\Core\Form\FormStateInterface;
use Drupal\flag\ActionLink\ActionLinkPluginManager;
use Drupal\flag\Entity\Flag;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Provides the base flag add/edit form.
 *
 * Since both the add and edit flag forms are largely the same, the majority of
 * functionality is done in this class. It generates the form, validates the
 * input, and handles the submit.
 */
abstract class FlagFormBase extends EntityForm {

  /**
   * The action link plugin manager.
   *
   * @var Drupal\flag\ActionLink\ActionLinkPluginManager
   */
  protected $actionLinkManager;

  /**
   * Constructs a new form.
   *
   * @param \Drupal\flag\ActionLink\ActionLinkPluginManager $action_link_manager
   *   The link type plugin manager.
   */
  public function __construct(ActionLinkPluginManager $action_link_manager) {
    $this->actionLinkManager = $action_link_manager;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    return new static(
      $container->get('plugin.manager.flag.linktype')
    );
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state, $entity_type = NULL) {
    $form = parent::buildForm($form, $form_state);

    $flag = $this->entity;

    $form['#flag'] = $flag;
    $form['#flag_name'] = $flag->id;

    $form['label'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Label'),
      '#default_value' => $flag->label,
      '#description' => $this->t('A short, descriptive title for this flag. It will be used in administrative interfaces to refer to this flag, and in page titles and menu items of some views this module provides (these are customizable, though). Some examples could be <em>Bookmarks</em>, <em>Favorites</em>, or <em>Offensive</em>.'),
      '#maxlength' => 255,
      '#required' => TRUE,
      '#weight' => -3,
    ];

    $form['id'] = [
      '#type' => 'machine_name',
      '#title' => $this->t('Machine name'),
      '#default_value' => $flag->id,
      '#description' => $this->t('The machine-name for this flag. It may be up to 32 characters long and may only contain lowercase letters, underscores, and numbers. It will be used in URLs and in all API calls.'),
      '#weight' => -2,
      '#machine_name' => [
        'exists' => '\Drupal\flag\Entity\Flag::load',
      ],
      '#disabled' => !$flag->isNew(),
      '#required' => TRUE,
    ];

    $form['global'] = [
      '#type' => 'radios',
      '#title' => $this->t('Scope'),
      '#default_value' => $flag->isGlobal() ? 1 : 0,
      '#options' => array(
        0 => t('Personal'),
        1 => t('Global'),
      ),
      '#weight' => -1,
    ];

    // Add descriptions for each radio button.
    $form['global'][0]['#description'] = $this->t('Each user has individual flags on entities.');
    $form['global'][1]['#description'] = $this->t('The entity is either flagged or not for all users.');

    $form['messages'] = [
      '#type' => 'details',
      '#open' => TRUE,
      '#title' => $this->t('Messages'),
    ];

    $flag_short = $flag->getFlagShortText();
    $form['messages']['flag_short'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Flag link text'),
      '#default_value' => !empty($flag_short) ? $flag_short : $this->t('Flag this item'),
      '#description' => $this->t('The text for the "flag this" link for this flag.'),
      '#required' => TRUE,
    ];

    $form['messages']['flag_long'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Flag link description'),
      '#default_value' => $flag->getFlagLongText(),
      '#description' => $this->t('The description of the "flag this" link. Usually displayed on mouseover.'),
    ];

    $form['messages']['flag_message'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Flagged message'),
      '#default_value' => $flag->getFlagMessage(),
      '#description' => $this->t('Message displayed after flagging content. If JavaScript is enabled, it will be displayed below the link. If not, it will be displayed in the message area.'),
    ];

    $unflag_short = $flag->getUnflagShortText();
    $form['messages']['unflag_short'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Unflag link text'),
      '#default_value' => !empty($unflag_short) ? $unflag_short : $this->t('Unflag this item'),
      '#description' => $this->t('The text for the "unflag this" link for this flag.'),
      '#required' => TRUE,
    ];

    $form['messages']['unflag_long'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Unflag link description'),
      '#default_value' => $flag->getUnflagLongText(),
      '#description' => $this->t('The description of the "unflag this" link. Usually displayed on mouseover.'),
    ];

    $form['messages']['unflag_message'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Unflagged message'),
      '#default_value' => $flag->getUnflagMessage(),
      '#description' => $this->t('Message displayed after content has been unflagged. If JavaScript is enabled, it will be displayed below the link. If not, it will be displayed in the message area.'),
    ];

    $form['access'] = [
      '#type' => 'details',
      '#open' => TRUE,
      '#title' => $this->t('Flag access'),
      '#tree' => FALSE,
      '#weight' => 10,
    ];

    // Switch plugin type in case a different is chosen.

    $flag_type_plugin = $flag->getFlagTypePlugin();
    $flag_type_def = $flag_type_plugin->getPluginDefinition();

    $bundles = \Drupal::entityManager()->getBundleInfo($flag_type_def['entity_type']);
    $entity_bundles = [];
    foreach ($bundles as $bundle_id => $bundle_row) {
      $entity_bundles[$bundle_id] = $bundle_row['label'];
    }

    // Flag classes will want to override this form element.
    $form['access']['bundles'] = [
      '#type' => 'checkboxes',
      '#title' => $this->t('Flaggable types'),
      '#options' => $entity_bundles,
      '#default_value' => $flag->getBundles(),
      '#description' => $this->t('Check any bundles that this flag may be used on. Leave empty to apply to all bundles.'),
      '#weight' => 10,
    ];

    $form['access']['unflag_denied_text'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Unflag not allowed text'),
      '#default_value' => $flag->getUnflagDeniedText(),
      '#description' => $this->t('If a user is allowed to flag but not unflag, this text will be displayed after flagging. Often this is the past-tense of the link text, such as "flagged".'),
      '#weight' => -1,
    ];

    $form['display'] = [
      '#type' => 'details',
      '#open' => TRUE,
      '#title' => $this->t('Display options'),
      '#description' => $this->t('Flags are usually controlled through links that allow users to toggle their behavior. You can choose how users interact with flags by changing options here. It is legitimate to have none of the following checkboxes ticked, if, for some reason, you wish <a href="@placement-url">to place the the links on the page yourself</a>.', array('@placement-url' => 'http://drupal.org/node/295383')),
      '#tree' => FALSE,
      '#weight' => 20,
      '#prefix' => '<div id="link-type-settings-wrapper">',
      '#suffix' => '</div>',
      // @todo: Move flag_link_type_options_states() into controller?
      // '#after_build' => array('flag_link_type_options_states'),
    ];

    $form['display']['settings'] = [
      '#type' => 'container',
      '#weight' => 21,
    ];

    $form = $flag_type_plugin->buildConfigurationForm($form, $form_state);

    $form['display']['link_type'] = [
      '#type' => 'radios',
      '#title' => $this->t('Link type'),
      '#options' => $this->actionLinkManager->getAllLinkTypes(),
      // '#after_build' => array('flag_check_link_types'),
      '#default_value' => $flag->getLinkTypePlugin()->getPluginId(),
      // Give this a high weight so additions by the flag classes for entity-
      // specific options go above.
      '#weight' => 18,
      '#attributes' => [
        'class' => ['flag-link-options'],
      ],
      '#required' => TRUE,
      '#ajax' => [
        'callback' => '::updateSelectedPluginType',
        'wrapper' => 'link-type-settings-wrapper',
        'event' => 'change',
        'method' => 'replace',
      ],
    ];
    //debug($flag->getLinkTypePlugin()->getPluginId(), 'default value');
    $form['display']['link_type_submit'] = [
      '#type' => 'submit',
      '#value' => $this->t('Update'),
      '#submit' => ['::submitSelectPlugin'],
      '#weight' => 20,
      '#attributes' => ['class' => ['js-hide']],
    ];
    // Add the descriptions to each ratio button element. These attach to the
    // elements when FormAPI expands them.
    $action_link_plugin_defs = $this->actionLinkManager->getDefinitions();
    foreach ($action_link_plugin_defs as $key => $info) {
      $form['display']['link_type'][$key] = [
        '#description' => $info['description'],
        '#executes_submit_callback' => TRUE,
        '#limit_validation_errors' => [['link_type']],
        '#submit' => ['::submitSelectPlugin'],
      ];
    }

    $action_link_plugin = $flag->getLinkTypePlugin();
    $form = $action_link_plugin->buildConfigurationForm($form, $form_state);

    return $form;
  }

  /**
   * Handles switching the configuration type selector.
   */
  public function updateSelectedPluginType($form, FormStateInterface $form_state) {
    return $form['display'];
  }

  /**
   * Handles submit call when sensor type is selected.
   */
  public function submitSelectPlugin(array $form, FormStateInterface $form_state) {
    // Rebuild the entity using the form's new state.
    $this->entity = $this->buildEntity($form, $form_state);
    $form_state->setRebuild();
  }

  /**
   * {@inheritdoc}
   */
  public function buildEntity(array $form, FormStateInterface $form_state) {
    $entity = parent::buildEntity($form, $form_state);
    // Update the link type plugin.
    // @todo Do this somewhere else?
    $entity->setLinkTypePlugin($entity->get('link_type'));
    //debug($entity->getLinkTypePlugin()->getPluginId(), $entity->get('link_type'));
    return $entity;
  }

  /**
   * {@inheritdoc}
   */
  public function validateForm(array &$form, FormStateInterface $form_state) {
    parent::validateForm($form, $form_state);

    // @todo Move this to the validation method for the confirm form plugin
    $flag = $this->entity;
    $flag->getFlagTypePlugin()->validateConfigurationForm($form, $form_state);
    $flag->getLinkTypePlugin()->validateConfigurationForm($form, $form_state);
  }

  /**
   * {@inheritdoc}
   */
  public function save(array $form, FormStateInterface $form_state) {
    $flag = $this->entity;

    $flag->getFlagTypePlugin()->submitConfigurationForm($form, $form_state);
    $flag->getLinkTypePlugin()->submitConfigurationForm($form, $form_state);

    $flag->enable();
    $status = $flag->save();
    $url = $flag->urlInfo();
    if ($status == SAVED_UPDATED) {
      drupal_set_message(t('Flag %label has been updated.', ['%label' => $flag->label()]));
      $this->logger('flag')->notice('Flag %label has been updated.', ['%label' => $flag->label(), 'link' => $this->l($this->t('Edit'), $url)]);
    }
    else {
      drupal_set_message(t('Flag %label has been added.', ['%label' => $flag->label()]));
      $this->logger('flag')->notice('Flag %label has been added.', ['%label' => $flag->label(), 'link' => $this->l($this->t('Edit'), $url)]);
    }

    // We clear caches more vigorously if the flag was new.
    // _flag_clear_cache($flag->entity_type, !empty($flag->is_new));

    // Save permissions.
    // This needs to be done after the flag cache has been cleared, so that
    // the new permissions are picked up by hook_permission().
    // This may need to move to the flag class when we implement extra
    // permissions for different flag types: http://drupal.org/node/879988

    // If the flag ID has changed, clean up all the obsolete permissions.
    if ($flag->id != $form['#flag_name']) {
      $old_name = $form['#flag_name'];
      $permissions = ["flag $old_name", "unflag $old_name"];
      foreach (array_keys(user_roles()) as $rid) {
        user_role_revoke_permissions($rid, $permissions);
      }
    }
    /*
        foreach (array_keys(user_roles(!\Drupal::moduleHandler()->moduleExists('session_api'))) as $rid) {
          // Create an array of permissions.
          $permissions = array(
            "flag $flag->name" => $flag->roles['flag'][$rid],
            "unflag $flag->name" => $flag->roles['unflag'][$rid],
          );
          user_role_change_permissions($rid, $permissions);
        }
    */
    // @todo: when we add database caching for flags we'll have to clear the
    // cache again here.

    $form_state->setRedirect('entity.flag.collection');
  }

  /**
   * {@inheritdoc}
   */
  public function delete(array $form, FormStateInterface $form_state) {
    $form_state->setRedirect('flag_list');
  }

}
