<?php if (!defined ('ABSPATH')) die('No direct access allowed');
/**
 * WP Backitup Admin Class
 * 
 * @package WP Backitup
 * 
 * @author cssimmon
 * 
 */


class WPBackitup_Admin {

    public $namespace = WPBACKITUP__NAMESPACE;
    public $friendly_name = WPBACKITUP__FRIENDLY_NAME;
    public $version = WPBACKITUP__VERSION;

    private static $instance = false;

    //Use Getters
    private $license_key;//Loaded in getter
    private $license_type; //Loaded in getter
    private $license_expires;
    
    private $license_active;//Loaded in getter
    private $license_status;//Loaded in getter   
    private $license_status_message;//Loaded in getter   
    private $license_type_description; //Getter will load

    private $backup_retained_number; //Getter will load
    private $notification_email;//Getter will load
    private $logging;//Getter will load

    private $backup_count; //getter will load
    private $successful_backup_count;
    
    // Default plugin options
    public $defaults = array(
        'logging' => false,
        'license_key' => "lite",
        'license_last_check_date'=> "1970-01-01 00:00:00",
        'license_status' => "",
        'license_status_message'=> "",
        'license_type' => "0",
        'license_expires'=> "1970-01-01 00:00:00",
        'license_limit'=> "1",
        'license_sitecount'=> "",
        'notification_email' => "",
        'backup_retained_number' => "3",
        'lite_backup_retained_number' => "1",
        'backup_count'=>0,
        'successful_backup_count'=>0,
        'stats_last_check_date'=> "1970-01-01 00:00:00",
    );


     /**
     * Retrieve the current WP backItUp instance.
     */
    public static function get_instance() {
        if ( ! self::$instance ) {
//            echo('new instance');
            self::$instance = new self();
        }

        return self::$instance;
    }

    /**
     * Instantiation construction
     * 
     */
    private function __construct() {
        /**
         * Make this plugin available for translation.
         * Translations can be added to the /languages/ directory.
         */

        //TODO: Add multi Language Support back
        //load_theme_textdomain( $this->namespace, WPBACKITUP__PLUGIN_DIR . '/languages' );

        // Add all action, filter and shortcode hooks
        $this->_add_hooks();
    }

    /**
     * Add in various hooks
     */
    private function _add_hooks() {

        // Options page for configuration
        add_action( 'admin_menu', array( &$this, 'admin_menu' ) );

        // Route requests for form processing
        add_action( 'admin_init', array( &$this, 'route' ) );        
        
        // Add a settings link next to the "Deactivate" link on the plugin listing page
        add_filter( 'plugin_action_links', array( &$this, 'plugin_action_links' ), 10, 2 );
        
        //Load all the resources
        add_action( 'admin_enqueue_scripts', array( &$this, 'load_resources' ) );   

        //Load the backup action
        add_action('wp_ajax_backup', array( &$this, 'ajax_backup' ));

        //Load the restore action
        add_action('wp_ajax_restore', array( &$this, 'ajax_restore' ));

        //Load the upload action
        add_action('wp_ajax_upload', array( &$this, 'ajax_upload' ));

	    //Status reader for UI
	    add_action('wp_ajax_status_reader', array( &$this,'ajax_status_reader'));

        add_action('wp_ajax_response_reader', array( &$this,'ajax_response_reader'));

        //Delete File Action
        add_action('wp_ajax_delete_file', array( &$this,'ajax_delete_file'));

        //View Log Action
        add_action('admin_post_viewlog', array( &$this,'admin_viewlog'));

        //List Logs Action
        add_action('admin_post_nopriv_listlogs', array( &$this,'admin_listlogs'));

    }


