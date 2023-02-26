import $ from 'jquery'
import * as bootstrap from 'bootstrap'

$('#select-all').on('click', function () {
    $('body input:checkbox').prop('checked', true)
})

$('#clean-all').on('click', function () {
    $('body input:checkbox').prop('checked', false)
})

$('#load-query').on('click', function () {
    if ($('input[type=checkbox]:checked').length === 0) {
        alert('Не выбран ни один слушатель')
        return false
    }

    $.ajax({
        url: $('#load-query').data('url')
    }).done(function (data) {
        $('#myModalLabel').html('Укажите курс')
        $('#myModalBody').html(data)
        let myModal = new bootstrap.Modal($('#myModal'), {
            keyboard: false
        })
        myModal.show();
    }).fail(function (data) {
        console.log(data)
    })
})


$('#q-search').on('keyup', function () {
    applyFilter();
})

$('#profile-select').on('change', function () {
    applyFilter();
})

function applyFilter() {
    let filter = $('#q-search').val().toUpperCase()
    let selectedProfile = Number($('#profile-select :selected').val())
    $('#course-select').find('option').each(function (i, e) {
        let isHidden = $(e).attr('style')
        let txtValue = $(e).text()
        let profile = Number($(e).data('profile'))
        let nowHidden = true
        let mustHidden = true

        if (typeof isHidden === 'undefined' || isHidden === 'display: block;') {
            nowHidden = false
        }

        if (txtValue.toUpperCase().indexOf(filter) > -1) {
            mustHidden = false
        }

        if (selectedProfile !== 0 && profile !== selectedProfile) {
            mustHidden = true
        }

        if (!nowHidden && mustHidden) {
            $(e).hide()
        }

        if (nowHidden && !mustHidden) {
            $(e).removeAttr('style').show()
        }
    })
}

$('#send-to-query').on('click', function () {
    let duration = $('#duration').val()
    if (duration === 0 || duration === '') {
        alert('Не заполнена продолжительность доступа!')
        return false
    }

    let course = $('#course-select').val()
    let userQuery = $('.user-list').find('input:checkbox:checked')
    if (userQuery.length === 0) {
        alert('Не отмечен ни один слушатель!!')
        return false
    }
    $('#myModal').modal('hide')
    let data = []
    userQuery.each(function (i, e) {
        let mainElement = $(e).parent().parent();
        data.push([
            $(mainElement).find('.user-list-order').text(),
            $(mainElement).find('.user-list-lastname').text(),
            $(mainElement).find('.user-list-firstname').text(),
            $(mainElement).find('.user-list-patronymic').text(),
            $(mainElement).find('.user-list-organization').text()
        ])
    })

    $.ajax({
        url: 'AssHole',
        data: {duration: duration, course: course, data: JSON.stringify(data)},
        method: 'POST'
    }).done(function (data) {
        $('#toast-message').html(data)
        let toast = new bootstrap.Toast(toastLiveExample)
        toast.show()
    }).fail(function (data) {
        console.log(data)
    })
})
