<?php
/*
Plugin Name: Agree on a Date
Plugin URI: http://aufpapier.de/agree-on-a-date-plugin/
Description: This plugin allows you to agree on a date in your WP-Blog
Author: Matthias Meiling
Version: 1.0.0.4
Author URI: http://aufpapier.de
*/

/* hook plugin with WP */

global $wpdb;
define('DB_CORE', $wpdb->prefix."dateagr_core");
register_activation_hook( __FILE__, array(new DateAgreement(), 'on_install'));
// register_deactivation_hook( __FILE__, array(new DateAgreement(), 'on_deactivate'));
register_uninstall_hook( __FILE__, array('DateAgreement', 'on_uninstall'));

add_shortcode('DateAgreement', array(new DateAgreement(), 'shortcode'));

class DateAgreement {
	protected $db_core;
	public function __construct(){
		/* set up global values */
		global $wpdb;
		$this->db_core = DB_CORE;
		add_action('wp_print_styles', array($this, 'add_css'));
	}

	function add_css(){
		$style = WP_PLUGIN_URL . '/agree-on-a-date/dagr.css';
		$location = WP_PLUGIN_DIR . '/agree-on-a-date/dagr.css';
		if ( file_exists($location) ) {
			wp_register_style('template', $style);
			wp_enqueue_style( 'template');
		} 
	}

	/* helper functions */
	function convert_string($input){
		return htmlspecialchars ($input, $double_encode = false);
	}

	function parse_time($input){
		/* Converts a string of kind: "jj.mm.dd:hh.mm; ... " into timeslots */
		$trim_string = "; \n\t";
		$output = array();
		$slots = explode(";", ltrim(rtrim($input, $trim_string), $trim_string));
		foreach ( $slots as $slot){
			/* parse Slots */
			list($date, $time) = explode(":", $slot);
			list($year, $month, $day) = explode(".", $date);
			list($hour, $minute) = explode(".", $time);

			if ( ($time = mktime(intval($hour), intval($minute), 0, intval($month), intval($day), intval($year))) ){
				$output[] = $time;
			}
		}
		sort($output);
		return $output;
	}

	function time_string($input){
		return date("D d.m.y H:i", $input);
	}
	
	function display_time($input){
		$time = getdate($input);
		$wdays = array("SU", "MO", "TU", "WE", "TH", "FR", "SA");
		$wday = $wdays[$time['wday']];
		$date = sprintf("%02u.%02u", $time['mday'], $time['mon']);
		$time_string = sprintf("%02u:%02u", $time['hours'], $time['minutes']);
		$returnString = '
			<div class="dagr_time_wrapper">
				<div class="dagr_time_header">'.$wday.'</div>
				<div class="dagr_time_body">'.$date.'<br>'.$time_string.'</div>
			</div>
		';
		return $returnString;
	}

