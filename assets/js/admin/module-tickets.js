import $ from 'jquery'
import * as bootstrap from 'bootstrap'

$('#createTickets').on('click', function() {

    let hasTicketCount = $('#createTickets').data('count')
    if (hasTicketCount > 0 ) {
        if (!confirm('Билеты уже созданы. Вы уверены, что хотите создать новые билеты ?')) {
            return false
        }
    }

    let ticketCount = Number($('#ticketCount').val())
    let questionCount = Number($('#questionCount').val())
    let timeLeft = Number($('#timeLeft').val())
    let errorsCount = Number($('#errorsCount').val())

    if (ticketCount === 0 || questionCount === 0) {
        alert('Нужно указать количество билетов и количество вопросов, отличное от 0')
        return false
    }

    $.ajax({
        url: $('#createTickets').data('url'),
        data: {ticketCount: ticketCount, questionCount: questionCount, timeLeft: timeLeft, errorsCount: errorsCount}
    }).done(function (data) {
        if (data.result) {
            $('#toast-message').html('Билеты были успешно созданы')
            $('.toast-header').css('background-color', 'green')
            $('.me-auto').css('color', 'white')
            let toast = new bootstrap.Toast(toastLiveExample)
            toast.show()
        }
    }).fail(function (data) {
        console.log(data)
    })
})
