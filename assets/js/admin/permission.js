import $ from 'jquery'
import AirDatepicker from 'air-datepicker'
import 'air-datepicker/air-datepicker.css'

const createdDatepickerElem = document.getElementById('permission_edit_createdAt')
const createdDatepicker = new AirDatepicker(createdDatepickerElem, {
    format: 'dd.MM.yyyy',
    language: 'ru',
    autohide: true
})

const activatedDatepickerElem = document.getElementById('permission_edit_activatedAt')
const activatedDatepicker = new AirDatepicker(activatedDatepickerElem, {
    format: 'dd.MM.yyyy',
    language: 'ru',
    autohide: true
})

$('#permission_edit_lifeSearch').on('keyup', function () {
    applyFilter()
})

$('#permission_edit_profile').on('change', function () {
    applyFilter()
})

function applyFilter() {

    let filter = $('#permission_edit_lifeSearch').val().toUpperCase()

    let selectedProfile = Number($('#permission_edit_profile :selected').val())

    $('#permission_edit_course').find('option').each(function (i, e) {
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