    /**
     * 
     * Define the admin menu options for this plugin
     * 
     */
    public  function admin_menu() {
      
       // add_menu_page( $page_title, $menu_title, $capability, $menu_slug, $function, $icon_url, $position );
        add_menu_page( $this->friendly_name, $this->friendly_name, 'administrator', $this->namespace, array( &$this, 'admin_backup_page' ), WPBACKITUP__PLUGIN_URL .'/images/icon.png', 77);

        //Add Backup Menu Nav
        add_submenu_page( $this->namespace, 'Backup', 'Backup', 'administrator', $this->namespace.'-backup', array( &$this, 'admin_backup_page' ) );
        
        //Add Restore Menu Nav IF licensed
        if ($this->license_type()!=0) {
            add_submenu_page( $this->namespace, 'Restore', 'Restore', 'administrator', $this->namespace.'-restore', array( &$this, 'admin_restore_page' ) );
        }

        //Add Settings Menu Nav
        add_submenu_page( $this->namespace, 'Settings', 'Settings', 'administrator', $this->namespace.'-settings', array( &$this, 'admin_settings_page' ) );

        if (WPBACKITUP__DEBUG===true){
            add_submenu_page( $this->namespace, 'Test', 'Test', 'administrator', $this->namespace.'-test', array( &$this, 'admin_test_page' ) );
        }
        // remove duplicate submenu page. wp limitations // 
        // http://wordpress.stackexchange.com/questions/16401/remove-duplicate-main-submenu-in-admin
        remove_submenu_page($this->namespace,$this->namespace); 
        
        // Add print scripts and styles action based off the option page hook
        add_action( 'admin_print_scripts', array( &$this, 'admin_print_scripts' ) );
        add_action( 'admin_print_styles', array( &$this, 'admin_print_styles' ) );
    }

    /**
     * Load JavaScript for the admin options page
     * 
     * @uses wp_enqueue_script()
     */
    public  function admin_print_scripts() {
        wp_enqueue_script( "{$this->namespace}-admin" );
        wp_enqueue_script( "{$this->namespace}-admin-viewlog" );

    }

    public  function load_resources() {
        // Admin JavaScript
        wp_register_script( "{$this->namespace}-admin", WPBACKITUP__PLUGIN_URL . "/js/admin.js", array( 'jquery' ), $this->version, true );
        //wp_register_script( "{$this->namespace}-admin-viewlog", WPBACKITUP__PLUGIN_URL . "/js/admin_test.js", array( 'jquery' ), $this->version, true );

        // Admin Stylesheet
        wp_register_style( "{$this->namespace}-admin", WPBACKITUP__PLUGIN_URL . "/css/admin.css", array(), $this->version, 'screen' );

        wp_register_style( 'google-fonts', '//netdna.bootstrapcdn.com/font-awesome/4.0.3/css/font-awesome.css');
        wp_enqueue_style( 'google-fonts' );
    }

    /**
     * Load Stylesheet for the admin options page
     * 
     * @uses wp_enqueue_style()
     */
    public  function admin_print_styles() {
        wp_enqueue_style( "{$this->namespace}-admin" );
    }
    
    /**
     * The admin section backup page rendering method
     * 
     */
    public  function admin_backup_page()
    {
      if( !current_user_can( 'manage_options' ) ) {
          wp_die( 'You do not have sufficient permissions to access this page' );
      }   

      include WPBACKITUP__PLUGIN_PATH . "/views/backup.php";
    }

    /**
     * The admin section restore page rendering method
     * 
     */
    public  function admin_restore_page()
    {
      if( !current_user_can( 'manage_options' ) ) {
          wp_die( 'You do not have sufficient permissions to access this page.' );
      }   

      include WPBACKITUP__PLUGIN_PATH . "/views/restore.php";
    }

    /**
     * The admin section settings page rendering method
     * 
     */
    public  function admin_settings_page()
    {

      if( !current_user_can( 'manage_options' ) ) {
          wp_die( 'You do not have sufficient permissions to access this page.' );
      }

      include WPBACKITUP__PLUGIN_PATH . "/views/settings.php";
    }

    /**
     * The admin section backup page rendering method
     *
     */
    public  function admin_test_page()
    {
        if( !current_user_can( 'manage_options' ) ) {
            wp_die( 'You do not have sufficient permissions to access this page' );
        }

        include WPBACKITUP__PLUGIN_PATH . "/views/test.php";
    }
  
