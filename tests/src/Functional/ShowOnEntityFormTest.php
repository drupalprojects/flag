<?php

namespace Drupal\Tests\flag\Functional;

use Drupal\flag\Tests\FlagCreateTrait;
use Drupal\Tests\flag\Traits\FlagPermissionsTrait;
use Drupal\Tests\BrowserTestBase;

/**
 * Tests for the base entity Flag Type plugin.
 *
 * @group flag
 */
class ShowOnEntityFormTest extends BrowserTestBase {

  use FlagCreateTrait;
  use FlagPermissionsTrait;

  /**
   * {@inheritdoc}
   */
  public static $modules = [
    'system',
    'user',
    'node',
    'flag',
  ];

  /**
   * The flag to be flagged and unflagged.
   *
   * @var FlagInterface
   */
  protected $flag;

  /**
   * A user with Flag admin rights.
   *
   * @var \Drupal\user\UserInterface
   */
  protected $adminUser;

  /**
   * The node type to use in the test.
   *
   * @var string
   */
  protected $nodeType = 'article';

  /**
   * {@inheritdoc}
   */
  protected function setUp() {
    parent::setUp();

    // Create content type.
    $this->drupalCreateContentType(['type' => $this->nodeType]);

    // Create the admin user.
    $this->adminUser = $this->drupalCreateUser([
      'administer flags',
      'administer modules',
      'administer nodes',
      'create ' . $this->nodeType . ' content',
      'edit any ' . $this->nodeType . ' content',
      'delete any ' . $this->nodeType . ' content',
    ]);
  }

  /**
   * Tests if flags appear on the entity form.
   */
  public function testEntityForm() {
    // Login as the admin user.
    $this->drupalLogin($this->adminUser);

    // Create the flag with show_on_form, and grant permissions.
    $edit = [
      'bundles' => [$this->nodeType],
      'flagTypeConfig' => [
        'show_as_field' => TRUE,
        'show_on_form' => TRUE,
        'show_contextual_link' => FALSE,
        ],
    ];
    $flag = $this->createFlagFromArray($edit);
    $this->grantFlagPermissions($flag);
    $flag_checkbox_id = 'edit-flag-' . $flag->id();

    // Create a node and get the ID.
    $node = $this->createNode(['type' => $this->nodeType]);
    $node_id = $node->id();
    $node_edit_path = 'node/' . $node_id . '/edit';

    // See if the form element exists.
    $this->drupalGet($node_edit_path);
    $this->assertSession()->fieldExists($flag_checkbox_id);

    // See if flagging on the form works.
    $edit = [
      'flag[' . $flag->id() . ']' => TRUE,
    ];
    $this->drupalPostForm($node_edit_path, $edit, 'Save');

    // Check to see if the checkbox reflects the state correctly.
    $this->drupalGet($node_edit_path);
    $this->assertSession()->fieldExists($flag_checkbox_id);


    // See if unflagging on the form works.
    $edit = [
      'flag[' . $flag->id() . ']' => FALSE,
    ];
    $this->drupalPostForm($node_edit_path, $edit, 'Save');

    // Go back to the node edit page and check if the flag checkbox is updated.
    $this->drupalGet($node_edit_path);
    $this->assertNoFieldChecked($flag_checkbox_id, 'The flag checkbox is unchecked on the entity form.');

    // Verify link is on the add form.
    $this->drupalGet('node/add/' . $this->nodeType);
    $this->assertSession()->fieldExists($flag_checkbox_id);

    // Tests flagging via the add form.
    $edit = [
      'title[0][value]' => $this->randomString(),
      'flag[' . $flag->id() . ']' => TRUE,
    ];
    $this->drupalPostForm('node/add/' . $this->nodeType, $edit, 'Save');
    $node = $this->getNodeByTitle($edit['title[0][value]']);
    $this->assertTrue($flag->isFlagged($node, $this->adminUser));

    // Tests submitting a new node and not flagging.
    $edit = [
      'title[0][value]' => $this->randomString(),
      'flag[' . $flag->id() . ']' => FALSE,
    ];
    $this->drupalPostForm('node/add/' . $this->nodeType, $edit, 'Save');
    $node = $this->getNodeByTitle($edit['title[0][value]']);
    $this->assertFalse($flag->isFlagged($node, $this->adminUser));

    // Form element should not appear on the delete form.
    $this->drupalGet($node->toUrl('delete-form'));
    $this->assertNoField($flag_checkbox_id);
  }

}
