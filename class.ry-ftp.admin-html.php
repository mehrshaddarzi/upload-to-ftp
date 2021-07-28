<?php
defined('RY_FTP_VERSION') OR exit('No direct script access allowed');

class RY_FTP_admin_html {
	public static function setting_page_header($now_tab) {
		?>
		<div class="wrap">
		<h2><?=__('Upload to FTP Options', RY_FTP::$textdomain) ?></h2>
		<nav class="nav-tab-wrapper">
			<?php foreach( RY_FTP_admin::$tab_list as $tag => $tag_info ) { ?>
				<a href="options-general.php?page=upload-to-ftp&tab=<?=$tag ?>" class="nav-tab<?=(($now_tab == $tag) ? ' nav-tab-active' : '') ?>"><?=$tag_info['name'] ?></a>
			<?php } ?>
		</nav>
		<?php
	}

	public static function setting_page_footer() {
		?>
		</div>
		<p><?=sprintf(__('Need translators! Please take a moment to <a href="%s">translate on-line</a>', RY_FTP::$textdomain), 'https://translate.wordpress.org/projects/wp-plugins/upload-to-ftp/stable') ?>
		<p><?=sprintf(__('Upload to FTP version %s', RY_FTP::$textdomain), RY_FTP::$version) ?></p>
		<?php
	}

	public static function show_ftp_setting_page() {
		?>
		<form method="post" action="" novalidate="novalidate">
			<table class="form-table">
				<tr>
					<th scope="row"><?=__('FTP Status', RY_FTP::$textdomain) ?></th>
					<td>
						<p><?=__('Upload File Status:', RY_FTP::$textdomain) ?>
							<strong class="upload-status <?=RY_FTP::$options['ftp_uplode_ok'] ? '' : 'hidden' ?>"><?=__('Can upload', RY_FTP::$textdomain) ?></strong>
							<strong class="upload-status-error <?=RY_FTP::$options['ftp_uplode_ok'] ? 'hidden' : '' ?>"><?=__('Can not upload', RY_FTP::$textdomain) ?></strong>
						</p>
						<p><?=__('File Link Status:', RY_FTP::$textdomain) ?>
							<strong class="link-status <?=RY_FTP::$options['html_file_line_ok'] ? '' : 'hidden' ?>"><?=__('HTML File Link is OK', RY_FTP::$textdomain) ?></strong>
							<strong class="link-status-error <?=RY_FTP::$options['html_file_line_ok'] ? 'hidden' : '' ?>"><?=__('HTML File Link is Error', RY_FTP::$textdomain) ?></strong>
						</p>
						<p><?=__('Delete File Status:', RY_FTP::$textdomain) ?>
							<strong class="delete-status <?=RY_FTP::$options['ftp_delete_ok'] ? '' : 'hidden' ?>"><?=__('Can delete', RY_FTP::$textdomain) ?></strong>
							<strong class="delete-status-error <?=RY_FTP::$options['ftp_delete_ok'] ? 'hidden' : '' ?>"><?=__('Can not delete', RY_FTP::$textdomain) ?></strong>
						</p>
						<p><?=__('Create Directory Status:', RY_FTP::$textdomain) ?>
							<strong class="mkdir-status <?=RY_FTP::$options['ftp_mkdir_ok'] ? '' : 'hidden' ?>"><?=__('Can create', RY_FTP::$textdomain) ?></strong>
							<strong class="mkdir-status-error <?=RY_FTP::$options['ftp_mkdir_ok'] ? 'hidden' : '' ?>"><?=__('Can not create', RY_FTP::$textdomain) ?></strong>
						</p>
					</td>
				</tr>
				<tr>
					<th scope="row"><label for="<?=esc_attr('ry_ftp_host') ?>"><?=__('FTP Host', RY_FTP::$textdomain) ?></label></th>
					<td>
						<select id="<?=esc_attr('ry_ftp_host_mode') ?>" name="<?=esc_attr('ry_ftp_host_mode') ?>">
							<option value="ftp">ftp://</option>
							<option value="ftps">ftps://</option>
							<option value="sftp">sftp://</option>
						</select>
						<input type="text" id="<?=esc_attr('ry_ftp_host') ?>" name="<?=esc_attr('ry_ftp_host') ?>" value="<?=esc_attr(RY_FTP::$options['ftp_host']) ?>" size="30" placeholder="example.com">
					</td>
				</tr>
				<?php self::input_text(__('FTP Port', RY_FTP::$textdomain), 'ry_ftp_port', RY_FTP::$options['ftp_port'], 6, '21'); ?>
				<?php self::input_text(__('FTP Timeout', RY_FTP::$textdomain), 'ry_ftp_timeout', RY_FTP::$options['ftp_timeout'], 4, '5'); ?>
				<?php self::input_text(__('FTP Username', RY_FTP::$textdomain), 'ry_ftp_username', RY_FTP::$options['ftp_username'], 30, 'user_name'); ?>
				<?php self::input_text(__('FTP Password', RY_FTP::$textdomain), 'ry_ftp_password', '', 30, 'user_password', __('Only when you want to change your password, enter it.', RY_FTP::$textdomain)); ?>
				<?php self::input_text(__('FTP Directory', RY_FTP::$textdomain), 'ry_ftp_dir', RY_FTP::$options['ftp_dir'], 60, '/public_html/	'); ?>
				<?php self::input_text(__('HTML link url', RY_FTP::$textdomain), 'ry_html_link_url', RY_FTP::$options['html_link_url'], 60, 'http://example.com/'); ?>
				<tr class="hidden">
					<th scope="row"><?=__('Test Status', RY_FTP::$textdomain) ?></th>
					<td>
						<p><?=__('Connect and Login:', RY_FTP::$textdomain) ?> <span class="testing"><?=__('Testing', RY_FTP::$textdomain) ?></span><span class="test-result"><?=__('Test Complete', RY_FTP::$textdomain) ?></span></p>
						<p><?=__('Upload file:', RY_FTP::$textdomain) ?> <span class="testing"><?=__('Testing', RY_FTP::$textdomain) ?></span><span class="test-result"><?=__('Test Complete', RY_FTP::$textdomain) ?></span></p>
						<p><?=__('HTML link:', RY_FTP::$textdomain) ?> <span class="testing"><?=__('Testing', RY_FTP::$textdomain) ?></span><span class="test-result"><?=__('Test Complete', RY_FTP::$textdomain) ?></span></p>
						<p><?=__('Delete file:', RY_FTP::$textdomain) ?> <span class="testing"><?=__('Testing', RY_FTP::$textdomain) ?></span><span class="test-result"><?=__('Test Complete', RY_FTP::$textdomain) ?></span></p>
						<p><?=__('Create directory:', RY_FTP::$textdomain) ?> <span class="testing"><?=__('Testing', RY_FTP::$textdomain) ?></span><span class="test-result"><?=__('Test Complete', RY_FTP::$textdomain) ?></span></p>
					</td>
				</tr>
			</table>
			<p class="submit"><button type="button" class="button-primary ry_Test_ftpsetting"><?=__('Save & Test Changes', RY_FTP::$textdomain) ?></button></p>
		</form>
		<?php
	}