     /**
     * Route the user based off of environment conditions
     * 
     * @uses WPBackitup::_admin_options_update()
     */
    public  function route() {
        $uri = $_SERVER['REQUEST_URI'];
        $protocol = isset( $_SERVER['HTTPS'] ) ? 'https' : 'http';
        $hostname = $_SERVER['HTTP_HOST'];
        $url = "{$protocol}://{$hostname}{$uri}";
        $is_post = (bool) ( strtoupper( $_SERVER['REQUEST_METHOD'] ) == "POST" );

        // Check if a nonce was passed in the request
        if( isset( $_REQUEST['_wpnonce'] ) ) {
            $nonce = $_REQUEST['_wpnonce'];
//            $logger->log('NONCE:' .$nonce);

            // Handle POST requests
            if( $is_post ) {

                if( wp_verify_nonce( $nonce, "{$this->namespace}-update-options" ) ) {
                    $this->_admin_options_update();
                }
            } 
            // Handle GET requests
            else {

            }
        }
    }

    public function initialize(){
        $this->check_license();
    }
    //load backup
    public  function ajax_backup() {
      include_once( WPBACKITUP__PLUGIN_PATH.'/lib/includes/backup.php' );
    }

    //load restore
    public  function ajax_restore() {
        include_once( WPBACKITUP__PLUGIN_PATH.'/lib/includes/restore.php' );
    }

    //load upload
    public  function ajax_upload() {
        include_once( WPBACKITUP__PLUGIN_PATH.'/lib/includes/upload.php' );
    }

    public  function ajax_status_reader() {
		$log = WPBACKITUP__PLUGIN_PATH .'/logs/status.log';
		if(file_exists($log) ) {
			readfile($log);
		}
		die();
	}

    public  function ajax_response_reader() {
        $log = WPBACKITUP__PLUGIN_PATH .'/logs/response.log';
        if(file_exists($log) ) {
            readfile($log);
        }else{
            $rtnData = new stdClass();
            $rtnData->message = 'No response log found.';
            echo json_encode($rtnData);
        }
        die();
    }

    public  function ajax_delete_file()
    {
        $backup_file_name = str_replace('deleteRow', '', $_POST['filed']);
        $backup_file_path =  WPBACKITUP__BACKUP_PATH .'/' . $backup_file_name;
        $log_file_path = str_replace('.zip','.log',$backup_file_path);

        if (file_exists($backup_file_path)){
            unlink($backup_file_path);
        }

        if (file_exists($log_file_path)) {
            unlink($log_file_path);
        }

        exit('deleted');
    }

    function admin_viewlog(){
        include_once( WPBACKITUP__PLUGIN_PATH.'/lib/includes/viewlog.php' );
    }

    function admin_listlogs(){
        include_once( WPBACKITUP__PLUGIN_PATH.'/lib/includes/listlogs.php' );
    }



