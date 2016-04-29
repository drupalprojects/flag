<?php

namespace Drupal\flag\Tests;
use Drupal\Core\Entity\EntityInterface;
use Drupal\Core\Session\AccountInterface;
use Drupal\flag\FlagInterface;

/**
 * Tests Flag module permissions.
 *
 * @group flag
 */
class FlagPermissionsTest extends FlagTestBase {

  /**
   * @var FlagInterface
   */
  protected $flag;

  /**
   * @var EntityInterface
   */
  protected $node;

  /**
   * @var AccountInterface
   */
  protected $fullFlagUser;

  /**
   * @var AccountInterface
   */
  protected $flagOnlyUser;

  /**
   * @var AccountInterface
   */
  protected $authUser;

  /**
   * {@inheritdoc}
   */
  public function setUp() {
    parent::setUp();

    // Create the flag.
    $this->flag = $this->createFlag();

    // Create the full permission flag user.
    $this->fullFlagUser = $this->drupalCreateUser([
      'flag ' . $this->flag->id(),
      'unflag ' . $this->flag->id(),
    ]);

    // Create the flag only user.
    $this->flagOnlyUser = $this->drupalCreateUser([
      'flag ' . $this->flag->id(),
    ]);

    // Create a user with no flag permissions.
    $this->authUser = $this->createUser();

    // Create a node to test.
    $this->node = $this->drupalCreateNode(['type' => $this->nodeType]);
  }

  /**
   * Test permissions.
   */
  public function testPermissions() {
    // Check the full flag permission user can flag...
    $this->drupalLogin($this->fullFlagUser);
    $this->drupalGet('node/' . $this->node->id());
    $this->assertLink($this->flag->getFlagShortText());
    $this->clickLink($this->flag->getFlagShortText());
    $this->assertResponse(200);

    // ...and also unflag.
    $this->drupalGet('node/' . $this->node->id());
    $this->assertResponse(200);
    $this->assertLink($this->flag->getUnflagShortText());

    // Check the flag only user can flag...
    $this->drupalLogin($this->flagOnlyUser);
    $this->drupalGet('node/' . $this->node->id());
    $this->assertLink($this->flag->getFlagShortText());
    $this->clickLink($this->flag->getFlagShortText());
    $this->assertResponse(200);

    // ...but not unflag.
    $this->drupalGet('node/' . $this->node->id());
    $this->assertResponse(200);
    $this->assertNoLink($this->flag->getFlagShortText());
    $this->assertNoLink($this->flag->getUnflagShortText());

    // Check an unprivileged authenticated user.
    $this->drupalLogin($this->authUser);
    $this->drupalGet('node/' . $this->node->id());
    $this->assertNoLink($this->flag->getFlagShortText());

    // Check the anonymous user.
    $this->drupalLogout();
    $this->drupalGet('node/' . $this->node->id());
    $this->assertNoLink($this->flag->getFlagShortText());
  }
}
