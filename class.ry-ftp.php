<?php
defined('RY_FTP_VERSION') OR exit('No direct script access allowed');

class RY_FTP {
	public static $options = array();
	public static $version = '0.0.0';
	public static $textdomain = 'upload-to-ftp';

	private static $option_prefix = 'RY_FTP_';
	private static $initiated = false;
	private static $ftp_system = false;
	private static $add_list = array();
	private static $complete_list = array();

	public static function init() {
		if( !self::$initiated ) {
			self::$initiated = true;

			if( is_admin() ) {
				require_once(RY_FTP_PLUGIN_DIR . 'class.ry-ftp.update.php');
				RY_FTP_update::update();

				require_once(RY_FTP_PLUGIN_DIR . 'class.ry-ftp.admin.php');
				RY_FTP_admin::init();
			}
			
			self::$options = self::get_option('options');
			self::$version = self::get_option('version');

			add_action('shutdown', array(__CLASS__, 'shutdown'));

			if( self::$options['ftp_uplode_ok'] ) {
				add_filter('wp_update_attachment_metadata', array(__CLASS__, 'set_upload_file'), 10, 2);
				add_filter('wp_delete_file', array(__CLASS__, 'delete_file')); // Do Not Work For Attachment Sizes [ Use Wp Delete Attachment ]
			}

			add_filter('load_image_to_edit_path', array(__CLASS__, 'load_file'), 10, 2);
			add_filter('wp_get_attachment_image_attributes', array(__CLASS__, 'resrc_file'), 10, 2);
			add_filter('wp_get_attachment_url', array(__CLASS__, 'reurl_file'), 10, 2);

			if( (bool) self::$options['rename_file'] ) {
				add_filter('sanitize_file_name', array(__CLASS__, 'sanitize_file_name'));
			}
		}
	}

	public static function shutdown() {
		self::$ftp_system = false;
	}


	public static function log($message)
    {
        if (is_array($message)) {
            $message = json_encode($message);
        }
        $file = fopen(ABSPATH . "/wp-ftp-upload.log", "a");
        fwrite($file, "\n" . date('Y-m-d h:i:s') . " :: " . $message);
        fclose($file);
    }

	public static function set_upload_file($data, $attachment_id) {

		$parent_post_id = wp_get_post_parent_id($attachment_id);
		if( $parent_post_id > 0 ) {
			if( $post = get_post($parent_post_id) ) {
				if( substr($post->post_date, 0, 4) > 0 ) {
					$time = $post->post_date;
				}
			}
		}
		if( !isset($time) ) {
			$post = get_post($attachment_id);
			$time = $post->post_date;
		}
		$uploads = wp_upload_dir($time);
		$local_file = $uploads['basedir'] . '/' . get_post_meta($attachment_id, '_wp_attached_file', true);
		self::$add_list[] = array(
			'file_post_id' => $attachment_id,
			'local_file' => $local_file,
			'ftp_file' => self::make_local_to_ftp($local_file)
		);
		if( isset($data['sizes']) ) {
			foreach( $data['sizes'] as $size_data ) {
				$local_file = $uploads['path'] . '/' . $size_data['file'];
				self::$add_list[] = array(
					'file_post_id' => 0,
					'local_file' => $local_file,
					'ftp_file' => self::make_local_to_ftp($local_file)
				);
			}
		}

		self::do_ftp_upload();
		return $data;
	}

	public static function load_file($file, $attachment_id) {
		$meta_date = get_post_meta($attachment_id, 'file_to_ftp', true);
		if( isset($meta_date['up_time']) && $meta_date['up_time'] >= 1 ) {
			if( is_file($file) && filesize($file) == 0 ) {
				if( function_exists('fopen') && function_exists('ini_get') && true == ini_get('allow_url_fopen') ) {
					$file = self::clear_basedir($file);
					$file = self::$options['html_link_url'] . $file;
				} else {
					return '';
				}
			}
		}
		return $file;
	}

