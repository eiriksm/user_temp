<?php

/**
 * @file
 * Contains Drupal\user_temp\Access\PostTempAccessCheck.
 */

namespace Drupal\user_temp\Access;

use Drupal\Core\Access\AccessResult;
use Drupal\Core\Routing\Access\AccessInterface;
use Drupal\Core\Database\Database;
use Symfony\Component\HttpFoundation\Request;

/**
 * Access check for user registration routes.
 */
class PostTempAccessCheck implements AccessInterface {

  /**
   * Checks access.
   *
   * @param \Symfony\Component\HttpFoundation\Request $request
   *   The request object.
   *
   * @return \Drupal\Core\Access\AccessResultInterface
   *   The access result.
   */
  public function access(Request $request) {
    if ($request->getMethod() != 'POST') {
      return AccessResult::forbidden();
    }
    $headers = $request->headers->all();
    if (empty($headers['x-user-temp']) || empty($headers['x-user-temp'][0])) {
      return AccessResult::forbidden();
    }
    // Check if the actual path is allowed for the actual "api-key".
    $user = $request->attributes->get('user');
    $key = $headers['x-user-temp'][0];
    if ((bool) Database::getConnection()->query("SELECT * FROM {user_temp_keys} u WHERE u.uid = :uid AND u.user_key = :user_key", [
      ':uid' => $user,
      ':user_key' => $key
    ])->fetchField()) {
      return AccessResult::allowed();
    }
    // If the user did not supply the key for the user in the route, then they
    // are denied access.
    return AccessResult::forbidden();
  }

}
