import AirDatepicker from 'air-datepicker'
import 'air-datepicker/air-datepicker.css'

const startDatepickerSurveyElem = document.getElementById('survey_startPeriod')
const startDatepickerSurvey = new AirDatepicker(startDatepickerSurveyElem, {
    format: 'dd.MM.yyyy',
    language: 'ru',
    autohide: true
})

const endDatepickersurveyElem = document.getElementById('survey_endPeriod')
const endDatepickerSurvey = new AirDatepicker(endDatepickersurveyElem, {
    format: 'dd.MM.yyyy',
    language: 'ru',
    autohide: true
})
