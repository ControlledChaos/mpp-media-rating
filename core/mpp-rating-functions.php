<?php

/* 
 * To change this license header, choose License Headers in Project Properties.
 * To change this template file, choose Tools | Templates
 * and open the template in the editor.
 */


function mpp_rating_get_users_vote( $post_id ) {
    
    if( !$post_id ){
        
        return;
        
    }
    
    global $wpdb;
    
    $user_id = get_current_user_id();
    
    $votes  = $wpdb->get_col( $wpdb->prepare( "SELECT votes FROM {$wpdb->prefix}mpp_rating WHERE post_id = %d AND user_id = %d", $post_id, $user_id ) );

    return $votes[0];
    
}

function mpp_rating_get_average_vote_for_media( $media_id ){

    global $wpdb;

	$table_name = mpp_rating_get_table_name();
    
    if( ! $media_id ){
        return;
    }    
    
    $average  = $wpdb->get_var( "SELECT AVG(votes) FROM {$table_name} WHERE media_id = {$media_id}" );

	if ( is_null( $average ) ) {
		$average = 0;
	}
    
    return absint( $average );
    
}


function mpp_rating_is_user_can_rate() {
    
    $allow          = false;
    $who_can_rate   = mpp_get_option('who-can-rate');
    
    if ( 'any' == $who_can_rate ) {
	    $allow = true;
    } elseif ( 'loggedin' == $who_can_rate && is_user_logged_in() ) {
	    $allow = true;
    }
    
    return apply_filters( 'mpp_rating_is_user_can_rate', $allow );
    
}

function mpp_rating_is_media_rateable( $media_id ) {

	if ( ! $media_id ) {
		return false;
	}

	$media = mpp_get_media( $media_id );

	if ( is_null( $media ) ) {
		return false;
	}

	$can_be_rated = true;

	$component_can_be_rated = (array) mpp_get_option('component-can-be-rated');
	$type_can_be_rated      = (array) mpp_get_option('type-can-be-rated');

	if ( ! $component_can_be_rated || ! $type_can_be_rated ) {
		$can_be_rated = false;
	} elseif ( ! in_array( $media->component, $component_can_be_rated ) ) {
		$can_be_rated = false;
	} elseif ( ! in_array( $media->type, $type_can_be_rated ) ) {
		$can_be_rated = false;
	}

	return apply_filters( 'mpp_rating_is_media_rateable', $can_be_rated );

}

function mpp_rating_is_user_rated_on_media( $user_id, $media_id ) {

	if ( ! $user_id || ! $media_id ) {
		return false;
	}

	global $wpdb;

	$table_name = mpp_rating_get_table_name();

	$result = $wpdb->get_row( "SELECT * FROM {$table_name} WHERE user_id = {$user_id} AND media_id = {$media_id}" );

	if ( is_null( $result ) ) {
		return false;
	} else {
		return true;
	}

}

function mpp_rating_get_table_name() {

	global $wpdb;
	return $wpdb->prefix.'mpp_media_rating';

}

function mpp_rating_is_read_only_media_rating( $media_id ) {

	if ( ! $media_id ) {
		return;
	}

	if ( ! mpp_rating_is_user_can_rate() || mpp_rating_is_user_rated_on_media( get_current_user_id(), $media_id ) ) {
		return true;
	} else {
		return false;
	}

}

function mpp_rating_get_top_rated_media( $ids = array(), $interval = 7, $limit = 5 ) {

	global $wpdb;

	if( empty( $ids ) ) {
		return false;
	}

	$interval = absint( $interval );

	$ids = join( ',', $ids );

	$media_ids = $wpdb->get_results( $wpdb->prepare( "SELECT media_id FROM {$wpdb->prefix}mpp_media_rating WHERE 1 =1 AND ( date >= DATE(NOW()) - INTERVAL %d DAY ) AND media_id IN ( {$ids} ) GROUP BY media_id ORDER BY avg( votes ) DESC LIMIT 0 , %d", $interval, $limit ), 'ARRAY_A' );

	if ( empty( $media_ids ) ) {
		return false;
	}

	return wp_list_pluck( $media_ids, 'media_id' );

}

function mpp_rating_get_component_can_be_rated() {

	$component_can_be_rated = array(
		'members'        => __( 'Users', 'mpp-media-rating' ),
		'sitewide'    => __( 'SiteWide', 'mpp-media-rating' )
	);

	if ( bp_is_active( 'groups' ) ) {
		$component_can_be_rated['groups'] = __( 'Groups', 'mpp-media-rating' );
	}

	return apply_filters( 'mpp_rating_component_can_be_rated', $component_can_be_rated );
}

function mpp_rating_get_who_can_rate() {

	$who_can_rate = array(
		'any'   => __( 'Anyone', 'mpp-media-rating' ),
		'loggedin' => __( 'Logged In', 'mpp-media-rating' )
	);

	return apply_filters( 'mpp_rating_who_can_rate', $who_can_rate  );
}

function mpp_rating_get_rating_html( $media_id, $readonly ) {

	$average = mpp_rating_get_average_vote_for_media( $media_id );

	?>
	<select id="mpp-rating-value-<?php echo $media_id; ?>" style="display: none">
		<option value="1" <?php selected( 1, $average )?>>1</option>
		<option value="2" <?php selected( 2, $average )?>>2</option>
		<option value="3" <?php selected( 3, $average )?>>3</option>
		<option value="4" <?php selected( 4, $average )?>>4</option>
		<option value="5" <?php selected( 5, $average )?>>5</option>
	</select>
	<div class="mpp-media-rating" data-rateit-readonly="<?php echo $readonly; ?>" data-media-id="<?php echo $media_id; ?>" data-rateit-backingfld="#mpp-rating-value-<?php echo $media_id; ?>"></div>

	<?php
}

function mpp_rating_show_media_of() {

	return array(
		'loggedin'  => __( 'Logged In User', 'mpp-media-rating'),
		'displayed' => __( 'Displayed User', 'mpp-media-rating'),
		'any'       => __( 'Any', 'mpp-media-rating'),
	);
}

function mpp_rating_get_intervals() {

	return array(
		7   => __( 'Last weak', 'mpp-media-rating'),
		30  => __( 'Last month', 'mpp-media-rating'),
		365 => __( 'Last Year', 'mpp-media-rating'),
	);
}