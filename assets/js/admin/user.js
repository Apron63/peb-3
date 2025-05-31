import $ from 'jquery'
import * as bootstrap from 'bootstrap'
import Inputmask from 'inputmask'

let url = $('#add-duration-button').data('add-duration-url')

$('.button-add-duration').on('click', function(e) {
    let duration = $(e.target).closest('.input-duration-row').find('.form-control').val()
    let valueNow = $(e.target).closest('.input-duration-row').find('.permission-duration')
    let permissionHistory = $(e.target).closest('.input-duration-row').find('.permission-history')
    let inputNow = $(e.target).closest('.input-duration-row').find('input')

    if (duration != '') {
        let permissionId = $(e.target).data('permission-id')

        $.ajax({
            url: url,
            data: {permissionId: permissionId, duration: Number(duration)}
        }).done(function (data) {
            $(valueNow).text('Всего: ' + data['duration'] + ' дней')
            $(permissionHistory).html(data['content'])
            $(inputNow).val('')

            tooltipTriggerList = document.querySelectorAll('[data-bs-toggle="tooltip"]')
            tooltipList = [...tooltipTriggerList].map(tooltipTriggerEl => new bootstrap.Tooltip(tooltipTriggerEl))

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

$('.user-list-checkbox').on('click', function(e) {
    e.stopImmediatePropagation

    let permissionId = $(e.target).data('permission-id')
    let url = $('#check-user-list').data('check-user-list-url')

    $.ajax({
        url: url,
        data: {permissionId: permissionId}
    }).done(function (data) {
        if (data.length !== 0) {
            $(e.target).prop('checked', false)

            $('#toast-message').html(data)
            $('.toast-header').css('background-color', 'red')
            let toast = new bootstrap.Toast(toastLiveExample)
            toast.show()
        }
    }).fail(function (data) {
       console.log(data)
    })
})

$('#permission-checked-prolongate').on('click', function(e) {
    e.stopImmediatePropagation

    $.ajax({
        url: $('#permission-checked-prolongate').data('url')
    }).done(function (data) {
        $('#myModalLabel').html('Массовое продление доступов')
        $('#myModalBody').html(data)

        const myModal = new bootstrap.Modal($('#myModal'), {
            keyboard: false
        })

        myModal.show()

        $('#permission-action-prolongate').on('click', function() {
            let duration = $('#add_days').val()
            if (duration === 0 || duration === '') {
                alert('Не указано количество дней!')
                return false
            }

            if (duration > 999) {
                duration = 999
            }

            $.ajax({
                url: $('#permission-action-prolongate').data('url'),
                data: {duration: duration}
            }).done(function (data) {
                window.location.reload(true)
            }).fail(function (data) {
                console.log(data)
            }).always(function(data) {
                myModal.hide()
            })
        })
    }).fail(function (data) {
        console.log(data)
    })
})

let selector = $('#user_edit_mobilePhone')
if (selector.length > 0) {
    let im = new Inputmask('+7 999 999 9999')
    im.mask(selector)

    $('#user-edit-form').on('submit', function() {
        let mobilePhoneValue = $(selector).val()

        // Это страшный костыль, который потом надо будет поправить
        if (mobilePhoneValue.indexOf('_') !== -1) {
            alert('Номер телефона не совпадает с форматом +7 XXX XXX XXXX')

            return false
        }
    })
}

// Enable tooltips
let tooltipTriggerList = document.querySelectorAll('[data-bs-toggle="tooltip"]')
let tooltipList = [...tooltipTriggerList].map(tooltipTriggerEl => new bootstrap.Tooltip(tooltipTriggerEl))
