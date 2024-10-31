jQuery(document).ready(function ($) {
    if (typeof productMultiple.multiple !== 'undefined' && productMultiple.multiple > 0) {
        var multiple = productMultiple.multiple;
        var errorMessage = productMultiple.error_message;
        // Adjust the step, min, and default value for the quantity input
        var $quantityInput = $('input.qty');
        if ($quantityInput.length === 0) {
            $quantityInput = $('input[name="quantity"]');
        }
        // dont do anything if the quantity input is not found
        if ($quantityInput.length === 0) {
            return;
        }
        $quantityInput.attr('step', multiple);
        $quantityInput.attr('min', multiple);
        $quantityInput.val(multiple);

        // Add a validation check on quantity input change
        $quantityInput.on('change', function () {
            var value = $(this).val();
            if (value % multiple !== 0) {
                alert(errorMessage);
                $(this).val(multiple * Math.ceil(value / multiple));
            }
        });
    }
});