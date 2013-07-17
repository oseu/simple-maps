<?php
/*
Plugin Name: Simple Maps
Plugin URI: http://oseu.github.io/simple-map
Description: Simple Google Maps for Wordpress
Version: 1.0
Author: Oseu Yuliani
Author URI: http://www.amsyarmedia.com
License: Copyright 2013  Oseu Yuliani  (email : oseuyuliani@gmail.com)
	===========================================================================
    This program is free software; you can redistribute it and/or modify
    it under the terms of the GNU General Public License, version 2, as 
    published by the Free Software Foundation.

    This program is distributed in the hope that it will be useful,
    but WITHOUT ANY WARRANTY; without even the implied warranty of
    MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
    GNU General Public License for more details.

    You should have received a copy of the GNU General Public License
    along with this program; if not, write to the Free Software
    Foundation, Inc., 51 Franklin St, Fifth Floor, Boston, MA  02110-1301  USA
	===========================================================================
*/

//SETTING
	define('PLUGIN_NAME', 'simple_maps');
	define('PLUGIN_URL', plugins_url('/',__FILE__));
	define('PLUGIN_DIR', plugin_dir_path( __FILE__ ));
	
	include('state.php');
	
//ADD SCRIPT ADMIN POST TYPE
	function my_enqueue($hook) {
		if( 'edit.php?post_type='.PLUGIN_NAME == $hook )
			return;
		
		wp_register_script('map_core', 'http://maps.google.com/maps/api/js?sensor=false&libraries=places');
		wp_register_script('gmap', PLUGIN_URL.'gmaps.js');
		wp_register_script('autocomplete', PLUGIN_URL.'jquery.geocomplete.min.js');
		wp_enqueue_script('jquery' );
		wp_enqueue_script( 'map_core');
		wp_enqueue_script( 'gmap', array('jquery', 'map_core') );
		wp_enqueue_script( 'autocomplete');
	}
	add_action( 'admin_enqueue_scripts', 'my_enqueue' );
	
//FRONT END SCRIPTS
	function smaps_scripts_method() {
		wp_enqueue_script('jquery' );
		wp_register_script('map_core', 'http://maps.google.com/maps/api/js?sensor=false&libraries=places');
		wp_register_script('gmap', PLUGIN_URL.'/gmaps.js');	
		wp_register_script('autocomplete', PLUGIN_URL.'/jquery.geocomplete.min.js');
		wp_enqueue_script('jquery' );
		wp_enqueue_script( 'map_core');
		wp_enqueue_script( 'gmap', array('jquery', 'map_core') );
	}
	add_action( 'wp_enqueue_scripts', 'smaps_scripts_method' );

//REGISTER POST TYPES
	add_action( 'init', 'register_smaps_ptype' );
	function register_smaps_ptype() {
	
		register_post_type( PLUGIN_NAME,
			array(
				'labels' => array(
					'name' => __( 'Simple Maps' ),
					'singular_name' => __( 'Simple Maps' ),
					'add_new' => 'New Maps',
					'add_new_item' => 'Add New Maps',
					'edit_item' => 'Edit Maps',
					'new_item' => 'New Maps',
					'all_items' => 'All Maps',
					'view_item' => 'View Maps',
					'search_items' => 'Search Maps',
					'not_found' =>  'No Maps found',
					'not_found_in_trash' => 'No Maps found in Trash', 
					'parent_item_colon' => '',
					'menu_name' => 'Simple Maps'
				),
			'public' => true,
			'show_ui' => true,
			'taxonomies' => array('group_map'),
			'capability_type' => 'post',
			'hierarchical' => false,
			'has_archive' => true,
			'supports' => false,
			'menu_icon' => PLUGIN_URL.'style/img/map.png'
			)
		);	
		
		if(!term_exists('Ungrouped', 'group_map')):
			wp_insert_term(
			  'Ungrouped', // the term 
			  'group_map', // the taxonomy
			  array(
				'description'=> 'Ungrouped maps',
				'slug' => 'ungrouped',
				'parent'=> false
			  )
			);
		endif;
		
	}

