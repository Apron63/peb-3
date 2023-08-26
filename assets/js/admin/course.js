import $ from 'jquery'
import 'jquery-ui-bundle'

const parsedUrl = new URL(window.location)

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
