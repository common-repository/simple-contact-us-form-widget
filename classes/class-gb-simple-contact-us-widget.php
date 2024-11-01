<?php
/**
 * Display form fields
 *
 * @package gb-simple-contact-us-widget
 *
 * phpcs:disable Generic.CodeAnalysis.UnusedFunctionParameter
 * phpcs:disable Squiz.PHP.CommentedOutCode.Found
 * phpcs:disable WordPress.DB.DirectDatabaseQuery.DirectQuery
 * phpcs:disable WordPress.DB.DirectDatabaseQuery.NoCaching
 */

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

register_activation_hook( GBSCUW_SIMPLE_CONTACT_US_WIDGET_MAIN_FILE, array( 'GB_Simple_Contact_Us_Widget', 'gb_cnt_wdgt__activation' ) );
register_deactivation_hook( GBSCUW_SIMPLE_CONTACT_US_WIDGET_MAIN_FILE, array( 'GB_Simple_Contact_Us_Widget', 'gb_cnt_wdgt__deactivation' ) );
add_action( 'plugins_loaded', array( 'GB_Simple_Contact_Us_Widget', 'gb_cnt_wdgt__check_version' ) );
add_action( 'widgets_init', array( 'GB_Simple_Contact_Us_Widget', 'gb_cnt_wdgt__register_widget' ) );
add_action( 'plugins_loaded', array( 'GB_Simple_Contact_Us_Widget', 'register_my_session' ) );

/**
 * Let's the magic BEGIN
 */
class GB_Simple_Contact_Us_Widget extends WP_Widget {

    /**
     * Set $display_form variable.
     *
     * @var $display_form
     */
    private static $display_form = true;

    /**
     * Array holding all colors for a drop down
     *
     * @var $dd_config
     */
    private static $dd_config = array();

    /**
     * Register widget.
     */
    public function __construct() {

        parent::__construct(
            'GB_Simple_Contact_Us_Widget',
            esc_html__( 'Simple Contact Us Form Widget', 'gb-simple-contact-us-widget' ),
            array( 'description' => esc_html__( 'Simple Contact Us Widget to be added to sidebars or footer area, or any post / page ( as a shortcode )', 'gb-simple-contact-us-widget' ) )
        );
        add_action( 'wp_enqueue_scripts', array( get_class(), 'theme_enqueue_styles' ) );
        add_action( 'admin_enqueue_scripts', array( get_class(), 'admin_enqueue_styles' ) );
        add_action( 'wp_ajax_nopriv_ajax_form', array( get_class(), 'ajax_form' ) );
        add_action( 'wp_ajax_ajax_form', array( get_class(), 'ajax_form' ) );
        add_shortcode( 'gbsimple_contact_us_widget', array( get_class(), 'shortcode' ) );
        // add_action( 'in_widget_form', array( get_class(), 'return_class_name' ) ); .
    }

    /**
     * Return the class name assigned to widget,
     * so it is available throughout the class.
     *
     * @param array $instance Saved values from database.
     */
    public static function return_class_name( $instance ) {

        if ( '__i__' === $widget->number ) {
            $style = 'default';
        } else {
            $instances = get_option( 'widget_' . strtolower( get_class() ) );
            $style     = $instances[ $widget->number ]['style'];
        }

        return esc_attr( $style );
    }

    /**
     * Check if the current plugin's version number exists.
     */
    public static function gb_cnt_wdgt__check_version() {

        if ( GBSCUW_SIMPLE_CONTACT_US_WIDGET_VERSION !== get_option( strtolower( get_class() . '__version' ) ) ) {
            self::gb_cnt_wdgt__activation();
        }
    }

    /**
     * Apply plugins's setting on activation.
     */
    public static function gb_cnt_wdgt__activation() {
        update_option( strtolower( get_class() . '__version' ), GBSCUW_SIMPLE_CONTACT_US_WIDGET_VERSION );
    }

