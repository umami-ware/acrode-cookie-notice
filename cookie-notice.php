<?php
/*
Plugin Name: acrode Cookie Notice
Description: acrode Cookie Notice allows you to elegantly inform users that your site uses cookies. This plugin also helps website owners to comply with the EU cookie law.
Version: 2.3.1
Author: acrode
Author URI: https://acrode.com
*/

//Exit if accessed directly
if ( ! defined( 'ABSPATH' ) )
    exit;

//Set plugin instance
$cookie_notice = new Cookie_Notice();

//Cookie Notice class.
class Cookie_Notice {

    //Defaults variable
    private $defaults = array(
		'general' => array(
		    'message_text'  => '',
		    'accept_text'   => '',
		    'settings_text' => '',
		    'save_text'     => '',
            'time'          => '90',
            'tabs' => array(
                array(
                    'title'         => '',
                    'description'   => '',
                    'select'        => '',
                    'provider'      => '',
                    'purpose'       => '',
                    'options'       => array(
                        array(
                            'title'         => '',
                            'description'   => '',
                            'script'        => ''
                        )
                    )
                )
            ),
            'deactivation_delete'   => 'no',
            'show_on_function_call'   => 'no',
			'version'               => '2.3.1'
		)
	);

	private $options = array();

    //Constructor
	public function __construct() {
		register_activation_hook( __FILE__, array( $this, 'activation' ) );
		register_deactivation_hook( __FILE__, array( $this, 'deactivation' ) );

		//Settings
		$this->options = array(
			'general' => array_merge( $this->defaults['general'], get_option( 'cookie_notice_options', $this->defaults['general'] ) )
		);

		//Actions
		add_action( 'admin_init', array( $this, 'register_settings' ) );
		add_action( 'admin_menu', array( $this, 'admin_menu_options' ) );
		add_action( 'admin_enqueue_scripts', array( $this, 'admin_enqueue_scripts' ) );
		add_action( 'wp_enqueue_scripts', array( $this, 'wp_enqueue_scripts' ) );
		add_action( 'wp_footer', array( $this, 'add_cookie_notice' ), 1000 );
		add_action( 'wp_ajax_modal_loader', array( $this, 'modal_loader' ) ); 
        add_action( 'wp_ajax_nopriv_modal_loader', array( $this, 'modal_loader' ) ); 
	}

    //Add submenu
    public function admin_menu_options() {
        add_options_page( 'Cookie Notice', 'Cookie Notice', apply_filters( 'cn_manage_cookie_notice_cap', 'manage_options' ), 'cookie-notice', array( $this, 'options_page' ) );
    }

	//Options page output
	public function options_page() {
		echo '<div class="wrap">
			    <h2>Cookie Notice</h2>
			    <div class="cookie-notice-settings">
				    <form action="options.php" method="post">';
                      settings_fields( 'cookie_notice_options' );
                      do_settings_sections( 'cookie_notice_options' );
                      echo '<p class="submit">';
                      submit_button( 'Save settings', 'primary', 'save_cookie_notice_options', false );
                      echo ' ';
                      submit_button( 'Reset to defaults', 'secondary', 'reset_cookie_notice_options', false );
                      echo '</p>
				    </form>
			  </div>
			<div class="clear"></div>
		</div>';
	}

