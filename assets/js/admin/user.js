import $ from 'jquery'
import * as bootstrap from 'bootstrap'

let url = $('#add-duration-button').data('add-duration-url')
let sendReportType = null

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

$('.send-user-list').on('click', function(e) {
    sendReportType = $(e.target).data('type')
    
    const myModal = new bootstrap.Modal($('#myModal'), {
        keyboard: false
    })

    $('#myModalLabel').html('Отправка данных')

    $.get(
        $('#dropdownMenuButton3').data('load-url'), 
        function(data) {
            $('#myModalBody').html(data.data)
            myModal.show()

            $('#send-email-to-client').on('click', function(e) {
                $.ajax({
                    url: $('#send-email-to-client').data('url'),
                    data: {
                        recipient: $('#recipient').val(),
                        subject: $('#subject').val(),
                        comment: $('#comment').val(),
                        type: sendReportType,
                        criteria: $('#dropdownMenuButton3').data('criteria')
                    }
                }).done(function (data) {
                    if (!data.success) {
                        $('#email-error').html(data.message)
                    } else {
                        myModal.hide()
                    }
                }).fail(function (data) {
                   console.log(data)
                })
            })
        }
    )
})
