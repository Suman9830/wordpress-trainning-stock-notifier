<?php
namespace StockNotifier\Managers;

use Exception;

class DataBaseManager{
    const TABLE_NAME = 'stock_notifier_reminder';
    const DB_VERSION_OPTION_CHECKER = 's_n_db_version_checker_option';
    const DB_VERSION = '1.1';

    public function add_hook(){
        // check if woocommerce is active
        if (in_array('woocommerce/woocommerce.php', apply_filters('active_plugins', get_option('active_plugins')))) {
            add_action('admin_init', array($this, 'update_database_check'), 10, 2);
        }
    }

    public function update_database_check(){
        if ( ! get_site_option( self::DB_VERSION_OPTION_CHECKER ) ) {
            // Fresh install: create table.
            $this->create_table();
        }
//        else{
//            //check version
//            if(self::DB_VERSION != get_site_option(self::DB_VERSION_OPTION_CHECKER)){
//                $this->delete_table();
//                $this->create_table();
//            }
//        }
    }

    public function create_table(){
        global $wpdb;
        $table_name = self::get_the_table_name();

        $sql = "CREATE TABLE {$table_name}(
			id bigint(20) unsigned NOT NULL AUTO_INCREMENT,
			user_id bigint(20) unsigned NOT NULL DEFAULT 0,
			product_id bigint(20) unsigned NOT NULL DEFAULT 0,
			user_email varchar(200) NOT NULL DEFAULT '',
			prompted int unsigned NOT NULL DEFAULT 0,
			created_at datetime NOT NULL DEFAULT '0000-00-00 00:00:00',
			PRIMARY KEY  (id)
		)";

        require_once ABSPATH . 'wp-admin/includes/upgrade.php';
        dbDelta($sql);

        add_option(self::DB_VERSION_OPTION_CHECKER, self::DB_VERSION);
    }

//    public function delete_table() {
//        global $wpdb;
//
//        // Delete all checkouts at least 30 days old.
//        $table_name = self::get_the_table_name();
//
//        try{
//            $wpdb->query(
//                $wpdb->prepare(
//                    "DROP TABLE {$table_name}"
//                )
//            );
//        }catch(Exception $e){
//
//        }
//
//        add_option(self::DB_VERSION_OPTION_CHECKER, self::DB_VERSION);
//    }

    public function get_reminders(){
        global $wpdb;
        $table_name = self::get_the_table_name();

        return $wpdb->get_results(
            $wpdb->prepare("SELECT * FROM $table_name ", $table_name)
        );
    }

    public function get_product_filtered_reminders(){
        global $wpdb;
        $table_name = self::get_the_table_name();

        return $wpdb->get_results(
            $wpdb->prepare("SELECT *, COUNT(*) as `total_email` FROM $table_name GROUP BY `product_id` ", $table_name)
        );
    }

    public function get_emails_for_out_of_stock_product_id($product_id){
        global $wpdb;
        $table_name = self::get_the_table_name();

        $res = $wpdb->get_results(
            $wpdb->prepare("SELECT user_email FROM $table_name WHERE `product_id` = %d ", $product_id)
        );
        $res_emails = '';
        foreach ($res as $result){
            $res_emails .= $result->user_email.',';
        }
        return rtrim($res_emails, ',');
    }

    public function delete_record($id){
        global $wpdb;
        $table_name = self::get_the_table_name();

        return $wpdb->delete($table_name, array('id' => $id));
    }

    public function insert_data($user_id, $product_id, $user_email){
        global $wpdb;
        $table_name = self::get_the_table_name();

        $res = $wpdb->get_results(
            $wpdb->prepare(
                "SELECT * FROM {$table_name} "
                ."WHERE `user_email` = '{$user_email}' AND `product_id` = %d", $product_id
            )
        );
        if($res){
            if(count($res) > 0){
                $res = $wpdb->update(
                    $table_name,
                    array(
                        'prompted' => ++$res[0]->prompted
                    ),
                    array('id' => $res[0]->id)
                );
                return $res;
            }
        }else{
            $res = $wpdb->insert(
                $table_name,
                array(
                    'user_id' => $user_id,
                    'product_id' => $product_id,
                    'user_email' => $user_email,
                    'prompted' => 1,
                    'created_at' => date('Y-m-d H:i:s')
                )
            );

            return $res;
        }
        return false;
    }

    public static function get_the_table_name(){
        global $wpdb;
        return $wpdb->prefix . self::TABLE_NAME;
    }
}