	//Regiseter plugin settings.
	public function register_settings() {
		register_setting( 'cookie_notice_options', 'cookie_notice_options', array( 'sanitize_callback' => array( $this, 'validate_options' ) ) );

		//Сonfiguration
		add_settings_section( 'cookie_notice_configuration', 'Plugin settings', array( $this, 'cn_section_configuration' ), 'cookie_notice_options' );
		add_settings_field( 'cn_message_text', 'Message', array( $this, 'cn_message_text' ), 'cookie_notice_options', 'cookie_notice_configuration' );
		add_settings_field( 'cn_accept_text', 'Button text', array( $this, 'cn_accept_text' ), 'cookie_notice_options', 'cookie_notice_configuration' );
		add_settings_field( 'cn_time', 'Cookie expiry', array( $this, 'cn_time' ), 'cookie_notice_options', 'cookie_notice_configuration' );
		add_settings_field( 'cn_tabs_list', 'Cookie tabs', array( $this, 'cn_tabs_list' ), 'cookie_notice_options', 'cookie_notice_configuration' );
        add_settings_field( 'cn_deactivation_delete', 'Deactivation', array( $this, 'cn_deactivation_delete' ), 'cookie_notice_options', 'cookie_notice_configuration' );
        add_settings_field( 'cn_show_on_function_call', 'Show Cookie Notifaction only after calling a function', array( $this, 'cn_show_on_function_call' ), 'cookie_notice_options', 'cookie_notice_configuration' );
	}

    //Plugin settings.
    public function cn_section_configuration() {}

	//Cookie message option.
	public function cn_message_text() {
		echo '
		<fieldset>
			<div id="cn_message_text">
				<textarea name="cookie_notice_options[message_text]" class="large-text" cols="50" rows="4">' . esc_textarea( $this->options['general']['message_text'] ) . '</textarea>
				<p class="description">Enter cookie notification messages.</p>
			</div>
		</fieldset>';
	}

	//Accept cookie label option.
	public function cn_accept_text() {
		echo '
		<fieldset>
			<div id="cn_accept_text">
				<input type="text" class="regular-text" name="cookie_notice_options[accept_text]" value="' . esc_attr( $this->options['general']['accept_text'] ) . '" />
                <p class="description">Button text: allow the use of cookies.</p>
                <input type="text" class="regular-text" name="cookie_notice_options[settings_text]" value="' . esc_attr( $this->options['general']['settings_text'] ) . '" />
                <p class="description">Button text: cookie settings.</p>
                <input type="text" class="regular-text" name="cookie_notice_options[save_text]" value="' . esc_attr( $this->options['general']['save_text'] ) . '" />
                <p class="description">Button text: save cookie settings.</p>
            </div>
		</fieldset>';
	}

	//Expiration time option.
	public function cn_time() {
		$times = array(
			'1'     => '1 day',
			'7'     => '1 week',
			'30'    => '1 month',
			'90'    => '3 months',
			'180'   => '6 months',
			'360'   => '1 year',
			'1080'  => '3 years'
		);

		echo '
		<fieldset>
			<div id="cn_time">
              <select name="cookie_notice_options[time]">';
              foreach ( $times as $time => $value ) {
                echo '<option value="' . $time . '" ' . selected( $this->options['general']['time'], $time ) . '>' . esc_html( $value ) . '</option>';
              }
		echo '</select>
              <p class="description">The period of time for how long cookies should be stored.</p>
			</div>
		</fieldset>';
	}

