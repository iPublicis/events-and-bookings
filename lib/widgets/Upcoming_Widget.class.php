<?php

class Eab_Upcoming_Widget extends Eab_Widget {
	
	private $_defaults = array();
    
    function __construct () {
    	$this->_defaults = apply_filters('eab-widgets-upcoming-default_fields', array( 
			'title' => __('Upcoming', $this->translation_domain),
			'excerpt' => false,
			'excerpt_words_limit' => false,
			'thumbnail' => false,
			'limit' => 5,
			'dates' => false,
		));
		$widget_ops = array('description' => __('Display List of Upcoming Events', $this->translation_domain));
        $control_ops = array('title' => __('Upcoming', $this->translation_domain));        
		parent::WP_Widget( 'incsub_event_upcoming', __('Upcoming Events', $this->translation_domain), $widget_ops, $control_ops );
    }
    
    function widget ($args, $instance) {
		global $wpdb, $current_site, $post, $wiki_tree;
		
		extract($args);
		
		$instace = apply_filters('eab-widgets-upcoming-instance_read', $instance, $this);
		$options = wp_parse_args((array)$instance, $this->_defaults);
		
		$title = apply_filters('widget_title', empty($instance['title']) ? __('Upcoming', $this->translation_domain) : $instance['title'], $instance, $this->id_base);
		$query_args = array(
			'posts_per_page' => $options['limit'],
		);
		if ($options['category']) {
			$query_args['tax_query'] = array(array(
				'taxonomy' => 'eab_events_category',
				'field' => 'id',
				'terms' => (int)$options['category'],
			));
		}
		if ($options['lookahead'] && is_numeric($options['lookahead'])) {
			$lookahead_func = create_function('', 'return ' . $options['lookahead'] . ';');
			add_filter('eab-collection-upcoming_weeks-week_number', $lookahead_func);
		}
		$_events = Eab_CollectionFactory::get_upcoming_weeks_events(eab_current_time(), $query_args);
		if (!empty($lookahead_func)) {
			remove_filter('eab-collection-upcoming_weeks-week_number', $lookahead_func);	
		}
	
		if (is_array($_events) && count($_events) > 0) {
			echo $before_widget;
			echo $before_title . $title . $after_title;
	        echo '<div id="event-popular"><ul>';
			foreach ($_events as $_event) {
				$thumbnail = $excerpt = false;
				if ($options['thumbnail']) {
					$raw = wp_get_attachment_image_src(get_post_thumbnail_id($_event->get_id()));
					$thumbnail = $raw ? @$raw[0] : false;
				}
				$excerpt = false;
				if (!empty($options['excerpt'])) {
					$words = (int)$options['excerpt_words_limit'] ? (int)$options['excerpt_words_limit'] : false;
					$excerpt = eab_call_template('util_words_limit', $_event->get_excerpt_or_fallback(), $words);
				}
				echo '<li>';
				echo '<a href="' . get_permalink($_event->get_id()) . '" class="' . ($_event->get_id() == $post->ID ? 'current' : '') . '" >' .
					($options['thumbnail'] && $thumbnail
						? '<img src="' . $thumbnail . '" /><br />'
						: ''
					) .
					$_event->get_title() .
				'</a>';
				if (!empty($options['dates'])) echo '<div class="wpmudevevents-date">' . Eab_Template::get_event_dates($_event) . '</div>';
				if (!empty($options['excerpt']) && !empty($excerpt)) echo '<p>' . $excerpt . '</p>';
				do_action('eab-widgets-upcoming-after_event', $options, $_event, $this);
				echo '</li>';
			}
			echo '</ul></div>';
	        echo $after_widget;
		} else {
			echo $before_widget .
				$before_title . $title . $after_title .
				'<p class="eab-widget-no_events">' . __('No upcoming events.', Eab_EventsHub::TEXT_DOMAIN) . '</p>' .
			$after_widget;
		}
    }
    
    function update ($new_instance, $old_instance) {
		$instance = $old_instance;
        $new_instance = wp_parse_args((array)$new_instance, $this->_defaults);
        
        $instance['title'] = strip_tags($new_instance['title']);
        $instance['excerpt'] = (int)$new_instance['excerpt'];
        $instance['excerpt_words_limit'] = (int)$new_instance['excerpt_words_limit'];
        $instance['thumbnail'] = (int)$new_instance['thumbnail'];
        $instance['limit'] = (int)$new_instance['limit'];
        $instance['lookahead'] = (int)$new_instance['lookahead'];
        $instance['dates'] = (int)$new_instance['dates'];
        $instance['category'] = (int)$new_instance['category'];

        $instance = apply_filters('eab-widgets-upcoming-instance_update', $instance, $new_instance, $this);
	
        return $instance;
    }
    
