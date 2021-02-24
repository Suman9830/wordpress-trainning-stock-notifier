<?php
namespace StockNotifier\Admin;

use StockNotifier\Managers\DataBaseManager;

class StockNotifierAdmin
{
    /**
     * @var DataBaseManager
    */
    private $db;

    /**
     * @param DataBaseManager
    */
    public function __construct($db)
    {
        add_action('admin_menu', array($this, 'add_notifier_menu'), 100);
        add_action('admin_enqueue_scripts', array($this, 'enqueue_styles_scripts'));
        $this->db = $db;
    }

    public function add_notifier_menu(){
        add_submenu_page(
            'woocommerce',
            'Stock Notifier',
            'Notifier',
            'manage_woocommerce',
            's-n-report',
            array($this, 'stock_notifier_report_page')
        );
    }


    public function stock_notifier_report_page(){
        $allRecords = $this->db->get_product_filtered_reminders();
        ?>
        <div class="wrap">
            <h2>Notifier Report</h2>
            <br>
            <div class="sn__container">
                <h2>Reminders Set For</h2>
                <hr>
                <br>
                <?php
                if(count($allRecords) > 0){
                    foreach ($allRecords as $record){
                        $product_details = wc_get_product($record->product_id);
                        ?>
                        <div class="sn__card">
                            <div class="sn__row">
                                <div class="sn__col sn__card-image">
                                    <img src="<?php echo wp_get_attachment_image_url($product_details->get_image_id()); ?>" height=100 />
                                </div>
                                <div class="sn__col sn__card-text">
                                    Product Name: <b><?= $product_details->get_name()?></b>
                                    <br>
                                    Number Of Emails Subscribed: <b><?= $record->total_email?></b>
                                    <br>
                                    <br>
                                    <button class="sn__notifier_email_details"
                                            data-modal-id="sn__display-subscribed-emails"
                                            data-emails="<?= $this->db->get_emails_for_out_of_stock_product_id($record->product_id)?>">
                                        Details
                                    </button>
                                </div>
                            </div>
                        </div>
                        <?php
                    }
                    ?>
                    <div class="sn__hover-modal-container modal-hide" id="sn__display-subscribed-emails">
                        <div class="sn__hover-background">
                            <div class="sn__hover-modal">
                                <div class="sn__hover-modal-header">
                                    All Subscribed User Emails
                                </div>
                                <div class="sn__hover-modal-body" id="email-display-body">
                                </div>
                                <div class="sn__hover-modal-footer">
                                    <div class="sn__row">
                                        <div class="sn__col sn__text-right">
                                            <button class="sn__hover-modal-close" data-id="sn__display-subscribed-emails">Close</button>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                    <?php
                }
                else{
                    echo '<h3>No Data To Show</h3>';
                }
                ?>
            </div>
        </div>
        <?php
    }

    public function enqueue_styles_scripts(){
//        wp_enqueue_style('google-fonts', "//fonts.googleapis.com/css?family=Roboto+Condensed:300,300i,400,400i,700,700i|Roboto:100,300,400,400i,700,700i");
//        wp_enqueue_style('font-awesome', "//maxcdn.bootstrapcdn.com/font-awesome/4.7.0/css/font-awesome.min.css");
        wp_enqueue_style("BootStrap");
        wp_enqueue_style('stock-notifier-style-handler', plugins_url('stock-notifier/src/assets/css/admin-main.css'), NULL, microtime());
        wp_enqueue_script("jQuery");
        wp_enqueue_script("stock-notifier-script-handler", plugins_url("stock-notifier/src/assets/js/scripts-bundled.js"), NULL, microtime(),true);
    }

}