    //Cookie tabs option.
    public function cn_tabs_list() {
        echo '
        <fieldset class="cn-tabs">
            <ul class="cn-tabs-caption">';
            foreach( $this->options['general']['tabs'] as $t=>$tab ) {
                echo ( ( $t == 0 ) ? '<li class="cn-active">' . ( ( $tab['title'] ) ? $tab['title'] : 'New tab ' . ($t+1) ) . '</li>' : '<li>' . ( ( $tab['title'] ) ? $tab['title'] : 'New tab ' . ($t+1) ) . '<button type="button" class="cn-remove-tab"><svg><path d="M1.4 0L0 1.4 3.6 5 0 8.6 1.4 10 5 6.4 8.6 10 10 8.6 6.4 5 10 1.4 8.6 0 5 3.6 1.4 0z"/></svg></button></li>' );
            }
            echo '
                <li class="cn-add-tab">
                    <button type="button"><svg><path d="M5 0v5H0v2h5v5h2V7h5V5H7V0H5z"/></svg></button>
                </li>
            </ul>';
            foreach( $this->options['general']['tabs'] as $i=>$tab ) {
                echo '
                <div class="' . ( ( $i == 0 ) ? 'cn-tabs-content cn-active' : 'cn-tabs-content' ) . '">
                    <p class="cn-label">Tab Title:</p>
                    <input type="text" class="regular-text" name="cookie_notice_options[tabs]['.$i.'][title]" value="' . esc_attr( $tab['title'] ) . '" />
                    <p class="cn-label">Tab Description:</p>
                    <textarea class="large-text" cols="50" name="cookie_notice_options[tabs]['.$i.'][description]" rows="3">' . esc_textarea( $tab['description'] ) . '</textarea>
                    <p class="cn-subtitle">Cookie options</p>
                    <div class="cn-options-caption">
                        <div>
                            <p class="cn-label">Column Title:</p>
                            <input type="text" class="regular-text" name="cookie_notice_options[tabs]['.$i.'][select]" value="' . esc_attr( $tab['select'] ) . '" />
                        </div>
                        <div>
                            <p class="cn-label">Column Title:</p>
                            <input type="text" class="regular-text" name="cookie_notice_options[tabs]['.$i.'][provider]" value="' . esc_attr( $tab['provider'] ) . '" />
                        </div>
                        <div>
                            <p class="cn-label">Column Title:</p>
                            <input type="text" class="regular-text" name="cookie_notice_options[tabs]['.$i.'][purpose]" value="' . esc_attr( $tab['purpose'] ) . '" />
                            <p class="description">Leave this field empty if the tab is used as an information.</p>
                        </div>
                    </div>';
                foreach( $tab['options'] as $key=>$option ) {
                    echo '
                    <div class="cn-option-list">
                        <hr/>
                        <button type="button" class="cn-remove-option">Remove option</button>
                        <p class="cn-label">Option Title:</p>
                        <input type="text" class="regular-text" name="cookie_notice_options[tabs]['.$i.'][options]['.$key.'][title]" value="' . esc_attr( $option['title'] ) . '" />
                        <p class="cn-label">Option Description:</p>
                        <textarea name="cookie_notice_options[tabs]['.$i.'][options]['.$key.'][description]" class="large-text" rows="3">' . esc_textarea( $option['description'] ) . '</textarea>
                        <p class="cn-label">Option script:</p>
                        <textarea name="cookie_notice_options[tabs]['.$i.'][options]['.$key.'][script]" class="large-text" rows="4">' . esc_textarea( $option['script'] ) . '</textarea>
                        <p class="description">Enter the JavaScript code enclosed in <code>script</code> tags (two <code>script</code> tags possible; first only with <code>src</code> and <code>async</code> attributes) here (e.g. Google Analytics). The code will be executed after the cookies have been accepted by the user.<br>The second <code>script</code> will be executed as callback after the first <code>script</code> has been loaded.<br>Leave this field empty if the tab is used as an information.</p>
                    </div>';
                }
                echo '
                    <hr/>
                    <button type="button" class="cn-add-option button button-primary">Add option</button>';
    		    echo '
    		    </div>';
    		}
        echo '
        </fieldset>';
    }

	//Delete plugin data on deactivation.
	public function cn_deactivation_delete() {
		echo '
		<fieldset>
			<label><input id="cn_deactivation_delete" type="checkbox" name="cookie_notice_options[deactivation_delete]" value="1" ' . checked( 'yes', $this->options['general']['deactivation_delete'], false ) . '/>Enable if you want all plugin data to be deleted after plugin deactivation.</label>
		</fieldset>';
    }
    
    //Show cookie notification only after calling a function
	public function cn_show_on_function_call() {
		echo '
		<fieldset>
			<label><input id="cn_show_on_function_call" type="checkbox" name="cookie_notice_options[show_on_function_call]" value="1" ' . checked( 'yes', $this->options['general']['show_on_function_call'], false ) . '/>Enable if you want to show the cookie notification only after calling a JavaScript function (<code>window.acrode.initCookieNotification</code>).</label>
		</fieldset>';
	}