	public static function show_base_setting_page() {
		?>
		<form method="post" action="" novalidate="novalidate">
			<table class="form-table">
				<tr>
					<th scope="row"><?=__('Rename file', RY_FTP::$textdomain) ?></th>
					<td>
						<select name="ry_rename_file" size="1">
							<option value="0"<?php selected('0', RY_FTP::$options['rename_file']); ?>><?=__('disable', RY_FTP::$textdomain) ?></option>
							<option value="1"<?php selected('1', RY_FTP::$options['rename_file']); ?>><?=__('enable', RY_FTP::$textdomain) ?></option>
						</select>
						<br><em><?=__('Proposal enabled! Because the file name to avoid some of the resulting error can not be expected', RY_FTP::$textdomain) ?></em>
					</td>
				</tr>
				<tr>
					<th scope="row"><?=__('Delete Auto build local file', RY_FTP::$textdomain) ?></th>
					<td>
						<select name="ry_delete_local_auto_build" size="1">
							<option value="0"<?php selected('0', RY_FTP::$options['delete_local_auto_build']); ?>><?=__('disable', RY_FTP::$textdomain) ?></option>
							<option value="1"<?php selected('1', RY_FTP::$options['delete_local_auto_build']); ?>><?=__('enable', RY_FTP::$textdomain) ?></option>
						</select>
						<br><em><?=__('Only enable the when you local storage space have limited.', RY_FTP::$textdomain) ?></em>
					</td>
				</tr>
			</table>
			<p class="submit"><button type="submit" name="ry_Update_setting" class="button-primary"><?=__('Save Changes', RY_FTP::$textdomain) ?></button></p>
		</form>
		<?php
	}

	public static function show_advanced_setting_page() {
		?>
		<form method="post" action="" novalidate="novalidate">
			<p>
				<?=__('This setting is ONLY set the mark of the media.', RY_FTP::$textdomain) ?><br>
				<?=__('You NEED move the file to you ftp by youself.', RY_FTP::$textdomain) ?><br>
				<?=__('And all the post you had will stile use the file from this webserver until you reimport the media to the post.', RY_FTP::$textdomain) ?>
			</p>
			<p class="submit"><button type="submit" name="ry_SetExistFile" class="button-primary"><?=__('Set Exists File In FTP', RY_FTP::$textdomain) ?></button></p>
		</form>
		<?php
	}

	public static function show_tools_setting_page() {
		$option = array(
			'version' => RY_FTP::$version,
			'option' => RY_FTP::$options
		);
		?>
		<table class="form-table">
			<tr>
				<th scope="row"><?=__('Export') ?></th>
				<td>
					<textarea class="large-text" rows="4" cols="50" readonly><?=base64_encode(json_encode($option)) ?></textarea><br>
					<?=__('Copy this data to another site to run in the same setting.', RY_FTP::$textdomain) ?>
				</td>
			</tr>
			<tr>
				<th scope="row"><?=__('Import') ?></th>
				<td>
					<form method="post" action="" novalidate="novalidate">
						<textarea class="large-text" rows="4" cols="50" name="ry_ftp_setting"></textarea><br>
						<?=__('Paste the data from another site to run in the same setting.', RY_FTP::$textdomain) ?>
						<p class="submit"><button type="submit" name="ry_ImportSetting" class="button-primary"><?=__('Import') ?></button></p>
					</form>
				</td>
			</tr>
		</table>
		<?php
	}

	private static function input_text($label, $input_name, $input_value, $input_size = 30, $placeholder = '', $note = '') {
		?>
		<tr>
			<th scope="row"><label for="<?=esc_attr($input_name) ?>"><?=$label ?></label></th>
			<td>
				<input type="text" id="<?=esc_attr($input_name) ?>" name="<?=esc_attr($input_name) ?>" value="<?=esc_attr($input_value) ?>" size="<?=intval($input_size) ?>" <?=empty($placeholder) ? '' : ('placeholder="' . esc_attr($placeholder) . '"') ?>>
				<?=empty($note) ? '' : ('<br>' . $note) ?>
			</td>
		</tr>
		<?php
	}
}
