<?php

class WPEN_Admin_Settings {

    private $wpen_email_options;
    private $wpen_cpt_options;

    public function __construct() {
        add_action( 'admin_menu', array( $this, 'wpen_admin_menu' ) );
        add_action( 'admin_init', array( $this, 'wpen_admin_page' ) );
        add_action( 'update_option_wpen_email', array( $this, 'wpen_update_cron_time' ), 10, 3 );

        add_filter( 'plugin_action_links_wen-post-expiry-notification/wen-post-expiry-notification.php', array( $this, 'wpen_action_setting_links' ) );
    }

    public function wpen_update_cron_time( $old_value, $value, $option ) {
        wp_clear_scheduled_hook( 'wpen_check_daily_for_post_expiry' );
        if( !wp_next_scheduled( 'wpen_check_daily_for_post_expiry' ) ) {
            wp_schedule_event( strtotime( $value['wpen_cron_time'] ), 'daily', 'wpen_check_daily_for_post_expiry' );
        }
    }

    /*
     * Setting link
    */
    public function wpen_action_setting_links( $links ) {
        (array) $links[] = '<a href="' . esc_url( admin_url( 'options-general.php?page=wpen' ) ) . '">' . __( 'Settings', 'wen-post-expiry-notification' ) . '</a>';
        return $links;
    }

    /*
    * create the admin menu
    */
    public function wpen_admin_menu() {
        add_options_page(
            'WEN Post Expiry Notification',
            'WEN Post Expiry Notification',
            'manage_options',
            'wpen',
            array( $this, 'create_admin_page' )
        );
    }

    public function create_admin_page() {
        $this->wpen_email_options    = get_option( 'wpen_email' );
        $this->wpen_cpt_options      = get_option( 'wpen_cpt' );
        ?>
        <div class="wrap">
            <h1><?php _e( 'WEN Post Expiry Notification', 'wen-post-expiry-notification' ); ?></h1>
            
            <?php
            if( isset( $_GET['tab'] ) ) {
                $active_tab = $_GET['tab'];
            } else {
                $active_tab = 'email_options';
            }
            ?>

            <h2 class="nav-tab-wrapper">
                <a href="?page=wpen&tab=email_options" class="nav-tab <?php echo $active_tab == 'email_options' ? 'nav-tab-active' : ''; ?>"><?php _e ( 'Email Options', 'wen-post-expiry-notification' ); ?></a>
                <a href="?page=wpen&tab=cpt_options" class="nav-tab <?php echo $active_tab == 'cpt_options' ? 'nav-tab-active' : ''; ?>"><?php _e ( 'CPT Options', 'wen-post-expiry-notification' ); ?></a>
            </h2>
            <form method="post" action="options.php">
               <?php
               if( $active_tab == 'email_options' ) {
                    settings_fields( 'wpen_email_options_group' );
                    do_settings_sections( 'wpen_email_notify' );
                } else if ($active_tab == 'cpt_options'){
                    settings_fields( 'wpen_cpt_options_group' );
                    do_settings_sections( 'wpen_cpt_support' );
                } else {
                    settings_fields( 'wpen_email_options_group' );
                    do_settings_sections( 'wpen_email_notify' );
                }

                submit_button();
                ?>
            </form>
        </div>
        <?php
    }

