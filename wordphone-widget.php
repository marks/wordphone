<?php
/**
 * Plugin Name: WordPhone Widget
 * Plugin URI: http://github.com/marks/WordPhone
 * Description: A widget that allows you to embed a Phono(.com) phone on your blog.
 *							Uses HTML and Flash to let your users make free calls to you and/or your Tropo(.com) application.
 * Version: 0.1
 * Author: Mark Silverberg / @Skram
 * Author URI: http://twitter.com/skram
 *
 */

/**
 * Add function to widgets_init that'll load our widget and require dependences.
 * @since 0.1
 */
add_action( 'widgets_init', 'wordphone_load_widgets' );

/**
 * Register our widget.
 * 'WordPhone' is the widget class used below.
 * We also need to tell WordPress to include some required and bundled JS libraries.
 * @since 0.1
 */
function wordphone_load_widgets() {
	register_widget( 'WordPhone_Voice' );

	// Wordpress comes with an old version of jQuery so we're requiring a newer one from Google.
  wp_deregister_script( 'jquery' );
  wp_register_script( 'jquery', 'http://ajax.googleapis.com/ajax/libs/jquery/1.4.3/jquery.min.js', false, '1.4.3');
	// Make sure that jQuery and the Phono (included in the widget zip file) scripts are included on the page.
	wp_enqueue_script('phono', WP_PLUGIN_URL . '/wordphone/jquery.phono.js', array('jquery'), '0.1' );
}

/**
 * WordPhone_Voice class.
 * This class handles everything that needs to be handled with the widget:
 *
 * @since 0.1
 */
class WordPhone_Voice extends WP_Widget {

	/**
	 * Widget setup.
	 */
	function WordPhone_Voice() {
		/* Widget settings. */
		$widget_ops = array( 'classname' => 'wordphone', 'description' => __('A widget that adds a phone to your WordPress.', 'wordphone') );

		/* Widget control settings. */
		$control_ops = array( 'width' => 300, 'height' => 350, 'id_base' => 'wordphone-widget' );

		/* Create the widget. */
		$this->WP_Widget( 'wordphone-widget', __('WordPhone', 'wordphone'), $widget_ops, $control_ops );
		
	}

