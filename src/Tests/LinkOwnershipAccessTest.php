<?php
/**
 * @file
 * Contains \Drupal\flag\Tests\LinkOwnershipAccessTest.
 */

namespace Drupal\flag\Tests;

use Drupal\user\RoleInterface;
use Drupal\flag\Entity\Flag;
use Drupal\flag\Tests\FlagTestBase;

/**
 * Tests the current user sees links for their own flaggings, or global ones.
 *
 * @group flag
 */
class LinkOwnershipAccessTest extends FlagTestBase {

  /**
   * {@inheritdoc}
   */
  protected function setUp() {
    parent::setUp();

    $this->entityQueryManager = $this->container->get('entity.query');

    // Create a node to flag.
    $this->node = $this->drupalCreateNode(['type' => $this->nodeType]);
    $this->nodeId = $this->node->id();
  }

  /**
   * Test ownership access.
   */
  public function testFlagOwnershipAccess() {
    $this->doFlagOwnershipAccessTest();
    $this->doGlobalFlagOwnershipAccessTest();
  }

  public function doFlagOwnershipAccessTest() {
    $flag_link_text = 'Flag this item';
    $unflag_link_text = 'Unflag this item';

    // Create a non-global flag.
    $flag = Flag::create([
      'id' => strtolower($this->randomMachineName()),
      'label' => $this->randomString(),
      'entity_type' => 'node',
      'bundles' => ['article'],
      'flag_short' => $flag_link_text,
      'unflag_short' => $unflag_link_text,
      'flag_type' => $this->getFlagType('node'),
      'link_type' => 'reload',
      'linkTypeConfig' => [],
      'global' => FALSE,
    ]);

    // Save the flag.
    $flag->save();

    // Make sure that we actually did get a flag entity.
    $this->assertTrue($flag instanceof Flag);

    // Grant the flag permissions to the authenticated role, so that both
    // users have the same roles and share the render cache.
    $this->grantFlagPermissions($flag, RoleInterface::AUTHENTICATED_ID);

    // Create and login a new user.
    $user_1 = $this->drupalCreateUser();
    $this->drupalLogin($user_1);

    // Flag the node with user 1.
    $this->drupalGet('node/' . $this->nodeId);
    $this->clickLink($flag_link_text);
    $this->assertResponse(200);
    $this->assertLink($unflag_link_text);

    // Switch to user 2. They should see the link to flag.
    $user_2 = $this->drupalCreateUser();
    $this->drupalLogin($user_2);
    $this->drupalGet('node/' . $this->nodeId);
    $this->assertLink($flag_link_text, 0, "A flag link is found on the page for user 2.");

  }

  public function doGlobalFlagOwnershipAccessTest() {
    $flag_link_text = 'Flag this global item';
    $unflag_link_text = 'Unflag this global item';

    // Create a global flag.
    $flag = Flag::create([
      'id' => strtolower($this->randomMachineName()),
      'label' => $this->randomString(),
      'entity_type' => 'node',
      'bundles' => ['article'],
      'flag_short' => $flag_link_text,
      'unflag_short' => $unflag_link_text,
      'flag_type' => $this->getFlagType('node'),
      'link_type' => 'reload',
      'linkTypeConfig' => [],
      'global' => TRUE,
    ]);

    // Save the flag.
    $flag->save();

    // Make sure that we actually did get a flag entity.
    $this->assertTrue($flag instanceof Flag);

    // Grant the flag permissions to the authenticated role, so that both
    // users have the same roles and share the render cache.
    $this->grantFlagPermissions($flag, RoleInterface::AUTHENTICATED_ID);

    // Create and login a new user.
    $user_1 = $this->drupalCreateUser();
    $this->drupalLogin($user_1);

    // Flag the node with user 1.
    $this->drupalGet('node/' . $this->nodeId);
    $this->clickLink($flag_link_text);
    $this->assertResponse(200);
    $this->assertLink($unflag_link_text);

    // Switch to user 2. They should see the unflag link too.
    $user_2 = $this->drupalCreateUser();
    $this->drupalLogin($user_2);
    $this->drupalGet('node/' . $this->nodeId);
    $this->assertLink($unflag_link_text, 0, "The unflag link is found on the page for user 2.");
  }

}
