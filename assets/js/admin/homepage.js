import $ from 'jquery'
import AirDatepicker from 'air-datepicker'
import 'air-datepicker/air-datepicker.css'

const startDatepickerElem = document.getElementById('user_search_startPeriod')
const startDatepicker = new AirDatepicker(startDatepickerElem, {
    format: 'dd.mm.yyyy',
    language: 'ru',
    autohide: true
})

const endDatepickerElem = document.getElementById('user_search_endPeriod')
const endDatepicker = new AirDatepicker(endDatepickerElem, {
    format: 'dd.mm.yyyy',
    language: 'ru',
    autohide: true
})

$('#user_search_lifeSearch').on('keyup', function () {
    applyFilter()
})

$('#user_search_profile').on('change', function () {
    applyFilter()
})

function applyFilter() {

    let filter = $('#user_search_lifeSearch').val().toUpperCase()

    let selectedProfile = Number($('#user_search_profile :selected').val())

    $('#user_search_course').find('option').each(function (i, e) {
        let isHidden = $(e).attr('style')
        let txtValue = $(e).text()
        let profile = Number($(e).data('profile'))
        let nowHidden = true
        let mustHidden = true

        if (typeof isHidden === "undefined" || isHidden === "display: block;") {
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
