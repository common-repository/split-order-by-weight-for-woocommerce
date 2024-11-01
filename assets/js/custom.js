/* global woocommerce_settings_params, wp */
( function( $, params, wp ) {
    $( function() {
        // DropDown Change Effect
        $('select#sow_auto_forced').change(function () {
            if ('no' === $(this).val()) {
                $(this).closest('tbody').find('tr').not(':first').css('display', 'none');
                $(this).closest('tbody').find('tr:last').css('display', 'none');
            } else {
                $(this).closest('tbody').find('tr:last').show();
                $(this).closest('tr').next('tr').show();
                if($('select#sow_splitorderbyweight').val() !== 'default') {
                    $(this).closest('tr').next('tr').next('tr').show();
                }
            }
        }).change();
        $('select#sow_splitorderbyweight').change(function () {
            if ('default' === $(this).val()) {
                $(this).closest('tr').next('tr').hide();
                $(this).closest('tr').next().next('tr').show();
            } else {
                $(this).closest('tr').next('tr').show();
                $(this).closest('tr').next().next('tr').hide();
                $(this).closest('tbody').find('tr:last').show();
            }
        }).change();
    });
})( jQuery, wp );

jQuery(document).ready(function () {
    jQuery("#attribute").on('change', function () {
        var id = jQuery(this).val();  
        jQuery.ajax({
            type: "POST",
            url: ajaxurl,
            data: {id: id, action: 'sow_select_variation'},
            beforeSend: function () {
                //jQuery(".loading-box").show();
            },
            success: function (data) {
                console.log(data);
                jQuery('#variation').html(data);
            },
            error: function (xhr) { // if error occured
                //jQuery(".loading-box").hide();
            },
        });
    });
});