<?php
namespace LeadpagesWP\Helpers;

class LeadpageType
{
    public static function getFrontLeadpage()
    {
        $v = get_option('leadpages_front_page_id', false);
        return ($v == '') ? false : $v;
    }

    public static function setFrontLeadpage($id)
    {
        update_option('leadpages_front_page_id', $id);
    }

    public static function isFrontPage($id)
    {
        $front = self::getFrontLeadpage();
        return ($id == $front && $front !== false);
    }

    public static function getWelcomeGate()
    {
        $v = get_option('leadpages_wg_page_id', false);
        return ($v == '') ? false : $v;
    }

    public static function setWelcomeGate($id)
    {
        update_option('leadpages_wg_page_id', $id);
    }

    public static function get404Leadpage()
    {
        $v = get_option('leadpages_404_page_id', false);
        return ($v == '') ? false : $v;
    }

    public static function set404Leadpage($id)
    {
        update_option('leadpages_404_page_id', $id);
    }

    public static function isNotFoundPage($id)
    {
        $notFoundId = self::get404Leadpage();
        return $id == $notFoundId && $notFoundId !== false;
    }

    /**
     * Render html procedure
     *
     * Hide the implementation details and allow for
     * a single point to make global changes to html
     * from the plugin.
     *
     * @param string $html        page html
     * @param int    $status_code 200 | 404
     *
     * @return string
     */
    public static function renderHtml($html, $status_code = 200)
    {
        if (ob_get_length() > 0) {
            ob_clean();
        }

        ob_start([get_called_class(), "preprocessHtml"]);

        status_header($status_code);
        echo $html;

        ob_end_flush();
    }

    /**
     * Output buffering stack of callbacks
     *
     * @param string $content html
     *
     * @return string
     */
    public static function preprocessHtml($content)
    {
        $html = self::modifyServingTags($content);
        $html = self::modifyOgUrlTag($html);
        return $html;
    }

    /**
     * Output buffering callback to add serving-tags meta for analytics
     *
     * @param string $content html from output buffer
     *
     * @return string
     */
    public static function modifyServingTags($content)
    {
        $search = '</head>';
        $replace = '<meta name="leadpages-serving-tags" content="wordpress"></head>';
        return str_replace($search, $replace, $content);
    }

    /**
     * Output buffering callback for meta og:url
     *
     * @param string $content html from output buffer
     *
     * @return string
     */
    public static function modifyOgUrlTag($content)
    {
        $regex = '/(<meta property="og:url" content=")[^"]+(">)/';
        $url = self::getCurrentUrl();
        return preg_replace($regex, '${1}' . $url . '${2}', $content);
    }

    /**
     * Get current page url
     *
     * @return string
     */
    public static function getCurrentUrl()
    {
        try {
            global $wp;
            if (empty($wp)) {
                throw new \Exception("Missing wp global object");
            }
        } catch (\Exception $e) {
            return 'https://' . $_SERVER['HTTP_HOST'] . $_SERVER['REQUEST_URI'];
        }

        return home_url($wp->request);
    }

    /**
     * Wrap die() for documentation sake
     *
     * Removed die() from renderHtml for testability
     *
     * @return none
     */
    public static function preventDefault()
    {
        die();
    }
}