    /**
     * Delete plugins's setting on deactivation.
     */
    public static function gb_cnt_wdgt__deactivation() {

        global $wpdb;
        $wpdb->query( $wpdb->prepare( "DELETE FROM `{$wpdb->prefix}options` WHERE `option_name` LIKE %s", '%' . get_class() . '%' ) );
    }

    /**
     * Check if all the rules were fullfilled.
     */
    public static function validate_entries() {

        $validate_number = ! empty( $_POST['contact_us_form_widget-validate'] ) && sanitize_text_field( wp_unslash( $_POST['contact_us_form_widget-validate'] ) ) == $_SESSION['validate_entry']['w']  ? true : false;
        $nonce           = ! empty( $_POST['send-emil-simple-contact-us-widget-nonce'] ) ? sanitize_text_field( wp_unslash( $_POST['send-emil-simple-contact-us-widget-nonce'] ) ) : '';

        if ( isset( $_POST['contact_us_form_widget-name'] )
            && trim( sanitize_text_field( wp_unslash( $_POST['contact_us_form_widget-name'] ) ) ) !== ''
            && isset( $_POST['contact_us_form_widget-email'] )
            && trim( sanitize_email( wp_unslash( $_POST['contact_us_form_widget-email'] ) ) ) !== ''
            && isset( $_POST['contact_us_form_widget-email'] )
            && preg_match( '/^[_a-z0-9-]+(\.[_a-z0-9-]+)*@[a-z0-9-]+(\.[a-z0-9-]+)*(\.[a-z]{2,3})$/', trim( sanitize_email( wp_unslash( $_POST['contact_us_form_widget-email'] ) ) ) )
            && isset( $_POST['contact_us_form_widget-message'] )
            && trim( wp_kses_post( wp_unslash( $_POST['contact_us_form_widget-message'] ) ) ) !== ''
            && ! empty( $_POST['send-emil-simple-contact-us-widget-nonce'] )
            && wp_verify_nonce( $nonce, 'send-emil-simple-contact-us-widget' )
            && $validate_number
            && trim( sanitize_text_field( wp_unslash( $_POST['contact_us_form_widget-first_name'] ) ) ) == ''
        ) {
            return true;
        }

        return false;
    }

    /**
     * If the JS is enabled, send email via AJAX
     */
    public static function ajax_form() {

        if ( defined( 'DOING_AJAX' ) && DOING_AJAX ) {

            $nonce = ! empty( $_POST['send-emil-simple-contact-us-widget-nonce'] ) ? sanitize_text_field( wp_unslash( $_POST['send-emil-simple-contact-us-widget-nonce'] ) ) : '';

            if ( isset( $_POST['go'] ) && self::validate_entries() && ! empty( $_POST['send-emil-simple-contact-us-widget-nonce'] ) && wp_verify_nonce( $nonce, 'send-emil-simple-contact-us-widget' ) ) {
                self::deliver_mail();
            }

            self::the_form();
        }
        wp_die();
    }

