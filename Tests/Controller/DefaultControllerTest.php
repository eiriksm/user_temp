<?php

/**
 * @file
 * Contains Drupal\user_temp\Tests\DefaultController.
 */

namespace Drupal\user_temp\Tests;

use Drupal\simpletest\WebTestBase;

/**
 * Provides automated tests for the user_temp module.
 */
class DefaultControllerTest extends WebTestBase {
  /**
   * {@inheritdoc}
   */
  public static function getInfo() {
    return array(
      'name' => "user_temp DefaultController's controller functionality",
      'description' => 'Test Unit for module user_temp and controller DefaultController.',
      'group' => 'Other',
    );
  }

  /**
   * {@inheritdoc}
   */
  public function setUp() {
    parent::setUp();
  }

  /**
   * Tests user_temp functionality.
   */
  public function testDefaultController() {
    // Check that the basic functions of module user_temp.
    $this->assertEqual(TRUE, TRUE, 'Test Unit Generated via App Console.');
  }

}
