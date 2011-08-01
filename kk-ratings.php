<?php

/*
Plugin Name: kk Star Ratings
Plugin URI: http://wakeusup.com/2011/05/kk-star-ratings/
Description: A clean, animated and sweat ratings feature for your blog <strong>With kk Star Ratings, you can allow your blog posts to be rated by your blog visitors</strong>. There are some useful options you can set to customize this plugin. You can do all that after installing the plugin and then visiting the <a href="options-general.php?page=kk-ratings_options">Plugin Settings</a>.
Version: 1.2
Author: Kamal Khan
Author URI: http://bhittani.com
License: GPLv2 or later
*/

// Make sure class does not already exist (Playing safe).
if(!class_exists('kk_Ratings') && !isset($kkratings) && !function_exists('kk_star_ratings')) :

    // Declare and define the plugin class.
	class kk_Ratings
	{	
	    // will contain id of plugin
		private $plugin_id;
		// js file
		private $js_file;
		// css file
		private $css_file;
		// Will be responsible for storing plugin options
		private $options;
		// Manual mode flag
		private $manual_mode;
		
		/** function/method
		* Usage: defining the constructor (setting the plugin id and table prefix)
		* Arg(2): string(alphanumeric, underscore, hyphen), string
		* Return: void
		*/
		public function __construct($id)
		{
			// set plugin id
			$this->plugin_id = $id;
			// set js file
			$this->js_file = '/js/kk-ratings.js';
			// set css file
			$this->css_file = '/css/kk-ratings.css';
			// retrieve array of options
			$this->options = $this->get_options();
			// set manual mode to false
			$this->manual_mode = false;
		}
		/** function/method
		* Usage: return file path relative to current plugin directory 
		* Arg(1): string
		* Return: string
		*/
		private function file_path($filepath)
		{
			return plugins_url($filepath, __FILE__);
		}
		/** function/method
		* Usage: helper for hooking js script
		* Arg(0): null
		* Return: void
		*/
		public function js()
		{
			if (!is_admin())
			{
				$nonce = wp_create_nonce($this->plugin_id);
				$params = array();
				$params['nonce'] = $nonce; //for security
				$params['path'] = $this->file_path('').'/';
				$params['root'] = urlencode(ABSPATH);
				$params['pos'] = $this->options['position'];
				
				//wp_deregister_script('jquery');
			    //wp_register_script('jquery', ("http://ajax.googleapis.com/ajax/libs/jquery/1.4/jquery.min.js"), false);
			    wp_enqueue_script('jquery');
				wp_enqueue_script($this->plugin_id.'_js', $this->file_path($this->js_file), array('jquery') );
				wp_localize_script($this->plugin_id.'_js', str_replace('-', '_', $this->plugin_id).'_settings', $params);
			}
		}
		public function css($head)
		{
		    echo "<link href='".$this->file_path($this->css_file)."' rel='stylesheet' />";	
		}
		/** function/method
		* Usage: return saved options
		* Arg(0): null
		* Return: array
		*/
		private function get_options($key=false)
		{
			// return saved options
			$options = get_option($this->plugin_id);
			if($key) return $options[$key];
			return $options;
		}
		/** function/method
		* Usage: update new options
		* Arg(0): null
		* Return: void
		*/
		private function update_options($options=false)
		{
			// update options
			update_option($this->plugin_id, $options ? $options : $this->options);
		}
		public function activate()
		{
		    $this->options['legend'] = '[avg]([per]) [total] votes'; // [total]=total ratings, [rating]=average, [per]=percentage
			$this->update_options();
		    if(!count($this->options)) :
				$this->options['enable'] = 1; // 1, 0
				$this->options['clear'] = 0; // 1, 0
				$this->options['show_in_home'] = 0; // 1, 0
				$this->options['show_in_archives'] = 0; // 1, 0
				$this->options['show_in_posts'] = 1; // 1, 0
				$this->options['show_in_pages'] = 0; // 1, 0
				$this->options['unique'] = 0; // 1, 0
				$this->options['position'] = 'top-left'; // 'top-left', 'top-right', 'bottom-left', 'bottom-right'
				$this->options['legend'] = '[avg]([per]) [total] votes'; // [total]=total ratings, [rating]=average, [per]=percentage
				$this->options['init_msg'] = 'Be the first to rate it!'; // string
				$this->update_options();
			endif;
		}
		public function admin_init()
		{
			register_setting($this->plugin_id.'_options', $this->plugin_id);
		}
		/** function/method
		* Usage: helper for hooking (registering) the theme menu under settings
		* Arg(0): null
		* Return: void
		*/
		public function menu()
		{
			add_options_page('kk Star Ratings', 'kk Star Ratings', 'manage_options', $this->plugin_id.'_options', array(&$this, 'options_page'));
		}
		/** function/method
		* Usage: show options/settings form page
		* Arg(0): null
		* Return: void
		*/
		public function options_page()
		{
			if (!current_user_can('manage_options')) 
			{
				wp_die( __('You do not have sufficient permissions to access this page.') );
			}
			
			//FLUSH the ratings
			if(!empty($_POST) && check_admin_referer('kkratings_flush','kkratings-flush-nonce'))
			{
				if(!strcmp($_POST['kkratings-flush-cfm'],'Yes'))
				{
					$postids = trim($_POST['kkratings_input']);
					if(strcmp($postids,''))
					{
						$posts = explode(',', $postids);
						$poststoflush = '';
						foreach($posts as $key=>$val)
						{
							delete_post_meta($val, '_kk_ratings_ratings');
							delete_post_meta($val, '_kk_ratings_casts');
							delete_post_meta($val, '_kk_ratings_ips');
						}
						echo '<div class="updated settings-error" style="padding:5px;">Ratings for id(s) '.$postids.' have been FLUSHED Successfully</div>';
					}
					else
					{
						$allposts = get_posts('numberposts=-1&post_type=post&post_status=any');
						foreach( $allposts as $postinfo)
						{
							delete_post_meta($postinfo->ID, '_kk_ratings_ratings');
							delete_post_meta($postinfo->ID, '_kk_ratings_casts');
							delete_post_meta($postinfo->ID, '_kk_ratings_ips');
						}
						echo '<div class="updated settings-error" style="padding:5px;">All ratings have been FLUSHED Successfully</div>';
					}
					
				}
				if(!strcmp($_POST['kkratings-flush'],'Flush Ratings'))
				{
					$postids = trim(str_replace(' ', '',$_POST['kkratings_input']));
					echo '<div class="updated settings-error" style="padding:5px;">';
					echo '<form action="" method="POST">';
					wp_nonce_field('kkratings_flush','kkratings-flush-nonce');
					$msg = "<p>Are you sure to remove ratings of posts/pages for the following id(s) : $postids</p>";
					if(!strcmp($postids,''))
					{
						$msg = '<p>Are you sure to <strong>REMOVE ALL RATINGS</strong> that have been occured on the site</p>';
					}
					echo $msg;
					echo '<input type="submit" value="Yes" name="kkratings-flush-cfm" class="button-primary" />';
					echo '<input type="submit" value="No" name="kkratings-flush-cfm" class="button-primary" />';
					echo '<input name="kkratings_input" type="hidden" value="'.$postids.'" size="10" />';
					echo '</form>';
					echo '</div>';
				}
			}
			//end
	
			// get saved options
			$options = $this->get_options();
			include('admin/options.php');
		}
		public function init()
		{
		    if(!is_admin() && $this->options['enable'])
			{
				add_filter('the_content', array(&$this, 'filter_content'));
				add_filter('the_excerpt', array(&$this, 'filter_content'));
			}
		}
		public function markup($id=false)
		{
			$markup = '<div class="kk-ratings open">
						  <span>'.(!$id?get_the_ID():$id).'</span>
						  <div class="stars-turned-on"> </div>
						  <!--.stars-turned-on-->
						  <div class="hover-panel"> 
							  <a href="javascript:void();" rel="star-1"></a>
							  <a href="javascript:void();" rel="star-2"></a>
							  <a href="javascript:void();" rel="star-3"></a>
							  <a href="javascript:void();" rel="star-4"></a>
							  <a href="javascript:void();" rel="star-5"></a>
						  </div>
						  <!--.hover-panel-->
						  <div class="casting-desc">'.$this->options['init_msg'].'</div>
						  <div class="casting-thanks">Thanks!</div>
						  <div class="casting-error">An error occurred!</div>
						  <!--.casting-desc--> 
						</div>
						<!--.kk-ratings-->';
			$markup .= $this->options['clear']? '<br clear="both" />' : '';
			
			return $markup;
		}
		public function do_it_manually()
		{
		    if(!is_admin() && $this->options['enable'])
			{
			    if(
					(($this->options['show_in_home']) && (is_front_page() || is_home()))
					|| (($this->options['show_in_archives']) && (is_archive()))
				  )
				    return $this->markup();
				else if(is_single() || is_page())
				    return $this->markup();
			}
			else
			    remove_shortcode('kkratings');
			return '';
		}
		public function filter_content($content)
		{
			if(
			    (($this->options['show_in_home']) && (is_front_page() || is_home()))
				|| (($this->options['show_in_archives']) && (is_archive()))
				|| (($this->options['show_in_posts']) && (is_single()))
				|| (($this->options['show_in_pages']) && (is_page()))
			  ) : 
			    remove_shortcode('kkratings');
				$content = str_replace('[kkratings]', '', $content);
				$markup = $this->markup();
				switch($this->options['position'])
				{
					case 'bottom-left' :
					case 'bottom-right' : return $content . $markup;
					default : return $markup . $content;
				}
				
			endif;
			return $content;
		}
		public function kk_star_rating($pid=false)
		{
		    if($this->options['enable'])
				return $this->markup($pid);
			return '';
		}
	}
	
	// Instantiate the plugin
	$kkratings = new kk_Ratings('kk-ratings');
	register_activation_hook(__FILE__, array($kkratings, 'activate'));
	add_action('wp_head', array($kkratings, 'css'));
	add_action('wp_print_scripts', array($kkratings, 'js')); 
	add_action('admin_init', array($kkratings, 'admin_init'));
	add_action('admin_menu', array($kkratings, 'menu'));
	add_action('init', array($kkratings, 'init'));
	// add shortcode handler
    add_shortcode('kkratings', array($kkratings, 'do_it_manually'));
	
	function kk_star_ratings($pid=false)
	{
		global $kkratings;
		return $kkratings->kk_star_rating($pid);
	}
	
endif;

?>