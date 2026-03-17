<?php

namespace core_ltix\local\ltiopenid;

interface lti_user_authenticator_interface {
    public function authenticate(\stdClass $toolconfig, string $loginhint): lti_user;
}
