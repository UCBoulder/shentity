<?php

namespace Drupal\shentity;

use Drupal\Core\Entity\ContentEntityInterface;
use Drupal\Core\Entity\EntityChangedInterface;
use Drupal\user\EntityOwnerInterface;

/**
 * Provides an interface defining a Contact entity.
 *
 * We have this interface so we can join the other interfaces it extends.
 *
 * @ingroup shentity
 */
interface ShentityInterface extends ContentEntityInterface, EntityOwnerInterface, EntityChangedInterface {

}
