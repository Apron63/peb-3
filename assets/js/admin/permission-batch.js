import $ from 'jquery'
import AirDatepicker from 'air-datepicker'
import 'air-datepicker/air-datepicker.css'

const createBatchdDatepickerElem = document.getElementById('permission_batch_create_createdAt')
const createBatchdDatepicker = new AirDatepicker(createBatchdDatepickerElem, {
    format: 'dd.MM.yyyy',
    language: 'ru',
    autohide: true
})

$('#permission_batch_create_lifeSearch').on('keyup', function () {
    applyFilter()
})

$('#permission_batch_create_profile').on('change', function () {
    applyFilter()
})

function applyFilter() {

    let filter = $('#permission_batch_create_lifeSearch').val().toUpperCase()

    let selectedProfile = Number($('#permission_batch_create_profile :selected').val())

    $('#permission_batch_create_course').find('option').each(function (i, e) {
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
