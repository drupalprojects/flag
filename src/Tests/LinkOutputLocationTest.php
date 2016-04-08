<?php
/**
 * @file
 * Contains \Drupal\flag\Tests\LinkOutputLocationTest.
 */

namespace Drupal\flag\Tests;

use Drupal\node\Entity\Node;
use Drupal\flag\FlagInterface;
use Drupal\Core\Entity\EntityInterface;

/**
 * Tests the Flag link is output in various locations.
 *
 * This test does not cover the access to the link, or that the link works
 * correctly. It merely checks that the link is output when the various output
 * settings (e.g. 'show in entity links') call for it.
 *
 * @todo Parts of this test relating to entity links and contextual links are
 * not written, as that functionality is currently broken in Flag: see
 * https://www.drupal.org/node/2411977.
 *
 * @group flag
 */
class LinkOutputLocationTest extends FlagTestBase {

  /**
   * The flag.
   *
   * @var \Drupal\flag\FlagInterface
   */
  protected $flag;

  /**
   * The node.
   *
   * @var \Drupal\node\NodeInterface
   */
  protected $node;

  /**
   * {@inheritdoc}
   */
  protected function setUp() {
    parent::setUp();

    // Create a flag.
    $this->flag = $this->createFlag('node', ['article'], 'reload');

    // Create a user who may flag and log them in. This ensures we don't have
    // to worry about flag access.
    $this->adminUser = $this->drupalCreateUser([
      'administer flags',
      // This permission is needed to change the view mode settings to show and
      // hide the flag link pseudofield.
      'administer node display',
    ]);
    $this->grantFlagPermissions($this->flag);

    $this->drupalLogin($this->adminUser);

    // Create a node to flag.
    $this->node = Node::create([
      'body' => [
        [
          'value' => $this->randomMachineName(32),
          'format' => filter_default_format(),
        ],
      ],
      'type' => 'article',
      'title' => $this->randomMachineName(8),
      'uid' => $this->adminUser->id(),
      'status' => 1,
      // Promoted to front page to test teaser view mode.
      'promote' => 1,
      'sticky' => 0,
    ]);
    $this->node->save();
  }

  /**
   * Test the link output.
   */
  public function testLinkLocation() {
    // Turn off all link output for the flag.
    $flag_config = $this->flag->getFlagTypePlugin()->getConfiguration();
    $flag_config['show_as_field'] = FALSE;
    $flag_config['show_in_links'] = [];
    $this->flag->getFlagTypePlugin()->setConfiguration($flag_config);
    $this->flag->save();

    // Check the full node shows no flag link.
    $this->drupalGet('node/' . $this->node->id());
    $this->assertNoPseudofield($this->flag, $this->node);
    // TODO: check no entity link.

    // Check the teaser view mode for the node shows no flag link.
    $this->drupalGet('node');
    $this->assertNoPseudofield($this->flag, $this->node);
    // TODO: check no entity link.

    // Turn on 'show as field'.
    // By default, this will be visible on the field display configuration.
    $flag_config = $this->flag->getFlagTypePlugin()->getConfiguration();
    $flag_config['show_as_field'] = TRUE;
    $flag_config['show_in_links'] = [];
    $this->flag->getFlagTypePlugin()->setConfiguration($flag_config);
    $this->flag->save();

    // Check the full node shows the flag link as a pseudofield.
    $this->drupalGet('node/' . $this->node->id());
    $this->assertPseudofield($this->flag, $this->node);
    // TODO: check no entity link.

    // Check the teaser view mode shows the flag link as a pseudofield.
    $this->drupalGet('node');
    $this->assertPseudofield($this->flag, $this->node);
    // TODO: check no entity link.

    // Hide the flag pseudofield on teaser view mode.
    $edit = [
      'fields[flag_' . $this->flag->id() . '][type]' => 'hidden',
    ];
    $this->drupalPostForm('admin/structure/types/manage/article/display/teaser', $edit, $this->t('Save'));

    // Check the form was saved successfully.
    $this->assertText('Your settings have been saved.');

    // Check the full node still shows the flag link as a pseudofield.
    $this->drupalGet('node/' . $this->node->id());
    $this->assertPseudofield($this->flag, $this->node);
    // TODO: check no entity link.

    // Check the teaser view mode does not show the flag link as a pseudofield.
    $this->drupalGet('node');
    $this->assertNoPseudofield($this->flag, $this->node);
    // TODO: check no entity link.

    // TODO:
    // Turn on the entity link, and turn off the pseudofield.
    // Check the full and teaser view modes.
    // Turn off the entity link for one view mode.
    // Check both view modes are as expected.
  }

  /**
   * Pass if the flag link is shown as a pseudofield on the page.
   *
   * @param \Drupal\flag\FlagInterface $flag
   *   The flag to look for.
   * @param \Drupal\Core\Entity\EntityInterface $entity
   *   The flaggable entity the flag is on.
   * @param string $message
   *   (Optional) Message to display.
   */
  protected function assertPseudofield(FlagInterface $flag, EntityInterface $entity, $message = '') {
    $this->assertPseudofieldHelper($flag, $entity, $message ?: "The flag link is shown as a pseudofield.", TRUE);
  }

  /**
   * Pass if the flag link is not shown as a pseudofield on the page.
   *
   * @param \Drupal\flag\FlagInterface $flag
   *   The flag to look for.
   * @param \Drupal\Core\Entity\EntityInterface $entity
   *   The flaggable entity the flag is on.
   * @param string $message
   *   (Optional) Message to display.
   */
  protected function assertNoPseudofield(FlagInterface $flag, EntityInterface $entity, $message = '') {
    $this->assertPseudofieldHelper($flag, $entity, $message ?: "The flag link is not shown as a pseudofield.", FALSE);
  }

  /**
   * Helper for assertPseudofield() and assertNoPseudofield().
   *
   * It is not recommended to call this function directly.
   *
   * @param \Drupal\flag\FlagInterface $flag
   *   The flag to look for.
   * @param \Drupal\Core\Entity\EntityInterface $entity
   *   The flaggable entity the flag is on.
   * @param string $message
   *   Message to display.
   * @param bool $exists
   *   TRUE if the flag link should exist, FALSE if it should not exist.
   */
  protected function assertPseudofieldHelper(FlagInterface $flag, EntityInterface $entity, $message, $exists) {
    $xpath = $this->xpath("//*[contains(@class, 'node__content')]/a[@id = :id]", [
      ':id' => 'flag-' . $flag->id() . '-id-' . $entity->id(),
    ]);
    $this->assert(count($xpath) == ($exists ? 1 : 0), $message);
  }

  // TODO: add assertions:
  // assertEntityLink
  // assertNoEntityLink

}
