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

$('.loader-checkbox').on('change', function(e) {
    let id = Number($(e.target).data('loader-id'))
    let value = $(e.target).is(':checked')

    $.ajax({
         url: $('#checkbox-change-url').data('url'),
         data: {id: id, value: value}
     }).done(function (data) {

     }).fail(function (data) {
        console.log(data)
     })
})

$('#selectAll').on('click', function(e) {
    e.stopImmediatePropagation

    let url = $('#selectAll').data('url')
    let action = 'select'

    setAllCheckBox(action, url)
})

$('#unselectAll').on('click', function(e) {
    e.stopImmediatePropagation

    let url = $('#unselectAll').data('url')
    let action = 'unselect'

    setAllCheckBox(action, url)
})

$('#assignCourse').on('click', function(e) {
    e.stopImmediatePropagation

    let url = $('#assignCourse').data('check')

    $.ajax({
        url: url
    }).done(function (data) {
        if (data.empty === false) {
            alert('Не выбраны слушатели')
            return false;
        }

        assignUsersToLoader()

    }).fail(function (data) {
       console.log(data)
    })
})

function setAllCheckBox(action, url)
{
    $.ajax({
        url: url,
        data: {action: action}
    }).done(function (data) {
        location.reload()
    }).fail(function (data) {
       console.log(data)
    })
}

function assignUsersToLoader()
{
    $.ajax({
        url: $('#assignCourse').data('prepare')
    }).done(function (data) {
        $('#myModalLabel').html('Укажите курс')
        $('#myModalBody').html(data)

        const myModal = new bootstrap.Modal($('#myModal'), {
            keyboard: false
        })

        function updateRightSelect()
        {
            rightSelect.innerHTML = '';

            for (let i = 0; i < leftSelect.options.length; i++) {
                const option = leftSelect.options[i];

                if (option.selected) {
                    const newOption = document.createElement('option');
                    newOption.value = option.value;
                    newOption.textContent = option.textContent;
                    rightSelect.appendChild(newOption);
                }
            }
        }

        const leftSelect = document.getElementById('course-select');
        const rightSelect = document.getElementById('course-selected');
        leftSelect.addEventListener('change', updateRightSelect);
        updateRightSelect();

        $('#q-search').on('keyup', function () {
            applyFilter();
        })

        $('#profile-select').on('change', function () {
            applyFilter();
        })

        $('#send-to-query').on('click', function () {
            let duration = Number($('#duration').val())

            if (duration <= 0 || duration === '') {
                alert('Не указана или неправильно указана продолжительность доступа!')
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

            if (course.length >= 30) {
                alert('Вы выбрали более 30 курсов одновременно. Максимальное количество - 30 курсов')
                return false
            }

            $.ajax({
                url: $('#send-to-query').data('url'),
                data: {duration: duration, course: course},
                method: 'POST',
                beforeSend: function () {
                    $('body').addClass('loading');
                    $('#loading-content').addClass('loading-content');
                },
            }).done(function (data) {
                if (data.success === false) {
                    location.reload()
                }

                $(function(){
                    window.setInterval(checkQuery, 1000 )
                });

            }).fail(function (data) {
                console.log(data)
            }).always(function(data){
                myModal.hide()
            })
        })

        myModal.show()
    }).fail(function (data) {
        console.log(data)
    })
}

function checkQuery ()
{
    $.ajax({
        url:$('#assignCourse').data('query'),
    }).done(function (data) {
        if (data.result) {
            location.reload()
        }
    }).fail(function (data) {
       console.log(data)
    })
}

function applyFilter()
{
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