    /**
     * Process update page form submissions and validate license key
     * 
     */
    public  function _admin_options_update() {
        // Verify submission for processing using wp_nonce
        if( wp_verify_nonce( $_REQUEST['_wpnonce'], "{$this->namespace}-update-options" ) ) {

            /**
             * Loop through each POSTed value and sanitize it to protect against malicious code. Please
             * note that rich text (or full HTML fields) should not be processed by this function and 
             * dealt with directly.
             */
           
            $logger = new WPBackItUp_Logger(false);
            $logger->log("Posted Fields");
            $logger->log($_POST['data']); //License will not be in this array

            foreach( $_POST['data'] as $key => $val ) {
                $posted_value = $this->_sanitize($val);
                //If license updated then validate
                if (!empty($key) && $key=='license_key') {
                    $logger->log('License Posted:' .$posted_value);
                    $this->update_license_options($posted_value);
                }
                else {
                    $data[$key] =$posted_value;
                    }
            }

            $license_description = $this->license_type_description();

            //Could have just been a license update
            if(!empty($data)) {

                //Set back to original settings if value not changed
                if(!empty($data['backup_retained_number']) && !is_numeric($data['backup_retained_number']))
                {
                  $data['backup_retained_number'] = $this->defaults['backup_retained_number'];
                  set_transient('settings-error-number', __('Please enter a number', $this->namespace), 60);
                }
                else{ //Empty OR not NUMERIC

                    //Empty
                    if ( empty($data['backup_retained_number']) ){
                        set_transient('settings-error-number', __('Please enter a number', $this->namespace), 60);
                    }

                    //exceeds lite threshold
                    if ( !empty($data['backup_retained_number']) && ($this->license_type()==0)  && ($data['backup_retained_number'] > 1) ){
                        $data['backup_retained_number'] = $this->defaults['lite_backup_retained_number'];
                        set_transient('settings-license-error', __(ucfirst($license_description) .' license holders may only save 1 backup archive.', $this->namespace), 60);
                    }

                    //exceeds pro threshold
                    if (!empty($data['backup_retained_number']) && ($this->license_type()==1) && ($data['backup_retained_number'] > 3)){
                        $data['backup_retained_number'] = $this->defaults['backup_retained_number'];
                        set_transient('settings-license-error', __(ucfirst($license_description) .' license holders may only save up to 3 backup archives.', $this->namespace), 60);
                    }

                }

                if(!empty($data['notification_email']) && !is_email($data['notification_email']))
                {
                  $data['notification_email'] = $this->defaults['notification_email'];
                  set_transient('settings-error-email', __('Please enter a a valid email', $this->namespace), 60);
                }

                // Update the options value with the data submitted
                foreach( $data as $key => $val ) {
                    $this->set_option($key, $val);
                    $logger->log('Updated Option: ' .$key .':' .$val);
                }
            }

            // Redirect back to the options page with the message flag to show the saved message
            wp_safe_redirect( $_REQUEST['_wp_http_referer'] . '&update=1' );
            exit;
        }
    }

    /**
     * Hook into plugin_action_links filter
     * 
     * @param object $links An array of the links to show, this will be the modified variable
     * @param string $file The name of the file being processed in the filter
     * 
     */
    public  function plugin_action_links( $links, $file ) {

        // Add links to plugin
        if ( $file == plugin_basename( WPBACKITUP__PLUGIN_PATH . '/wp-backitup.php' ) ) {
            $settings_link = '<a href="' . esc_url( self::get_settings_page_url() ) . '">'.esc_html__( 'Settings' , 'wp-backitup').'</a>';
            array_unshift($links, $settings_link);
        }

        return $links;
    }

    /**
     * 
     *       GETTERS
     * 
     **/   

    /**
    * Generic Getter
    */
    public  function get($property) {

       if (empty($this->$property)) {
         $this->$property = $this->get_option($property);

         //If not set then use the defaults
          if (empty($this->$property)) {
            $this->$property=$this->defaults[$property];
          }
      }

      return $this->$property;
        
    }


    /**
    * Getter - license key
    */
    public function license_key(){
      return $this->get('license_key');
    }

    /**
    * Getter - license status message
    */
    public function license_status_message(){
      return $this->get('license_status_message');
    }

    /**
    * Getter - license expires
    */
    public function license_expires(){
      return $this->get('license_expires');
    }

    /**
    * Getter - notification email
    */
    public function notification_email(){
      return $this->get('notification_email');
    }

    /**
    * Getter - logging
    */
    public function logging(){
      $logging = $this->get('logging');
      return $logging === 'true'? true: false;
    }

    /**
    * Getter - license active - derived property
    */
    public function license_active(){
      //echo('</br>license Active Value1:' .$this->license_active);   
       
      if (empty($this->license_active)) {
        //echo('</br>SET PROP');   

        $this->license_active = false;//default

        $license_key = $this->license_key();
        $license_status = $this->license_status();

        //Allow expired licenses to be active for now
        if(false !== $license_key && false !== $license_status) { 
         if ('valid'== $license_status || 'expired'== $license_status) {
            $this->license_active= true;
          } 
        }        
      }

      //echo('</br>license Active Value2:' .$this->license_active);  
      return $this->license_active;
    }

    /**
    * Getter - license status
    */
    public function license_status(){
      return $this->get('license_status');
    }


    /**
    * Getter: Get license type or default
    */
    public function license_type(){
       return $this->get('license_type');
    }