    public function wpen_admin_page() {

        //Email Setting
        register_setting(
            'wpen_email_options_group',
            'wpen_email',
            array( 'sanitize_callback' => array( $this, 'wpen_sanitize_email_option_fields' ) )
        );

        //CPT Setting
        register_setting(
            'wpen_cpt_options_group',
            'wpen_cpt'
        );

        add_settings_section(
            'email_settings',
            __( 'Email Settings', 'wen-post-expiry-notification' ),
            '',
            'wpen_email_notify'
        );

        add_settings_field(
            'wpen_cron_time',
            __( 'Run Cron At', 'wen-post-expiry-notification' ),
            array( $this, 'wpen_set_cron_time' ),
            'wpen_email_notify',
            'email_settings'
        );

        add_settings_field(
            'wpen_send_before_days',
            __( 'Send Notification Before', 'wen-post-expiry-notification' ),
            array( $this, 'wpen_send_notification_before' ),
            'wpen_email_notify',
            'email_settings'
        );

        add_settings_field(
            'email_from_name',
            __( 'From Name', 'wen-post-expiry-notification' ),
            array( $this, 'wpen_email_from_name_field' ),
            'wpen_email_notify',
            'email_settings'
        );

        add_settings_field(
            'email_from',
            __( 'From Email', 'wen-post-expiry-notification' ),
            array( $this, 'wpen_email_from_field' ),
            'wpen_email_notify',
            'email_settings'
        );

        add_settings_field(
            'email_to',
            __( 'Recipient Email (To)', 'wen-post-expiry-notification' ),
            array( $this, 'wpen_email_to_field' ),
            'wpen_email_notify',
            'email_settings'
        );

        add_settings_field(
            'email_cc',
            __( 'Recipient Email (CC)', 'wen-post-expiry-notification' ),
            array( $this, 'wpen_email_cc_field' ),
            'wpen_email_notify',
            'email_settings'
        );

        add_settings_field(
            'email_subject',
            __( 'Subject', 'wen-post-expiry-notification' ),
            array( $this, 'wpen_email_subject_field' ),
            'wpen_email_notify',
            'email_settings'
        );

        add_settings_field(
            'email_template',
            __( 'Email Template', 'wen-post-expiry-notification' ),
            array( $this, 'wpen_email_template_field' ),
            'wpen_email_notify',
            'email_settings'
        );

        //Cpt Setting
        add_settings_section(
            'cpt_settings',
            __( 'CPT Support', 'wen-post-expiry-notification' ),
            '',
            'wpen_cpt_support'
        );

        add_settings_field(
            'enable_cpt',
            __( 'Enable For', 'wen-post-expiry-notification' ),
            array( $this, 'wpen_cpt_support' ),
            'wpen_cpt_support',
            'cpt_settings'
        );
    }


    public function wpen_set_cron_time() {
        printf(
            '<input type="text" name="wpen_email[wpen_cron_time]" class="regular-text" id="wpen_cron_time" min="1" value="%s"/>',
            !empty( $this->wpen_email_options['wpen_cron_time'] ) ? esc_attr( $this->wpen_email_options['wpen_cron_time'] ) : '08:00:00'
        );
        echo '<p class="description">' . __( 'Format', 'wen-post-expiry-notification' ) . ': <code>HH:MM:SS</code></p>';
    }

    public function wpen_send_notification_before() {
        printf(
            '<input type="number" name="wpen_email[wpen_send_before_days]" class="regular-text" id="wpen_send_before_days" min="1" value="%s"/>',
            !empty( $this->wpen_email_options['wpen_send_before_days'] ) ? esc_attr( $this->wpen_email_options['wpen_send_before_days'] ) : '1'
        );
        echo '<p class="description">' . __( 'Enter number of days.', 'wen-post-expiry-notification' ) . '</p>';
    }

    public function wpen_email_from_name_field() {
        printf(
            '<input type="text" class="regular-text" id="email_from_name" name="wpen_email[email_from_name]" value="%s"/>',
            isset( $this->wpen_email_options['email_from_name'] ) ? esc_attr( $this->wpen_email_options['email_from_name'] ) : ''
        );
    }

    public function wpen_email_from_field() {
        printf(
            '<input type="email" class="regular-text" id="email_from" name="wpen_email[email_from]" value="%s"/>',
            isset( $this->wpen_email_options['email_from'] ) ? esc_attr( $this->wpen_email_options['email_from'] ) : ''
        );
    }

    public function wpen_email_to_field() {
        printf(
            '<input type="email" class="regular-text" id="email_to" name="wpen_email[email_to]" value="%s"/>',
            isset( $this->wpen_email_options['email_to'] ) ? esc_attr( $this->wpen_email_options['email_to'] ) : ''
        );
    }

