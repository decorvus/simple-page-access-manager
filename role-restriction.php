<?php
/**
 * Plugin Name: Simple Page Access Manager - Restrict Pages/Posts by User Role
 * Plugin URI: https://github.com/decorvus/simple-page-access-manager
 * Description: Enable user role restriction per page or post.
 * Version: 1.0.0
 * Author: decorvus
 * Author URI: https://github.com/decorvus/simple-page-access-manager
 */
// Constants
define('SAM_PLUGIN_PATH', plugin_dir_url( __FILE__ ));
define('SAM_PLUGIN_FILE', SAM_PLUGIN_PATH.'role-restriction.php');
include_once( plugin_dir_path( __FILE__ ) . 'updater.php');
$updater = new Simple_page_access_manager_updater( __FILE__ ); 
$updater->set_username( 'decorvus' ); 
$updater->set_repository( 'simple-page-access-manager' ); 
$updater->initialize(); 
if( ! class_exists( 'Simple_page_access_manager_updater' ) ){
	include_once( plugin_dir_path( __FILE__ ) . 'updater.php' );
}
if(!function_exists('sam_admin_enqueue_scripts')){
    add_action( 'admin_enqueue_scripts', 'sam_admin_enqueue_scripts');
    function sam_admin_enqueue_scripts($hook) {
        wp_enqueue_media();
        wp_enqueue_style( 'wp-color-picker');
        wp_enqueue_script( 'wp-color-picker');
        wp_enqueue_script('sam-custom-scripts', SAM_PLUGIN_PATH.'js/admin-scripts.js', array('jquery'));
        if(get_current_screen()->base == 'toplevel_page_sam-default-settings') wp_enqueue_style('sam-custom-styles', SAM_PLUGIN_PATH.'css/admin-styles.css');
        $cm_settings['codeEditor'] = wp_enqueue_code_editor(array('type' => 'text/css'));
        wp_localize_script('jquery', 'cm_settings', $cm_settings);
        wp_enqueue_script('wp-theme-plugin-editor');
        wp_enqueue_style('wp-codemirror');
    }
}


