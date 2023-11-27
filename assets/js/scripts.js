function openPopup() {
    let overlay = document.querySelector('[popup-element="overlay"]')
    let popup = document.querySelector('[popup-element="popup"]')
    overlay.classList.add('show')
    setTimeout(function () {
        popup.classList.add('show')
    }, 300)
}

function closePopup(event) {
    let overlay = document.querySelector('[popup-element="overlay"]')
    let popup = document.querySelector('[popup-element="popup"]')
    let popupCloseButton = document.querySelector('[popup-element="popup-close"]')

    if (event.target.closest('[popup-element="popup"]') != popup || event.target == popupCloseButton) {
        popup.classList.remove('show')
        setTimeout(function () {
            overlay.classList.remove('show')
        }, 300)
    }
}

// Функция открытия подменю после загрузки страницы
function openMenuAfterLoad(menu) {
    let menuItems = menu.querySelectorAll('[menu-elem="main-menu-item"]')

    menuItems.forEach(mainMenuElem => {
        if (mainMenuElem.classList.contains('active')) {
            let programsMenu = mainMenuElem.querySelector('[menu-elem="programs-menu"]')

            if (programsMenu) {
                programsMenu.style.maxHeight = programsMenu.scrollHeight + 'px';
                let programMenuItem = programsMenu.querySelectorAll('[menu-elem="programs-menu-item"]')

                programMenuItem.forEach(program => {
                    if (program.classList.contains('active')) {
                        let modulesMenu = program.querySelector('[menu-elem="modules-menu"]')

                        if (modulesMenu) {
                            modulesMenu.style.maxHeight = modulesMenu.scrollHeight + 'px';
                            programsMenu.style.maxHeight = modulesMenu.scrollHeight + programsMenu.scrollHeight + 'px';
                        }
                    }
                })
            }
        }
    })
}

// Открытие программ в боковом меню
function openMenuPrograms(event) {
    let mainMenuItem = event.target.closest('[menu-elem="main-menu-item"]')
    let programsMenu = mainMenuItem.querySelector('[menu-elem="programs-menu"]')

    // Если кликаем по уже открытому пункту
    if (mainMenuItem.classList.contains('active')) {
        mainMenuItem.classList.remove('active')
        programsMenu.style.maxHeight = 0; // Закрываем его

        // Закрываем все подпункты с модулями внутри этого пункта
        let programModulesMenu = programsMenu.querySelectorAll('[menu-elem="modules-menu"]')
        programModulesMenu.forEach(element => {
            let elementProgramItem = element.closest('[menu-elem="programs-menu-item"]')
            let elementProgramHeader = elementProgramItem.querySelector('[menu-elem="program-header"]')

            elementProgramItem.classList.remove('active')
            elementProgramHeader.classList.remove('active')
            element.style.maxHeight = 0;
        })
    } else { // Если кликаем по неактивному пункту
        let allMainMenuItems = mainMenu.querySelectorAll('[menu-elem="main-header"]')
        // Проходим по всем остальные пункты
        allMainMenuItems.forEach(element => {
            let elementMenuItem = element.closest('[menu-elem="main-menu-item"]')
            let elementProgramsMenu = elementMenuItem.querySelector('[menu-elem="programs-menu"]')

            elementMenuItem.classList.remove('active')
            elementProgramsMenu.style.maxHeight = 0; // Закрываем их

            let elementProgramModulesMenu = elementProgramsMenu.querySelectorAll('[menu-elem="modules-menu"]')
            // Проходим по всем подпунктам с модулями
            elementProgramModulesMenu.forEach(elem => {
                let elemProgramItem = elem.closest('[menu-elem="programs-menu-item"]')
                let elemProgramHeader = elemProgramItem.querySelector('[menu-elem="program-header"]')
                // Закрываем их
                elemProgramItem.classList.remove('active')
                elemProgramHeader.classList.remove('active')
                elem.style.maxHeight = 0;
            })
        });

        // Открываем нужный пункт

        mainMenuItem.classList.add('active')
        programsMenu.style.maxHeight = programsMenu.scrollHeight + 'px';
        mainMenu.style.maxHeight = mainMenu.scrollHeight + programsMenu.scrollHeight + 'px';
    }
}

