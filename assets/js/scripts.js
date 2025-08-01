function openPopup() {
  let overlay = document.querySelector('[popup-element="overlay"]');
  let popup = document.querySelector('[popup-element="popup"]');
  overlay.classList.add("show");
  setTimeout(function () {
    popup.classList.add("show");
  }, 300);
}

function closePopup(event) {
  let overlay = document.querySelector('[popup-element="overlay"]');
  let popup = document.querySelector('[popup-element="popup"]');
  let popupCloseButton = document.querySelector(
    '[popup-element="popup-close"]'
  );

  if (
    event.target.closest('[popup-element="popup"]') != popup ||
    event.target == popupCloseButton
  ) {
    popup.classList.remove("show");
    setTimeout(function () {
      overlay.classList.remove("show");
    }, 300);
  }
}

// Функция открытия подменю после загрузки страницы
function openMenuAfterLoad(menu) {
  let menuItems = menu.querySelectorAll('[menu-elem="main-menu-item"]');

  menuItems.forEach((mainMenuElem) => {
    if (mainMenuElem.classList.contains("active")) {
      let programsMenu = mainMenuElem.querySelector(
        '[menu-elem="programs-menu"]'
      );

      if (programsMenu) {
        programsMenu.style.maxHeight = programsMenu.scrollHeight + "px";
        let programMenuItem = programsMenu.querySelectorAll(
          '[menu-elem="programs-menu-item"]'
        );

        programMenuItem.forEach((program) => {
          if (program.classList.contains("active")) {
            let modulesMenu = program.querySelector(
              '[menu-elem="modules-menu"]'
            );

            if (modulesMenu) {
              modulesMenu.style.maxHeight = modulesMenu.scrollHeight + "px";
              programsMenu.style.maxHeight =
                modulesMenu.scrollHeight + programsMenu.scrollHeight + "px";
            }
          }
        });
      }
    }
  });
}

// Открытие программ в боковом меню
function openMenuPrograms(event) {
  let mainMenuItem = event.target.closest('[menu-elem="main-menu-item"]');
  let programsMenu = mainMenuItem.querySelector('[menu-elem="programs-menu"]');

  // Если кликаем по уже открытому пункту
  if (mainMenuItem.classList.contains("active")) {
    mainMenuItem.classList.remove("active");
    programsMenu.style.maxHeight = 0; // Закрываем его

    // Закрываем все подпункты с модулями внутри этого пункта
    let programModulesMenu = programsMenu.querySelectorAll(
      '[menu-elem="modules-menu"]'
    );
    programModulesMenu.forEach((element) => {
      let elementProgramItem = element.closest(
        '[menu-elem="programs-menu-item"]'
      );
      let elementProgramHeader = elementProgramItem.querySelector(
        '[menu-elem="program-header"]'
      );

      elementProgramItem.classList.remove("active");
      elementProgramHeader.classList.remove("active");
      element.style.maxHeight = 0;
    });
  } else {
    // Если кликаем по неактивному пункту
    let allMainMenuItems = mainMenu.querySelectorAll(
      '[menu-elem="main-header"]'
    );
    // Проходим по всем остальные пункты
    allMainMenuItems.forEach((element) => {
      let elementMenuItem = element.closest('[menu-elem="main-menu-item"]');
      let elementProgramsMenu = elementMenuItem.querySelector(
        '[menu-elem="programs-menu"]'
      );

      elementMenuItem.classList.remove("active");
      elementProgramsMenu.style.maxHeight = 0; // Закрываем их

      let elementProgramModulesMenu = elementProgramsMenu.querySelectorAll(
        '[menu-elem="modules-menu"]'
      );
      // Проходим по всем подпунктам с модулями
      elementProgramModulesMenu.forEach((elem) => {
        let elemProgramItem = elem.closest('[menu-elem="programs-menu-item"]');
        let elemProgramHeader = elemProgramItem.querySelector(
          '[menu-elem="program-header"]'
        );
        // Закрываем их
        elemProgramItem.classList.remove("active");
        elemProgramHeader.classList.remove("active");
        elem.style.maxHeight = 0;
      });
    });

    // Открываем нужный пункт

    mainMenuItem.classList.add("active");
    programsMenu.style.maxHeight = programsMenu.scrollHeight + "px";
    mainMenu.style.maxHeight =
      mainMenu.scrollHeight + programsMenu.scrollHeight + "px";
  }
}

