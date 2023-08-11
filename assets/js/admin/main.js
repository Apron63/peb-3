import $, { data } from 'jquery'
import * as bootstrap from 'bootstrap'

$(window).on('load', function() {
    $(document).find('.toast-error-message').each(function(i, e) {
        $('#toast-message').html($(e).data('message'))
        $('.toast-header').css('background-color', $(e).data('back-color'))
        $('.me-auto').css('color', $(e).data('color'))
        let toast = new bootstrap.Toast(toastLiveExample)
        toast.show();
    })
});