	/**
	 * How to display the widget on the screen.
	 */
	function widget( $args, $instance ) {
		extract( $args );
		$wid = $args['widget_id']."_div";
		
		/* Our variables from the widget settings. */
		$title = apply_filters('widget_title', $instance['title'] );
		$api_key = $instance['api_key'];
		$connect_to = $instance['connect_to'];
		$user_instructions = $instance['user_instructions'];
		$show_keypad = isset( $instance['show_keypad'] ) ? $instance['show_keypad'] : false;
		$button_text = !empty($instance['button_text']) ? $instance['button_text'] : "Call";
		
		echo "<script type='text/javascript'>try{jQuery.noConflict();}catch(e){};</script>\n";

		/* Before widget (defined by themes). */
		echo $before_widget;

		/* Display the widget title if one was input (before and after defined by themes). */
		if ( $title )
			echo $before_title . $title . $after_title;

		if ( !empty($user_instructions) )
			echo "<span class='user-instructions'>".$user_instructions."</span><br />";
		
		echo "<div id='".$wid."'>";
		echo "<input class='call-button' type='button' disabled='true' value='Loading...' /><span class='status'></span>";
		echo "<div class='call-controls' style='padding:5px;'>";
		
		if ( $show_keypad )
			echo "
				<table width=\"110px\">
					<tr>
						<td><input class='digit-1' type='button' onclick=\"calls['".$wid."'].digit('1');\" value='1' /></td>
						<td><input class='digit-2' type='button' onclick=\"calls['".$wid."'].digit('2');\" value='2' /></td>
						<td><input class='digit-3' type='button' onclick=\"calls['".$wid."'].digit('3');\" value='3' /></td>
					</tr>
					<tr>
						<td><input class='digit-4' type='button' onclick=\"calls['".$wid."'].digit('4');\" value='4' /></td>
						<td><input class='digit-5' type='button' onclick=\"calls['".$wid."'].digit('5');\" value='5' /></td>
						<td><input class='digit-6' type='button' onclick=\"calls['".$wid."'].digit('6');\" value='6' /></td>
					</tr>
					<tr>
						<td><input class='digit-7' type='button' onclick=\"calls['".$wid."'].digit('7');\" value='7' /></td>
						<td><input class='digit-8' type='button' onclick=\"calls['".$wid."'].digit('8');\" value='8' /></td>
						<td><input class='digit-9' type='button' onclick=\"calls['".$wid."'].digit('9');\" value='9' /></td>
					</tr>
					<tr>
						<td><input class='digit-star'  type='button' onclick=\"calls['".$wid."'].digit('*');\" value='*' /></td>
						<td><input class='digit-0' 	   type='button' onclick=\"calls['".$wid."'].digit('0');\" value='0' /></td>
						<td><input class='digit-pound' type='button' onclick=\"calls['".$wid."'].digit('#');\" value='#' /></td>
					</tr>
				</table>";

		// Hangup button inside #call-controls.
		echo "<input style='width: 100px;' class='hangup-button' type='button' onclick=\"calls['".$wid."'].hangup();\" value='Hangup' /></td>";
		
		// Close #call-controls div
		echo "</div>";
		
		// Phono Javascript initialization and calling code.
		echo "
	    <script>
				var $ = jQuery.noConflict(); // this is needed for Phono to work; it doesn't support jQuery.noConflict() from what I can tell
				
				// create an array of phonos and calls if they aren't initialized already by a prior Phono widget
				if(typeof(phonos) == 'undefined'){ var phonos = new Array(); }
				if(typeof(calls) == 'undefined'){ var calls = new Array(); }
				
				jQuery('#".$wid." .call-controls').hide();
	      phonos['".$wid."'] = jQuery.phono({
					apiKey: '".$api_key."',
	        onReady: function() {
	          jQuery('#".$wid." .call-button').attr('disabled', false).val('".$button_text."');
						console.log('Phono ".$wid." is ready!')
	        },
					onUnready: function() {
						console.log('Phono ".$wid." is NOT ready');
						jQuery('#".$wid." .call-button').val('phone not ready');
					}
				});
				
				jQuery('#".$wid." .call-button').click(function() {
				  jQuery('#".$wid." .call-button').attr('disabled', true).val('Busy');
				  calls['".$wid."'] = phonos['".$wid."'].phone.dial('".$connect_to."', {
				    onRing: function() {
				      jQuery('#".$wid." .status').html('Ringing');
							jQuery('#".$wid." .call-controls input').attr('disabled',true);
							jQuery('#".$wid." .hangup-button').attr('disabled',false);
							jQuery('#".$wid." .call-controls').show().fadeIn();
				    },
				    onAnswer: function() {
				      jQuery('#".$wid." .status').html('Answered').show().fadeIn();
							jQuery('#".$wid." .call-controls input').attr('disabled',false);
				    },
				    onHangup: function() {
		          jQuery('#".$wid." .call-button').attr('disabled', false).val('".$button_text."');
				      jQuery('#".$wid." .status').html('Call has ended');
							jQuery('#".$wid." .call-controls input').attr('disabled',true);
							jQuery('#".$wid." .call-controls').fadeOut();
				    }
				  });
				});
	    </script>";
		echo "</div>";

		echo $after_widget;
	}

	/**
	 * Update the widget settings.
	 */
	function update( $new_instance, $old_instance ) {
		$instance = $old_instance;

		/* Strip tags for text inputs. */
		$instance['title'] = strip_tags( $new_instance['title'] );
		$instance['user_instructions'] = strip_tags( $new_instance['user_instructions'] );
		$instance['api_key'] = strip_tags( $new_instance['api_key'] );
		$instance['button_text'] = strip_tags( $new_instance['button_text'] );
		$instance['connect_to'] = strip_tags( $new_instance['connect_to'] );

		/* No need to strip tags for these. */
		$instance['show_keypad'] = $new_instance['show_keypad'];

		return $instance;
	}