// Открытие модулей в программе
function openMenuModules(event) {
  let programMenuItem = event.target.closest(
    '[ menu-elem="programs-menu-item"]'
  );
  let modulesMenu = programMenuItem.querySelector('[menu-elem="modules-menu"]');
  let programsMenu = programMenuItem.closest('[menu-elem="programs-menu"]');

  // Если кликаем по уже открытому модулю
  if (programMenuItem.classList.contains("active")) {
    programMenuItem.classList.remove("active");
    modulesMenu.style.maxHeight = 0; // Закрываем его
  } else {
    // Если кликаем по неактивному пункту
    let allProgramsMenuItems = mainMenu.querySelectorAll(
      '[menu-elem="program-header"]'
    );

    // Закрываем другие модули
    allProgramsMenuItems.forEach((element) => {
      let elementProgramItem = element.closest(
        '[menu-elem="programs-menu-item"]'
      );
      let elementModulesMenu = elementProgramItem.querySelector(
        '[menu-elem="modules-menu"]'
      );

      elementProgramItem.classList.remove("active");
      elementModulesMenu.style.maxHeight = 0;
    });

    // Открываем нужный пункт
    programMenuItem.classList.add("active");

    modulesMenu.style.maxHeight = modulesMenu.scrollHeight + "px";
    programsMenu.style.maxHeight =
      modulesMenu.scrollHeight + programsMenu.scrollHeight + "px";
    mainMenu.style.maxHeight =
      mainMenu.scrollHeight +
      programsMenu.scrollHeight +
      modulesMenu.scrollHeight +
      "px";
  }
}

let mobileOpenMenuButton = document.querySelector('[menu-elem="mobile-open"]');
if (mobileOpenMenuButton) {
  mobileOpenMenuButton.addEventListener("click", (event) => {
    let mainMenu = document.querySelector('[menu-elem="main-menu"]');
    if (mainMenu.classList.contains("open")) {
      mainMenu.classList.remove("open");
      mainMenu.style.maxHeight = 0;
    } else {
      mainMenu.classList.add("open");
      mainMenu.style.maxHeight = mainMenu.scrollHeight + "px";
    }
  });
}

let forms = document.querySelectorAll('[form-elem="form"]');

if (forms.length > 0) {
  forms.forEach((form) => {
    let textInputs = form.querySelectorAll('[form-elem="input-text"]');

    textInputs.forEach((input) => {
      if (input.value != "") {
        input.closest('[form-elem="label"]').classList.add("active");
      }
    });

    form.addEventListener("focusin", (event) => {
      if (event.target.getAttribute("form-elem") == "input-text") {
        event.target.closest('[form-elem="label"]').classList.add("active");
      }
    });
    form.addEventListener("focusout", (event) => {
      if (event.target.getAttribute("form-elem") == "input-text") {
        if (event.target.value == "") {
          event.target
            .closest('[form-elem="label"]')
            .classList.remove("active");
        }
      }
    });
  });
}

let openPopupButton = document.querySelector('[element-action="open-popup"]');

if (openPopupButton) {
  openPopupButton.addEventListener("click", openPopup);
}

let overlay = document.querySelector('[popup-element="overlay"]');
if (overlay) {
  overlay.addEventListener("click", closePopup);
}

let mainMenu = document.querySelector('[menu-elem="main-menu"]');

if (mainMenu) {
  openMenuAfterLoad(mainMenu);
}

let finalTestingLink = document.querySelector('[content-elem="final-testing"]');

if (finalTestingLink) {
  if (finalTestingLink.getAttribute("status") == "not-passed") {
    finalTestingLink.addEventListener("click", openPopup);
  }
}

let faqItems = document.querySelectorAll('[faq-elem="item"]')