// CREATE TAXONOMIES LIKE CATEGORY
	function create_smaps_taxonomi() {
		$labels = array(
			'name'              => _x( 'Group Maps', 'group_map' ),
			'singular_name'     => _x( 'Group Maps', 'group_map' ),
			'search_items'      => __( 'Search Group' ),
			'all_items'         => __( 'All Groups' ),
			'parent_item'       => __( 'Parent Group' ),
			'parent_item_colon' => __( 'Parent Group:' ),
			'edit_item'         => __( 'Edit Group' ),
			'update_item'       => __( 'Update Group' ),
			'add_new_item'      => __( 'Add New Group' ),
			'new_item_name'     => __( 'New Group Name' ),
			'menu_name'         => __( 'Group Maps' )
		);

		$args = array(
			'hierarchical'      => true,
			'labels'            => $labels,
			'show_ui'           => true,
			'show_admin_column' => true,
			'query_var'         => true
		);

		register_taxonomy( 'group_map', PLUGIN_NAME, $args );
	}
	add_action( 'init', 'create_smaps_taxonomi', 0 );
	
//CHANGIN DEFAULT HEADER
	add_action('admin_head', 'plugin_header_smaps');
	function plugin_header_smaps() {
		global $post_type;
		?>
		<style>
		<?php if (($_GET['post_type'] == PLUGIN_NAME) || ($post_type == PLUGIN_NAME)) : ?>
		#icon-edit { background:transparent url('<?php echo PLUGIN_URL.'style/img/map32.png';?>') no-repeat; }    
		<?php endif; ?>
		
		</style>
	<?php
	}
	
/*METABOX
====================================================================================== */
	add_action( 'do_meta_boxes', 'smaps_box' );
	function smaps_box() {
	/*EDIT ADDRESS METABOX POSITION*/	
		add_meta_box( 'map_location',__( 'Add Location' ), 'smaps_box_func', PLUGIN_NAME, 'normal', 'high');	

	}

//METABOX TEMPLATE FIELDS
	function smaps_box_func($post){	
		wp_nonce_field( plugin_basename( __FILE__ ), 'smaps_box_nonce' );
		global $state;
		
		//hide save draft and preview button
		echo "<style>#minor-publishing, #tes {display: none !important;}</style>";	
		
		?>
		<script type="text/javascript">
		jQuery(document).ready(function($){
			map = new GMaps({
				div: '#map',		
				lat: -6.904013,
				lng: 107.649788,
				zoom: 9,
				panControl : false,
				disableDefaultUI: true			
			});
		  
		  
			jQuery('#s_map').click(function(e){
				
				e.preventDefault();
				
				GMaps.geocode({
					address: jQuery('#alamat').val().trim(),
					callback: function(results, status){
					
						if(status=='OK'){
						
							var latlng = results[0].geometry.location;
							
							map.setCenter(latlng.lat(), latlng.lng()); 
							
							var la, ln;
							la = latlng.lat();
							ln = latlng.lng();
							
							map.removeMarkers();
							
							map.addMarker({
								lat: la,
								lng: ln
							});

						}
					}
				});		
			});
		  
			jQuery("#alamat").geocomplete();
		  
		});

		</script>
		<?php
		
		echo '<p><label>Address </label> &nbsp;&nbsp;&nbsp;<input type="text" name="alamat" id="alamat" style="width:70%"><button class="button-primary" id="s_map">Search</button></p>';
		
		echo '<div id="map" style="width:100%;height:400px;"></div>';

	}
	