    /**
    * Getter - license type description - derived property
    */
    public function license_type_description(){

        if (empty($this->license_type_description)) {
            
            switch ($this->license_type()) {
                case 0:
                    $this->license_type_description = 'lite';
                    break;
                case 1:
                    $this->license_type_description = 'personal';
                    break;

                case 2:
                    $this->license_type_description = 'business';
                    break;

                case 3:
                    $this->license_type_description = 'professional';
                    break;
            }
        }

        return $this->license_type_description;
    }

    /**
    * Getter - backup retained number - derived property
    */
    public function backup_retained_number(){
        if (empty($this->backup_retained_number)) {
            $this->backup_retained_number = $this->get_option('backup_retained_number');

            //If not set then use the defaults
            if (empty($this->backup_retained_number)) {

                switch ($this->license_type()) {
                    case 0: //Lite
                        $this->backup_retained_number=1;
                        break;
                    case 1: //Personal
                        $this->backup_retained_number=3;
                        break;

                    case 2: //Business
                        $this->backup_retained_number=3;
                        break;

                    case 3: //Pro
                        $this->backup_retained_number=3;
                        break;
                }

                $this->set_option('backup_retained_number',$this->backup_retained_number); 
            }

        }
        
        return $this->backup_retained_number;
        
    }

    function backup_count(){
       return $this->get('backup_count');
    }

    function successful_backup_count(){
        return $this->get('successful_backup_count');
    }

    /**---------- END GETTERS --------------- **/

    /**---------- SETTERS --------------- **/

    /**
     * Generic Setter
     */
    private  function set($property,$value) {

        $this->set_option($property, $value);
        $this->$property = $value;

        //If not set then use the defaults
        if (empty($this->$property)) {
            $this->$property=$this->defaults[$property];
        }

    }

    function set_backup_count($value){
        $this->set('backup_count', $value);
    }

    function set_successful_backup_count($value){
        $this->set('successful_backup_count', $value);
    }

    /**---------- END SETTERS --------------- **/


    /**-------------- LICENSE FUNCTIONS ---------------**/

    /**
     * Validate License Info Once per day
     */
    public function check_license(){
        $license_key=$this->license_key(); 
        //echo "</br>License Key:" .$license_key;
        
        $license_last_check_date=$this->get_option('license_last_check_date');

        //Validate License once per day
        $license_last_check_date = new DateTime($license_last_check_date);
        //echo($license_last_check_date->format('Y-m-d H:i:s') .'</br>');
          
        $now = new DateTime('now');//Get NOW
        $yesterday = $now->modify('-1 day');//subtract a day
        //$yesterday = $now->sub(new DateInterval('P1D'));//subtract a day PHP 3.0 only
        //echo($yesterday->format('Y-m-d H:i:s') .'</br>');

        //Validate License
        if ($license_last_check_date<$yesterday)
        {
          //echo "Validate License";
          $this->update_license_options($license_key);
          //$this->update_stats($license_key);
        }
    }

