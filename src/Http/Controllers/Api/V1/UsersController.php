<?php

namespace Motor\Admin\Http\Controllers\Api\V1;

use Motor\Admin\Http\Controllers\Api\UsersController as BaseUsersController;

/**
 * V1 Users Controller - Frozen copy of current behavior.
 *
 * This controller extends the unversioned controller to ensure V1 routes
 * maintain the exact same behavior as the original unversioned API.
 */
class UsersController extends BaseUsersController
{
    // Inherits all behavior from current controller
    // This ensures V1 stays frozen to current behavior
}
