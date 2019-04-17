<?php

namespace Orca\UserLogBundle;

/**
 * Events.
 *
 * @author Dev Lexik <dev@lexik.fr>
 */
final class Events
{
    /**
     * Dispatched after the token generation to allow sending more data
     * on the authentication success response.
     */
    const AUTHENTICATION_SUCCESS = 'orca_userlog_authentication.on_authentication_success';
}
