import AirDatepicker from 'air-datepicker'
import 'air-datepicker/air-datepicker.css'

const createdDatepickerElem = document.getElementById('permission_edit_createdAt')
const createdDatepicker = new AirDatepicker(createdDatepickerElem, {
    format: 'dd.mm.yyyy',
    language: 'ru',
    autohide: true
})

const activatedDatepickerElem = document.getElementById('permission_edit_activatedAt')
const activatedDatepicker = new AirDatepicker(activatedDatepickerElem, {
    format: 'dd.mm.yyyy',
    language: 'ru',
    autohide: true
})
