<?php
// Store all business logic here
class Admin
{
    private $plugin_name;

    private $version;

    public function __construct($plugin_name, $version)
    {
        $this->plugin_name = $plugin_name;
        $this->version = $version;
    }

    // Don't forget to hook your functions
    public function create_portal()
    {
        require_once plugin_dir_path(dirname(__FILE__)) . '/wp-cloner.php';
        // Instantiate the cloner plugin
        $portal_copy = new WP_Cloner();
    }
}
