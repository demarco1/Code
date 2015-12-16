/**
 * Modify the donation form based on the payment type selection
 */
jQuery(document).ready(function(){
	var e = 'ligminchapaulista' + '@' + 'gmail.com'; // avoid cloaking
	var d = jQuery('#donation');
	var a = jQuery('#amount', d);
	jQuery('#donation input[type=submit]').prop('disabled', true);
	jQuery('#donation .paytype').removeAttr('checked').change(function(){

		// Action URL for the three payment types
		var actions = [
			'/index.php?option=com_content&view=article&id=110',            // Bank deposit (set to ID of the bank account info page)
			'https://www.paypal.com/cgi-bin/webscr',                        // Paypal
			'/components/com_jshopping/payments/pm_pagseguro/donations.php' // PagSeguro
		];

		// Set the form action to the URL for the selected payment type and enable the submit button
		d.attr('action', actions[jQuery(this).val()]);
		jQuery('#donation input[type=submit]').prop('disabled', false);
	});
	jQuery('#donation').submit(function(){
		a.val(a.val().replace(',','.'));
	});
});
