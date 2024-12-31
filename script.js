jQuery(document).ready(function($) {
    $('#cc-calculate').on('click', function() {
        const amount = parseFloat($('#cc-amount').val());
        const fromCurrency = $('#cc-from-currency').val();
        const toCurrency = $('#cc-to-currency').val();

        if (isNaN(amount) || amount <= 0) {
            alert('Please enter a valid amount.');
            return;
        }

        $.ajax({
            url: cc_ajax.ajax_url,
            type: 'POST',
            data: {
                action: 'cc_currency_conversion',
                nonce: cc_ajax.nonce,
                amount: amount,
                from_currency: fromCurrency,
                to_currency: toCurrency,
            },
            success: function(response) {
                if (response.success) {
                    const result = `Converted amount: ${response.data.converted_amount.toFixed(2)} ${response.data.to_currency}`;
                    $('#cc-result').text(result);
                } else {
                    alert('Error: ' + response.data);
                }
            },
            error: function() {
                alert('An error occurred. Please try again.');
            },
        });
    });
});
