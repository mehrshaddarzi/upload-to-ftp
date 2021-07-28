<?php
defined('RY_FTP_VERSION') OR exit('No direct script access allowed');

class RY_FTP_admin_ajax {
	private static $initiated = false;
	protected static $test_file_name = 'test-file.txt';
	
	public static function init() {
		if( !self::$initiated ) {
			self::$initiated = true;

			add_action('wp_ajax_ry_ftp_test_step_1', array(__CLASS__, 'test_step_1'));
			add_action('wp_ajax_ry_ftp_test_step_2', array(__CLASS__, 'test_step_2'));
			add_action('wp_ajax_ry_ftp_test_step_3', array(__CLASS__, 'test_step_3'));
			add_action('wp_ajax_ry_ftp_test_step_4', array(__CLASS__, 'test_step_4'));
			add_action('wp_ajax_ry_ftp_test_step_5', array(__CLASS__, 'test_step_5'));
		}
	}

	public static function test_step_1() {
		$ftp_info = self::get_post_info();
		$ftp_system = self::get_ftp_class($ftp_info);

		self::check_error_and_out($ftp_system->errors);

		RY_FTP::$options['ftp_host'] = $ftp_info['host'];
		RY_FTP::$options['ftp_host_mode'] = $ftp_info['host_mode'];
		RY_FTP::$options['ftp_port'] = $ftp_info['port'];
		RY_FTP::$options['ftp_timeout'] = $ftp_info['timeout'];
		RY_FTP::$options['ftp_username'] = $ftp_info['username'];
		RY_FTP::$options['ftp_password'] = $ftp_info['password'];
		RY_FTP::$options['ftp_dir'] = $ftp_info['dir'];
		RY_FTP::$options['html_link_url'] = $ftp_info['link_url'];
		RY_FTP::$options['ftp_uplode_ok'] = false;
		RY_FTP::$options['ftp_delete_ok'] = false;
		RY_FTP::$options['html_file_line_ok'] = false;
		RY_FTP::$options['ftp_mkdir_ok'] = false;
		RY_FTP::update_option('options', RY_FTP::$options);

		wp_send_json_success();
	}

	public static function test_step_2() {
		$ftp_info = self::get_post_info();
		$ftp_system = self::get_ftp_class($ftp_info);

		if( !@$ftp_system->chdir($ftp_info['dir']) ) {
			$ftp_system->errors->add('chdir',
				sprintf(__('Open directory <strong>%s</strong> failure.', RY_FTP::$textdomain),
					$ftp_info['dir']
				)
			);
		}

		self::check_error_and_out($ftp_system->errors);

		if( !@$ftp_system->put_contents($ftp_info['dir'] . self::$test_file_name, file_get_contents(RY_FTP_PLUGIN_DIR . '/' . self::$test_file_name)) ) {
			$ftp_system->errors->add('upload',
				sprintf(__('Directory <strong>%s</strong> is not writable.', RY_FTP::$textdomain),
					$ftp_info['dir']
				)
			);
		}

		self::check_error_and_out($ftp_system->errors);

		RY_FTP::$options['ftp_uplode_ok'] = true;
		RY_FTP::update_option('options', RY_FTP::$options);

		wp_send_json_success(RY_FTP::$options['ftp_uplode_ok'] ? 'upload_ok' : null);
	}

	public static function test_step_3() {
		$ftp_info = self::get_post_info();

		$response = wp_remote_get($ftp_info['link_url'] . self::$test_file_name, array(
			'httpversion' => '1.1'
		));
		if( is_wp_error($response) ) {
			self::check_error_and_out($response);
		}

		$body = wp_remote_retrieve_body($response);
		if( $body != file_get_contents(RY_FTP_PLUGIN_DIR . '/' . self::$test_file_name) ) {
			self::check_error_and_out(new WP_Error('http_request_failed',
				__('HTML link url don\'t match FTP dir', RY_FTP::$textdomain)
				. '<br><a href="' . $ftp_info['link_url'] . self::$test_file_name. '">' . $ftp_info['link_url'] . self::$test_file_name . '</a>'
			));
		}

		RY_FTP::$options['html_file_line_ok'] = true;
		RY_FTP::update_option('options', RY_FTP::$options);

		wp_send_json_success(RY_FTP::$options['html_file_line_ok'] ? 'link_ok' : null);
	}