    /**
     * Do the job - send the mail.
     */
    public static function deliver_mail() {

        $nonce       = ! empty( $_POST['send-emil-simple-contact-us-widget-nonce'] ) ? sanitize_text_field( wp_unslash( $_POST['send-emil-simple-contact-us-widget-nonce'] ) ) : '';
        $server_name = ! empty( $_SERVER['SERVER_NAME'] ) ? sanitize_text_field( wp_unslash( $_SERVER['SERVER_NAME'] ) ) : 'contact-us';

        if ( isset( $_POST['go'] ) && isset( $_POST['send-emil-simple-contact-us-widget-nonce'] )
        && wp_verify_nonce( $nonce, 'send-emil-simple-contact-us-widget' ) ) {

            // Sanitize form values.
            $name    = isset( $_POST['contact_us_form_widget-name'] ) ? sanitize_text_field( wp_unslash( $_POST['contact_us_form_widget-name'] ) ) : '';
            $email   = isset( $_POST['contact_us_form_widget-email'] ) ? sanitize_text_field( wp_unslash( $_POST['contact_us_form_widget-email'] ) ) : '';
            $message = isset( $_POST['contact_us_form_widget-message'] ) ? wp_kses_post( wp_unslash( $_POST['contact_us_form_widget-message'] ) ) : '';
            $message .= "\r\n\r\n\r\n\r\n-----------------------------------------\r" . $name . "\r" . $email . "\r" . esc_html__( '( Hit \'Reply\' button to answer ).', 'gb-simple-contact-us-widget' );
            $message .= "\r\n\r\nSimple Contact Us Widget";

            // Get the blog administrator's email address.
            $to = get_option( 'admin_email' );

            // Mimic the WordPress email address.
            $from_email = 'wordpress@' . $server_name;

            // Send email.
            $headers  = 'From: ' . $server_name . ' <' . $from_email . '>' . "\r\n";
            $headers .= 'Reply-To: ' . $name . ' <' . $email . '>' . "\r\n";
            $headers .= 'Return-Path: <' . $from_email . '>' . "\r\n";
            $headers .= 'Message-ID: <' . time() . ' TheSystem@' . $server_name . '>' . "\r\n";
            $headers .= 'X-Mailer: PHP v' . phpversion() . "\r\n";

            // If email has been processed for sending, send it, then display a success message and then suppress the form from being displayed again.
            $mail_result = wp_mail( $to, esc_html__( 'Message from Website.', 'gb-simple-contact-us-widget' ), $message, $headers );

            if ( $mail_result ) {
                // Display OK message.
                echo '<p>' . esc_html__( 'Thank you for contacting us!', 'gb-simple-contact-us-widget' ) . '</p>';
                echo '<p>' . esc_html__( 'We have received your email and we will respond to it as soon as possible.', 'gb-simple-contact-us-widget' ) . '</p>';

                // Kill the session.
                unset( $_SESSION['validate_entry'] );
            } else {
                echo 'Problem with sending email!';
            }

            self::$display_form = false;
        }
    }

    /**
     * Start PHP SESSION on plugins loaded hook
     */
    public static function register_my_session() {

        if ( ! session_id() ) {
            session_start();
        }
    }

    /**
     * Generate random string
     *
     * @param int $length Number of characters to be returned.
     *
     * @return int Generated string.
     */
    private static function generate_random_string( $length = 8 ) {

        $characters        = '12345679ABCabcdefhqzDEFGHJKMNPQRSkmnrstwvTUVWXYZ123456789';
        $characters_length = strlen( $characters );
        // $random_string     = '';

        for ( $i = 0; $i < $length; $i++ ) {
            $_SESSION['validate_entry']['chain'] .= $characters[ wp_rand( 0, $characters_length - 1 ) ];
        }
        // echo '<br>Chain: ' . $_SESSION['validate_entry']['chain'];

        $_SESSION['validate_entry']['rand_1'] = wp_rand( '1', '8' );
        $_SESSION['validate_entry']['rand_2'] = wp_rand( '1', '8' );

        if( $_SESSION['validate_entry']['rand_2'] == $_SESSION['validate_entry']['rand_1'] ) {
            $_SESSION['validate_entry']['rand_2'] = wp_rand( '1', '8' );
        }

        $_SESSION['validate_entry']['w'] = substr( $_SESSION['validate_entry']['chain'], $_SESSION['validate_entry']['rand_1'] - 1, 1 ) . substr( $_SESSION['validate_entry']['chain'], $_SESSION['validate_entry']['rand_2'] - 1, 1 );

        $export = $_SESSION['validate_entry'];
        return $export;
    }

    /**
     * Assign verification data to a SESSION
     */
    // public static function define_session_data() {

    //  $ops = array( '+', '-' );

    //  $_SESSION['validate_entry']['key']      = array_rand( $ops, 1 );
    //  $_SESSION['validate_entry']['rand_1']   = wp_rand( '3', '7' );
    //  $_SESSION['validate_entry']['rand_2']   = wp_rand( '1', '2' );
    //  $_SESSION['validate_entry']['operator'] = $ops[ $_SESSION['validate_entry']['key'] ];

