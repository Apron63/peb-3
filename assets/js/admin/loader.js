import $ from 'jquery'
import * as bootstrap from 'bootstrap'

$('#load1C').on('click', function(e) {
    e.stopImmediatePropagation

    let emptyData = Number($('#empty-data').data('empty'))

    if (!emptyData) {
        if (!confirm('Вы уверены, что хотите загрузить новые данные ?')) {
            return false;
        }
    }

    let url = $('#load1C').data('url')
    window.location = url
})

$('#selectAll').on('click', function(e) {
    e.stopImmediatePropagation
    $('input[type="checkbox"]').prop('checked', true)
})

$('#unselectAll').on('click', function(e) {
    e.stopImmediatePropagation
    $('input[type="checkbox"]').prop('checked', false)
})

$('#assignCourse').on('click', function(e) {
    e.stopImmediatePropagation

    let loaderIds = []
    $('.loader-checkbox').each(function(index, elem) {
        if ($(elem).is(':checked')) {
            loaderIds.push($(elem).data('loader-id'))
        }
    })

    if (loaderIds.length === 0) {
        alert('Не выбран ни один слушатель')
    } else {
        assignUsersToLoader(loaderIds)
    }
})

function assignUsersToLoader(loaderIds)
{
    $.ajax({
        url: $('#assignCourse').data('prepare')
    }).done(function (data) {
        $('#myModalLabel').html('Укажите курс')
        $('#myModalBody').html(data)

        const myModal = new bootstrap.Modal($('#myModal'), {
            keyboard: false
        })

        $('#q-search').on('keyup', function () {
            applyFilter();
        })

        $('#profile-select').on('change', function () {
            applyFilter();
        })

        $('#send-to-query').on('click', function () {
            let duration = $('#duration').val()

            if (duration === 0 || duration === '') {
                alert('Не указана продолжительность доступа!')
                return false
            }

            if (duration > 999) {
                alert('Длительность не может превышать 999 дней !')
                return false
            }

            let course = $('#course-select').val()

            if (course == 0) {
                alert('Не выбран курс!')
                return false
            }

            $.ajax({
                url: $('#send-to-query').data('url'),
                data: {duration: duration, course: course, loaderIds: loaderIds},
                method: 'POST',
                beforeSend: function () {
                    myModal.hide()
                },
            }).done(function (data) {
                location.reload()
            }).fail(function (data) {
                console.log(data)
            }).always(function(data){
            })
        })

        myModal.show()
    }).fail(function (data) {
        console.log(data)
    })
}

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
