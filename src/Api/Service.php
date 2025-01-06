<?php

namespace App\Api;

class Service
{
    /**
     * @var array
     */
    private $removeFields = ['guid', 'type', 'link', 'template', 'meta'];

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
     * Service constructor.
     */
    private function __construct()
    {
        add_action('pre_get_posts', [$this, 'preGetPosts']);
        add_filter('rest_request_before_callbacks', [$this, 'restRequestBeforeCallbacks'], 10, 3);
        add_filter('rest_request_after_callbacks', [$this, 'restRequestAfterCallbacks'], 10, 3);
        add_filter('rest_prepare_services', [$this, 'restPrepareServices'], 10, 3);
    }


    /**
     * @param WP_Query $query
     *
     * @return void
     */
    public function preGetPosts(\WP_Query $query)
    {
        $userID = get_current_user_id();

        if (is_admin() && user_can($userID, 'administrator')) {
            return;
        }

        if ('services' === $query->get('post_type')) {
            $query->set('author__in', [$userID]);
        }
    }

    /**
     * @param WP_REST_Response|WP_HTTP_Response|WP_Error|mixed $response
     * @param array $handler
     * @param \WP_REST_Request $request
     *
     * @return WP_REST_Response|WP_HTTP_Response|WP_Error|mixed
     */
    public function restRequestBeforeCallbacks($response, $handler, \WP_REST_Request $request)
    {
        $body = json_decode($request->get_body(), true);

        if (isset($body['fields'])) {
            $body['acf'] = $body['fields'];
            unset($body['fields']);

            $request->set_body(json_encode($body));
        }

        return $request;
    }

    /**
     * @param WP_REST_Response|WP_HTTP_Response|WP_Error|mixed $response
     * @param array $handler
     * @param \WP_REST_Request $request
     *
     * @return WP_REST_Response|WP_HTTP_Response|WP_Error|mixed
     */
    public function restRequestAfterCallbacks($response, $handler, \WP_REST_Request $request)
    {
        foreach ($response->data as $key => $value) {
            if (isset($value['title'])) {
                $value['title'] = $value['title']['rendered'];
            }

            if (isset($value['acf'])) {
                $value['fields'] = $value['acf'];
                unset($value['acf']);
            }


            foreach ($this->removeFields as $field) {
                if (isset($value[$field])) {
                    unset($value[$field]);
                }
            }

            if (is_array($value) && isset($value['_links'])) {
                unset($value['_links']);
            }

            $response->data[$key] = $value;
        }

        if ($request->get_method() === 'POST') {
            $this->changeFields($response);
        }

        return $response;
    }

    /**
     * @param \WP_REST_Response $response
     * @param \WP_Post $service
     * @param \WP_REST_Request $request
     *
     * @return \WP_REST_Response|\WP_Error
     */
    public function restPrepareServices(\WP_REST_Response $response, \WP_Post $service, \WP_REST_Request $request)
    {
        if (null === $request->get_param('id')) {
            return $response;
        }


        if (get_current_user_id() !== (int) get_post($service->ID)->post_author) {
            return new \WP_Error('rest_forbidden', esc_html__('Você não tem permissão para visualizar esse serviço.', 'api-first'), ['status' => 401]);
        }

        $this->changeFields($response);

        return $response;
    }

    /**
     * @param WP_REST_Response $response
     *
     * @return void
     */
    private function removeLinks(\WP_REST_Response $response)
    {
        foreach ($response->get_links() as $rel => $link) {
            $response->remove_link($rel);
        }
    }

    /**
     * @param  WP_REST_Response $response
     *
     * @return void
     */
    private function removeFields(\WP_REST_Response $response)
    {
        foreach ($this->removeFields as $field) {
            unset($response->data[$field]);
        }

        return $response;
    }

    /**
     * @param \WP_REST_Response $response
     *
     * @return \WP_REST_Response
     */
    private function changeFields(\WP_REST_Response $response)
    {
        if (isset($response->data['title']['rendered'])) {
            $response->data['title'] = $response->data['title']['rendered'];
        }

        if (isset($response->data['acf'])) {
            $response->data['fields'] = $response->data['acf'];
            unset($response->data['acf']);
        }

        $this->removeLinks($response);
        $this->removeFields($response);

        return $response;
    }
}