    /**
    * Update ALL the license options
    */
    private function update_license_options($license)
    {
        $logger = new WPBackItUp_Logger(false);
        $logger->log('Update License Options:' .$license);

        $license=trim($license);

        //Load the defaults
        $data['license_key'] = $this->defaults['license_key'];
        $dt = new DateTime('now');
        $data['license_last_check_date'] = $dt->format('Y-m-d H:i:s');

        $data['license_status'] = $this->defaults['license_status'];
        $data['license_status_message']= $this->defaults['license_status_message'];
        $data['license_expires']= $this->defaults['license_expires'];
        $data['license_limit']= $this->defaults['license_limit'];
        $data['license_sitecount']= $this->defaults['license_sitecount'];
        $data['license_type']= $this->defaults['license_type'];

        //If no value then default to lite     
        if (empty($license) || 'lite'== $license ){
            $data['license_status'] = 'free';
            $data['license_expires']= $this->defaults['license_expires'];
            $data['license_limit']= 1;
            $data['license_sitecount']= 1;
            $data['license_type']= 0;        
        } else {
            //CALL EDD_ACTIVATE_LICENSE to get activation information
            $api_params = array( 
                'edd_action'=> 'activate_license', 
                'license'   => $license, 
                'item_name' => urlencode( WPBACKITUP__ITEM_NAME ) // the name of our product in EDD
            );
            
            $response = wp_remote_get( add_query_arg( $api_params, WPBACKITUP__SECURESITE_URL ), array( 'timeout' => 15, 'sslverify' => true ) );
            $logger->log('Validation Response:');
            $logger->log($response);

            if ( is_wp_error( $response ) )
                return false; //Exit and don't update
            
            $license_data = json_decode( wp_remote_retrieve_body( $response ) ); 
            $logger->log('License Object Info');
            $logger->log($license_data);

            $data['license_key'] = $license;
            $data['license_status'] = $license_data->license;
            $data['license_limit'] = $license_data->license_limit;
            $data['license_sitecount'] = $license_data->site_count;
            $data['license_expires'] = $license_data->expires; 

            //This is how we determine the type of license because
            //there is no difference in EDD
            if (is_numeric($license_data->license_limit)){

                //Personal
                if ($license_data->license_limit<5) {
                        $data['license_type'] = 1;
                }

                //Business
                if ($license_data->license_limit>=5  && $license_data->license_limit<20) {
                        $data['license_type'] = 2;
                }

               //Professional
               if ($license_data->license_limit>=20) {
                        $data['license_type'] = 3;
                }

                //EDD sends back expired in the error
                if (($license_data->license=='invalid') && ($license_data->error=='expired')){

                    //Default to valid for now
                    $data['license_status'] ='valid';
                    $data['license_status_message'] ='';

                    //Only expire license in current month
                    $license_expire_date = $license_data->expires;
                    $expire_date_array = date_parse($license_expire_date);
                    $logger->log('Expire Date Array');
                    $logger->log($expire_date_array);
                    $logger->log('Expire Month: ' .$expire_date_array[month]);
                    $logger->log('Current Month: ' .date('m'));

                    //only EXPIRE current month
                    if ($expire_date_array[month]==date('m')) {
                        $data['license_status'] ='expired';
                        $data['license_status_message'] ='License has expired.';
                        $logger->log('Expire License.');
                    }
                }

                if (($license_data->license=='invalid') && ($license_data->error=='no_activations_left')){
                    $data['license_status_message'] ='Activation limit has been reached.';
                }
            }  
        }

        $logger->log('Updating License Options');  
        foreach($data as $key => $val ) {
            $this->set_option($key, $val);
            $logger->log('Updated Option: ' .$key .':' .$val);
        }
        return true;
    }
    
    /**-------------- END LICENSE FUNCTIONS ---------------**/

    /**
     * Retrieve the stored plugin option or the default if no user specified value is defined
     * 
     * @param string $option_name
     * 
     * @uses get_option()
     * 
     * @return mixed Returns the option value or false(boolean) if the option is not found
     */
    public function get_option( $option_name ) {
        // Load option values if they haven't been loaded already
        $wp_option_name = $this->namespace .'_' .$option_name;

        //Use this after migration
        //$option_value = get_option($wp_option_name,$this->defaults[$option_name]);   
       
        $option_value = get_option($wp_option_name);  

        //return the value
        if(isset( $option_value ) && !empty( $option_value )) return $option_value;
        
        //Should only happen once
        //Can take this out in next release
        //If looking for license then migrate the old settings
        if ('license_key'==$option_name) {
            $options = get_option('_' . $this->namespace . '--options');
            $license = $options[$option_name];
            if( isset( $license ) || !empty( $license ) ) {
                //migrate to new option setting
                $this->set_option($option_name, $license);
                $this->update_license_options($license);
                
                //Delete the old options  
                delete_option('_' . $this->namespace . '--options');               

                return $license;
            }
        
        }
        //Return the default
        return $this->defaults[$option_name];
    }

    //Prefix options with namespace & save
    public function set_option($option_name, $value) {
        $option_name = $this->namespace .'_' .$option_name;
        update_option($option_name,$value);

        //Check class variables
        if($option_name=='license_type')
            $this->license_type= $value;       
    }

    public function increment_backup_count(){
        $backup_count = $this->backup_count();
        $backup_count=$backup_count+1;
        $this->set_backup_count($backup_count);
    }