	/* parse the shortcode of post */
	function shortcode( $attrs, $content = "" ){
		extract ( shortcode_atts ( array (
			description => "",
			active => "true",
			time => ""), $attrs)
		);
		$status = $this->perform_db_request();
		$description = $this->convert_string($description);
		$time = $this->parse_time($time);
		
		/* parse active */
		if ($active == "false") {
			$active = false;
		} else {
			$active = true;
		}

		if ( count($time) <= 0 ){
			/* parsing-Error */
			return "An error orrured with <b>Agree on a Date</b> Plugin";	/*locate*/
		}

		$participants = $this->get_db();

		/* output */
		$returnString = "<div class=\"dagr_wrapper\"><div class=\"dagr_descr\">".$description."</div>\n";
		/* print status */
		if ( isset($status) )
			$returnString .= '<div class="dagr_status">'.$status.'</div>';

		if ( $active ){
			$returnString .= '<form action="'.get_permalink().'" method="post">';
			$returnString .= '<input type="hidden" name="dagr" value="true" >';

			/*nonce*/
			$returnString .= wp_nonce_field( 'nonce_verify', 'dagr_nonce', true, false );
		}

		$returnString .= '<ul class="dagr_timeslots">';

		/* namescolumn */
		$returnString .= '<li><ul class="dagr_names"><li class="dagr_first">&nbsp;</li>'; /* first element is empty */
		foreach($participants as $participant){
			$returnString .= "<li>".$participant['name']."</li>\n";
		}

		if ( $active )
			$returnString .= '<li><input name="dagr_name" type="text" /></li>';

		$returnString .= "</ul></li>";

		/* timeslots */
		foreach ($time as $timeslot){
			$returnString .= '<li><ul class="dagr_slot"><li class="dagr_header">'.$this->display_time($timeslot)."</li>\n";
			foreach ($participants as $participant ){
				if ( in_array($timeslot, $participant['time']) ){
					$returnString .= "<li class=\"dagr_yes\">yes</li>\n";
				} else {
					$returnString .= "<li class=\"dagr_no\">no</li>\n";
				}
			}

			if ($active)
				$returnString .='<li><input type = "checkbox" name = "timeslots[]" value = "'.$timeslot.'" /></li>';
			
			$returnString .= '</ul></li>';

		}
		$returnString .= "</ul>";

		if ($active) 
			$returnString .= '<p style="clear:left"><input type="submit" value="update" /></p></form>';

		$returnString .= '</div>';


		return $returnString;
	}

	function perform_db_request(){

		if (isset ($_POST)){
			if (isset ($_POST['dagr'])){
				/* check nonce */
				if (! wp_verify_nonce($_POST['dagr_nonce'], 'nonce_verify') ) return "nonce not verified";

				if (isset($_POST['dagr_name']) and strlen($_POST['dagr_name']) > 0){
					/* Put in DB-Routine here */
					if (isset ($_POST['timeslots'])){
						$timeslot_string = implode(";", $_POST['timeslots']);
					} else {
						$timeslot_string = "";
					}
					return $this->insert_db($_POST['dagr_name'], $timeslot_string);
				} else {
					return "please insert name\n";
				}
				return "an unknown warning occured\n";
			}
		}
	}

	function insert_db($name, $time_slots){
		global $wpdb;
		if ($wpdb->get_var("show tables like '$this->db_core'") == $this->db_core){
			$insert = "INSERT INTO `".$this->db_core."`".
				"(`article`, `participant`, `ackstring`) VALUES (".
				 "'" .$wpdb->escape(get_the_ID()). "', ".
				 "'".$wpdb->escape($this->convert_string($name))."', ".
				 "'".$wpdb->escape($this->convert_string($time_slots))."')";
			if ( $wpdb->query( $insert ) ){
				return "database updated";
			}
		} else {
			//var_dump($this->on_install());
			return "error: table does not exists";
		}
	}

	function get_db(){
		global $wpdb;
		$results =  $wpdb->get_results("SELECT `participant`, `ackstring` FROM `".$this->db_core."` WHERE `article`=".get_the_ID());
		$participants = array();
		foreach ($results as $result){
			$participants[] = array('name'=>$result->participant, 'time'=>explode(";", $result->ackstring));
		}
		return $participants;

	}

	function on_install (){
		$structure = "CREATE TABLE `".$this->db_core."` ( 
			id DOUBLE NOT NULL AUTO_INCREMENT,
			article DOUBLE NOT NULL,
			participant VARCHAR (32),
			ackstring VARCHAR(600),
			editstring VARCHAR (8),
			UNIQUE KEY ID (id)
		);";
		require_once(ABSPATH . 'wp-admin/includes/upgrade.php');
		return dbDelta($structure);
	}

	function on_deactivate(){
		/* dummy */
	}

	function on_uninstall(){
		global $wpdb;
		$request = "DROP TABLE ".DB_CORE.";";
		return $wpdb->query($request);
	}
}
?>
