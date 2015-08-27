/**
 * Modify the donation form based on the payment type selection
 */
$(document).ready(function(){
	$('#donation .paytype').change(function(){
		var e = 'ligminchapaulista' + '@' + 'gmail.com'; // avoid cloaking
		var d = $('#donation');
		var p = $(this).val();
		if(p==2) {
			d.attr('action','https://www.paypal.com/cgi-bin/webscr');
			$('input[type=hidden]', d).remove();
			$('#amount', d).attr('name', 'amount');
			d.append( '<input type="hidden" name="cmd" value="_xclick">' );
			d.append( '<input type="hidden" name="business" value="' + e + '" />' );
			d.append( '<input type="hidden" name="item_name" value="Doação para Ligmincha Brasil" />' );
			d.append( '<input type="hidden" name="currency_code" value="BRL" />' );
		}
	});
});
