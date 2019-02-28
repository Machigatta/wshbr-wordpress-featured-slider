<?php
/*
Plugin Name: wshbr-wordpress-featured-slider
Plugin URI: https://github.com/Machigatta/wshbr-wordpress-featured-slider
Description: wshbr.de - Provide a slider for the frontpage
Author: Machigatta
Author URI: https://machigatta.com/
Version: 1.0
Stable Tag: 1.0
*/

function wfs_init() {
	$plugin_dir = basename(dirname(__FILE__));
	load_plugin_textdomain( 'wfs', null, $plugin_dir.'/languages/' );
}
add_action('plugins_loaded', 'wfs_init');

function wfs_meta_custom() {
	add_meta_box('wfsdiv', __('wshbr-slider','post-expirator'), 'wfs_meta_box', 'post', 'side', 'core'); //slider meta
}
add_action ('add_meta_boxes','wfs_meta_custom');


function wfs_meta_box($post) { 
	// Get default month
	wp_nonce_field( plugin_basename( __FILE__ ), 'wfs_nonce' );
	$isSlider = get_post_meta($post->ID,"isSlider",true);
	
	wp_enqueue_media();	
	echo "<div><h3><span class=\"dashicons dashicons-format-gallery\"></span> Slider</h3>
	<input id='isNoSlider' type=\"radio\" name='isSlider' value='false'";
	echo ($isSlider == "0" || $isSlider == "") ? "checked" : "";
	echo ">Nein<br><input id='isYesSlider' type=\"radio\" name='isSlider' value='true'";
	echo ($isSlider == "1") ? "checked" : "";
	
	echo ">Ja
	<div id='sliderOptions' >
	<hr>
	<h5>Slider-Caption</h5>
	<input type=\"text\" name=\"sliderCaption\" value=\"".get_post_meta($post->ID,"sliderCaption",true)."\" alt=\"Caption\" style='width:100%;'>
	<h5>Slider-Image (360 px X 200 px)</h5>
	<center><div class='image-preview-wrapper' style='width: 190px;height: 100px;overflow: hidden;position: relative;'>
		<img id='image-preview' src='".wp_get_attachment_url( get_post_meta( $post->ID,"sliderImage",true ) )."' style='left: 50%;top: 50%;transform: translate(-50%, -50%);height: 100%;position: absolute;width: auto;'>
	</div>
	<br>
	<input id=\"upload_image_button\" type=\"button\" class=\"button\" value=\"";
	_e( 'Upload image' ); 
	echo "\" /><input type='hidden' name='image_attachment_id' id='image_attachment_id' value='".get_post_meta( $post->ID,"sliderImage",true )."'></center></div></div>";
}
add_action( 'save_post', 'wfs_field_data' );
function wfs_field_data($post_id) {
	    // check if this isn't an auto save
    if ( defined( 'DOING_AUTOSAVE' ) && DOING_AUTOSAVE )
        return;
    // security check
    if ( !wp_verify_nonce( $_POST['wfs_nonce'], plugin_basename( __FILE__ ) ) )
        return;
	
	if ( isset( $_POST['image_attachment_id'] ) ) :
		update_post_meta( $post_id, 'sliderImage', absint( $_POST['image_attachment_id'] ) );
	endif;
	
    // now store data in custom fields based on checkboxes selected
    if ( isset( $_POST['isSlider'] )){
		if($_POST['isSlider'] == "true"){
			update_post_meta( $post_id, 'isSlider', "1" );
		}else{
			update_post_meta( $post_id, 'isSlider', "0" );
		}
	}
	if ( isset( $_POST['sliderCaption'] ) ) :
		update_post_meta( $post_id, 'sliderCaption', $_POST['sliderCaption']);
	endif;
	if ( isset( $_POST['quellePost'] ) ) :
		update_post_meta( $post_id, 'quellenAngaben', $_POST['quellePost']);
	endif;
}