// Открытие модулей в программе
function openMenuModules(event) {
    let programMenuItem = event.target.closest('[ menu-elem="programs-menu-item"]')
    let modulesMenu = programMenuItem.querySelector('[menu-elem="modules-menu"]')
    let programsMenu = programMenuItem.closest('[menu-elem="programs-menu"]')

    // Если кликаем по уже открытому модулю
    if (programMenuItem.classList.contains('active')) {
        programMenuItem.classList.remove('active')
        modulesMenu.style.maxHeight = 0; // Закрываем его

    } else { // Если кликаем по неактивному пункту
        let allProgramsMenuItems = mainMenu.querySelectorAll('[menu-elem="program-header"]')

        // Закрываем другие модули
        allProgramsMenuItems.forEach(element => {
            let elementProgramItem = element.closest('[menu-elem="programs-menu-item"]')
            let elementModulesMenu = elementProgramItem.querySelector('[menu-elem="modules-menu"]')

            elementProgramItem.classList.remove('active')
            elementModulesMenu.style.maxHeight = 0;
        })

        // Открываем нужный пункт
        programMenuItem.classList.add('active')

        modulesMenu.style.maxHeight = modulesMenu.scrollHeight + 'px';
        programsMenu.style.maxHeight = modulesMenu.scrollHeight + programsMenu.scrollHeight + 'px';
        mainMenu.style.maxHeight = mainMenu.scrollHeight + programsMenu.scrollHeight + modulesMenu.scrollHeight + 'px';
    }
}

let mobileOpenMenuButton = document.querySelector('[menu-elem="mobile-open"]')
if (mobileOpenMenuButton) {
    mobileOpenMenuButton.addEventListener('click', event => {

        let mainMenu = document.querySelector('[menu-elem="main-menu"]')
        if (mainMenu.classList.contains('open')) {
            mainMenu.classList.remove('open')
            mainMenu.style.maxHeight = 0;
        } else {
            mainMenu.classList.add('open')
            mainMenu.style.maxHeight = mainMenu.scrollHeight + 'px';
        }

    })
}


let forms = document.querySelectorAll('[form-elem="form"]')

if (forms.length > 0) {
    forms.forEach(form => {
        let textInputs = form.querySelectorAll('[form-elem="input-text"]')

        textInputs.forEach(input => {
            if (input.value != '') {
                input.closest('[form-elem="label"]').classList.add('active')
            }
        })

        form.addEventListener('focusin', event => {
            if (event.target.getAttribute('form-elem') == 'input-text') {
                event.target.closest('[form-elem="label"]').classList.add('active')
            }
        })
        form.addEventListener('focusout', event => {
            if (event.target.getAttribute('form-elem') == 'input-text') {
                if (event.target.value == '') {
                    event.target.closest('[form-elem="label"]').classList.remove('active')
                }
            }
        })
    })
}


let openPopupButton = document.querySelector('[element-action="open-popup"]')

if (openPopupButton) {
    openPopupButton.addEventListener('click', openPopup)
}


let overlay = document.querySelector('[popup-element="overlay"]')
if (overlay) {
    overlay.addEventListener('click', closePopup)
}

let mainMenu = document.querySelector('[menu-elem="main-menu"]')

if (mainMenu) {
    openMenuAfterLoad(mainMenu)
}


let programs = document.querySelector('[content-elem="programs"]')

if (programs) {
    let programsItems = programs.querySelectorAll('[content-elem="programs-item"]')

    programsItems.forEach(element => {
        element.addEventListener('mouseover', () => {
            element.classList.add('hover')
        })
        element.addEventListener('mouseleave', () => {
            element.classList.remove('hover')
        })
    })
}


let finalTestingLink = document.querySelector('[content-elem="final-testing"]')

if (finalTestingLink) {
    if (finalTestingLink.getAttribute('status') == 'not-passed') {
        finalTestingLink.addEventListener('click', openPopup)
    }
}