    //Validate options.
	public function validate_options( $input ) {
		if ( ! check_admin_referer( 'cookie_notice_options-options') )
			return $input;

		if ( ! current_user_can( apply_filters( 'cn_manage_cookie_notice_cap', 'manage_options' ) ) )
			return $input;

		if ( isset( $_POST['save_cookie_notice_options'] ) ) {

            //Version
            $input['version'] = time();

			//Deactivation
            $input['deactivation_delete'] = (bool) isset( $input['deactivation_delete'] ) ? 'yes' : 'no';
            
            //Show cookie notification only after calling a function
			$input['show_on_function_call'] = (bool) isset( $input['show_on_function_call'] ) ? 'yes' : 'no';

            //Create js
            $script = "var cn_time = " . $input['time'] . ", cn_version = " . $input['version'] . ";\n";
            $script .= 'var cn_script = [';

            foreach( $input['tabs'] as $tab ) { //  $this->options['general']['tabs']
                foreach( $tab['options'] as $option ) {
                    if( $option['script'] ){
                        $script .= '{';

                        //Script with src
                        preg_match_all('#<script.*?src=+(.*?)><\/script>#is', $option['script'], $src);
                        if( $src[1][0] ) {
                            $script .= 'link: ' . $src[1][0] . ',';
                        } else {
                            $script .= 'link: null,';
                        }

                        //Inline script callback
                        preg_match_all('#<script>(.*?)<\/script>#is', $option['script'], $scr);
                        if( $scr[1][0] ) {
                            $script .= 'callback: function(){' . trim(preg_replace('/\s\s+/', ' ', $scr[1][0])) . '}';
                        } else {
                            $script .= 'callback: null';
                        }

                        $script .= '},';
                    }
                }
            }

            $script .= "];\n";

            $generate_js = plugin_dir_path(__FILE__) . 'js/cookie.js';
            file_put_contents( $generate_js, $script );

            $static_js = file_get_contents( plugin_dir_path(__FILE__) . 'js/front.js' );
            file_put_contents( $generate_js, $static_js, FILE_APPEND | LOCK_EX );

		} elseif ( isset( $_POST['reset_cookie_notice_options'] ) ) {
			$input = $this->defaults['general'];
			add_settings_error( 'reset_cookie_notice_options', 'reset_cookie_notice_options', 'Settings restored to defaults.', 'updated' );
		}

		return $input;
	}

