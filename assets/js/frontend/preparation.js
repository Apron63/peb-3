import $ from 'jquery'

$(document).on('click', '.control-questions_item_button', function(e) {
    e.stopImmediatePropagation()

    let wrapperId = $(e.target).data('form-id')
    let answerType = $(e.target).data('answer-type')

    let correct = true

    if (answerType == 1) {
        $(this).closest('#'+ wrapperId).find('input[type="radio"]').each(function(i, e) {
            let userAnswer = $(e).prop('checked')
            let correctAnswer = Boolean($(e).data('right'))

            if (userAnswer != correctAnswer) {
                correct = false
            }
        })
    } else {
        $(this).closest('#'+ wrapperId).find('input[type="checkbox"]').each(function(i, e) {
            let userAnswer = $(e).prop('checked')
            let correctAnswer = Boolean($(e).data('right'))

            if (userAnswer != correctAnswer) {
                correct = false
            }
        })
    }

    let result = $(this).closest('#'+ wrapperId).find('.control-questions_item_result')
    let hasRight = $(result).hasClass('right')

    if (correct) {
        if (!hasRight) {
            $(result).addClass('right')
        }

        $(result).html('Правильно')
    } else {
        if (hasRight) {
            $(result).removeClass('right')
        }

        $(result).html('Неправильно')
    }
})

$(document).on('change', '.test_form_checkbox', function(e) {
    e.stopImmediatePropagation()

    let data = {};

    $(e.target)
    .closest('.control-questions_item')
    .find('.test_form_question_wrapper')
    .each(function(i, el) {
        data.questionId = $(el).data('question-id')
    })

    let answers = {}

    $(e.target)
    .closest('.control-questions_item')
    .find('.test_form_checkbox')
    .each(function(i, el) {
        answers[$(el).data('answer-id')] = $(el).is(':checked')
    })

    data.answers = answers

    savePreparationData(data)
})

$(document).on('change', '.test_form_radio', function(e) {
    e.stopImmediatePropagation()

    let data = {};

    $(e.target)
    .closest('.control-questions_item')
    .find('.test_form_question_wrapper')
    .each(function(i, el) {
        data.questionId = $(el).data('question-id')
    })

    let answers = {}

    $(e.target)
    .closest('.control-questions_item')
    .find('.test_form_radio')
    .each(function(i, el) {
        answers[ $(el).data('answer-id')] = $(el).is(':checked')
    })

    data.answers = answers

    savePreparationData(data)
})

function savePreparationData(data)
{
    data.permissionId = $('.time-control').data('permission-id')

    $.ajax({
        url: '/preparation-save-history/',
        data: JSON.stringify(data),
        dataType: 'json',
        method: 'POST',
        processData : false,
    }).done(function(data) {
    }).fail(function(data) {
        console.log(data)
    })
}