//ACTION SAVE
	add_action( 'save_post', 'simple_maps_save' );
	function simple_maps_save( $post_id ) {

		if ( defined( 'DOING_AUTOSAVE' ) && DOING_AUTOSAVE ) 
		return;

		if ( !wp_verify_nonce( $_POST['smaps_box_nonce'], plugin_basename( __FILE__ ) ) )
		return;

		if ( 'page' == $_POST['post_type'] ) {
			if ( !current_user_can( 'edit_page', $post_id ) )
			return;
		} else {
			if ( !current_user_can( 'edit_post', $post_id ) )
			return;
		}
		
		if ( !is_object_in_term( $post_id, 'group_map') ) :
			wp_set_object_terms( $post_id, 'ungrouped', 'group_map' );			
		endif;
		
		update_post_meta($post_id, 'alamat', $_POST['alamat']);		
		
	}
	
//CHANGE DEFAULT TITLE WHEN SAVING POST TYPE AND REPLACE WITH ADDRESS
	function modify_smaps_post_title( $data , $postarr )
	{
		if($data['post_type'] == PLUGIN_NAME) {
			$data['post_title'] = $_POST['alamat'];
		}
		return $data;
	}
	add_filter( 'wp_insert_post_data' , 'modify_smaps_post_title' , '99', 2 );
	