    public function wpen_email_cc_field() {
        printf(
            '<input type="email" multiple class="regular-text" id="email_cc" name="wpen_email[email_cc]" value="%s"/>',
            isset( $this->wpen_email_options['email_cc'] ) ? esc_attr( $this->wpen_email_options['email_cc'] ) : ''
        );
        echo '<p class="description">' . __( 'Separate multiple recipients by comma (,).', 'wen-post-expiry-notification' ) . '</p>';
    }

    public function wpen_email_subject_field() {
        printf(
            '<input type="text" class="regular-text" id="email_subject" name="wpen_email[email_subject]" value="%s"/>',
            isset( $this->wpen_email_options['email_subject'] ) ? esc_attr( $this->wpen_email_options['email_subject'] ) : ''
        );
    }

    public function wpen_email_template_field() {
        $email_template = isset( $this->wpen_email_options['email_template'] ) ? $this->wpen_email_options['email_template'] : '';
        $editor_id = 'email_template';
        $settings = array( 'media_buttons' => false, 'textarea_rows' => 15, 'editor_height' => 250, 'textarea_name' => 'wpen_email[email_template]' );
        wp_editor( $email_template , $editor_id, $settings );
        echo '<p class="description">' . __( 'Available placeholders', 'wen-post-expiry-notification' ) . ':</p>';
        echo '<p class="description">%post_title% : ' . __( 'Post title.', 'wen-post-expiry-notification' ) . '</p>';
        echo '<p class="description">%expiry_date% : ' . __( 'Post expiry date.', 'wen-post-expiry-notification' ) . '</p>';
        echo '<p class="description">%site_url% : ' . __( 'Site URL.', 'wen-post-expiry-notification' ) . '</p>';
        echo '<p class="description">%site_title% : ' . __( 'Site title.', 'wen-post-expiry-notification' ) . '</p>';
    }

    public function wpen_sanitize_email_option_fields( $input ) {
        $email_input = array();
        if( isset( $input['wpen_cron_time'] ) ){
            $email_input['wpen_cron_time'] = sanitize_text_field( $input['wpen_cron_time'] );
        }

        if( isset( $input['wpen_send_before_days'] ) ){
            $email_input['wpen_send_before_days'] = sanitize_text_field( $input['wpen_send_before_days'] );
        }

        if( isset( $input['email_from_name'] ) ){
            $email_input['email_from_name'] = sanitize_text_field( $input['email_from_name'] );
        }

        if( isset( $input['email_from'] ) ){
            $email_input['email_from'] = sanitize_text_field( $input['email_from'] );
        }

        if( isset( $input['email_to'] ) ){
            $email_input['email_to'] = sanitize_text_field( $input['email_to'] );
        }

        if( isset( $input['email_cc'] ) ){
            $email_input['email_cc'] = sanitize_text_field( $input['email_cc'] );
        }

        if( isset( $input['email_subject'] ) ){
            $email_input['email_subject'] = sanitize_text_field( $input['email_subject'] );
        }

        if( isset( $input['email_template'] ) ){
            $email_input['email_template'] = wpautop( $input['email_template'] );
        }

        return $email_input;
    }

    public function wpen_cpt_support() {
        $post_types = get_post_types( array (
            'show_ui' => true,
            'show_in_menu' => true,
        ), 'objects' );
        foreach ( $post_types  as $post_type ) {
            if ( $post_type->name == 'attachment' ) {
                continue;
            }   
            $cpt_selected = $this->wpen_cpt_options ? $this->wpen_cpt_options : array();
            $checked = in_array($post_type->name, $cpt_selected);

            printf(
                '<label><input type="checkbox" name="wpen_cpt[]" value="'.$post_type->name.'" %s>'.
                $post_type->label.'</label><br>',
                $checked ? "checked" : ''
            );
        }
    }

}

new WPEN_Admin_Settings();