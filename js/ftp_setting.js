jQuery(document).ready(function($) {

$('.ry_Test_ftpsetting').click(function(){
	$('.test-result').addClass('hidden');
	$('.testing').addClass('hidden');
	$('.test-info').remove();
	$('.form-table tr.hidden').removeClass('hidden');

	$('.upload-status').addClass('hidden');
	$('.link-status').addClass('hidden');
	$('.delete-status').addClass('hidden');
	$('.mkdir-status').addClass('hidden');
	$('.upload-status-error').removeClass('hidden');
	$('.link-status-error').removeClass('hidden');
	$('.delete-status-error').removeClass('hidden');
	$('.mkdir-status-error').removeClass('hidden');

	var postData = {};
	postData.host_mode = $('#ry_ftp_host_mode').val();
	postData.host = $('#ry_ftp_host').val();
	postData.port = $('#ry_ftp_port').val();
	postData.timeout = $('#ry_ftp_timeout').val();
	postData.username = $('#ry_ftp_username').val();
	postData.password = $('#ry_ftp_password').val();
	postData.dir = $('#ry_ftp_dir').val();
	postData.link_url = $('#ry_html_link_url').val();

	testStep(postData, 1);
});

function testStep(postData, step) {
	var $testing = $('.testing:nth(' + (step - 1) + ')');
	$testing.removeClass('hidden');

	postData.action = 'ry_ftp_test_step_' + step;
	$.post(ajaxurl, postData, function(response) {
		var $testResult = $('.test-result:nth(' + (step - 1) + ')');
		if( response.success == true ) {
			$testing.addClass('hidden');
			$testResult.removeClass('hidden');
			if( typeof response.data == 'string' ) {
				if( response.data == 'upload_ok' ) {
					$('.upload-status-error').addClass('hidden');
					$('.upload-status').removeClass('hidden');
				}
				if( response.data == 'link_ok' ) {
					$('.link-status-error').addClass('hidden');
					$('.link-status').removeClass('hidden');
				}
				if( response.data == 'delete_ok' ) {
					$('.delete-status-error').addClass('hidden');
					$('.delete-status').removeClass('hidden');
				}
				if( response.data == 'mkdir_ok' ) {
					$('.mkdir-status-error').addClass('hidden');
					$('.mkdir-status').removeClass('hidden');
				}
			}

			if( step < 5 ) {
				testStep(postData, step + 1);
			}
		} else {
			if( typeof response.data == 'object' ) {
				$testResult.parent().after('<div class="notice notice-error test-info"><p>' + response.data.join('<br>') + '</p></div>');
			}
		}
	});
}

});