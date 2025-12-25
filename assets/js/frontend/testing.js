import $ from 'jquery'

let questionType = $('.express-test_form_button_next').data('question-type')

$('.express-test_form_button_next').on('click', function() {
    let answerNom = []

    if (questionType == 1) {
        let selectedAnswerCount = $('.express-test_form').find('.test_form_radio:checked').length

        // if (selectedAnswerCount < 1) {
        //     $('.express-test_form_error').text('Выберите один из вариантов ответа !')
        //     return false
        // }

        answerNom = [$('.express-test_form').find('.test_form_radio:checked').val()]
    } else {
        let selectedAnswerCount = $('.express-test_form').find('.test_form_checkbox:checked').length

        if (selectedAnswerCount > 0 && selectedAnswerCount < 2) {
            $('.express-test_form_error').text('Выберите не меньше двух вариантов ответа !')
            return false
        }

        $('.test_form_checkbox:checked').each(function() {
            answerNom.push($(this).val());
        });
    }

    $.ajax({
        url: $('.express-test_form_button_next').data('url'),
        data: {
            loggerId: $('.express-test_form_button_next').data('logger-id'),
            permissionId: $('.express-test_form_button_next').data('permission-id'),
            answers: answerNom
        },
        method: 'POST'
    }).done(function(data) {
        $(location).prop('href', data['redirectUrl'])
    }).fail(function(data) {
        console.log(data)
    })
})

$('.set_permission_first_time').on('click', function() {
     $.ajax({
        url: $('.set_permission_first_time').data('url'),
        method: 'POST'
    }).done(function(data) {

    }).fail(function(data) {
        console.log(data)
    })
})

document.addEventListener('ticketTimeout', function() {
    $(location).prop('href', $('.express-test_form_button_next').data('final-url'))
}, false);
