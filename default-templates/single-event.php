<?php get_header( 'event' ); ?>
<div id="content">
    <div class="padder">
        <div id="eab-page-wrapper">
            
            <?php
            the_eab_error_notice();
            the_post();
            ?>
            
            <div id="event-bread-crumbs">
                <a href="<?php echo event_link('event_or_calendar');?>" class="parent"><?php _e("Events", Booking::$_translation_domain); ?></a> &gt;
                <span class="current"><?php the_title(); ?></span>
            </div>
            
            <div id="single-event">
                <h1><?php the_title(); ?></h1>
                <?php the_content(); ?>
            </div>
            
            <div id="event-details">
                <?php event_details(); ?>
            </div>
            
            <div id="event-rsvp">
                <?php event_rsvp_form(); ?>
            </div>
            
            <?php if (is_user_logged_in()) {?>
            <div id="event-bookings">
            <?php if (has_bookings()) {?>
                <h3><?php _e("Attendees", Booking::$_translation_domain); ?></h3>
                <div id="event-booking-yes">
                    <?php event_bookings('yes'); ?>
                </div>
                <div class="clear"></div>
                <div id="event-booking-maybe">
                    <?php event_bookings('maybe'); ?>
                </div>
            <?php }  else { ?>
                <div id="event-first-booking">
                    <?php _e("Be the first to RSVP", Booking::$_translation_domain); ?>
                </div>
            <?php } ?>
            </div>
            <?php } ?>
        </div>
    </div>
</div>
<?php get_sidebar('event'); ?>
<?php get_footer('event'); ?>
