import $ from 'jquery'
import * as bootstrap from 'bootstrap'

let popoverIsActive = false
let elementWithPopover = {}
let popover = {}

$('#build-tickets').on('click', function (e) {
    e.stopImmediatePropagation()

    if (Object.keys(popover).length !== 0) {
        popover.hide()
        popoverIsActive = false
    }

    if ($('#ticket-cnt').val() <= 0) {
        popoverIsActive = true
        elementWithPopover = $('#ticket-cnt')

        popover = new bootstrap.Popover(document.querySelector('#ticket-cnt'), {
            title: 'Неправильное количество',
            content: 'Количество билетов должно быть больше 0',
            container: 'body',
            trigger: 'manual'
        })

        popover.show()
        return false
    }

    let theme = []
    let questionCompleted = true
    let popoverIsActive = false

    $.each($.find('.theme-cnt-input'), function (i, e) {
        let element = $(e).closest('tr').find('.theme-max-cnt')
        let id = Number($(element).data('max-cnt-id'))
        let value = Number($(element).data('max-cnt-value'))
        let inputValue = $(e).val()

        if ((inputValue > value || inputValue <= 0)) {
            questionCompleted = false

            if (!popoverIsActive) {
                popoverIsActive = true
                elementWithPopover = e

                popover =  new bootstrap.Popover(e, {
                    title: 'Неправильное количество',
                    content: 'Количество вопросов должно быть больше 0 и меньше ' + value,
                    container: 'body',
                    trigger: 'manual'
                })

                popover.show()
            }
        }
        theme.push({id: id, inputValue: inputValue})
    })

    if (!questionCompleted) {
        return false
    }

    let data = {
        course: Number($('#course-id').val()),
        ticketCnt: Number($('#ticket-cnt').val()),
        errCnt: Number($('#err-cnt').val()),
        themes: theme
    };

    $.ajax({
        url: $('#build-tickets').data('url'),
        data: data
    }).done(function (e) {
        $('#toast-message').html('Билеты успешно созданы!')
        let toast = new bootstrap.Toast(toastLiveExample)
        toast.show()
    }).fail(function (e) {
        console.log(e)
    });

    return false
})

$('#admin-load-question').on('click', function (e) {
    e.stopImmediatePropagation()

    if ($('#admin-load-question').data('qcount') > 0) {
        if(!confirm('Вопросы уже загружены. При новой загрузке вопросы и билеты будут удалены и созданы заново. Продолжить ?')) {
            return false
        }
    }
})
