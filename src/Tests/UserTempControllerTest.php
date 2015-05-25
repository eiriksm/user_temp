<?php

/**
 * @file
 * Contains Drupal\user_temp\Tests\UserTempControllerTest.
 */

namespace Drupal\user_temp\Tests;

use Drupal\simpletest\WebTestBase;
use Drupal\Core\Database\Database;

/**
 * Provides automated tests for the user_temp module.
 *
 * @group user_temp
 */
class UserTempControllerTest extends WebTestBase {
  /**
   * Modules to enable.
   *
   * @var array
   */
  public static $modules = array('user_temp');
  /**
   * {@inheritdoc}
   */
  public static function getInfo() {
    return array(
      'name' => "user_temp controller functionality",
      'description' => 'Test Unit for module user_temp and controller.',
      'group' => 'Other',
    );
  }

  /**
   * {@inheritdoc}
   */
  public function setUp() {
    parent::setUp();
    $this->api_user = $this->drupalCreateUser(array('create own temperatures'));
  }

  /**
   * Tests user_temp functionality.
   */
  public function testUserTempController() {
    // Start by asserting we are denied access to the route used for POSTing
    // temperatures.
    $this->drupalPost('/user/1/user_temp_post', 'application/json', array());
    $this->assertResponse(403);
    // Check that we are able to POST on behalf of the $api_user.
    $path = sprintf('user/%d/user_temp_post', $this->api_user->id());
    $temp = rand(0, 100);
    $this->curlExec(array(
      CURLOPT_URL => $this->buildUrl($path),
      CURLOPT_POST => TRUE,
      CURLOPT_POSTFIELDS => json_encode(array('temp' => $temp)),
      CURLOPT_HTTPHEADER => array(
        'Accept: application/json',
        'Content-Type: application/json',
        'x-user-temp: abc',
      ),
    ));
    // We should still not be able to POST it, since the API key is not
    // generated yet.
    $this->assertResponse(403);
    // Log in, and generate the API key.
    $this->drupalLogin($this->api_user);
    $overview_path = sprintf('user/%d/user_temp', $this->api_user->id());
    $this->drupalGet($overview_path);
    // At this point we should have 0 temperatures registered.
    $temp_rows_selector = '//div[@class="view-content"]//tr';
    $this->assertTrue((1 > count($this->xpath($temp_rows_selector))));
    // Check that some API key is generated and therefore the response would be
    // good.
    $this->assertText('Your API key is');
    $key_row = Database::getConnection()->query("SELECT * FROM {user_temp_keys} u WHERE u.uid = :uid", [
      ':uid' => $this->api_user->id(),
    ])->fetchObject();
    $key = $key_row->user_key;
    // Do an actual POST with the value.
    $response = $this->curlExec(array(
      CURLOPT_URL => $this->buildUrl($path),
      CURLOPT_POST => TRUE,
      CURLOPT_POSTFIELDS => json_encode(array('temp' => $temp)),
      CURLOPT_HTTPHEADER => array(
        'x-user-temp: ' . $key,
      ),
    ));
    // This should be response code 200.
    $this->assertResponse(200);
    // Take a note of what nid we posted. Probably 1, in this case.
    $nid = json_decode($response)->nid;
    // The visit that page, and verify that the temperature is set to what we
    // expect.
    $this->drupalGet('node/' . $nid);
    // Check the value.
    $temp_values = $this->xpath('//div[@class="field field-node--field-user-temperature field-name-field-user-temperature field-type-float field-label-above"]//div[@class="field-item"]');
    $this->assertTrue($temp_values[0] == $temp);
    // ...and check that we have that temperature on the user temp page.
    $this->drupalGet($overview_path);
    $this->assertTrue(1 == count($this->xpath($temp_rows_selector)));
  }

}
