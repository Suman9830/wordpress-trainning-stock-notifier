<?php


namespace StockNotifier\Managers;


use Automattic\Jetpack\Config;
use PHPMailer\PHPMailer\Exception;
use WC_Email;

class ProductManager
{

    public static $form_submitted = false;
    public static $form_errors = [];
    public $REMINDER_PRODUCT_ID = 'reminder_sn_product_id';
    public $REMINDER_USER_ID = 'reminder_sn_user_id';

    private $current_product;
    private $product_id;
    private $product_type;
    private $db;

    public static $allowed_product_types = array(
        'simple',
        'variation',
    );

    public function __construct(){
        global $product;
        $this->current_product = $product;
    }

    public function add_hook($db){
        if($this->db == null){
            $this->db = $db;
        }
        add_action('woocommerce_before_main_content', [$this, 'get_the_form']);
        add_action('wp', [$this, 'handle_form_submission']);
        add_action('init', [$this, 'search_for_if_any_stock_back']);
    }

    public function search_for_if_any_stock_back(){
        if($this->db != null){
            $all_products = $this->db->get_reminders();
            if($all_products){
                foreach ($all_products as $pro){
                    $product_details = wc_get_product($pro->product_id);
                    if($product_details){
                        if($product_details->is_in_stock()){
                            $res = wp_mail($pro->user_email,
                                "Product Back In Stock Remainder",
                                "Your Product got back<br><a href='".get_permalink($pro->product_id)."'>Click Here</a><br>",
                            '',
                            []
                            );
                            if($res){
                                //deleted
                                $this->db->delete_record($pro->id);
                            }
                        }
                    }
//                    else{
//                        //deleted
//                        $this->db->delete_record($pro->id);
//                    }
                }
            }

        }else{
            throw new Exception('Get db null');
        }
    }

    public function get_the_form(){
        global $post;

        ?>
<!--        <div style="padding: 10px;background-color:red;position:fixed;z-index: 1002;">HI Working</div>-->
        <?php

        $post_id = $post->ID;

        if(get_post_type($post_id) == 'product' && is_product()){
            $this->current_product = wc_get_product($post_id);


            if( $this->current_product->product_type === 'grouped'){
                return;
            }

            $args = $this->current_product->product_type === 'variable' ? 5 : 3;

            add_action('woocommerce_stock_html', [$this, 'form_output'], 20 , $args);
        }
    }

    public function handle_form_submission(){
        if(isset($_REQUEST['_wpnonce'])){
            if(wp_verify_nonce($_REQUEST['_wpnonce'], 'action_notify_on_stock')){
                if(isset($_REQUEST[$this->REMINDER_USER_ID], $_REQUEST[$this->REMINDER_PRODUCT_ID],
                    $_REQUEST['sn_email_notifier'])){
                    //get product id & user_id
                    $product_id = intval($_REQUEST[$this->REMINDER_PRODUCT_ID]);
                    $user_id = intval($_REQUEST[$this->REMINDER_USER_ID]);
                    $email_id = filter_var($_REQUEST['sn_email_notifier'], FILTER_SANITIZE_EMAIL);

                    if($user_id > 0){
                        $user_details = get_userdata($user_id);
                        if($user_details){
                            $mailer = WC()->mailer();
                            $flag = get_option('woocommerce_email_from_address');
                            update_option('woocommerce_email_from_address', 'test@greatcareers.in');
                            $res = $mailer->send($user_details->user_email, "Hi "
                                .$user_details->display_name.", You Have Subscribed for Product id = ".$product_id,
                                $this->get_custom_email_html("Hi "
                                    .$user_details->display_name.", You Have Subscribed for Product id = ".$product_id, $mailer),
                                "Content-Type: text/html\r\n");
                            file_put_contents('success_on_send.txt', json_encode($res), FILE_APPEND);
                            update_option('woocommerce_email_from_address', $flag);
                        }
                    }

                    if(!$email_id){
                        self::$form_errors[] = "Please Enter A Valid Email Id";
                    }else{
                        if($this->db->insert_data($user_id, $product_id, $email_id)){
                            self::$form_errors[] = "You have SubScribed for this Product Successfully";
                        }else{
                            self::$form_errors[] = "Unable To Subscribe Please Try Again Later";
                        }

                    }

                    add_action('before_notifier_form', [$this, 'notify_before']);
                    add_action('after_notifier_form', [$this, 'notify_after']);
                    self::$form_submitted = true;
                }
            }
        }
    }

    public function get_custom_email_html($heading, $mailler){
        return wc_get_template_html(
                'emails/test.php', array('email' => $mailler, "email_heading" => $heading)
        );
    }

    public function notify_before(){
        ?>
        <div class="container">
            <?php
            foreach(self::$form_errors as $msg){
                echo "<div>$msg</div>";
            }
            ?>
        </div>
        <?php
    }

    public function notify_after(){
        self::$form_submitted = false;
        self::$form_errors = [];
    }

    public function form_output($html, $availability, $product = false){
        if(!$product){
            $product = $this->current_product;
        }

        if($product->is_in_stock()){
           return $html;
        }

        return $this->form_data($product, $html);
    }

    public function form_data($product, $html){
        if(!isset($product) || $product->is_in_stock()){
            return $html;
        }

        $product_type = $product->product_type;

        $product_id = ('simple' === $product_type )
            ? $product->id : $product->variation_id;

        $product_url = 'simple' === $product_type
            ? get_permalink($product->id)
            : get_permalink($product->parent->id);

        $this->product_id = $product_id;

        $url = wp_nonce_url($product_url, 'action_notify_on_stock');
        $url = add_query_arg($this->REMINDER_PRODUCT_ID, $product_id, $url);

        ob_start();

        $is_logged_in = is_user_logged_in();

        if($is_logged_in){
            global $current_user;
            $url = add_query_arg($this->REMINDER_USER_ID, $current_user->ID, $url);
        }

        do_action('before_notifier_form', $product, $is_logged_in);
        if(!self::$form_submitted){
        ?>
        <form action="" method="post">
            <div class="sn-user-form">
                <div class="sn-user-form__content">
                    <div class="sn-user-form__message">
                        <div class="sn-user-form__message-item">
                            <h4 style="color: orangered">Out Of Stock</h4>
                            <?php
                            //                        echo apply_filters('sn-product-out-of-stock', 'Out of Stock', $is_logged_in);
                            ?>
                        </div>
                        <div class="sn-user-form__message-item">
                            <h5>Leave You Email so we can email when products comes back.</h5>
                        </div>
                    </div>
                    <div class="sn-user-form__field">
                        <input type="hidden" name="_wpnonce"
                               value="<?php echo wp_create_nonce('action_notify_on_stock')?>">
                        <input type="hidden" name="<?php echo $this->REMINDER_PRODUCT_ID;?>"
                               value="<?php echo $product_id; ?>">
                        <input type="hidden" name="<?php echo $this->REMINDER_USER_ID;?>"
                               value="<?php                             if($is_logged_in){
                                   global $current_user;
                                   echo $current_user->ID;
                               }else{
                                   echo '0';
                               }?>"/>
                        <input type="email"
                               placeholder="Enter Your Email Address..."
                               id="sn-email-for-notification"
                               name="sn_email_notifier"
                               class="sn-user-form__field-email"
                               required />
                        <button class="sn-user-form__field-submit-btn" type="submit">Notify</button>
                    </div>
                </div>
            </div>
        </form>

        <?php
        }
        do_action('after_notifier_form', $product, $is_logged_in);

        return ob_get_clean();
    }

}