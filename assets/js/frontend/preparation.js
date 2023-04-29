import $ from 'jquery'

$('.control-questions_item_button').on('click', function(e) {
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
