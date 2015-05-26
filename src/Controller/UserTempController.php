<?php

/**
 * @file
 * Contains Drupal\user_temp\Controller\UserTempController.
 */

namespace Drupal\user_temp\Controller;

use Drupal\Core\Url;
use Drupal\Core\Controller\ControllerBase;
use Drupal\user\UserInterface;
use Drupal\Core\Database\Connection;
use Drupal\Component\Utility\Crypt;
use Drupal\Component\Utility\SafeMarkup;
use Drupal\views\ViewExecutableFactory;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpKernel\Exception\AccessDeniedHttpException;
use Symfony\Component\HttpKernel\Exception\FlattenException;


/**
 * Class UserTempController.
 *
 * @package Drupal\user_temp\Controller
 */
class UserTempController extends ControllerBase {

  /**
   * The database service.
   *
   * @var \Drupal\Core\Database\Connection
   */
  protected $database;
  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    return new static(
      $container->get('database'),
      $container->get('views.executable')
    );
  }
  /**
   * Construct the userTempController.
   *
   * @param \Drupal\Core\Database\Connection $database
   *   A database connection.
   * @param \Drupal\views\ViewExecutableFactory $views_factory
   *   A... views factory? Whatever that is...
   */
  public function __construct(Connection $database, ViewExecutableFactory $views_factory) {
    $this->database = $database;
    $this->views_factory = $views_factory;
  }
  /**
   * User tab page for displaying user_temp info.
   *
   * @param \Drupal\user\UserInterface $user
   *   A Drupal user.
   *
   * @return string
   *   Return some markup.
   */
  public function temp(UserInterface $user) {
    // See if the user already has an API key.
    $user_key_object = $this->database->query("SELECT * FROM {user_temp_keys} u WHERE u.uid = :uid", array(':uid' => $user->id()))->fetchObject();
    if (!$user_key_object) {
      // The user does not have a key. Generate one for them.
      $user_key = sha1(uniqid());
      // Insert it to the database.
      $this->database
        ->insert('user_temp_keys')
        ->fields(array(
          'uid' => $user->id(),
          'user_key' => $user_key,
        ))
        ->execute();
    }
    else {
      $user_key = $user_key_object->user_key;
    }

    // Generate the URL which we should use in the CURL explaination.
    $post_url = Url::fromRoute('user_temp.post_temperatures', [
      'user' => $user->id(),
    ], [
      'absolute' => TRUE,
    ])->toString();

    // Also get a view of the users temperatures.
    $view = entity_load('view', 'user_temperatures');

    // Add the variables to the render array and render from our template.
    return [
      '#api_key' => $user_key,
      '#user_view' => $this->views_factory->get($view)->preview(),
      '#post_url' => $post_url,
      '#theme' => 'user-temp-user-page',
    ];
  }

  /**
   * POST handler for processing the temperatures.
   *
   * @param string $user
   *   The uid in the route.
   * @param \Symfony\Component\HttpFoundation\Request $request
   *   The request object.
   *
   * @return string
   *   A JSON encoded object containing the nid of the node.
   */
  public function post($user, Request $request) {
    // Get the value posted.
    $data = $request->getContent();
    if (!$data) {
      throw new AccessDeniedHttpException();
    }
    // Then see if it is JSON.
    if (!$json = json_decode($data)) {
      throw new AccessDeniedHttpException();
    }
    // If it does not include a temperature, we don't want it.
    if (empty($json->temp)) {
      throw new AccessDeniedHttpException();
    }
    // Then create a node with the temperature.
    $nid = NULL;
    try {
      $edit = [
        'uid' => $user,
        'type' => 'user_temperature',
        'langcode' => 'en',
        'title' => $this->t('Temperature logged at !date', [
          '!date' => format_date(time(), 'custom', 'd.m.Y H:i:s'),
        ]),
        'promote' => 0,
      ];
      $node = entity_create('node', $edit);
      $node->get('field_user_temperature')->setValue(SafeMarkup::checkPlain($json->temp));
      $node->save();
      $nid = $node->id();
    }
    catch (Exception $e) {
      // We had a problem.
      throw new FlattenException($e, 500);
    }

    return new JsonResponse(array(
      'nid' => $nid,
    ));
  }

}
