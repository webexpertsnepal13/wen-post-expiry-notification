<?php
$wpen_email_options = get_option( 'wpen_email' );
$wpen_selected_cpt = get_option( 'wpen_cpt' ) ? get_option( 'wpen_cpt' ) : array();

if ( $wpen_selected_cpt ){

    $args = array(
        'post_type' => $wpen_selected_cpt,
        'post_status' => 'publish',
        'meta_key' => '_wen_post_expiry_date',
    );

    $query = new WP_Query($args);
    
    while ( $query->have_posts() ) : $query->the_post();
        $expiry_date = get_post_meta( get_the_ID(), '_wen_post_expiry_date', false );

        if( !empty( $expiry_date ) ) {

            $str_expiry_date = strtotime( date( 'Y-m-d', strtotime( $expiry_date[0] ) ) );
            $todays_date = strtotime( date( "Y-m-d" ) );
            $diff = $str_expiry_date - $todays_date;
            $diff = floor( $diff / (60*60*24) );

            if( $diff > 0 && $diff == $wpen_email_options['wpen_send_before_days'] ) {

                $post_title = get_the_title();

                $from_name = $wpen_email_options['email_from_name'];
                $from = $wpen_email_options['email_from'];

                $to = get_post_meta( get_the_ID(), '_wen_post_email_recepient_to', true );
                $to = !empty( $to ) ? $to : $wpen_email_options['email_to'];

                $cc = get_post_meta( get_the_ID(), '_wen_post_email_recepient_cc', true );
                $cc = !empty( $cc ) ? $cc : $wpen_email_options['email_cc'];

                $subject = get_post_meta( get_the_ID(), '_wen_post_email_subject', true );
                $subject = !empty( $subject ) ? $subject : $wpen_email_options['email_subject'];
                $subject = !empty( $subject ) ? $subject : 'Expiry Notification';

                $message = get_post_meta( get_the_ID(), '_wen_email_template', true );

                if( empty( $message ) ) {
                    $message = $wpen_email_options['email_template'];
                    $message = str_replace( array( '%post_title%', '%expiry_date%', '%site_url%', '%site_title%' ), array( $post_title, $expiry_date[0], get_bloginfo( 'url' ), get_bloginfo( 'name' ) ), $message );
                }

                // Set content-type header for sending HTML email
                $headers = array();
                $headers[] = "MIME-Version: 1.0";
                $headers[] = "Content-type:text/html;charset=UTF-8";
                $headers[] = "From: " . $from_name . " <" . $from . ">";
                if( !empty( $cc ) ) {
                    $headers[] = 'CC: ' . $cc;
                }

                if( !empty( $to ) && !empty( $subject ) && !empty( $message ) ) {
                    wp_mail( $to, $subject, $message, $headers );
                }
            }
        }

    endwhile;
}