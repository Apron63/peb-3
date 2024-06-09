import $ from 'jquery'
import * as bootstrap from 'bootstrap'

let url = $('#add-duration-button').data('add-duration-url')

$('.button-add-duration').on('click', function(e) {
    let duration = $(e.target).closest('.input-group').find('.form-control').val()
    let valueNow = $(e.target).closest('.input-group').find('span')
    let inputNow = $(e.target).closest('.input-group').find('input')

    if (duration != '') {
        let permissionId = $(e.target).data('permission-id')

        $.ajax({
            url: url,
            data: {permissionId: permissionId, duration: Number(duration)}
        }).done(function (data) {
            $(valueNow).text(data['duration'])
            $(inputNow).val('')
        }).fail(function (data) {
           console.log(data)
        })
    }
})

$('#change-user-password').on('click', function() {
    let url = $('#change-user-password').data('url')

    $.ajax({
        url: url
    }).done(function (data) {
        $('#user_edit_plainPassword').val(data.password)

        $('#toast-message').html('Пароль слушателя был обновлен')
        $('.toast-header').css('background-color', 'green')
        let toast = new bootstrap.Toast(toastLiveExample)
        toast.show()

    }).fail(function (data) {
       console.log(data)
    })
})