// Edit page/post hooks 
if(!function_exists('sam_meta_init') && !function_exists('sam_meta_init') && !function_exists('sam_page_options_meta_box')){
    add_action( 'load-post.php', 'sam_meta_init' );
    add_action( 'load-post-new.php', 'sam_meta_init' );
    add_action( 'load-page.php', 'sam_meta_init' );
    add_action( 'load-page-new.php', 'sam_meta_init' );
    function sam_meta_init(){
        $current_screen = get_current_screen()->id; //current post type slug
        $allowed_post_types = get_option('allowed_post_types');
        if(in_array($current_screen, $allowed_post_types)){
            add_action( 'add_meta_boxes', 'sam_add_post_meta_boxes' );
            wp_enqueue_style('sam-custom-styles', SAM_PLUGIN_PATH.'css/admin-styles.css');
        }
        add_action( 'save_post', 'sam_save_post_meta', 10, 2 );
    }
    // Create the meta box to be displayed on the post editor screen. 
    function sam_add_post_meta_boxes() {
        add_meta_box(
            'sam-page-options',
            esc_html__( 'Page Access', 'sam' ), 
            'sam_page_options_meta_box',
            NULL,     
            'advanced', 
            'default' 
        );
    }
    // Display the post meta box.
    function sam_page_options_meta_box( $post ) { 
        wp_nonce_field( basename( __FILE__ ), 'sam_page_options_nonce' );
        $post_id = $post->ID;
        $postmeta = maybe_unserialize( get_post_meta( $post_id, 'sam_page_options', true ) );
        $show_override = maybe_unserialize( get_post_meta($post_id,'show_override', false) )[0];
        $enable_access_schedule = maybe_unserialize( get_post_meta($post_id,'enable_access_schedule', false) )[0]; 
        $access_schedule = maybe_unserialize( get_post_meta($post_id,'access_schedule', false) )[0];  
        if(!$show_override){ //default value taken from global options
            $restriction_method = get_option('restriction_method');
            $show_error_message = get_option('show_error_message');
            $redirect_slug = get_option('redirect_slug');
            $error_message_background_color = get_option('error_message_background_color');
            $error_message_text_color = get_option('error_message_text_color');
            $pagepost_access_denied = get_option('pagepost_access_denied');
            $additional_content = get_option('additional_content');
            $custom_css = get_option('custom_css');
        }else{
            $restriction_method = maybe_unserialize( get_post_meta($post_id,'restriction_method', false) )[0]; 
            $show_error_message = maybe_unserialize( get_post_meta($post_id,'show_error_message', false) )[0]; 
            $redirect_slug = maybe_unserialize( get_post_meta($post_id,'redirect_slug', false) )[0]; 
            $error_message_background_color = maybe_unserialize( get_post_meta($post_id,'error_message_background_color', false) )[0]; 
            $error_message_text_color = maybe_unserialize( get_post_meta($post_id,'error_message_text_color', false) )[0]; 
            $pagepost_access_denied = maybe_unserialize( get_post_meta($post_id,'pagepost_access_denied', false) )[0]; 
            $additional_content = (string) maybe_unserialize( get_post_meta($post_id,'additional_content', false) )[0]; 
            $custom_css = maybe_unserialize( get_post_meta($post_id,'custom_css', false) )[0]; 
        }
    ?>   <table class="sam-page-options">
            <tr>
                <td>
                <p><b>Users that can access this page</b></p>
                <p style="font-size: 0.8em; color: ccc;">leave blank to allow all</p>
                <ul class="user-roles-list" style="margin-bottom: 20px;">
                    <?php
                        global $wp_roles;
                        $roles = $wp_roles->roles;
                        if($roles){        
                            foreach( $roles as $key=>$role ) {   
                                if ( is_array( $postmeta ) && in_array( $key, $postmeta ) ) { $checked = 'checked="checked"'; } 
                                else { $checked = null; }   
                                ?><li>
                                    <label for="<?php echo $key; ?>" class="toggler-wrapper"><input type="checkbox" name="allowedroles[]" id="<?php echo $key; ?>" value="<?php echo $key; ?>" <?php echo $checked; ?>/><div class="toggler-slider"><div class="toggler-knob"></div></div><span> <?php echo $role['name']; ?></span></label>
                                </li><?php
                            }
                        }
                    ?>
                </ul>
                </td>
                <td>
                    <p><b>Restriction Schedule</b></p>
                    <label for="enable_access_schedule" class="inline-check toggler-wrapper"><input type="checkbox" name="enable_access_schedule[]" id="enable_access_schedule"<?php if($enable_access_schedule) echo 'checked'; ?>  conditional-formatting="true" data-condition="enable_access_schedule" class="field"/><div class="toggler-slider"><div class="toggler-knob"></div></div><span> Enable access scheduling</span></label>
                    <div class="schedule-wrap" condition="enable_access_schedule" <?php if($enable_access_schedule) echo 'show="true"'; else echo 'show="false"'; ?>>
                    <label for="access_schedule" style="font-weight: 600; margin-bottom: 10px;">Restrict page until</label>
                    <input type="date" class="datepicker" name="access_schedule" id="access_schedule" min="<?php echo date('Y-m-d'); ?>" value="<?php echo $access_schedule; ?>"/>    
                    </div>
                </td>
            </tr>
            </table>
            <p><b>Restriction Settings</b></p>
            <label for="show_override" class="inline-check toggler-wrapper"><input type="checkbox" name="show_override[]" id="show_override"<?php if($show_override) echo 'checked'; ?>  class="field"/><div class="toggler-slider"><div class="toggler-knob"></div></div><span> Override Default Settings</span></label>
            <div class="access-manager-wrapper" style="grid-template-columns: 1fr; margin: 0;">
                <table id="page-overrides" class="form-table" style="<?php if(!$show_override) echo 'display: none;'; ?>">
                    <tr>
                        <th colspan=3>
                            <p style="font-size: 20px; margin: 0;"><b>Page Overrides</b></p>
                        </th>
                    </tr>
                    <tr>
                        <td>
                            <label for="restriction_method">Restriction Method</label>
                            <select id="restriction_method" name="restriction_method" conditional-formatting="true" data-condition="restriction_method" class="field">
                                <option value="redirect" <?php if($restriction_method == 'redirect') echo 'selected'; ?>>Redirect</option>
                                <option value="stay" <?php if($restriction_method == 'stay') echo 'selected'; ?>>Stay on current page</option>
                            </select>
                        </td>
                        <td condition="restriction_method" condition-value="redirect" show="<?php if($restriction_method == 'redirect' || $restriction_method == '') echo 'true'; else echo 'false'; ?>">
                            <label for="redirect_slug">Redirect Slug</label>
                            <select id="redirect_slug" name="redirect_slug">
                                <?php echo sam_get_page_list(true, $post_id); ?>
                            </select>
                            <p style="font-size: 0.8em;">Select the page redirect destination.</p>
                        </td>
                    </tr>
                    <tr>
                        <td>
                            <label for="show_error_message" class="inline-check toggler-wrapper"><input type="checkbox" name="show_error_message" <?php if($show_error_message) echo 'checked'; ?> conditional-formatting="true" data-condition="show_error_message" id="show_error_message"  class="field"/><div class="toggler-slider"><div class="toggler-knob"></div></div><span> Show Error Message</span></label>
                        </td>
                        <td condition="show_error_message" <?php if($show_error_message) echo 'show="true"'; else echo 'show="false"'; ?>>
                            <label for="error_message_background_color">Error message background color</label>
                            <input type="text" class="color-picker" name="error_message_background_color" id="error_message_background_color" value="<?php echo $error_message_background_color; ?>"/>
                        </td>
                        <td condition="show_error_message" <?php if($show_error_message) echo 'show="true"'; else echo 'show="false"'; ?>>
                            <label for="error_message_text_color">Error message text color</label>
                            <input type="text" class="color-picker" name="error_message_text_color" id="error_message_text_color" value="<?php echo $error_message_text_color; ?>"/>
                        </td>
                    </tr>
                    <tr>
                        <td condition="show_error_message" <?php if($show_error_message) echo 'show="true"'; else echo 'show="false"'; ?> colspan=3>
                            <label for="pagepost_access_denied">Error message</label>
                            <textarea name="pagepost_access_denied" id="pagepost_access_denied"><?php echo $pagepost_access_denied; ?></textarea>
                        </td>
                    </tr>
                    <tr>
                        <td colspan=3>
                            <label for="additional_content">Insert content</label>
                            <?php 
                            wp_editor( $additional_content, 'additional_content', array() );
                            ?>
                        </td>
                    </tr>
                    <tr>
                        <td colspan=3>
                            <label for="custom_css">Custom CSS</label>
                            <textarea name="custom_css" id="custom_css"><?php echo $custom_css; ?></textarea>
                        </td>
                    </tr>
                </table>
            </div>
        <?php 
    }
}
if(!function_exists('sam_save_post_meta')){
    // Save validation for post meta
    function sam_save_post_meta( $post_id, $post ) {
        // Verify the nonce before proceeding.
        if ( !isset( $_POST['sam_page_options_nonce'] ) || !wp_verify_nonce( $_POST['sam_page_options_nonce'], basename( __FILE__ ) ) ){
            return $post_id;
        }
        $post_type = get_post_type_object( $post->post_type );
        // Verify user capabilities.
        if ( !current_user_can( $post_type->cap->edit_post, $post_id ) ){
            return $post_id;
        }
        if ( !empty($_POST['allowedroles']) ){
            update_post_meta( $post_id, 'sam_page_options', $_POST['allowedroles'] );
        }else{
            delete_post_meta( $post_id, 'sam_page_options' );
        }

        if(!empty($_POST['enable_access_schedule'])){
            update_post_meta( $post_id, 'enable_access_schedule', $_POST['enable_access_schedule'] );
            update_post_meta( $post_id, 'access_schedule', $_POST['access_schedule'] );
        }else{
            delete_post_meta( $post_id, 'enable_access_schedule');
            delete_post_meta( $post_id, 'access_schedule');
        }


        if(!empty($_POST['show_override'])){
            update_post_meta( $post_id, 'show_override', $_POST['show_override'] );
            update_post_meta( $post_id, 'restriction_method', $_POST['restriction_method'] );
            update_post_meta( $post_id, 'redirect_slug', $_POST['redirect_slug'] );
            update_post_meta( $post_id, 'show_error_message', $_POST['show_error_message'] );
            update_post_meta( $post_id, 'error_message_background_color', $_POST['error_message_background_color'] );
            update_post_meta( $post_id, 'error_message_text_color', $_POST['error_message_text_color'] );
            update_post_meta( $post_id, 'pagepost_access_denied', $_POST['pagepost_access_denied'] );
            update_post_meta( $post_id, 'additional_content', $_POST['additional_content'] );
            update_post_meta( $post_id, 'custom_css', $_POST['custom_css'] );
        }else{
            delete_post_meta( $post_id, 'show_override' );
            delete_post_meta( $post_id, 'restriction_method' );
            delete_post_meta( $post_id, 'redirect_slug' );
            delete_post_meta( $post_id, 'show_error_message' );
            delete_post_meta( $post_id, 'error_message_background_color' );
            delete_post_meta( $post_id, 'error_message_text_color' );
            delete_post_meta( $post_id, 'pagepost_access_denied' );
            delete_post_meta( $post_id, 'additional_content' );
            delete_post_meta( $post_id, 'custom_css' );
        }
    
    }
}
if(!function_exists('sam_default_settings_menu') && !function_exists('sam_default_options_init') && !function_exists('sam_default_settings_options') ){
    // Create the default options page
    add_action('admin_menu', 'sam_default_settings_menu');
    function sam_default_settings_menu() {
        global $submenu;
        $menu_slug = "sam-default-settings"; // used as "key" in menus
        $menu_pos = 20; // whatever position you want your menu to appear
        // $menu_icon = 'dashicons-lock';
        $menu_icon = SAM_PLUGIN_PATH.'img/icon-20x20.png';
        add_menu_page( 'Access Manager Page', 'Page Access Manager', 'manage_options', $menu_slug, 'sam_default_settings_options', $menu_icon, $menu_pos);
        add_action( 'admin_init', 'sam_default_options_init' );
    }
    function sam_default_options_init(){
        register_setting( 'sam-default-settings', 'restriction_method' );
        register_setting( 'sam-default-settings', 'redirect_slug' );
        register_setting( 'sam-default-settings', 'show_error_message' );
        register_setting( 'sam-default-settings', 'error_message_background_color' );
        register_setting( 'sam-default-settings', 'error_message_text_color' );
        register_setting( 'sam-default-settings', 'pagepost_access_denied' );
        register_setting( 'sam-default-settings', 'additional_content' );
        register_setting( 'sam-default-settings', 'custom_css' );
        register_setting( 'sam-default-settings', 'allowed_post_types' );
    }
    function sam_default_settings_options(){
        if(get_current_screen()->base == 'toplevel_page_sam-default-settings'){ ?>
        <h1 style="margin: 50px 0;">Access Manager Default Settings</h1>
        <form method="post" action="options.php"> 
            <?php settings_fields( 'sam-default-settings' ); ?>
            <?php do_settings_sections( 'sam-default-settings' ); ?>
            <?php 
                $restriction_method = get_option('restriction_method');
                $show_error_message = get_option('show_error_message');
                $redirect_slug = get_option('redirect_slug');
                $error_message_background_color = get_option('error_message_background_color');
                $error_message_text_color = get_option('error_message_text_color');
                $pagepost_access_denied = get_option('pagepost_access_denied');
                $additional_content = get_option('additional_content');
                $custom_css = get_option('custom_css');
                $allowed_post_types = get_option('allowed_post_types');
            ?>
            <div class="access-manager-wrapper">
                <table id="page-overrides" class="form-table">
                    <tr><th colspan=3>Access Manager Settings</th></tr>
                    <tr>
                        <td>
                            <label for="restriction_method">Restriction Method</label>
                            <select id="restriction_method" name="restriction_method" conditional-formatting="true" data-condition="restriction_method" class="field">
                                <option value="redirect" <?php if($restriction_method == 'redirect') echo 'selected'; ?>>Redirect</option>
                                <option value="stay" <?php if($restriction_method == 'stay') echo 'selected'; ?>>Stay on current page</option>
                            </select>
                        </td>
                        <td condition="restriction_method" condition-value="redirect" show="<?php if($restriction_method == 'redirect') echo 'true'; else echo 'false'; ?>">
                            <label for="redirect_slug">Redirect Slug</label>
                            <select id="redirect_slug" name="redirect_slug">
                                <?php echo sam_get_page_list(false, ''); ?>
                            </select>
                            <p style="font-size: 0.8em;">Select the page redirect destination.</p>
                        </td>
                    </tr>
                    <tr>
                        <td>
                            <label for="show_error_message" class="inline-check toggler-wrapper"><input type="checkbox" name="show_error_message" <?php if($show_error_message) echo 'checked'; ?> conditional-formatting="true" data-condition="show_error_message" id="show_error_message"  class="field"/><div class="toggler-slider"><div class="toggler-knob"></div></div><span> Show Error Message</span></label>
                        </td>
                        <td condition="show_error_message" <?php if($show_error_message) echo 'show="true"'; else echo 'show="false"'; ?>>
                            <label for="error_message_background_color">Error message background color</label>
                            <input type="text" class="color-picker" name="error_message_background_color" id="error_message_background_color" value="<?php echo $error_message_background_color; ?>"/>
                        </td>
                        <td condition="show_error_message" <?php if($show_error_message) echo 'show="true"'; else echo 'show="false"'; ?>>
                            <label for="error_message_text_color">Error message text color</label>
                            <input type="text" class="color-picker" name="error_message_text_color" id="error_message_text_color" value="<?php echo $error_message_text_color; ?>"/>
                        </td>
                    </tr>
                    <tr>
                        <td condition="show_error_message" <?php if($show_error_message) echo 'show="true"'; else echo 'show="false"'; ?> colspan=3>
                            <label for="pagepost_access_denied">Error message</label>
                            <textarea name="pagepost_access_denied" id="pagepost_access_denied"><?php echo $pagepost_access_denied; ?></textarea>
                        </td>
                    </tr>
                    <tr>
                        <td colspan=3>
                            <label for="additional_content">Insert content</label>
                            <?php 
                            wp_editor( $additional_content, 'additional_content', array() );
                            ?>
                        </td>
                    </tr>
                    <tr>
                        <td colspan=3>
                            <label for="custom_css">Custom CSS</label>
                            <textarea name="custom_css" id="custom_css"><?php echo $custom_css; ?></textarea>
                        </td>
                    </tr>
                </table>
                <table id="test" class="form-table">
                    <tr><th>General Settings</th></tr>
                    <tr>
                        <td>
                            <label for="allowed_post_types">Enable on the following: </label>
                            <ul class="post-types-list">
                                <?php echo sam_get_all_post_types(); ?>
                            </ul>
                        </td>
                    </tr>
                </table>
            </div>
            <?php submit_button(); ?>
        </form>
        <?php
        }
    } 
}
if(!function_exists('sam_get_page_list')){
    // Populate page list field with all page slugs
    function sam_get_page_list($is_page, $post_id) {
        $args = array(
            'post_status' => 'publish',
            'posts_per_page' => -1
        );
        $pages = get_pages($args);
        $field_options = '';
        if(!$is_page && get_option('restriction_method') == 'redirect'){
            $selected_val = get_option('redirect_slug');
        }
        if($is_page){
            $selected_val = get_post_meta($post_id, 'redirect_slug', false)[0];
        }
        if($pages){        
            foreach( $pages as $key=>$page ) {      
                $title = $page->post_title;
                $slug = $page->post_name;
                if($selected_val == $slug){
                    $selected = 'selected';
                }else{
                    $selected = '';
                }
                $field_options .= '<option value="'.$slug.'" '.$selected.'>'.$title.'</option>';
            }
        }
        return $field_options;
    }
}
if(!function_exists('sam_role_restriction_filter_content') && !function_exists('sam_get_all_post_types')){
    // Content filter logic
    add_filter('the_content', 'sam_role_restriction_filter_content');
    function sam_role_restriction_filter_content($content){
        $current_screen = get_post_type(); //current post type slug
        $allowed_post_types = get_option('allowed_post_types');
        $post_id = get_the_id();
        $enable_access_schedule = get_post_meta($post_id, 'enable_access_schedule', false)[0];
        if($enable_access_schedule){
            $access_schedule = get_post_meta($post_id, 'access_schedule', false)[0];
            if(date('Y-m-d') > $access_schedule){
                $enable_restrict = false;
            }else{
                $enable_restrict = true;
            }
        }else{
            $enable_restrict = true;
        }
        
        if (in_the_loop() && in_array($current_screen, $allowed_post_types) && $enable_restrict){ //only affeect the body content and the allowed post types in the global settings
            $role_restrictions = (array) get_post_meta( $post_id, 'sam_page_options', true ); 
            $user = wp_get_current_user();
            $user_roles = (array) $user->roles;
            $show_override = get_post_meta($post_id, 'show_override', false)[0][0];
            if($show_override == 'on'){ //use page options if override is enabled
                $restriction_method = get_post_meta($post_id, 'restriction_method', false)[0];
                $show_error_message = get_post_meta($post_id, 'show_error_message', false)[0];
                $redirect_slug = get_post_meta($post_id, 'redirect_slug', false)[0];
                $error_message_background_color = get_post_meta($post_id, 'error_message_background_color', false)[0];
                $error_message_text_color = get_post_meta($post_id, 'error_message_text_color', false)[0];
                $error_message = get_post_meta($post_id, 'pagepost_access_denied', false)[0];
                $additional_content = get_post_meta($post_id, 'additional_content', false)[0];
                $custom_css = get_post_meta($post_id, 'custom_css', false)[0];
            }else if($show_override == NULL){ //use default options
                $restriction_method = get_option('restriction_method');
                $show_error_message = get_option('show_error_message');
                $redirect_slug = get_option('redirect_slug');
                $error_message_background_color = get_option('error_message_background_color');
                $error_message_text_color = get_option('error_message_text_color');
                $error_message = get_option('pagepost_access_denied');
                $additional_content = get_option('additional_content');
                $custom_css = get_option('custom_css');
            }
            if($additional_content != ''){$margin = '100px';}
            else{$margin = '0';}
            echo  '<style>
                    .access-error-message{
                        color: '.$error_message_text_color.';
                        background:  '.$error_message_background_color.';
                        padding: 30px;
                        text-align: center;
                        margin-bottom: '.$margin.';
                    }                   
                </style>';
            if($custom_css != ""){echo "<style>".$custom_css."</style>";}   
            if (count($role_restrictions) >= 1 && $role_restrictions[0] != ""){ // check if current page has restrictions set
                $matched_roles = array_intersect($role_restrictions, $user_roles); //compare page restrictions with user role
                if (count($matched_roles) == 0){ //if user role is not within allowed roles ($role_restrictions), execute restriction
                    if ($restriction_method == 'redirect'){
                        if (!is_page($redirect_slug)){
                            if($redirect_slug == 'home') {$redirect_slug = '';}
                            wp_safe_redirect(home_url().'/'.$redirect_slug.'?redirected=true&rdid='.$post_id); //set the redirect path, add redirected variable and origin page ID to make error message appear on the page
                        }
                    }else if ($restriction_method == 'stay'){
                        if ($show_error_message){
                            $content = '<div class="access-error-message">'.$error_message.'</div>';
                            if ($additional_content != ""){
                                $content .= '<div class="additional-content">'.$additional_content.'</div>';
                            }
                        }else{
                            if ($additional_content != ""){
                                $content = '<div class="additional-content">'.$additional_content.'</div>'; //replace page content with additional content
                            }else{
                                $content = ''; //hide content without any messages on the page
                            }
                        }
                    }  
                }else{ //if user role is in the allowed array
                    foreach($matched_roles as $role){
                        /* 
                            Potential feature that could be added here: maybe have the option to let the user select custom redirects per 
                            user role. This option will be available per page. This could be worked on a separate "development" branch

                            -Carlo
                        */
                        //per role validation here
                        // if ($role == "role1"){}
                        // else if ($role == "role2"){}
                        // else{}
                    }
                }
            }
            //Executes if the page had just redirected by checking the redirect_slug and checking for the ?redirected=true variable
            if($_GET['rdid'] != NULL){
                $post_id = $_GET['rdid'];
                $show_override = get_post_meta($post_id, 'show_override', false)[0][0];
                if($show_override == 'on'){
                    $restriction_method = get_post_meta($post_id, 'restriction_method', false)[0];
                    $show_error_message = get_post_meta($post_id, 'show_error_message', false)[0];
                    $redirect_slug = get_post_meta($post_id, 'redirect_slug', false)[0];
                    $error_message_background_color = get_post_meta($post_id, 'error_message_background_color', false)[0];
                    $error_message_text_color = get_post_meta($post_id, 'error_message_text_color', false)[0];
                    $error_message = get_post_meta($post_id, 'pagepost_access_denied', false)[0];
                    $additional_content = get_post_meta($post_id, 'additional_content', false)[0];
                    $custom_css = get_post_meta($post_id, 'custom_css', false)[0];
                }else if($show_override == NULL){
                    $restriction_method = get_option('restriction_method');
                    $show_error_message = get_option('show_error_message');
                    $redirect_slug = get_option('redirect_slug');
                    $error_message_background_color = get_option('error_message_background_color');
                    $error_message_text_color = get_option('error_message_text_color');
                    $error_message = get_option('pagepost_access_denied');
                    $additional_content = get_option('additional_content');
                    $custom_css = get_option('custom_css');
                }
                if($additional_content != ''){$margin = '100px';}
                else{$margin = '0';}
                echo  '<style>
                        .access-error-message{
                            color: '.$error_message_text_color.';
                            background:  '.$error_message_background_color.';
                            padding: 30px;
                            text-align: center;
                            margin-bottom: '.$margin.';
                        }                   
                    </style>';
                if ($restriction_method == 'redirect' && is_page($redirect_slug) && $_GET['redirected'] && !wp_get_referer()){    
                    if ($show_error_message){  //show error message for a few seconds then animate to remove
                        if($additional_content != ""){
                            $content = '<div class="access-error-message">'.$error_message.'</div>'.$additional_content.$content; //insert additional content below error message
                        }else{
                            $content = '<div class="access-error-message">'.$error_message.'</div>'.$content;
                        }
                        echo '<style>.access-error-message{margin-top: 100px;}</style>';
                        echo "<script type='text/javascript'>
                            document.addEventListener('DOMContentLoaded', function(event) { 
                                jQuery(document).ready(function(){
                                    timeout = setTimeout(hideMessage, 3000);
                                });
                                function hideMessage(){
                                    jQuery('.access-error-message').animate({
                                        'opacity'   : 0, 
                                        'height'    : 0, 
                                        'padding'   : 0, 
                                        'margin'    : 0
                                    }, 1000, updateUrl);
                                }
                                function updateUrl(){
                                    jQuery('.access-error-message').remove();
                                    var url = window.location.href;
                                    url = url.split('?')[0];
                                    window.history.replaceState({}, null, url);
                                }
                            });
                            </script>";
                    }
                }
            }
        }return $content;
    }
    function sam_get_all_post_types(){
        global $wp_post_types;
        $sam_post_types = '';
        $allowed_post_types = get_option('allowed_post_types');
        foreach ( $wp_post_types as $type ) {
            if ( isset( $type) && !$type->exclude_from_search && $type->name != 'attachment') {
                if(!$allowed_post_types) $checked = 'checked';
                if(is_array($allowed_post_types) && in_array($type->name, $allowed_post_types)) $checked = 'checked';
                else{
                    $checked = '';
                }
                $sam_post_types .= '<li>
                <label for="'.$type->name.'" class="toggler-wrapper">
                <input type="checkbox" name="allowed_post_types[]" id="'.$type->name.'" value="'.$type->name.'" '.$checked.'/>
                <div class="toggler-slider"><div class="toggler-knob"></div></div><span>'.$type->label.'</span></label></li>';
            }
        }
        return $sam_post_types;
    }
}