	public static function test_step_4() {
		$ftp_info = self::get_post_info();
		$ftp_system = self::get_ftp_class($ftp_info);

		$check_by_ftp = $ftp_system->exists($ftp_info['dir'] . self::$test_file_name);

		$ftp_system->delete($ftp_info['dir'] . self::$test_file_name);
		if( $check_by_ftp ) {
			if( $ftp_system->exists($ftp_info['dir'] . self::$test_file_name) === FALSE ) {
				RY_FTP::$options['ftp_delete_ok'] = true;
				RY_FTP::update_option('options', RY_FTP::$options);
			}
		} else {
			$response = wp_remote_get($ftp_info['link_url'] . self::$test_file_name, array(
				'httpversion' => '1.1'
			));
			if( wp_remote_retrieve_response_code($response) == 404 ) {
				RY_FTP::$options['ftp_delete_ok'] = true;
				RY_FTP::update_option('options', RY_FTP::$options);
			}
		}

		wp_send_json_success(RY_FTP::$options['ftp_delete_ok'] ? 'delete_ok' : null);
	}

	public static function test_step_5() {
		$ftp_info = self::get_post_info();
		$ftp_system = self::get_ftp_class($ftp_info);

		do {
			$test_new_dir = 'mkdir-test-' . wp_rand();
		} while( $ftp_system->is_dir($ftp_info['dir'] . $test_new_dir) );
		$ftp_system->mkdir($ftp_info['dir'] . $test_new_dir);
		if( $ftp_system->is_dir($ftp_info['dir'] . $test_new_dir) ) {
			RY_FTP::$options['ftp_mkdir_ok'] = true;
			RY_FTP::update_option('options', RY_FTP::$options);
		}
		$ftp_system->rmdir($ftp_info['dir'] . $test_new_dir);

		wp_send_json_success(RY_FTP::$options['ftp_mkdir_ok'] ? 'mkdir_ok' : null);
	}

	protected static function get_post_info() {
		$ftp_info = array();
		$ftp_info['host_mode'] = trim($_POST['host_mode']);
		$ftp_info['host'] = trim($_POST['host']);
		$ftp_info['port'] = intval($_POST['port']);
		if( $ftp_info['port'] == 0 ) {
			$ftp_info['port'] = 21;
		}
		$ftp_info['timeout'] = intval($_POST['timeout']);
		if( $ftp_info['timeout'] == 0 ) {
			$ftp_info['timeout'] = 10;
		}
		$ftp_info['username'] = trim($_POST['username']);
		$ftp_info['password'] = trim($_POST['password']);
		if( empty($ftp_info['password']) ) {
			$ftp_info['password'] = RY_FTP::$options['ftp_password'];
		}

		$ftp_info['dir'] = trim($_POST['dir']);
		$ftp_info['dir'] = '/' . trim($ftp_info['dir'], '/') . '/';
		$ftp_info['dir'] = str_replace('//', '/', $ftp_info['dir']);

		$ftp_info['link_url'] = trim($_POST['link_url']);
		preg_match('/^http[s]?:\/\//i', $ftp_info['link_url'] , $is_http);
		if( !isset($is_http[0]) ) {
			$ftp_info['link_url'] = 'http://' . $ftp_info['link_url'];
		}
		$ftp_info['link_url'] = rtrim($ftp_info['link_url'], '/') . '/';
		if( strlen($ftp_info['link_url']) <= 7 ) {
			$ftp_info['link_url'] = '';
		}

		return $ftp_info;
	}

	protected static function get_ftp_class($ftp_info) {
		$ftp_system = RY_FTP::get_ftp_class(
			$ftp_info['host_mode'],
			$ftp_info['host'],
			$ftp_info['port'],
			$ftp_info['timeout'],
			$ftp_info['username'],
			$ftp_info['password']
		);

		self::check_error_and_out($ftp_system->errors);

		$ftp_system->connect();

		return $ftp_system;
	}

	protected static function check_error_and_out($wp_error) {
		if( count($wp_error->get_error_codes()) ) {
			wp_send_json_error($wp_error->get_error_messages());
		}
	}
}
