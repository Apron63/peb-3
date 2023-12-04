// По-умолчанию позагрузка элементов разрешена
let isLoading = true

function loadControlQuestions(wrapper, trigger) {
    // Если подзагрузка разрешена, то делаем запрос на следующую страницу
    if (isLoading) {
        // активируем анимацию загрузки
        trigger.classList.add('loading')
        // Ищем контейнер с вопросами
        let inner = wrapper.querySelector('[control-questions-elem="inner"]')
        // Парсим номер страницы, которую надо загрузить с элемента загрузки
        let page = parseInt(trigger.getAttribute('data-page'))
        // Парсим количество вопросов на странице с элемента загрузки
        let perPage = parseInt(trigger.getAttribute('data-per-page'))
        // Парсим endpoint запроса с элемента загрузки
        let url = trigger.getAttribute('data-url')
        // Запрещаем подзагрузку новых страниц до выполнения запроса
        isLoading = false

        // Формируем тело запроса
        let body = {
            'page': page,
            'per_page': perPage,
            'permission_id': parseInt(trigger.getAttribute('data-permission-id')),
            'theme_id': parseInt(trigger.getAttribute('data-theme-id'))
        }

        // Делаем AJAX-запрос на endpoint
        fetch(url, {
            method: 'POST',
            headers: {
                "Content-Type": "application/json",
            },
            body: JSON.stringify(body)
        })
            .then(response => response.json())
            .then((data) => {
                // Если запрос имеет статус "success"
                if(data.status == 'success') {
                    // Убираем анимацию загрузки
                    trigger.classList.remove('loading')
                    // Вставляем новые вопросы в конец контейнера с вопросами
                    inner.insertAdjacentHTML("beforeend", data.content)
                    // Меняем значения data-аттрибутов для следующих запросов
                    trigger.setAttribute('data-page', data.page)
                    trigger.setAttribute('data-per-page', data.per_page)

                    // Если общее количество вопросов больше чем уже загружено, то разрешаем подзагрузку
                    if (data.total > page * perPage) {
                        isLoading = true
                    }
                }
            })
            .catch(error => location.replace('/'))
    }
}

let controlQuestionsWrapper = document.querySelector('[control-questions-elem="wrapper"]')
if (controlQuestionsWrapper) {
    window.addEventListener('scroll', function () {
        let controlQuestionsAjaxTrigger = controlQuestionsWrapper.querySelector('[control-questions-elem="ajax-trigger"]')

        if (controlQuestionsAjaxTrigger && getComputedStyle(controlQuestionsAjaxTrigger).display == 'block') {
            let screenHeight = window.innerHeight
            let scrolled = window.scrollY
            let bottomScroll = screenHeight + scrolled

            let controlQuestionsAjaxTriggerBox = controlQuestionsAjaxTrigger.getBoundingClientRect()
            let controlQuestionsAjaxTriggerPosition = controlQuestionsAjaxTriggerBox.top + window.pageYOffset

            if (bottomScroll > controlQuestionsAjaxTriggerPosition) {
                loadControlQuestions(controlQuestionsWrapper, controlQuestionsAjaxTrigger)
            }
        }
    });

    controlQuestionsWrapper.addEventListener('click', function(event) {
        if (event.target.getAttribute('test-form-elem') == 'prompt-open') {
            let prompt = event.target.closest('[test-form-elem="prompt-wrapper"]')
            prompt.classList.add('open')
        }
    })
}