	public static function resrc_file($attr, $att) {
		$file_name = basename($attr['src']);
		$meta_date = get_post_meta($att->ID, 'file_to_ftp', true);
		if( isset($meta_date['up_time']) && $meta_date['up_time'] >= 1 ) {
			$attr['src'] = self::$options['html_link_url'] . $meta_date['up_dir'] . $file_name;
		}
		return $attr;
	}

	public static function reurl_file($url, $att_id) {
		$file_name = basename($url);
		$meta_date = get_post_meta($att_id, 'file_to_ftp', true);
		if( isset($meta_date['up_time']) && $meta_date['up_time'] >= 1 ) {
			$url = self::$options['html_link_url'] . $meta_date['up_dir'] .  $file_name;
		}
		return $url;
	}

	public static function delete_file($file) {
		if( self::$options['ftp_delete_ok'] && self::ftp_open() ) {
			$ftp_file = self::clear_basedir($file);
			self::$ftp_system->delete(self::$options['ftp_dir'] . $ftp_file);
		}
		return $file;
	}

	public static function sanitize_file_name($file_name) {
	    $parts = explode('.', $file_name);
	    if( preg_match('@^[a-z0-9][a-z0-9\-_]*$@i', $parts[0]) ) {
	        $file_name = $parts[0];
	    } else {
	        $file_name = substr(md5($parts[0]), 0, 10);
	    }
	    if( count($parts) < 2 ) {
	        return $file_name;
	    } else {
	        $extension = array_pop($parts);
	        return $file_name . '.' . $extension;
	    }
	}

	private static function clear_basedir($file) {
		if( ($uploads = wp_upload_dir()) && false === $uploads['error'] ) {
			if( 0 === strpos($file, $uploads['basedir']) ) {
				$file = str_replace($uploads['basedir'], '', $file);
				$file = ltrim($file, '/');
			}
		}
		return $file;
	}

	private static function make_local_to_ftp($local_file) {
		$dir = self::clear_basedir($local_file);
		$dir = '/' . substr($dir, 0, strrpos($dir, '/'));
		$dir = self::ftp_mkdir($dir);
		return $dir . basename($local_file);
	}

	private static function ftp_mkdir($dir) {
		$dir = explode('/', $dir);
		$now_dir = self::$options['ftp_dir'];
		$len = count($dir);
		for( $i = 1; $i < $len; $i++ ) {
			$now_dir .= $dir[$i] . '/';
			if( self::ftp_open() ) {
				if( !self::$ftp_system->is_dir($now_dir) ) {
					self::$ftp_system->mkdir($now_dir);
				}
			}
		}
		return $now_dir;
	}

	private static function do_ftp_upload() {
		if( count(self::$add_list) > 0 ) {

			$up_time = current_time('timestamp');

			foreach( self::$add_list as $file ) {


				if( self::do_upload_file($file['ftp_file'], $file['local_file']) ) {

					if( $file['file_post_id'] != 0 ) {

						$up_dir = dirname($file['ftp_file']);
						if( self::$options['ftp_dir'] != '/' ) {
							$up_dir = str_replace(self::$options['ftp_dir'], '', $up_dir);
						}
						$up_dir = trim($up_dir, '/');
						if( $up_dir != '' ) {
							$up_dir .= '/';
						}
						$metadate = array(
							'up_time' => $up_time,
							'up_dir' => $up_dir
						);
						add_post_meta($file['file_post_id'], 'file_to_ftp', $metadate, true);

					} else {

						if( self::$options['delete_local_auto_build'] == 1 ) {
							@unlink($file['local_file']);
						}

					}
				}
			}
		}
	}

	private static function do_upload_file($ftp_file, $local_file) {

		if(!in_array($local_file, self::$complete_list)) {
			if( self::$options['ftp_uplode_ok'] && self::ftp_open() ) {
				sleep(1);
				if(file_exists($local_file)) {
					self::$complete_list[] = $local_file;
					return self::$ftp_system->put_contents($ftp_file, file_get_contents($local_file));
				}
			}
		}
		
		return false;
	}