    //Cookie notice output.
    public function add_cookie_notice() {
        echo '
        <div class="cn-banner cn-banner-hidden' . ( $this->options['general']['show_on_function_call'] === 'yes' ? ' cn-init-fn' : '' ) . '" role="banner">
            <div class="cn-container">
                <svg xmlns="http://www.w3.org/2000/svg"><path d="M16.8 0C7.5 0 0 7.5 0 16.8c0 8.5 6.2 15.5 14.6 16.6 1 8.2 8 14.6 16.6 14.6C40.5 48 48 40.5 48 31.2v-1.7c0-.2-.1-.4-.3-.5s-.4-.2-.6-.2c-.2 0-.4.1-.6.1a4 4 0 0 1-4-3.5c0-.3-.3-.6-.6-.7-2.5-.6-4.2-3-4.2-5.4V19c0-.3-.2-.7-.5-.8-.6-.2-1-.8-1-1.5 0-.2.1-.5.2-.7.1-.2.1-.5 0-.7s-.3-.4-.5-.4l-2.2-.5C32.3 6.2 25.3 0 16.8 0zm0 1.6c7.6 0 14 5.4 15 12.8h-.6c-9.3 0-16.8 7.5-16.8 16.8v.6C7 30.7 1.6 24.4 1.6 16.8c0-8.4 6.8-15.2 15.2-15.2zm-6.4 3.2c-.4 0-.8.4-.8.8v3.2c0 .4.4.8.8.8h3.2c.4 0 .8-.4.8-.8V5.6c0-.4-.4-.8-.8-.8h-3.2zm.8 1.6h1.6V8h-1.6V6.4zM17.6 8c-.4 0-.8.4-.8.8s.4.8.8.8.8.4.8.8.4.8.8.8.8-.4.8-.8C20 9 19 8 17.6 8zm5.6 0c-.4 0-.8.4-.8.8V12c0 .4.4.8.8.8h3.2c.4 0 .8-.4.8-.8V8.8c0-.4-.4-.8-.8-.8h-3.2zm.8 1.6h1.6v1.6H24V9.6zM6.4 11.2c-.4 0-.8.4-.8.8s.4.8.8.8.8.4.8.8.4.8.8.8.8-.4.8-.8c0-1.3-1-2.4-2.4-2.4zm11.2 3.2c-1.3 0-2.4 1-2.4 2.4 0 .4.4.8.8.8s.8-.4.8-.8.4-.8.8-.8.8-.4.8-.8-.4-.8-.8-.8zM31.2 16h0c.4 0 1 0 1.4.1.6.1 1.2.1 1.8.3 0 .1 0 .3 0 .4 0 1.2.6 2.2 1.6 2.8.1 3 2 5.5 5 6.5.6 2.5 2.8 4.4 5.4 4.4v.8c0 8.4-6.8 15.2-15.2 15.2-8 0-14.4-6-15-13.7 0-.5-.1-1-.1-1.5 0-2 .4-4 1.2-6 2.3-5.5 7.7-9.3 14-9.3zm-20.8 3.2c-1.8 0-3.2 1.4-3.2 3.2s1.4 3.2 3.2 3.2 3.2-1.4 3.2-3.2-1.4-3.2-3.2-3.2zm0 1.6c1 0 1.6.7 1.6 1.6s-.7 1.6-1.6 1.6-1.6-.7-1.6-1.6.7-1.6 1.6-1.6zm19.2 0c-.4 0-.8.4-.8.8s.4.8.8.8.8.4.8.8.4.8.8.8.8-.4.8-.8c0-1.3-1-2.4-2.4-2.4zM24 24c-1.8 0-3.2 1.4-3.2 3.2s1.4 3.2 3.2 3.2 3.2-1.4 3.2-3.2S25.8 24 24 24zm0 1.6c1 0 1.6.7 1.6 1.6s-.7 1.6-1.6 1.6-1.6-.7-1.6-1.6.7-1.6 1.6-1.6zm8 1.6c-.4 0-.8.4-.8.8v3.2c0 .4.4.8.8.8h3.2c.4 0 .8-.4.8-.8V28c0-.4-.4-.8-.8-.8H32zm.8 1.6h1.6v1.6h-1.6v-1.6zm6.4 3.2c-.4 0-.8.4-.8.8s.4.8.8.8.8.4.8.8.4.8.8.8.8-.4.8-.8c0-1.3-1-2.4-2.4-2.4zm-17.6 2.4c-.4 0-.8.4-.8.8s.4.8.8.8.8.4.8.8.4.8.8.8.8-.4.8-.8c0-1.3-1-2.4-2.4-2.4zm8 3.2c-1.8 0-3.2 1.4-3.2 3.2s1.4 3.2 3.2 3.2 3.2-1.4 3.2-3.2-1.4-3.2-3.2-3.2zm8.8.8c-1.3 0-2.4 1-2.4 2.4 0 .4.4.8.8.8s.8-.4.8-.8.4-.8.8-.8.8-.4.8-.8-.4-.8-.8-.8zm-8.8.8c1 0 1.6.7 1.6 1.6s-.7 1.6-1.6 1.6-1.6-.7-1.6-1.6.7-1.6 1.6-1.6z"></path></svg>
                <p>' . $this->options['general']['message_text'] . '</p>
                <div>
                    <button type="button" id="cn-accept">' . $this->options['general']['accept_text'] . '</button>
                    <button type="button" id="cn-settings">' . $this->options['general']['settings_text'] . '</button>
                </div>
            </div>
        </div>';
    }