    public function increment_successful_backup_count(){
        $successful_backup_count = $this->successful_backup_count();
        $successful_backup_count=$successful_backup_count+1;
        $this->set_successful_backup_count($successful_backup_count);
    }

     /**
     * Sanitize data
     * 
     * @param mixed $str The data to be sanitized
     * 
     * @uses wp_kses()
     * 
     * @return mixed The sanitized version of the data
     */
    private function _sanitize( $str ) {
        if ( !function_exists( 'wp_kses' ) ) {
            include_once ABSPATH . 'wp-includes/kses.php';
        }
        global $allowedposttags;
        global $allowedprotocols;
        
        if ( is_string( $str ) ) {
            $str = wp_kses( $str, $allowedposttags, $allowedprotocols );
        } elseif( is_array( $str ) ) {
            $arr = array();
            foreach( (array) $str as $key => $val ) {
                $arr[$key] = $this->_sanitize( $val );
            }
            $str = $arr;
        }
        
        return $str;
    }

    /**STATIC FUNCTIONS**/

    private static function get_settings_page_url( $page = 'config' ) {

        $args = array( 'page' => 'wp-backitup-settings' );
        $url = add_query_arg( $args, admin_url( 'admin.php' ));

        return $url;
    }

    
    /**
     * Activation action
     */
    public static function activate() {
//        $logger = new WPBackItUp_Logger(true);

       try{
            //Check backup folder folders
            $backup_dir = WPBACKITUP__CONTENT_PATH . '/' . WPBACKITUP__BACKUP_FOLDER;
             if( !is_dir($backup_dir) ) {
                 @mkdir($backup_dir, 0755);
//                 $logger->log('Backup Folder Created:' . $backup_dir);
             }

             //Check restore folder folders
             $restore_dir = WPBACKITUP__CONTENT_PATH . '/' . WPBACKITUP__RESTORE_FOLDER;
             if( !is_dir($restore_dir) ) {
                 @mkdir($restore_dir, 0755);
//                 $logger->log('Restore Folder Created:' . $backup_dir);
             }

           //Make sure they exist now
           if( !is_dir($backup_dir) || !is_dir($restore_dir)) {
               exit ('WP BackItUp was not able to create the required backup and restore folders.');
           }

       } catch (Exception $e) {
//           $logger->log(' Activation Exception:' . $e->getMessage());
           exit ('WP BackItUp encountered an error during activation.</br>' .$e->getMessage());
       }
    }

    /**
     * Deactivation action
     */
    public static function deactivate() {
        // Do deactivation actions

    }

    /* ---------------------     PRIVATES      -----------------------------------------*/

    /**
     * Update statistics
     */
    private function update_stats($license)
    {
        $logger = new WPBackItUp_Logger(true);
        $logger->log('Update Stats:' .$license);

        $license=trim($license);

        //Get stats here

        //Setup API call
        $api_params = array(
            'wpb_action'=> 'update_stats',
            'license'   => $license
        );

        $url = WPBACKITUP__SECURESITE_URL .'/stats-update-test';
        $response = wp_remote_get( add_query_arg( $api_params, $url ), array( 'timeout' => 15, 'sslverify' => true ) );
        $logger->log('Stats Response:');
        $logger->log($response);

        if ( is_wp_error( $response ) )
            return false; //Exit and don't update

        //$license_data = json_decode( wp_remote_retrieve_body( $response ) );

        return true;
    }

    function get_anchor_with_utm($pretty,$page,$campaign,$content=null, $term=null ){

        $medium='plugin';
        $source=$this->namespace;

        $utm_url = WPBACKITUP__SITE_URL .'/' .$page .'/?utm_medium=' .$medium . '&utm_source=' .$source .'&utm_campaign=' .$campaign;

        if (!empty($content)){
            $utm_url .= '&utm_content=' .$content;
        }

        if (!empty($term)){
            $utm_url .= '&utm_term=' .$term;
        }

        $anchor = '<a href="'.$utm_url .'" target="_blank">' .$pretty .'</a>';
        return $anchor;

    }

    /* ---------------------   END PRIVATES   -----------------------------------------*/


}
