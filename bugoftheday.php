<?php
/*
 * Plugin Name: Bug Of The Day
 * Plugin URI: http://blog.tafoni.net/2010/05/04/bug-of-the-day/
 * Description: Add a bug to your blog. Display the latest photo in Jenn Forman Orth's Bug Of The Day series on Flickr.
 * Version: 1.6
 * Author: Dawn Endico
 * Author URI: http://www.tafoni.net/
 * License:  Released under GNU Lesser General Public License (http://www.gnu.org/copyleft/lgpl.html)
*/

if ( !class_exists('phpFlickr') ) {
  require_once("phpFlickr.php");
}

/**
 * Add function to widgets_init that'll load our widget.
 */
add_action( 'widgets_init', 'botd_load_widgets' );

/**
 * Register the widget.
 */
function botd_load_widgets() {
  register_widget( 'Bug_Of_The_Day_Widget' );
}

/**
 * Bug_Of_The_Day Widget class.
 */
class Bug_Of_The_Day_Widget extends WP_Widget {

  /**
   * Widget setup.
   */
  function Bug_Of_The_Day_Widget() {
    /* Widget settings. */
    $widget_ops = array( 'classname' => 'botd', 'description' => __('Add a bug to your sidebar') );

    /* Create the widget. */
    $this->WP_Widget( 'botd-widget', __('Bug Of The Day', 'botd'), $widget_ops, $control_ops );
  }

  /**
   * How to display the widget on the screen.
   */
  function widget( $args, $instance ) {
   
    extract( $args );
    /* Our variables from the widget settings. */
    $image_size = $instance['image_size']?$instance['image_size']:"Small";

    $f = new phpFlickr("9f596527813ac8f27cce0cd676154d4f");
    $f->enableCache('custom', array(array('Bug_Of_The_Day_Widget', 'cache_get'), array('Bug_Of_The_Day_Widget', 'cache_set')));

    $results = $f->photos_search(array(
      'user_id' => '49503155549@N01',
      'tags' => 'bugoftheday',
      'sort' => 'date-posted-desc',
      'per_page' => '1',
      ));
  

    /* Before widget (defined by themes). */
    echo $before_widget;

    echo $before_title .  "Bug Of The Day" . $after_title;


    if ($results['photo']) {
       foreach ($results['photo'] as $photo) { 
         // Build image and link tags for each photo
         $sizes = $f->photos_getSizes($featureID);
         echo "<a href=\"http://www.flickr.com/photos/$photo[owner]/$photo[id]\">\n";
         echo '<img alt="' . htmlentities($photo[title]) . '" ' .
             'title="' .  htmlentities($photo[title]) . '" ' .
             'src="' . $f->buildPhotoURL($photo, $image_size) .
             '" height="' . $sizes[2]['height'] .
             '" width="' . $sizes[2]['width'] . '"' . "/>";
         echo "</a>\n";
         echo '<p> Jenn Forman Orth</p>';
       }
     }

    /* After widget (defined by themes). */
    echo $after_widget;
  }

  /**
   * Update the widget settings.
   */
  function update( $new_instance, $old_instance ) {
    $instance = $old_instance;
    $instance['image_size'] = $new_instance['image_size'];
    return $instance;
  }

  /**
   * Displays the widget settings controls on the widget panel.
   * Make use of the get_field_id() and get_field_name() function
   * when creating your form elements. This handles the confusing stuff.
   */
  function form( $instance ) {

    /* Set up some default widget settings. */
    $defaults = array( 'image_size' => 'Small');
    $instance = wp_parse_args( (array) $instance, $defaults ); ?>

    <p>
      <label for="<?php echo $this->get_field_id( 'image_size' ); ?>"><?php _e('Image Size:', 'botd'); ?></label> 
      <br>
      <input class="radio" type="radio" value="Small"
             name="<?php echo $this->get_field_name( 'image_size' ); ?>"
             <?php if($instance['image_size'] =="Small") echo " checked" ?>
      > 240 x 180
      <br>
      <input class="radio" type="radio" value="Thumbnail"
             name="<?php echo $this->get_field_name( 'image_size' ); ?>"
             <?php if($instance['image_size'] =="Thumbnail") echo " checked" ?>
      > 100 x 75
    </p>

  <?php
  }

  function cache_get($key) {
           global $wpdb;
           $result = $wpdb->get_row('
                   SELECT
                           *
                   FROM
                           `' . $wpdb->prefix . 'phpflickr_cache`
                   WHERE
                           request = "' . $wpdb->escape($key) . '" AND
                           expiration >= NOW()
           ');
           if ( is_null($result) ) return false;
           return $result->response;
   }

   function cache_set($key, $value, $expire) {
           global $wpdb;
           $query = '
                   INSERT INTO `' . $wpdb->prefix . 'phpflickr_cache`
                           (
                                   request,
                                   response,
                                   expiration
                           )
                   VALUES
                           (
                                   "' . $wpdb->escape($key) . '",
                                   "' . $wpdb->escape($value) . '",
                                   FROM_UNIXTIME(' . (time() + (int) $expire) . ')
                           )
                   ON DUPLICATE KEY UPDATE
                           response = VALUES(response),
                           expiration = VALUES(expiration)
           ';
           $wpdb->query($query);
   }
}

?>