if (faqItems.length > 0) {
  faqItems.forEach((element) => {
    let question = element.querySelector('[faq-elem="question"]')

    question.addEventListener('click', function (event) {
      let item = this.closest('[faq-elem="item"]')
      let wrapper = this.closest('[faq-elem="wrapper"]')
      let thisAnswer = item.querySelector('[faq-elem="answer"]')

      if (item.classList.contains('active')) {
        thisAnswer.style.maxHeight = 0 + 'px'
        item.classList.remove('active')
      } else {
        let allItems = wrapper.querySelectorAll('[faq-elem="item"]')

        allItems.forEach(function (elem) {
          let answer = elem.querySelector('[faq-elem="answer"]')
          answer.style.maxHeight = 0 + 'px'
          elem.classList.remove('active')
        })

        thisAnswer.style.maxHeight = thisAnswer.scrollHeight + "px"
        item.classList.add('active')
      }
    })
  })
}

let supportSubmitButton = document.querySelector('[support-form-elem="submit"]')

if (supportSubmitButton) {

  supportSubmitButton.addEventListener('click', function (event) {
    let form = this.closest('form')
    let isError = false

    form.querySelectorAll('[form-elem="input-error"]').forEach(function(element) {
      element.innerText = ""
      element.closest('[form-elem="label"]').classList.remove("error")
    })

    form.querySelectorAll('[form-elem="input-text"]').forEach(function(element) {
      if(element.value == "") {
        isError = true
        element.nextElementSibling.innerText = "Поле не может быть пустым"
        element.closest('[form-elem="label"]').classList.add("error")
      }
    })

    if(!isError){
      let supportUrl =  document.querySelector('[support-form-elem="submit"]').getAttribute('support-url')
      fetch(supportUrl, {
        method: 'POST',
        body: new FormData(form),
      })
      .then(res => res.ok ? openPopup() : Promise.reject(res))
      .catch(() => alert('Ошибка отправки сообщения'));
    }
    
    
  })
}

let surveySubmitButton = document.querySelector('[survey-form-elem="submit"]')

if (surveySubmitButton) {
  surveySubmitButton.addEventListener('click', function (event) {
    let form = this.closest('form')
    let errorField = form.querySelector('[survey-form-elem="error"]')
    let isError = true
    let radioCourseWasUseful = ""
    let radioUsability = ""

    let formRadioCourseWasUseful = form.querySelectorAll('input[type="radio"][name="question1"]')
    let formRadioUsability = form.querySelectorAll('input[type="radio"][name="question3"]')
    let formTextareaMaterial = form.querySelector('textarea#usability')
    let formTextareaWish = form.querySelector('textarea#wish')

    formRadioCourseWasUseful.forEach(function (elem) {
      if (elem.checked) {
        radioCourseWasUseful = elem.value
      }
    })

    formRadioUsability.forEach(function (elem) {
      if (elem.checked) {
        radioUsability = elem.value
      }
    })

    if (radioCourseWasUseful != "" &&
      radioUsability != "" &&
      formTextareaMaterial.value != "" &&
      formTextareaWish.value != "") {
      isError = false
    }

    if (isError){
      errorField.innerText = "Пожалуйста, ответьте на все вопросы. Нам будет приятно"
    } else {
      errorField.innerText = ""
      let surveySaveUrl =  document.querySelector('[survey-form-elem="submit"]').getAttribute('survey-save-url')
      fetch(surveySaveUrl, {
        method: 'POST',
        body: new FormData(form),
      })
      .then(res => res.ok ? openPopup() : Promise.reject(res))
      .catch(() => alert('Ошибка отправки сообщения'));
    }
  })
}

const passwordInput = document.querySelector(".password_input");

if (passwordInput) {
  const eyeShowContainer = document.querySelector(".eye_show_container");
  const eyeHideContainer = document.querySelector(".eye_hide_container");

  if (passwordInput.getAttribute("type") == "password") {
    eyeHideContainer.style.display = "none";
    eyeShowContainer.style.display = "block";
  }

  if (passwordInput.getAttribute("text") == "password") {
    eyeHideContainer.style.display = "block";
    eyeShowContainer.style.display = "none";
  }

  eyeShowContainer.addEventListener("click", () => {
    passwordInput.setAttribute("type", "text");
    eyeHideContainer.style.display = "block";
    eyeShowContainer.style.display = "none";
  });
  eyeHideContainer.addEventListener("click", () => {
    passwordInput.setAttribute("type", "password");
    eyeHideContainer.style.display = "none";
    eyeShowContainer.style.display = "block";
  });
}