add_action('wp_enqueue_scripts', 'wfs_add_styles_scripts');
function wfs_add_styles_scripts()
{
	$options = get_option('wfs_settings');
	wp_enqueue_style('wfs-font', 'https://fonts.googleapis.com/css?family=Open+Sans');
	wp_enqueue_style('wfs-style', trailingslashit(plugin_dir_url(__FILE__)) . 'assets/css/style.css', array(), "0.0.6");
}

add_action( 'admin_footer', 'media_selector_print_scripts' );
function media_selector_print_scripts() {
	$my_saved_attachment_post_id = get_option( 'media_selector_attachment_id', 0 );
	?><script type='text/javascript'>
		jQuery( document ).ready( function( $ ) {
			// Uploading files
			var file_frame;
			if(wp.media){
				var wp_media_post_id = wp.media.model.settings.post.id; // Store the old id
				var set_to_post_id = <?php echo $my_saved_attachment_post_id; ?>; // Set this
			}
			
			jQuery('#upload_image_button').on('click', function( event ){
				event.preventDefault();
				// If the media frame already exists, reopen it.
				if ( file_frame ) {
					// Set the post ID to what we want
					file_frame.uploader.uploader.param( 'post_id', set_to_post_id );
					// Open frame
					file_frame.open();
					return;
				} else {
					// Set the wp.media post id so the uploader grabs the ID we want when initialised
					wp.media.model.settings.post.id = set_to_post_id;
				}
				// Create the media frame.
				file_frame = wp.media.frames.file_frame = wp.media({
					title: 'Select a image to upload',
					button: {
						text: 'Use this image',
					},
					multiple: false	// Set to true to allow multiple files to be selected
				});
				// When an image is selected, run a callback.
				file_frame.on( 'select', function() {
					// We set multiple to false so only get one image from the uploader
					attachment = file_frame.state().get('selection').first().toJSON();
					// Do something with attachment.id and/or attachment.url here
					$( '#image-preview' ).attr( 'src', attachment.url ).css( 'width', 'auto' );
					$( '#image_attachment_id' ).val( attachment.id );
					// Restore the main post ID
					wp.media.model.settings.post.id = wp_media_post_id;
				});
					// Finally, open the modal
					file_frame.open();
			});
			// Restore the main ID when the add media button is pressed
			jQuery( 'a.add_media' ).on( 'click', function() {
				wp.media.model.settings.post.id = wp_media_post_id;
			});
		});
	</script><?php
} 

function wfs_show(){
	$the_query = new WP_Query( array(
		'post_type' => 'post',
		'post_status' => 'publish',
		'posts_per_page' => '6',
		'meta_query' => array(
			array(
				'key' => 'isSlider',
				'value' => '1',
				'compare' => '='
			)
		),
		'orderby' => 'id')
	);

	if ( $the_query->have_posts() ) {
		echo '<div class="highlight_master">';
		while ( $the_query->have_posts() ) {
			echo '<div class="highlight">';
			$the_query->the_post();
			
			 $sliderMeta = get_post_meta(get_the_ID(),'sliderImage');
			 $sliderCaption = get_post_meta(get_the_ID(),'sliderCaption')[0];
			 if($sliderCaption == ""){
				 $sliderCaption = get_the_title();
			 }
			if(!empty($sliderMeta)){
				echo '<a href="'. esc_url( get_permalink()).'" target="_blank">'."<img id='image-preview' class='slider-preview' src='".wp_get_attachment_url( $sliderMeta[0] )."'></a>";
				echo '<div class="caption">
						<div class="blur"></div>
							<div class="caption-text">
								<h5 class="hwrap">'.$sliderCaption.'</h5>
							</div>
					</div>';
			}else{
				echo "no preview";
			}


			echo '</div>';
		}
		echo '</div>';
		/* Restore original Post Data */
		wp_reset_postdata();
		echo '</div>';
	} else {
		// no posts found
	}
}