	private static function ftp_open() {
		if( self::$ftp_system ) {
			return true;
		}
		if( is_callable('set_time_limit') ) {
			set_time_limit(300);
		}

		self::$ftp_system = self::get_ftp_class(
			self::$options['ftp_host_mode'],
			self::$options['ftp_host'],
			self::$options['ftp_port'],
			self::$options['ftp_timeout'],
			self::$options['ftp_username'],
			self::$options['ftp_password']
		);

		return self::$ftp_system->connect();
	}

	public static function get_option($option, $default = false) {
		return get_option(self::$option_prefix . $option, $default);
	}

	public static function update_option($option, $value) {
		return update_option(self::$option_prefix . $option, $value);
	}

	public static function add_option($option, $value = '', $deprecated = '', $autoload = 'yes') {
		return add_option(self::$option_prefix . $option, $value, $deprecated, $autoload);
	}

	public static function delete_option($option) {
		return delete_option(self::$option_prefix . $option);
	}

	public static function load_ftp_class() {
		require_once(ABSPATH . 'wp-admin/includes/class-wp-filesystem-base.php');
		require_once(ABSPATH . 'wp-admin/includes/class-wp-filesystem-ftpext.php');
		require_once(ABSPATH . 'wp-admin/includes/class-wp-filesystem-ssh2.php');

		if( !defined('FS_CHMOD_DIR') ) {
			define('FS_CHMOD_DIR', (fileperms(ABSPATH) & 0777 | 0755));
		}
		if( !defined('FS_CHMOD_FILE') ) {
			define('FS_CHMOD_FILE', (fileperms(ABSPATH . 'index.php') & 0777 | 0644));
		}
	}

	public static function get_ftp_class($mode, $host, $port, $timeout, $username, $password) {
		self::load_ftp_class();

		if( $mode == 'ftp' || $mode == 'ftps' ) {
			$ftp_system = new WP_Filesystem_FTPext(array(
				'connection_type' => $mode,
				'hostname' => $host,
				'port' => $port,
				'username' => $username,
				'password' => $password
			));
			if ( !defined('FS_CONNECT_TIMEOUT') ) {
				define('FS_CONNECT_TIMEOUT', $timeout);
			}
		} elseif( $mode == 'sftp' ) {
			$ftp_system = new WP_Filesystem_SSH2(array(
				'hostname' => $host,
				'port' => $port,
				'username' => $username,
				'password' => $password
			));
			if ( !defined('FS_TIMEOUT') ) {
				define('FS_TIMEOUT', $timeout);
			}
		}

		return $ftp_system;
	}

	public static function plugin_activation() {
		$old_option = get_option('U2FTP_options');
		if( $old_option !== false ) {
			self::update_option('options', $old_option);
			delete_option('U2FTP_options');
		}
		$old_version = get_option('U2FTP_version');
		if( $old_version !== false ) {
			self::update_option('version', $old_version);
			delete_option('U2FTP_version');
		}

		$options = self::get_option('options', array());
		if( count($options) == 0 ) {
			$options['ftp_host'] = '';
			$options['ftp_host_mode'] = 'ftp';
			$options['ftp_port'] = 21;
			$options['ftp_timeout'] = 5;
			$options['ftp_username'] = '';
			$options['ftp_password'] = '';
			$options['ftp_dir'] = '/';
			$options['ftp_uplode_ok'] = false;
			$options['html_link_url'] = '';
			$options['ftp_delete_ok'] = false;
			$options['html_file_line_ok'] = false;
			$options['ftp_mkdir_ok'] = false;
			$options['rename_file'] = 1;
			$options['delete_local_auto_build'] = 0;
			self::update_option('options', $options);
		}
	}

	public static function plugin_deactivation( ) {
	}
}