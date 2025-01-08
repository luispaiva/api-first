<?php

namespace App\Api;

/**
 * Class Attachment
 */
class Attachment
{
    /**
     * Post type
     *
     * @var string
     */
    private $postType = 'attachment';

    /**
     * Remove fields
     *
     * @var array
     */
    private $fields = [
        'guid',
        'ping_status',
        'comment_status',
        'meta',
        'featured_media',
        'status',
        'acf',
        'class_list',
        'date',
        'date_gmt',
        'modified',
        'modified_gmt',
        'post',
        'template'
    ];

    /**
     * Instance
     *
     * @var null
     */
    private static $instance = null;

    /**
     * Get instance
     *
     * @return Attachment
     */
    public static function getInstance()
    {
        if (self::$instance === null) {
            self::$instance = new self();
        }
        return self::$instance;
    }

    /**
     * Constructor
     */
    private function __construct()
    {
        // $this->removeFields = array_merge($this->removeFields, $this->fields);

        add_action('pre_get_posts', array( $this,'preGetPosts'));
        // add_filter('ajax_query_attachments_args', array( $this, 'returnOnlyMediaSubscriber' ));
        add_filter('rest_prepare_' . $this->postType, array( $this,'restPrepare'));
    }

    /**
     * Pre get posts
     *
     * @param \WP_Query $query
     */
    public function preGetPosts(\WP_Query $query)
    {
        if (is_admin() && user_can(get_current_user_id(), 'administrator')) {
            return;
        }

        if ($this->postType === $query->get('post_type')) {
            $query->set('author__in', [get_current_user_id()]);
        }
    }

    /**
     * Rest prepare property
     *
     * @param WP_REST_Response $response
     *
     * @return
     */
    public function restPrepare($response)
    {
        $this->removeFields($response);
        $this->removeLinks($response);

        if (is_array($response->data['title']) && isset($response->data['title']['rendered'])) {
            $response->data['title'] = sanitize_text_field($response->data['title']['rendered']);
        }

        if (is_array($response->data['description']) && isset($response->data['description']['rendered'])) {
            $response->data['description'] = sanitize_text_field($response->data['description']['rendered']);
        }

        if (is_array($response->data['caption']) && isset($response->data['caption']['rendered'])) {
            $response->data['caption'] = sanitize_text_field($response->data['caption']['rendered']);
        }

        $response->data['sizes'] = $response->data['media_details']['sizes'];

        unset($response->data['media_details']['image_meta']);
        unset($response->data['media_details']['width']);
        unset($response->data['media_details']['height']);
        unset($response->data['media_details']['file']);
        unset($response->data['media_details']['filesize']);
        unset($response->data['media_details']);

        return $response;
    }

    /**
     * Remove links
     *
     * @param mixed $response
     *
     * @return void
     */
    protected function removeLinks($response)
    {
        foreach ($response->get_links() as $_linkKey => $_linkVal) {
            $response->remove_link($_linkKey);
        }
    }

    /**
     * Remove fields
     *
     * @param WP_REST_Response $response
     *
     * @return void
     */
    protected function removeFields($response)
    {
        foreach ($this->fields as $_field) {
            unset($response->data[$_field]);
        }
    }
}
