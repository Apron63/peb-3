import $ from 'jquery'

let files = {}
let fileupload = $('#profile-upload')

$('#btn-profile-upload').on('click', function(e) {
    e.stopImmediatePropagation
   fileupload.trigger('click')
})

$(fileupload).on('change', function () {
    if( typeof files == 'undefined' ) {
        return
    }

    $.ajax({
        url: $(this).data('url'),
        data: new FormData(profileForm),
        dataType: 'json',
        contentType: false,
        method: 'POST',
        processData : false, 
    }).done(function(data) {
        if (!data.success) {
            $('#error-message').text(data.message)
        } else {
            location.reload(true)
        }
    }).fail(function(data) {
        console.log(data)
    })
})
