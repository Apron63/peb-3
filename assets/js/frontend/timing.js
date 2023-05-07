import $ from 'jquery'

let needTimeControl = $('.time-control')
if ($(needTimeControl).length > 0) {
    let backendUrl = $(needTimeControl).data('url')
    let permissionId = $(needTimeControl).data('permission-id')
    let startTime = $(needTimeControl).data('permission-start')

    setTimeout(function() {
        $.ajax({
            type: 'POST',
            url: backendUrl,
            data: {permissionId: permissionId, startTime: startTime, logout: true}
        }).done(function() {
            $(location).attr('href', '/');
        }).fail(function(data) {
            console.log(data)
        })

    }, 1000 * 60 * 60);

    if(navigator.userAgent.toLowerCase().indexOf('firefox') > -1) {
        $(window).on('pagehide', function(e) {
            e.isImmediatePropagationStopped
            $.ajax({
                type: 'POST',
                url: backendUrl,
                data: {permissionId: permissionId, startTime: startTime, logout: false}
            }).done(function(data) {
            }).fail(function(data) {
                console.log(data)
            })
        })
    } else {
        $(window).on('beforeunload', function(e) {
            e.isImmediatePropagationStopped
            $.ajax({
                type: 'POST',
                url: backendUrl,
                data: {permissionId: permissionId, startTime: startTime, logout: false}
            }).done(function(data) {
            }).fail(function(data) {
                console.log(data)
            })
        })
    }
}