    function form ($instance) {
    	$instance = apply_filters('eab-widgets-upcoming-instance_read', $instance, $this);
		$options = wp_parse_args((array)$instance, $this->_defaults);
        $options['title'] = strip_tags($instance['title']);	
		
		$categories = get_terms('eab_events_category');
	?>
	<div style="text-align:left">
            <label for="<?php echo $this->get_field_id('title'); ?>" style="line-height:35px;display:block;">
            	<?php _e('Title', $this->translation_domain); ?>:<br />
				<input class="widefat" 
					id="<?php echo $this->get_field_id('title'); ?>" 
					name="<?php echo $this->get_field_name('title'); ?>" 
					value="<?php echo $options['title']; ?>" type="text" style="width:95%;" 
				/>
            </label>
            <label for="<?php echo $this->get_field_id('dates'); ?>" style="display:block;">
				<input type="checkbox" 
					id="<?php echo $this->get_field_id('dates'); ?>" 
					name="<?php echo $this->get_field_name('dates'); ?>" 
					value="1" <?php echo ($options['dates'] ? 'checked="checked"' : ''); ?> 
				/>
            	<?php _e('Show dates', $this->translation_domain); ?>
            </label>
            <label for="<?php echo $this->get_field_id('excerpt'); ?>" style="display:block;">
				<input type="checkbox" 
					id="<?php echo $this->get_field_id('excerpt'); ?>" 
					name="<?php echo $this->get_field_name('excerpt'); ?>" 
					value="1" <?php echo ($options['excerpt'] ? 'checked="checked"' : ''); ?> 
				/>
            	<?php _e('Show excerpt', $this->translation_domain); ?>
            </label>
             <label for="<?php echo $this->get_field_id('excerpt_words_limit'); ?>" style="display:block; margin-left:1.8em">
            	<?php _e('Limit my excerpt to this many words <small>(<code>0</code> for no limit)</small>:', $this->translation_domain); ?>
				<input type="text" 
					size="2"
					id="<?php echo $this->get_field_id('excerpt_words_limit'); ?>" 
					name="<?php echo $this->get_field_name('excerpt_words_limit'); ?>" 
					value="<?php echo (int)$options['excerpt_words_limit']; ?>"
				/>
            </label>
            <label for="<?php echo $this->get_field_id('thumbnail'); ?>" style="display:block;">
				<input type="checkbox" 
					id="<?php echo $this->get_field_id('thumbnail'); ?>" 
					name="<?php echo $this->get_field_name('thumbnail'); ?>" 
					value="1" <?php echo ($options['thumbnail'] ? 'checked="checked"' : ''); ?> 
				/>
            	<?php _e('Show thumbnail', $this->translation_domain); ?>
           </label>
           <label for="<?php echo $this->get_field_id('limit'); ?>" style="line-height:35px;display:block;">
            	<?php _e('Limit', $this->translation_domain); ?>:
				<select id="<?php echo $this->get_field_id('limit'); ?>" name="<?php echo $this->get_field_name('limit'); ?>">
					<?php for ($i=1; $i<=10; $i++) { ?>
						<?php $selected = ($i == $options['limit']) ? 'selected="selected"' : ''; ?>
						<option value="<?php echo $i; ?>" <?php echo $selected;?>><?php echo $i;?></option>
					<?php } ?>
				</select> 
           </label>
           <label for="<?php echo $this->get_field_id('lookahead'); ?>" style="line-height:35px;display:block;">
            	<?php _e('Lookahead', $this->translation_domain); ?>:
				<select id="<?php echo $this->get_field_id('lookahead'); ?>" name="<?php echo $this->get_field_name('lookahead'); ?>">
					<?php for ($i=1; $i<=52; $i++) { ?>
						<?php $selected = ($i == $options['lookahead']) ? 'selected="selected"' : ''; ?>
						<option value="<?php echo $i; ?>" <?php echo $selected;?>><?php printf(__('%d weeks', Eab_EventsHub::TEXT_DOMAIN), $i);?></option>
					<?php } ?>
				</select>
           </label>
           <label for="<?php echo $this->get_field_id('category'); ?>" style="line-height:35px;display:block;">
            	<?php _e('Only Events from this category', $this->translation_domain); ?>:
				<select id="<?php echo $this->get_field_id('category'); ?>" name="<?php echo $this->get_field_name('category'); ?>">
					<option><?php _e('Any', $this->translation_domain);?></option>
					<?php foreach ($categories as $category) { ?>
						<?php $selected = ($category->term_id == $options['category']) ? 'selected="selected"' : ''; ?>
						<option value="<?php echo $category->term_id; ?>" <?php echo $selected;?>><?php echo $category->name;?></option>
					<?php } ?>
				</select> 
           </label>
           <?php do_action('eab-widgets-upcoming-widget_form', $options, $this); ?>
	</div>
	<?php
    }
}
