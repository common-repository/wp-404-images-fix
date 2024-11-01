<?php
/*
Plugin Name: 404 Image fix
Plugin URI: http://www.htmlremix.com/projects/wordpress-broken-image-fix
Description: This plugin allow you to show a default image for missing images on your blog.
Author: Remiz Rahnas
Version: 1.0
Author URI: http://www.htmlremix.com/
*/


/*
Handling abspath
*/
 if (!defined('ABSPATH')) {
	return ;
	}

/*
Prevents duplicating function
*/
if (!class_exists('wp_404_images_fix')) {

Class wp_404_images_fix {
/*
Creates plugin hook callbacks
*/
	function wp_404_images_fix() {

		// Calling onError handler to the_content
		
		add_action('the_content',
			array(&$this, '_content')
			);
		add_action('the_excerpt',
			array(&$this, '_content')
			);
		
		// Adding js in head
		
		add_action('wp_head',
			array(&$this, '_js')
			);

		// Admin menu
		
		if (is_admin()) {
			add_action('admin_menu',
				array(&$this, '_menu')
				);
			}
		
		//  Plugin installation details
		
		add_action(
			'activate_' . str_replace(
				DIRECTORY_SEPARATOR, '/',
				str_replace(
					realpath(ABSPATH . PLUGINDIR) . DIRECTORY_SEPARATOR,
						'', __FILE__
					)
				),
			array(&$this, 'install')
			);
		}
	
	// Adding the onError to the images
	
	function _content($content) {
		
		$_ = (array) get_option('wp_404_images_fix_settings');
		if (!$_ || !$_['mode']) {
			return $content;
			}

		return preg_replace(
			'~<img~Uis',
			'<img onError="javascript: wp_404_images_fix = window.wp_404_images_fix || function(){}; wp_404_images_fix(this);" ',
			$content
			);
		}

// ok
	function _js() {
		
		$_ = (array) get_option('wp_404_images_fix_settings');
		
		switch($_['mode']) {
// if set to hide			
			case 'hide' :
				$handle = 'img.style.display=\'none\';';
				break;
// if set to swap with alternate image			
			case 'swap' :
				$handle = 'img.src=\'' . $_['swap'] . '\';';
				$extra = 'var i = new Image(); i.src=\'' . $_['swap'] . '\';' . "\n";
				break;
// set to add class				
			case 'css' :
				$handle = '';
				//if ($_['spacer']) {
					$handle = 'img.src=\'' . $_['spacer'] . '\';' . "\n\t";
				//	}
				$handle .= 'img.className +=\' ' . $_['css'] . '\';';
				break;
			
			default :
				return;
			}
		
		echo <<<BROKEN_IMAGES_JS
		
<script type="text/javascript">
<!--//
/* Wordpress 404 Image Fix Plugin  v0.1 */
var wp_404_images_fix = wp_404_images_fix || function(img) {
	{$handle}
	img.onerror = function(){};
	}
{$extra}//-->
</script>
BROKEN_IMAGES_JS;
		}

	// -- -- -- -- -- -- -- -- -- -- -- -- -- -- -- -- -- -- -- -- -- -- 

	/* setting the settings array */	
	function install() {
		add_option(
			'wp_404_images_fix_settings',
				array(
					'mode' => ''
				)
			);
		}
	
	/* Adding back end menu */
	function _menu() {
		add_submenu_page('options-general.php',
			 '404 Images fix',
			 '404 Images fix', 8,
			 __FILE__,
			 array($this, 'menu')
			);
		}
		
	/* The menu page */
	function menu() {

		// sanitizing the referrer
		
		$_SERVER['HTTP_REFERER'] = preg_replace(
			'~&saved=.*$~Uis','', $_SERVER['HTTP_REFERER']
			);
		
		// submiting informations

		if ($_POST['submit']) {
			
			// submiting

			$_POST['wp_404_images_fix_settings']['swap'] = stripSlashes(
				$_POST['wp_404_images_fix_settings']['swap']);
			$_POST['wp_404_images_fix_settings']['css'] = stripSlashes(
				$_POST['wp_404_images_fix_settings']['css']);

			// spacer check

			$_POST['wp_404_images_fix_settings']['spacer'] =
				(file_exists(	dirname(__FILE__)
							. DIRECTORY_SEPARATOR
							. 'wp-broken-images-transaprent-1x1.gif')
						)
					? (get_option('siteurl')
						. '/' . PLUGINDIR
						. '/' . dirname($_GET['page'])
						. '/wp-broken-images-transaprent-1x1.gif') : null;

			// saveing options

			update_option(
				'wp_404_images_fix_settings',
				$_POST['wp_404_images_fix_settings']
				);

			die("<script>document.location.href = '{$_SERVER['HTTP_REFERER']}&saved=settings:" . time() . "';</script>");
			}

		// operation detected

		if (@$_GET['saved']) {
			
			list($saved, $ts) = explode(':', $_GET['saved']);
			if (time() - $ts < 10) {
				echo '<div class="updated"><p>';
	
				switch ($saved) {
					case 'settings' :
						echo 'Settings saved.';
						break;
					}
	
				echo '</p></div>';
				}
			}

		// loading settings from db

		$wp_404_images_fix_settings = get_option('wp_404_images_fix_settings');

?>
<div class="wrap">
	<h2>404 Images Fix</h2>
	<p>For latest information check out the <a href="http://www.htmlremix.com/projects/wordpress-broken-image-fix">404 Images Fix</a> homepage.</p>
	<form method="post">
	<fieldset class="options">
		
		<div>What if an image is missing?</div>
		
		<blockquote>
		<table>
			<tr><td>
			<input <?php echo (!$wp_404_images_fix_settings[mode]) ? 'checked="checked"' : ''; ?> type="radio" name="wp_404_images_fix_settings[mode]" value="" id="wp_404_images_fix_settings_mode_nothing" />
			</td><td>
			<label for="wp_404_images_fix_settings_mode_nothing"><b>Do nothing</b></label><br/>
			</td></tr><tr><td></td><td>
			Just like you deactivated this plugin
			<br/>&nbsp;</td></tr>
			
			<tr><td>
			<input <?php echo ($wp_404_images_fix_settings[mode] === 'hide') ? 'checked="checked"' : ''; ?> type="radio" name="wp_404_images_fix_settings[mode]" value="hide" id="wp_404_images_fix_settings_mode_hide" />
			</td><td>
			<label for="wp_404_images_fix_settings_mode_hide"><b>Hide 404 images</b></label><br/>
			</td></tr><tr><td></td><td>
			This hides the image. So no one know the image missing
			<br/>&nbsp;</td></tr>
			
			<tr><td>
			<input <?php echo ($wp_404_images_fix_settings[mode] === 'swap') ? 'checked="checked"' : ''; ?> type="radio" name="wp_404_images_fix_settings[mode]" value="swap" id="wp_404_images_fix_settings_mode_swap" />
			</td><td>
			<label for="wp_404_images_fix_settings_mode_swap"><b>Put an alternative image</b></label><br/>
			</td></tr><tr><td></td><td>
			Enter a default image for all the 404 images:<br/>
			<input size="52" name="wp_404_images_fix_settings[swap]" value="<?php echo $wp_404_images_fix_settings['swap']; ?>" />
			<br/>&nbsp;</td></tr>
			
			<tr><td>
			<input <?php echo ($wp_404_images_fix_settings[mode] === 'css') ? 'checked="checked"' : ''; ?> type="radio" name="wp_404_images_fix_settings[mode]" value="css" id="wp_404_images_fix_settings_mode_css" />
			</td><td>
			<label for="wp_404_images_fix_settings_mode_css"><b>Add CSS class</b></label><br/>
			</td></tr><tr><td></td><td>
			Ads a class to IMG tag of missing image:<br/>
			<input size="32" name="wp_404_images_fix_settings[css]" value="<?php echo $wp_404_images_fix_settings['css']; ?>" />
			<br/>&nbsp;</td></tr>

		</table>
		</blockquote>

		<p class="submit" style="text-align:left;"><input type="submit" name="submit" value="Update &raquo;" /></p>
	</fieldset>
	</form>
</div>
<?php
		}
	
	}

}

new wp_404_images_fix;

?>