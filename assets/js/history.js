// По-умолчанию подзагрузка элементов разрешена
let isLoading = true;

function loadHistoryElem(wrapper, trigger) {
  // Если подзагрузка разрешена, то делаем запрос на следующую страницу
  if (isLoading) {
    // активируем анимацию загрузки
    trigger.classList.add("loading");
    // Ищем контейнер с вопросами
    let inner = wrapper.querySelector('[history-elem="inner"]');
    // Парсим номер страницы, которую надо загрузить с элемента загрузки
    let page = parseInt(trigger.getAttribute("data-page"));
    // Парсим количество вопросов на странице с элемента загрузки
    let perPage = parseInt(trigger.getAttribute("data-per-page"));
    // Парсим endpoint запроса с элемента загрузки
    let url = trigger.getAttribute("data-url");
    // Запрещаем подзагрузку новых страниц до выполнения запроса
    isLoading = false;

    // Формируем тело запроса
    let body = {
      page: page,
      per_page: perPage,
    };

    // Делаем AJAX-запрос на endpoint
    fetch(url, {
      method: "POST",
      headers: {
        "Content-Type": "application/json",
      },
      body: JSON.stringify(body),
    })
      .then((response) => response.json())
      .then((data) => {
        // Если запрос имеет статус "success"
        if (data.status == "success") {
          // Убираем анимацию загрузки
          trigger.classList.remove("loading");
          // Вставляем новые вопросы в конец контейнера с вопросами
          inner.insertAdjacentHTML("beforeend", data.content);
          // Меняем значения data-аттрибутов для следующих запросов
          trigger.setAttribute("data-page", data.page);
          trigger.setAttribute("data-per-page", data.per_page);
          // Если общее количество вопросов больше чем уже загружено, то разрешаем подзагрузку
          if (data.total > page * perPage) {
            isLoading = true;
          }
        }
      });
  }
}

let historyElemWrapper = document.querySelector('[history-elem="wrapper"]');

if (historyElemWrapper) {
  window.addEventListener("scroll", function () {
    let historyAjaxTrigger = historyElemWrapper.querySelector(
      '[history-elem="ajax-trigger"]'
    );
    if (
      historyAjaxTrigger &&
      getComputedStyle(historyAjaxTrigger).display == "block"
    ) {
      let screenHeight = window.innerHeight;
      let scrolled = window.scrollY;
      let bottomScroll = screenHeight + scrolled;
      let historyAjaxTriggerBox = historyAjaxTrigger.getBoundingClientRect();
      let historyAjaxTriggerPosition =
        historyAjaxTriggerBox.top + window.pageYOffset;
      if (bottomScroll > historyAjaxTriggerPosition) {
        loadHistoryElem(historyElemWrapper, historyAjaxTrigger);
      }
    }
  });
}