/**
 * SIMPLE MAPS WIDGET
 */
	class Simple_Maps_Widget extends WP_Widget {

		/**
		 * Register widget with WordPress.
		 */
		public function __construct() {
			parent::__construct(
				'simple_maps_widget', // Base ID
				'Simple Maps', // Name
				array( 'description' => __( 'Simple Maps Widget', PLUGIN_NAME ), ) // Args
			);
		}

		/**
		 * Front-end display of widget.
		 *
		 * @see WP_Widget::widget()
		 *
		 * @param array $args     Widget arguments.
		 * @param array $instance Saved values from database.
		 */
		public function widget( $args, $instance ) {
			extract( $args );
			$title = apply_filters( 'widget_title', $instance['title'] );
			$group = $instance['group'];
			$idna = $widget_id;
			$idvar = str_replace('-', '', $idna);
			$baselocation = 'Jakarta';
			
			echo $before_widget;
			if ( ! empty( $title ) ){
				echo $before_title . $title . $after_title;
			}
			
			
			echo '<div id="map-'.$idna.'" style="width:100%; height:200px;"></div>';
			
			$cat = get_term_by('slug', 'ungrouped', 'group_map');
			$terms = get_terms("group_map", array('exclude' => array($cat->term_id)));
			$count = count($terms);
			
			echo '<span><select id="d-'.$idna.'" style="width:48%;margin-right:1.5%">';
			echo "<option value='".$baselocation."'>Location</option>";
			foreach ( $terms as $term ) {
			echo "<option value='". $term->slug ."'>" . $term->name . "</option>";
			}
			echo '</select></span>';
			
			
			echo '<span id="k-'.$idna.'"></span>';
			
			?>
			<script>
				var map<?php echo $idvar;?>;
				map<?php echo $idvar;?> = new GMaps({
					div: '#map-<?php echo $idna;?>',		
					lat: -6.904013,
					lng: 107.649788,
					zoom: 9,
					panControl : false,
					disableDefaultUI: true					
				 });
				 
				<?php
					$cat = get_term_by('slug', 'ungrouped', 'group_map');
					$m = new WP_Query(array(
						'post_type' => 'asheu_map',
						'category_not_in' => array($cat->term_id)						
					));
					
					while($m->have_posts()): $m->the_post();
						?>
						GMaps.geocode({
							address: '<?php the_title();?>',
							callback: function(results, status){
								if(status=='OK'){
									var latlng = results[0].geometry.location;									
									var lat, lnx;
									lax = latlng.lat();
									lnx = latlng.lng();
									map<?php echo $idvar;?>.addMarker({
										lat: lax,
										lng: lnx
									});
								}
							}
						});						
						<?php
					endwhile;
				 ?>
				 
				jQuery('#d-<?php echo $idna;?>').change(function(){
				
					GMaps.geocode({
						address: jQuery('#d-<?php echo $idna;?>').val().trim(),
						callback: function(results, status){
							if(status=='OK'){
								var latlng = results[0].geometry.location;
								map<?php echo $idvar;?>.setCenter(latlng.lat(), latlng.lng()); 
								//var la, ln;
								//la = latlng.lat();
								//ln = latlng.lng();
								//map.removeMarkers();
								//map.addMarker({
								//	lat: la,
								//	lng: ln
								//});
							}
						}
					});
					
					
					
					if(jQuery('#d-<?php echo $idna;?>').val() != '<?php echo $baselocation;?>'){
					
						jQuery('span#k-<?php echo $idna;?>').ajaxStart(function(){
							jQuery(this).html('<img id="loud" src="<?php echo PLUGIN_URL;?>/style/img/ajax-loader.gif">');
							jQuery('#loud').fadeIn();
						}).ajaxStop(function(){
							jQuery('#loud').fadeOut();
						});
					
						jQuery.ajax({
							url: '<?php echo admin_url('admin-ajax.php');?>',
							type: 'post',
							data: {
								action: 'get_map',
								wgid: '<?php echo $idna;?>',
								addr: jQuery('#d-<?php echo $idna;?>').val()
							},
							dataType: 'html',
							success: function(response){
								jQuery('span#k-<?php echo $idna;?>').html(response);
								
								jQuery('#dx-<?php echo $idna;?>').change(function(map){									
									
								
									GMaps.geocode({
										address: jQuery('#dx-<?php echo $idna;?>').val().trim(),
										callback: function(resultx, status){
											if(status=='OK'){
												var latlng = resultx[0].geometry.location;
												map<?php echo $idvar;?>.setCenter(latlng.lat(), latlng.lng()); 
												//var la, ln;
												//la = latlng.lat();
												//ln = latlng.lng();												
												//map<?php echo $idvar;?>.addMarker({
												//	lat: la,
												//	lng: ln
												//});
											}
										}
									});
								
								});
								
							}
						});
						
						/*
						*/
					}else{
						jQuery('span#k-<?php echo $idna;?>').html('');
					}
					
				});
			</script>
			<?php
			
			echo $after_widget;
		}

		/**
		 * Back-end widget form.
		 *
		 * @see WP_Widget::form()
		 *
		 * @param array $instance Previously saved values from database.
		 */
		public function form( $instance ) {
			if ( isset( $instance[ 'title' ] ) ) {
				$title = $instance[ 'title' ];
			}			
			else {
				$title = __( 'Maps Loaction', 'asheu-map' );
			}
			
			if ( isset( $instance[ 'group' ] ) ) {
				$group = $instance[ 'group' ];
			}else{
				$group = __( 'all', 'asheu-map' );
			}
			?>
			<p>
			<label for="<?php echo $this->get_field_name( 'title' ); ?>"><?php _e( 'Title:' ); ?></label> 
			<input class="widefat" id="<?php echo $this->get_field_id( 'title' ); ?>" name="<?php echo $this->get_field_name( 'title' ); ?>" type="text" value="<?php echo esc_attr( $title ); ?>" />
			</p>			
			
			<?php 
		}

		/**
		 * Sanitize widget form values as they are saved.
		 *
		 * @see WP_Widget::update()
		 *
		 * @param array $new_instance Values just sent to be saved.
		 * @param array $old_instance Previously saved values from database.
		 *
		 * @return array Updated safe values to be saved.
		 */
		public function update( $new_instance, $old_instance ) {
			$instance = array();
			$instance['title'] = ( !empty( $new_instance['title'] ) ) ? strip_tags( $new_instance['title'] ) : '';
			$instance['group'] = $new_instance['group'];

			return $instance;
		}

	} // class Pcat_Widget

	// register Pcat_Widget widget
	function smaps_register_widgets() {
		register_widget( 'simple_maps_widget' );
	}

	add_action( 'widgets_init', 'smaps_register_widgets' );
	
	