    //  switch ( $_SESSION['validate_entry']['operator'] ) {
    //      case '+':
    //          $total = $_SESSION['validate_entry']['rand_1'] + $_SESSION['validate_entry']['rand_2'];
    //          break;

    //      case '-':
    //          $total = $_SESSION['validate_entry']['rand_1'] - $_SESSION['validate_entry']['rand_2'];
    //          break;
    //  }

    //  $_SESSION['validate_entry']['w'] = $total;
    //  $numbers                         = $_SESSION['validate_entry'];

    //  return $numbers;
    // }

    /**
     * Get the verification data
     */
    public static function export_session_data() {

        unset($_SESSION['validate_entry']);
        if ( empty( $_SESSION['validate_entry'] ) ) {
            $get_data = self::generate_random_string();
        } else {
            $get_data = $_SESSION['validate_entry'];
        }

        return $get_data;
    }

    /**
     * Display the contact form.
     *
     * @param string $default_style Passes a shortcode attribute to the function.
     */
    public static function the_form( $default_style = '' ) {

        $validation_data = self::export_session_data();
        $instances       = get_option( 'widget_' . strtolower( get_class() ) );

        if ( '' !== $default_style ) {
            $this_class = $default_style;
        } elseif ( empty( $instances ) ) {
            $this_class = 'default';
        } else {
            $keys       = array_keys( $instances );
            $this_class = $instances[ $keys[0] ]['style'];
        }

        $numbers = array(
            1 => esc_html__( 'first', 'gb-simple-contact-us-widget' ),
            2 => esc_html__( 'second', 'gb-simple-contact-us-widget' ),
            3 => esc_html__( 'third', 'gb-simple-contact-us-widget' ),
            4 => esc_html__( 'fourth', 'gb-simple-contact-us-widget' ),
            5 => esc_html__( 'fifth', 'gb-simple-contact-us-widget' ),
            6 => esc_html__( 'sixth', 'gb-simple-contact-us-widget' ),
            7 => esc_html__( 'seventh', 'gb-simple-contact-us-widget' ),
            8 => esc_html__( 'eighth', 'gb-simple-contact-us-widget' ),
        );

        echo '<div id="ajax_form">';

        if ( self::$display_form ) {
            $nonce = ! empty( $_POST['send-emil-simple-contact-us-widget-nonce'] ) ? sanitize_text_field( wp_unslash( $_POST['send-emil-simple-contact-us-widget-nonce'] ) ) : '';
            ?>

            <form action="#contact_us_form_widget" method="post" class="<?php echo esc_attr( $this_class ); ?>" id="contact_us_form_widget" name="contact_us_form_widget" novalidate="novalidate" data-ajax-file="<?php echo esc_url( plugin_dir_url( GBSCUW_SIMPLE_CONTACT_US_WIDGET_MAIN_FILE ) . 'assets/inc/' ); ?>">

                <div class="reload_form">sdfsdfsfs</div>

                <p class="contact_us_form_widget__label" id="first_name">First name<span class="req_field name">(<?php esc_html_e( 'required', 'gb-simple-contact-us-widget' ); ?>)</span>
                <input type="text" name="contact_us_form_widget-first_name" value=""></p>

                <p class="contact_us_form_widget__label"><?php esc_html_e( 'Name', 'gb-simple-contact-us-widget' ); ?> <span class="req_field name">(<?php esc_html_e( 'required', 'gb-simple-contact-us-widget' ); ?>)</span></p>
                <p><input type="text" name="contact_us_form_widget-name" value="<?php // phpcs:ignore Squiz.PHP.EmbeddedPhp.ContentBeforeOpen
                if ( isset( $_POST['contact_us_form_widget-name'] ) && ! empty( $_POST['send-emil-simple-contact-us-widget-nonce'] ) && wp_verify_nonce( $nonce, 'send-emil-simple-contact-us-widget' ) ) {
                    echo esc_html( sanitize_text_field( wp_unslash( $_POST['contact_us_form_widget-name'] ) ) );
                }
                // phpcs:ignore Squiz.PHP.EmbeddedPhp.ContentAfterEnd ?>" required>
                <?php
                if ( isset( $_POST['contact_us_form_widget-name'] ) && esc_html( sanitize_text_field( wp_unslash( $_POST['contact_us_form_widget-name'] ) ) ) === '' ) {
                    echo '<em class="contact_us_form_widget__f_req">' . esc_html__( 'This field is required', 'gb-simple-contact-us-widget' ) . '.</em>';}
                ?>
                </p>

                <p class="contact_us_form_widget__label"><?php esc_html_e( 'Email', 'gb-simple-contact-us-widget' ); ?> <span class="req_field email">(<?php esc_html_e( 'required', 'gb-simple-contact-us-widget' ); ?>)</span></p>
                <p><input type="text" name="contact_us_form_widget-email" value="<?php // phpcs:ignore Squiz.PHP.EmbeddedPhp.ContentBeforeOpen
                if ( isset( $_POST['contact_us_form_widget-email'] ) ) {
                    echo esc_html( sanitize_text_field( wp_unslash( $_POST['contact_us_form_widget-email'] ) ) );
                }
                // phpcs:ignore Squiz.PHP.EmbeddedPhp.ContentAfterEnd ?>" required>
                <?php
                if ( isset( $_POST['contact_us_form_widget-email'] ) && esc_html( sanitize_text_field( wp_unslash( $_POST['contact_us_form_widget-email'] ) ) ) === '' ) {
                    echo '<em class="contact_us_form_widget__f_req">' . esc_html__( 'Email is required', 'gb-simple-contact-us-widget' ) . '.</em>';
                } elseif ( isset( $_POST['contact_us_form_widget-email'] ) && ! preg_match( '/^[_a-z0-9-]+(\.[_a-z0-9-]+)*@[a-z0-9-]+(\.[a-z0-9-]+)*(\.[a-z]{2,3})$/', esc_html( sanitize_text_field( wp_unslash( $_POST['contact_us_form_widget-email'] ) ) ) ) ) {
                    echo '<em class="contact_us_form_widget__f_req">' . esc_html__( 'Email seems to be incorrect', 'gb-simple-contact-us-widget' ) . '.</em>';
                }
                ?>
                </p>

                <p class="contact_us_form_widget__label"><?php esc_html_e( 'Message', 'gb-simple-contact-us-widget' ); ?> <span class="req_field">(<?php esc_html_e( 'required', 'gb-simple-contact-us-widget' ); ?>)</span></p>
                <p><textarea rows="5" name="contact_us_form_widget-message"><?php // phpcs:ignore Squiz.PHP.EmbeddedPhp.ContentBeforeOpen
                if ( isset( $_POST['contact_us_form_widget-message'] ) ) {
                    echo esc_html( wp_kses_post( wp_unslash( $_POST['contact_us_form_widget-message'] ) ) );
                }
                // phpcs:ignore Squiz.PHP.EmbeddedPhp.ContentAfterEnd ?></textarea>
                <?php
                if ( isset( $_POST['contact_us_form_widget-message'] ) && esc_html( wp_kses_post( wp_unslash( $_POST['contact_us_form_widget-message'] ) ) ) === '' ) {
                    echo '<em class="contact_us_form_widget__f_req">' . esc_html__( 'Message is required', 'gb-simple-contact-us-widget' ) . '.</em>';}
                ?>
                </p>

                <div class="contact_us_form_widget__form_validation" title="<?php esc_html_e( 'Human verification', 'gb-simple-contact-us-widget' ); ?>">
                    <div class="contact_us_form_widget__label">

                        <?php
                            /* translators: %1$s and %2$s are replaced with the generated numbers */
                            $ver_copy = esc_html__( 'Re-type %1$s and %2$s character or number from the following string with no spaces (the string is case sensitive)', 'gb-simple-contact-us-widget' );
                            printf( '<p class="contact_us_form_widget__instruction">' . $ver_copy . ':</p>', '<b>' . $numbers[ esc_html( $validation_data['rand_1'] ) ] . '</b>', '<b>' . $numbers[ esc_html( $validation_data['rand_2'] ) ] . '</b>' ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
                            echo '<p class="contact_us_form_widget__move_slightly_to_right"><b>' . esc_html( $validation_data['chain'] ) . '</b></p>';
                        ?>
                        <input type="text" name="contact_us_form_widget-validate" value="" required></p>
                    </div>
                </div>

                <?php
                if ( isset( $_POST['contact_us_form_widget-validate'] ) && (string) $_POST['contact_us_form_widget-validate'] !== (string) $validation_data['w'] ) {
                    echo '<em class="contact_us_form_widget__f_req">' . esc_html__( 'Provide the proper string', 'gb-simple-contact-us-widget' ) . '!</em>';
                }

                    wp_nonce_field( 'send-emil-simple-contact-us-widget', 'send-emil-simple-contact-us-widget-nonce' );
                ?>
                <p>
                <input id="go" type="hidden" name="go" value="ok" />
                <input type="submit" name="contact_us_form_widget-submitted" value="<?php esc_html_e( 'Submit', 'gb-simple-contact-us-widget' ); ?>">
                </p>
            </form>
            <?php
        }
        echo '</div>';
    }

    /**
     * Outputs the content of the widget.
     *
     * @param array $args     Widget arguments.
     * @param array $instance Saved values from database.
     */
    public function widget( $args, $instance ) {

        $nonce = ! empty( $_POST['send-emil-simple-contact-us-widget-nonce'] ) ? sanitize_text_field( wp_unslash( $_POST['send-emil-simple-contact-us-widget-nonce'] ) ) : '';

        echo $args['before_widget']; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
        if ( ! empty( $instance['title'] ) ) {
            echo $args['before_title'] . apply_filters( 'widget_title', $instance['title'] ) . $args['after_title'];  // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
        }

        if ( isset( $_POST['go'] ) && $this->validate_entries() && ! empty( $_POST['send-emil-simple-contact-us-widget-nonce'] ) && wp_verify_nonce( $nonce, 'send-emil-simple-contact-us-widget' ) ) {
            $this->deliver_mail();
        }

        $this->the_form();
        echo $args['after_widget']; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
    }

    /**
     * Create shortcode so the widget can be displayed on posts and pages as well.
     *
     * @param array $atts Takes shortcode parameters as array.
     */
    public static function shortcode( $atts ) {

        ob_start();

        $args = shortcode_atts(
            array(
                'style' => '',
            ),
            $atts,
            'gbsimple_contact_us_widget'
        );

        $shortcode_style = ( '' === $args['style'] ) ? 'default' : esc_attr( $args['style'] );
        $nonce           = ! empty( $_POST['send-emil-simple-contact-us-widget-nonce'] ) ? sanitize_text_field( wp_unslash( $_POST['send-emil-simple-contact-us-widget-nonce'] ) ) : '';

        if ( isset( $_POST['go'] ) && self::validate_entries() && wp_verify_nonce( $nonce, 'send-emil-simple-contact-us-widget' ) ) {
            self::deliver_mail();
        }

        self::the_form( $shortcode_style );
        return ob_get_clean();
    }

    /**
     * Back-end widget form.
     *
     * @see WP_Widget::form()
     *
     * @param array $instance Previously saved values from database.
     */
    public function form( $instance ) {

        $dd_config = array(
            'default' => esc_html__( '-- default --', 'gb-simple-contact-us-widget' ),
            'red'     => esc_html__( 'Shades of RED', 'gb-simple-contact-us-widget' ),
            'green'   => esc_html__( 'Shades of GREEN', 'gb-simple-contact-us-widget' ),
            'blue'    => esc_html__( 'Shades of BLUE', 'gb-simple-contact-us-widget' ),
            'white'   => esc_html__( 'Shades of WHITE', 'gb-simple-contact-us-widget' ),
            'black'   => esc_html__( 'Shades of BLACK', 'gb-simple-contact-us-widget' ),
        );

        $title = ! empty( $instance['title'] ) ? $instance['title'] : esc_html__( 'Widget`s title', 'gb-simple-contact-us-widget' );
        $style = ! empty( $instance['style'] ) ? $instance['style'] : esc_html__( 'default', 'gb-simple-contact-us-widget' );
        ?>
        <p>
            <label for="<?php echo esc_attr( $this->get_field_id( 'title' ) ); ?>"><?php esc_attr_e( 'Title:', 'gb-simple-contact-us-widget' ); ?></label>
            <input class="widefat" id="<?php echo esc_attr( $this->get_field_id( 'title' ) ); ?>" name="<?php echo esc_attr( $this->get_field_name( 'title' ) ); ?>" type="text" value="<?php echo esc_attr( $title ); ?>">
        </p>
        <p>
            <label for="<?php echo esc_attr( $this->get_field_id( 'style' ) ); ?>"><?php esc_attr_e( 'Style:', 'gb-simple-contact-us-widget' ); ?></label>
            <select id="<?php echo esc_attr( $this->get_field_id( 'style' ) ); ?>" name="<?php echo esc_attr( $this->get_field_name( 'style' ) ); ?>" class="widefat" style="width:100%;">
            <?php foreach ( $dd_config as $theme_type => $val ) { ?>
                <option <?php selected( $style, esc_attr( $theme_type ) ); ?> value="<?php echo esc_attr( $theme_type ); ?>"><?php echo esc_attr( $val ); ?></option>
            <?php } ?>
            </select>
            <?php vprintf(
                '<span class="bg_examples">%s, <a href="%s" target="_blank">%s</a></span>',
                array(
                    esc_html__( 'To view examples of different backgrounds and/or shortcode usage', 'gb-simple-contact-us-widget' ),
                    esc_url( plugin_dir_url( GBSCUW_SIMPLE_CONTACT_US_WIDGET_MAIN_FILE ) . 'assets/examples.html' ),
                    esc_html__( 'please follow this link', 'gb-simple-contact-us-widget' ),
                )
            );
            ?>
        </p>
        <?php
    }

    /**
     * Enqueue scripts / styles
     */
    public static function theme_enqueue_styles() {

        wp_enqueue_style( 'gb-simple-contact-front', esc_url( plugin_dir_url( GBSCUW_SIMPLE_CONTACT_US_WIDGET_MAIN_FILE ) . 'assets/style/gb_contact_us_widget_front.css' ), array(), GBSCUW_SIMPLE_CONTACT_US_WIDGET_VERSION );
        wp_enqueue_script( 'gb-simple-contact', esc_url( plugin_dir_url( GBSCUW_SIMPLE_CONTACT_US_WIDGET_MAIN_FILE ) . 'assets/js/para.js' ), array( 'jquery' ), GBSCUW_SIMPLE_CONTACT_US_WIDGET_VERSION, true );
        wp_localize_script(
            'gb-simple-contact',
            'parajax',
            array(
                'ajax_url' => admin_url( 'admin-ajax.php' ),
            )
        );
    }

    /**
     * Enqueue scripts and styles - admin area.
     */
    public static function admin_enqueue_styles() {
        wp_enqueue_style( 'gb-simple-contact-admin', esc_url( plugin_dir_url( GBSCUW_SIMPLE_CONTACT_US_WIDGET_MAIN_FILE ) . 'assets/style/gb_contact_us_widget_admin.css' ), array(), GBSCUW_SIMPLE_CONTACT_US_WIDGET_VERSION );
    }

    /**
     * Register widget.
     */
    public static function gb_cnt_wdgt__register_widget() {
        register_widget( 'GB_Simple_Contact_Us_Widget' );
    }
}
