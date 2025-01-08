<?php

namespace App\Api;

class Auth
{
    /**
     * @var self
     */
    private static $instance;

    /**
     * @return self
     */
    public static function getInstance()
    {
        if (null === self::$instance) {
            self::$instance = new self();
        }

        return self::$instance;
    }

    /**
     * Auth constructor.
     */
    private function __construct()
    {
        add_action('rest_api_init', [$this, 'registerRestRoute']);
    }

    /**
     * @return void
     */
    public function registerRestRoute()
    {
        register_rest_route('jwt-auth/v1', 'register', array(
            'methods' => \WP_REST_Server::CREATABLE,
            'callback' => [$this, 'registerUser']
        ));
    }

    /**
     * @param \WP_REST_Request $request
     *
     * @return WP_REST_Response|WP_Error
     */
    public function registerUser(\WP_REST_Request $request)
    {
        $data = $request->get_json_params();

        if (empty($data['username']) || empty($data['email']) || empty($data['password'])) {
            return new \WP_Error('registration_error', 'Username, email and password are required', array('status' => 400));
        }

        if (is_email($data['email']) === false) {
            return new \WP_Error('registration_error', 'Invalid email address', array('status' => 400));
        }

        $username = sanitize_text_field($data['username']);
        $email = sanitize_text_field($data['email']);
        $password = sanitize_text_field($data['password']);
        $user = wp_create_user($username, $password, $email);

        if (is_wp_error($user)) {
            return new \WP_Error('registration_error', $user->get_error_message(), array('status' => 400));
        }

        return new \WP_REST_Response(array('message' => 'User registered successfully'), 200);
    }
}
