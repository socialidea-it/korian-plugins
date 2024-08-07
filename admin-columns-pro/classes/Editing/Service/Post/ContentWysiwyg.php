<?php

namespace ACP\Editing\Service\Post;

use ACP\Editing\Service\BasicStorage;
use ACP\Editing\Storage;
use ACP\Editing\View;

class ContentWysiwyg extends BasicStorage
{

    public function __construct()
    {
        parent::__construct(new Storage\Post\Field('post_content'));
    }

    public function get_view(string $context): ?View
    {
        return new View\Wysiwyg();
    }

    public function get_value(int $id)
    {
        return wpautop(parent::get_value($id));
    }

}