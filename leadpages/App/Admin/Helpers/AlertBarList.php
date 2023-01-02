<?php namespace LeadpagesWP\Admin\Helpers;

use LeadpagesWP\models\LeadbarsModel;

class AlertBarList extends ListTable
{
    public $activeId = null;

    public function __construct()
    {
        parent::__construct(
            [
            'singular' => __('AlertBar', 'sp'),
            'plural'   => __('AlertBars', 'sp'),
            'ajax'     => false,
            ]
        );

        $this->activeId = LeadbarsModel::getActiveAlertBarId();
    }

    /**
     * Render the input to set active leadbar
     *
     * @param string $id        alert bar id
     * @param string $name      input name
     * @param bool   $isChecked true if active
     *
     * @return string
     */
    private function columnRadioInput($id, $name, $isChecked = false)
    {
        $checked = $isChecked ? ' checked="checked"' : '';
        return sprintf(
            '<input type="radio" id="row-%s" name="%s" value="%s" %s/>',
            $id,
            $name,
            $id,
            $checked
        );
    }

    public function get_columns()
    {
        return [
            'active' => 'Active',
            'name' => 'Title',
            'id' => 'ID',
            'domain' => 'Domain',
            'published' => 'Published',
        ];
    }

    public function get_sortable_columns()
    {
        return array();
    }

    public function column_default($item, $column_name)
    {
        return $item[$column_name];
    }


    public function prepare_items()
    {
        global $leadpagesApp;
        $columns = $this->get_columns();
        $hidden = ['id', 'domain'];
        $sortable = $this->get_sortable_columns();

        $this->_column_headers = [$columns, $hidden, $sortable];

        $response = $leadpagesApp['leadbarsApi']->getAllLeadbars();
        $json = json_decode($response['response'], true);
        $items = array_filter($json["_items"], [$this, "filterRow"]);
        $this->items = array_map([$this, 'processRow'], $items);


        $this->set_pagination_args(
            [
                "total_items" => count($this->items),
                "total_pages" => 1,
                "per_page" => count($this->items),
            ]
        );

        $this->items[] = $this->processRow(
            [
                'content' => [
                    'analyticsId' => '',
                    'name' => 'Disable Alert Bar',
                    'publicationDomain' => '',
                    'lastPublished' => false,
                ],
            ]
        );
    }

    public function filterRow($data)
    {
        return isset($data["content"]["lastPublished"]);
    }

    public function processRow($data)
    {
        $row = $data["content"];
        if (!isset($row["lastPublished"])) {
            return false;
        }

        $id = $row["analyticsId"];
        $isActive = $id == $this->activeId;

        return [
            'active' => $this->columnRadioInput($id, "active-alert-bar-id", $isActive),
            'id' => $id,
            'name' => '<label for="row-' . $id . '" >' . $row["name"] . '</label>',
            'domain' => $row["publicationDomain"],
            'published' => $row["lastPublished"]
                ? date("Y-m-d h:i:s", strtotime($row["lastPublished"])) : '-',
        ];
    }
}
