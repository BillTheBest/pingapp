$(document).ready(function() {
	$('#activity').dataTable( {
		"bJQueryUI": true,
		"bProcessing": true,
		"bServerSide": true,
		"sAjaxSource": "/dashboard/ajax_list?message_id=<?php echo $message->id; ?>",
	});
});
