<?php
namespace StockNotifier;

use StockNotifier\Admin\StockNotifierAdmin;
use StockNotifier\Managers\DataBaseManager;
use StockNotifier\Managers\ProductManager;

class StockNotifier
{
    public static $instance;
    public $db_manager;
    public $product_manager;
    public $admin;

    public function __construct()
    {
        $this->db_manager = new DataBaseManager();
        $this->product_manager = new ProductManager();
    }

    public function add_hook(){
        if(!$this->is_active()){
            return;
        }

        if($this->db_manager !== null){
            $this->db_manager->add_hook();
        }

        if($this->product_manager !== null){
            $this->product_manager->add_hook($this->db_manager);
        }

        if(is_admin()){
            $this->admin = new StockNotifierAdmin($this->db_manager);
        }

    }

    public function is_active()
    {
        return in_array(plugin_basename(SN_WP_PLUGIN_FILE), apply_filters('active_plugins', get_option('active_plugins')));
    }

    public static function get_instance()
    {

        if (self::$instance === null) {
            self::$instance = new StockNotifier();
        }

        return self::$instance;
    }


}