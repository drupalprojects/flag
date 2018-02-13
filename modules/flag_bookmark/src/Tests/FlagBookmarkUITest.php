<?php

namespace Drupal\flag_bookmark\Tests;

use Drupal\simpletest\WebTestBase;
use Drupal\Tests\BrowserTestBase;

/**
 * UI Test for flag_bookmark.
 *
 * @group flag_bookmark
 */
class FlagBookmarkUITest extends BrowserTestBase {

  /**
   * Modules to enable.
   *
   * @var array
   */
  public static $modules = [
    'views',
    'flag',
    'flag_bookmark',
  ];

  /**
   * Administrator user.
   *
   * @var \Drupal\Core\Session\AccountInterface
   */
  protected $adminUser;

  /**
   * {@inheritdoc}
   */
  public function setUp() {
    parent::setUp();

    $this->drupalCreateContentType(['type' => 'article', 'name' => 'Article']);

    // Create a test user and log in.
    $this->adminUser = $this->drupalCreateUser([
      'flag bookmark',
      'unflag bookmark',
      'create article content',
      'access content overview',
    ]);
    $this->drupalLogin($this->adminUser);
  }

  /**
   * Test the flag_bookmark UI.
   */
  public function testUi() {

    // Generate a unique title so we can find it on the page easily.
    $title = $this->randomMachineName();

    // Add articles.
    $this->drupalPostForm('node/add/article', [
      'title[0][value]' => $title,
    ], t('Save'));

    $auth_user = $this->drupalCreateUser([
      'flag bookmark',
      'unflag bookmark',
    ]);
    $this->drupalLogin($auth_user);

    // Check the link to bookmark exist.
    $this->drupalGet('node/1');
    $this->assertLink(t('Bookmark this'));

    // Bookmark article.
    $this->clickLink(t('Bookmark this'));

    // Check if the bookmark appears in the frontpage.
    $this->drupalGet('node');
    $this->assertLink(t('Remove bookmark'));

    // Check the view is shown correctly.
    $this->drupalGet('bookmarks');
    $this->assertText($title);
  }

  public function testBulkDelete() {
    // Create some nodes.
    $nodes[] = $this->drupalCreateNode(['type' => 'article']);
    $nodes[] = $this->drupalCreateNode(['type' => 'article']);

    // Login as an auth user.
    $auth_user = $this->drupalCreateUser([
      'flag bookmark',
      'unflag bookmark',
      'administer flaggings',
    ]);
    $this->drupalLogin($auth_user);

    // Flag the nodes.
    $this->drupalGet('node/' . $nodes[0]->id());
    $this->clickLink('Bookmark this');
    $this->drupalGet('node/' . $nodes[1]->id());
    $this->clickLink('Bookmark this');

    $this->drupalGet('bookmarks');
    $this->assertText('Delete flagging');
    $this->assertText($nodes[0]->label());
    $this->assertText($nodes[1]->label());

    $this->drupalPostForm('bookmarks', [
      'flagging_bulk_form[0]' => TRUE,
      'flagging_bulk_form[1]' => TRUE,
      'action' => 'flag_delete_flagging',
    ], 'Apply to selected items');

    $this->drupalGet('bookmarks');
    $this->assertText('No bookmarks available.');
    $this->assertNoText($nodes[0]->label());
    $this->assertNoText($nodes[1]->label());
  }

}
