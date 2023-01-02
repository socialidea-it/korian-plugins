<?php
namespace LeadpagesWP\Helpers;

class PasswordProtected
{
    public $submittedPassword;

    public function __construct($db)
    {
        $this->db = $db;
    }

    public function getPostPassword($postId)
    {
        $post = get_post($postId);
        if (strlen($post->post_password) > 0 || !is_null($post->post_password)) {
            return $post->post_password;
        } else {
            return false;
        }
    }

    /**
     * Note: Unused vars that i'm not sure do anything
     */
    public function checkWPPasswordHash($post, $COOKIEHASH)
    {
        global $wp_hasher;
        if (empty($wp_hasher)) {
            include_once ABSPATH . 'wp-includes/class-phpass.php';
            $wp_hasher = new \PasswordHash(8, true);
        }

        $password = $this->getPostPassword($post);
        $hash = $wp_hasher->HashPassword($password);

        return isset($_COOKIE['wp-postpass_' . $COOKIEHASH]);
    }
}
