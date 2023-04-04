<?php
function call_postExpiryMetaBoxes() {
    new postExpiryMetaBoxes();
}

if ( is_admin() ) {
    add_action( 'load-post.php', 'call_postExpiryMetaBoxes' );
    add_action( 'load-post-new.php', 'call_postExpiryMetaBoxes' );
}

class postExpiryMetaBoxes {

    public function __construct() {
        add_action( 'add_meta_boxes', array( $this, 'wpen_add_post_meta_box' ) );
        add_action( 'save_post', array( $this, 'wpen_save_post_options' ) );
    }

    public function wpen_add_post_meta_box( $post_type ) {
        $selected_post_types = get_option( 'wpen_cpt' ) ? get_option( 'wpen_cpt' ): array();

        if ( in_array( $post_type, $selected_post_types ) ) {
            add_meta_box(
                'post_expiry_meta_box',
                __( 'Expiry Settings', 'wen-post-expiry-notification' ),
                array( $this, 'render_meta_box_content' ),
                $post_type,
                'advanced',
                'high'
            );
        }
    }

    public function wpen_save_post_options( $post_id ) {

        if ( ! isset( $_POST['post_expiry_custom_box_nonce'] ) ) {
            return $post_id;
        }

        $nonce = $_POST['post_expiry_custom_box_nonce'];

        if ( ! wp_verify_nonce( $nonce, 'post_expiry_custom_box' ) ) {
            return $post_id;
        }


        if ( defined( 'DOING_AUTOSAVE' ) && DOING_AUTOSAVE ) {
            return $post_id;
        }

        if ( 'page' == $_POST['post_type'] ) {
            if ( ! current_user_can( 'edit_page', $post_id ) ) {
                return $post_id;
            }
        } else {
            if ( ! current_user_can( 'edit_post', $post_id ) ) {
                return $post_id;
            }
        }

        $expiry_date = sanitize_text_field( $_POST['date-picker'] );
        $email_recepient_data_to = sanitize_email( ( $_POST['email-recepient-to'] ) );
        $email_recepient_data_cc = sanitize_text_field( ( $_POST['email-recepient-cc'] ) );
        $email_subject = sanitize_text_field( ( $_POST['email-subject'] ) );
        $email_template = wp_kses_post( $_POST['email-template'] ) ;

        update_post_meta( $post_id, '_wen_post_expiry_date', $expiry_date );
        update_post_meta( $post_id, '_wen_post_email_recepient_to', $email_recepient_data_to );
        update_post_meta( $post_id, '_wen_post_email_recepient_cc', $email_recepient_data_cc );
        update_post_meta( $post_id, '_wen_post_email_subject', $email_subject );
        update_post_meta( $post_id, '_wen_email_template', $email_template );
    }


    public function render_meta_box_content( $post ) {

        wp_nonce_field( 'post_expiry_custom_box', 'post_expiry_custom_box_nonce' );

        $expiry_date = get_post_meta( $post->ID, '_wen_post_expiry_date', true );
        $email_recepient_to = get_post_meta( $post->ID, '_wen_post_email_recepient_to', true );
        $email_recepient_cc = get_post_meta( $post->ID, '_wen_post_email_recepient_cc', true );
        $email_subject = get_post_meta( $post->ID, '_wen_post_email_subject', true );
        $email_template = get_post_meta( $post->ID, '_wen_email_template', true );
        ?>
        <div class="post-expiry-settings">
            <div class="wen_date_picker">
                <label for="date-picker">
                    <?php _e( 'Expiry Date:', 'wen-post-expiry-notification' ); ?>
                </label>
                <input type="text" id="date-picker" name="date-picker" value="<?php echo esc_attr( $expiry_date ); ?>" size="25" >
            </div>

            <div class="wen_email_recepient_to">
                <label for="email-recepient-to">
                    <?php _e( 'Email Recepient (To):', 'wen-post-expiry-notification' ); ?>
                </label>
                <input type="email" id="email-recepient-to" name="email-recepient-to" value="<?php echo esc_attr( $email_recepient_to ); ?>" size="25" >
            </div>
            <div class="wen_email_recepient_cc">
                <label for="email-recepient-cc">
                    <?php _e( 'Email Recepient (CC):', 'wen-post-expiry-notification' ); ?>
                </label>
                <p class="description"><?php _e( 'Separate multiple recipients by comma (,).', 'wen-post-expiry-notification' ); ?></p>
                <input type="email" id="email-recepient-cc" name="email-recepient-cc" value="<?php echo esc_attr( $email_recepient_cc ); ?>" size="75" multiple>
            </div>
            <div class="wen_email_subject">
                <label for="email-subject">
                    <?php _e( 'Subject:', 'wen-post-expiry-notification' ); ?>
                </label>
                <input type="text" id="email-subject" name="email-subject" value="<?php echo esc_attr( $email_subject ); ?>" size="75">
            </div>
            <div class="wen_email_template">
                <label for="email-recepient">
                    <?php _e( 'Email Template:', 'wen-post-expiry-notification' ); ?>
                </label>
                <?php
                $editor_id = '_wen_email_template';
                $settings = array( 'textarea_name' => 'email-template' );
                wp_editor( $email_template , $editor_id, $settings );
                echo '<p class="description">' . __( 'Available placeholders', 'wen-post-expiry-notification' ) . ':</p>';
                echo '<p class="description">%post_title% : ' . __( 'Post title.', 'wen-post-expiry-notification' ) . '</p>';
                echo '<p class="description">%expiry_date% : ' . __( 'Post expiry date.', 'wen-post-expiry-notification' ) . '</p>';
                echo '<p class="description">%site_url% : ' . __( 'Site URL.', 'wen-post-expiry-notification' ) . '</p>';
                echo '<p class="description">%site_title% : ' . __( 'Site title.', 'wen-post-expiry-notification' ) . '</p>';
                ?>
            </div>
        </div>
        <?php
    }
}