    //Ajax modal loader.
    public function modal_loader() {
        $output = '
                    <div class="cn-modal">
                        <div class="cn-header">
                            <span>&lt;Cookie settings/&gt;</span>
                            <button id="cn-close"><svg xmlns="http://www.w3.org/2000/svg"><path d="M 20,2 18,0 10,8 2,0 0,2 l 8,8 -8,8 2,2 8,-8 8,8 2,-2 -8,-8 z"></path></svg></button>
                        </div>
                        <div class="cn-content">';
                    foreach( $this->options['general']['tabs'] as $tab ) {
                        $description = $tab['purpose'];
                        $purpose = ( $description ) ? '<th>' . $tab['purpose'] . '</th>' : '';
                        $chechbox = ( $description ) ? 'class="cn-checkbox-cat"' : 'disabled="disabled" checked="checked"';
                        $classes = ( $description ) ? 'class="cn-option-list"' : 'class="cn-option-list cn-description-list"';

                        $output .= '
                            <div class="cn-option-wrap">
                                <label class="cn-checkbox">
                                    <input type="checkbox" ' . $chechbox .'/>
                                    <div class="cn-trigger"></div>
                                </label>
                                <div class="cn-text">
                                    <div class="cn-option-title">' . $tab['title'] . '</div>
                                    <p>' . $tab['description'] . '</p>
                                    <div ' . $classes . '>
                                        <table>
                                            <tr>
                                                <th>' . $tab['select'] . '</th>
                                                <th>' . $tab['provider'] . '</th>'
                                                . $purpose .
                                            '</tr>';
                                    foreach( $tab['options'] as $option ) {
                                        $script = ( $description ) ? '<td><label class="cn-checkbox"><input type="checkbox" class="cn-checkbox-sub"/><div class="cn-trigger"></div></label></td>' : '';
                                        $output .= '
                                            <tr>'
                                                . $script .
                                                '<td>' . $option['title'] . '</td>
                                                <td>' . $option['description'] . '</td>
                                            </tr>';
                                    }
                                    $output .= '
                                        </table>
                                    </div>
                                </div>
                                <button class="cn-details">Details<svg xmlns="http://www.w3.org/2000/svg"><path d="M 0,0 5,5.0000005 9.9999998,0 Z"></path></svg></button>
                            </div>';
                    }
                        $output .= '
                            <button id="cn-save">' . $this->options['general']['save_text'] . '</button>
                        </div>
                        <span class="cn-decoration">&lt;Cookie settings/&gt;</span>
                    </div>';

        echo $output;
        die();
    }

	//Activate the plugin.
	public function activation() {
		add_option( 'cookie_notice_options', $this->defaults['general'], '', 'no' );
	}

	//Deactivate the plugin.
	public function deactivation() {
		if ( $this->options['general']['deactivation_delete'] === 'yes' ) {
			delete_option( 'cookie_notice_options' );
		}
	}

	//Load admin scripts and styles.
	public function admin_enqueue_scripts( $page ) {
		if ( $page !== 'settings_page_cookie-notice' )
			return;
        //Script
		wp_enqueue_script( 'cookie-notice-admin', plugins_url( 'js/admin.js', __FILE__ ), array( 'jquery' ), '1.0' );
		wp_localize_script( 'cookie-notice-admin', 'cn_args', array( 'message' => 'Are you sure you want to reset these settings to defaults?' ) );
        //Style
	    wp_enqueue_style( 'cookie-notice-admin', plugins_url( 'css/admin.css', __FILE__ ), array(), '1.0' );
	}

    //Load frontend scripts and styles.
	public function wp_enqueue_scripts() {
        //Script
        wp_enqueue_script( 'cookie-notice-front', plugins_url( 'js/cookie.js', __FILE__ ), array(), $this->options['general']['version'], true );
		wp_localize_script( 'cookie-notice-front', 'cn_args', array( 'ajaxurl' => admin_url( 'admin-ajax.php' ) ) );
        //Style
		wp_enqueue_style( 'cookie-notice-front', plugins_url( 'css/front.css', __FILE__ ), array(), '1.0' );
	}

}