	/**
	 * Displays the widget settings controls on the widget panel.
	 * Make use of the get_field_id() and get_field_name() function
	 * when creating your form elements. This handles the confusing stuff.
	 */
	function form( $instance ) {

		/* Set up some default settings. */
		$defaults = array(
			'title' => __('Phono us!'),
			'api_key' => __('GET ONE FROM PHONO.COM'),
			'connect_to' => __('app:9991456769'),	// this is the example application at http://tropo.heroku.com/
			'show_keypad' => true,
			'user_instructions' => '' );
		$instance = wp_parse_args( (array) $instance, $defaults ); ?>

		 
		<!-- Widget Title: Text Input -->
		<p>
			<label for="<?php echo $this->get_field_id( 'title' ); ?>"><?php _e('Title:', 'hybrid'); ?></label>
			<input id="<?php echo $this->get_field_id( 'title' ); ?>" name="<?php echo $this->get_field_name( 'title' ); ?>" value="<?php echo $instance['title']; ?>" style="width:100%;" />
		</p>

		<!-- Your API Key: Text Input -->
		<p>
			<label for="<?php echo $this->get_field_id( 'api_key' ); ?>"><?php _e('Phono API Key:'); ?></label>
			<input id="<?php echo $this->get_field_id( 'api_key' ); ?>" name="<?php echo $this->get_field_name( 'api_key' ); ?>" value="<?php echo $instance['api_key']; ?>" style="width:100%;" />
		</p>

		<!-- Connect To: Text Input -->
		<p>
			<label for="<?php echo $this->get_field_id( 'connect_to' ); ?>"><?php _e('Connect To:'); ?></label>
			<input id="<?php echo $this->get_field_id( 'connect_to' ); ?>" name="<?php echo $this->get_field_name( 'connect_to' ); ?>" value="<?php echo $instance['connect_to']; ?>" style="width:100%;" />
			<small>This should be a SIP URI (sip:9991456769@sip.tropo.com), Voxeo App ID (app:9991456769), or 10-digit phone number (calls will be limited to 10 minutes). Free calling provided by <a href="http://www.tropo.com">Tropo.com</a></small>
		</p>
		
		<h4 style="text-decoration:underline;" >Optional Fields</h4>
		<!-- Show Keypad? Checkbox -->
		<p>
			<input class="checkbox" type="checkbox" <?php checked( $instance['show_keypad'], true ); ?> id="<?php echo $this->get_field_id( 'show_keypad' ); ?>" name="<?php echo $this->get_field_name( 'show_keypad' ); ?>" /> 
			<label for="<?php echo $this->get_field_id( 'show_keypad' ); ?>"><?php _e('Show numeric keypad while call active?'); ?></label>
		</p>
		
		<!-- Call Button Text: Text Input -->
		<p>
			<label for="<?php echo $this->get_field_id( 'button_text' ); ?>"><?php _e('Call Button Text:'); ?></label>
			<input id="<?php echo $this->get_field_id( 'button_text' ); ?>" name="<?php echo $this->get_field_name( 'button_text' ); ?>" value="<?php echo $instance['button_text']; ?>" style="width:100%;" />
			<small>Text to show show on call initiation button. Will default to "Call", but you might want to make it something else.</small>
		</p>
				
		<!-- Call Button Text: Text Input -->
		<p>
			<label for="<?php echo $this->get_field_id( 'user_instructions' ); ?>"><?php _e('Instructions To Users:'); ?></label>
			<input id="<?php echo $this->get_field_id( 'user_instructions' ); ?>" name="<?php echo $this->get_field_name( 'user_instructions' ); ?>" value="<?php echo $instance['user_instructions']; ?>" style="width:100%;" />
			<small>You might want to explain to them what this phone embedded on your website is. Your choice. Defaults to empty and invisible. </small>
		</p>
		
				
	<?php
	}
}


?>