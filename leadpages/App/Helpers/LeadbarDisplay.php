<?php

namespace LeadpagesWP\Helpers;

use LeadpagesWP\models\Leadbars;

trait LeadbarDisplay
{
    protected function disallowedPostTypes()
    {
        return [
          'leadpages_post',
            //woocomerce
          'product_variation',
          'shop_order',
          'shop_order_refund',
          'shop_coupon',
          'shop_webhook',
          'user_request',
          'oembed_cache',
          'custom_css',
          'customize_changeset',
        ];
    }
}
