import $ from 'jquery'
import 'jquery-ui-bundle'

const parsedUrl = new URL(window.location)
const TYPE_INTERNAL_VIDEO = 4

$('#course-type-select').on('change', function () {
    const value = $('#course-type-select').val()

    if (value === '0') {
        parsedUrl.searchParams.delete('type')
    } else {
        parsedUrl.searchParams.set('type', value)
    }

    parsedUrl.searchParams.delete('page')
    window.location = parsedUrl.href
})

$('#profile-type-select').on('change', function () {
    const value = $('#profile-type-select').val()

    if (value === '0') {
        parsedUrl.searchParams.delete('profile')
    } else {
        parsedUrl.searchParams.set('profile', value)
    }

    parsedUrl.searchParams.delete('page')
    window.location = parsedUrl.href
})

$('#course-life-search-button').on('click', function (e) {
    let value = $('#course-life-search').val()

    if (value === '') {
        parsedUrl.searchParams.delete('name')
    } else {
        parsedUrl.searchParams.set('name', value)
    }

    parsedUrl.searchParams.delete('page')
    window.location = parsedUrl.href
})

$('#module_section_page_edit_type').on('change', function () {
    checkVideoUrl()
})

$(function($) {
    $('#draggable-module-container').sortable()
});

$('#course-edit-form').on('submit', function() {
    let sortOrder = new Map()
    $('#draggable-module-container')
    .find('.draggable-module-item')
    .each(function(i, e) {
        sortOrder.set($(e).data('id'), i + 1)
    })

    $('#course_edit_sortOrder').val(JSON.stringify(Object.fromEntries(sortOrder)))
})

$(window).on('load', function() {
    checkVideoUrl()
})

function checkVideoUrl()
{
    const value = $('#module_section_page_edit_type').val()
    if (value == TYPE_INTERNAL_VIDEO) {
        $('#video-file-area').show()
    }
    else {
        $('#video-file-area').hide()
    }
}
