// класс формы добавления прав пользователю
class ProvidingAccessUI {
    constructor( formId ) {
        this.form                           = document.getElementById( formId );
        this.inputsRequried                = this.form.querySelectorAll("[data-required]");
        this.textInput                      = this.form.querySelectorAll("input[type='text']");
        this.emailInput                     = this.form.querySelectorAll("[data-email]");
        this.numberInput                    = this.form.querySelectorAll("[data-number]");
        this.selectInput                    = this.form.querySelectorAll("select");
        this.organizationInput              = this.form.querySelector("[data-organization]");
        this.accessCheckboxWrapper          = this.form.querySelectorAll(".access__checkbox-wrapper");
        this.accessCheckbox                 = this.form.querySelectorAll(".access__checkbox");
        this.accessCheckboxWrapperCloser    = this.form.querySelector(".checkbox__closer");
        this.errors                         = [];
        this.processInAction                = false;
        this.timer                          = "";
        this.userId                         = this.form.querySelector('input[name="userId"]');
        this.userChief0                     = this.form.querySelector('input[name="userChief0"]');
        this.userChief                      = this.form.querySelector('input[name="userChief"]');
        this.signedParameters               = this.form.dataset.signed;


        this.initializeHandleCheckbox( this.accessCheckbox );
        this.checkboxWrapperHandler (this.accessCheckboxWrapperCloser);
        this.sendFormData(this.form);
        this.removeErrorMessageWhileKeyupInput(this.inputsRequried);
        this.checkFieldWhileFocusOut(this.inputsRequried);
        this.checkEmailFieldsForCorrectness(this.emailInput);
        this.getOrganizationsWhileTyping(this.organizationInput);
        this.handleOrganizations();
    }

    // обработчик нажатия на checkbox
    initializeHandleCheckbox( checkboxes ) {
        checkboxes.forEach( ( singleCheckbox ) => {
            singleCheckbox.addEventListener("click",  function( e ) {
                this.classList.contains('access__checkbox_checked') ? this.classList.remove('access__checkbox_checked') : this.classList.add('access__checkbox_checked');
                this.querySelector("input[type='checkbox']").checked = !this.querySelector("input[type='checkbox']").checked;
            });
        });
    }

    // обработчик закрытия div, который содержит внутри себя checkboxes
    checkboxWrapperHandler ( closer ) {
        closer.addEventListener("click",  function( e ) {
            let classExists = this.parentNode.classList.contains("access__checkbox-wrapper_active");
            if(classExists) {
                this.parentNode.classList.remove("access__checkbox-wrapper_active");
            } else {
                this.parentNode.classList.add("access__checkbox-wrapper_active");
            }
        });
    }

    // обработчик сабмита формы
    sendFormData(currentForm) {
        this.form.addEventListener("submit", (event) =>{
            event.preventDefault();
            this.errors = []; // обнулим ошибки при сабмите
            this.checkFieldsForEmptiness(this.inputsRequried);  // проверим required поля на заполнение
            this.checkEmailFields(this.emailInput);
            if(this.errors.length === 0) {
                if( this.checkFieldResponsible() ) {
                    this.ajaxRequestCreateDraftSmartProcess();
                }
            }
        })
    }

    // проверить все inputsRequired на незаполненность отобразить ошибку
    checkFieldsForEmptiness(inputs) {
        // проверка левых inputs
        inputs.forEach((singleInput) => {
            if(this.checkSingleFieldForEmptiness(singleInput)) {
                this.errors.push(this.checkSingleFieldForEmptiness(singleInput));
            }
        });
        // скроллим до первой попавшейся ошибки
        if(this.errors.length>0) {
            this.scrollToElement( this.form.querySelector(".access__input_error-message") );
        }
    }

    // проверить один input на пустоту
    checkSingleFieldForEmptiness(singleInput) {
        let error = false;
        if(singleInput.value === "") {
            error = this.errorFieldHandler(singleInput, error);
        }
        return error;
    }

    // добавление к ошибочному полю класса ошибки
    errorFieldHandler(singleInput, error = null) {
        if( !(singleInput.parentNode.querySelector(".access__input_error-message")) ) {
            // если ошибка еще не отображена, то отображаем ее
            let errorMessage;

            if( typeof singleInput.dataset.email !== "undefined" ) {
                if(singleInput.value.length > 0) {
                    errorMessage = "Введите корректный email";
                } else {
                    errorMessage = "Необходимо заполнить это поле";
                }
            } else {
                 errorMessage = "Необходимо заполнить это поле";
            }


            let errorMessageBox = BX.create(
                "div",
                {
                    props: {
                        className: "access__input_error-message"
                    },
                    text: errorMessage
                }
            );
            if(singleInput.parentNode.classList.contains("access__input")) {
                BX.append(errorMessageBox, singleInput.parentNode);
                error = true;
            }
        } else {
            error = true;
        }
        return error;
    }

    // проверить email поле
    checkEmailFields(emailFields) {
        emailFields.forEach((field) => {
            if(!(field.value.match(/^\S+@\S+\.\S+$/))) {
                this.errors.push(true);
                this.errorFieldHandler(field);
            }
        });
    }

    // проверка email полей при focusout на корректность введенного email
    checkEmailFieldsForCorrectness(emailFields) {
        emailFields.forEach((singleInput) => {
            singleInput.addEventListener("focusout",() => {
                this.checkEmailFields(this.emailInput);
            });
        });
    }

    // проверка полей на пустоту при focusout
    checkFieldWhileFocusOut(inputs) {
        inputs.forEach((singleInput) => {
            singleInput.addEventListener("focusout",() => {
                this.checkSingleFieldForEmptiness(singleInput);
            });
        });
    }

    // метод скроллит до позиций указанного элемента
    scrollToElement(element = null) {
        if( element !== null ) {
            $('html, body').animate({
                scrollTop: ( ($(element).offset().top) - 5 )
            }, 500);
        }
    }

    // удаляем сообщение об ошибке, как только пользователь установил курсор в input
    removeErrorMessageWhileKeyupInput(inputs) {
        inputs.forEach((input) => {
            if(input.attributes.type.value !== "submit") {
                input.addEventListener("keyup", function(event) {
                    // удалим сообщение об ошибке, если введен хотя бы один символ
                    if(input.value.length > 0) {
                        if(this.parentNode.querySelector(".access__input_error-message")) {
                            BX.remove(this.parentNode.querySelector(".access__input_error-message"));
                        }
                    }
                });
            }
        });
    }

    // получение организаций при вводе пользователем
    getOrganizationsWhileTyping(organization) {
        organization.addEventListener("keyup", (event) => {
            window.clearTimeout(this.timer);
            this.timer = window.setTimeout(() => {
                let data = {};
                data['inputValue'] = document.querySelector("[data-organization]").value;
                if( (!this.processInAction) && (event.target.value !== "") ) {
                    this.addLoader(event.target);
                    this.processInAction = true;
                    BX.ajax.runComponentAction("gazprom:providing.access", "organizations", {
                        mode: 'class',
                        data: data,
                    }).then( response => {
                        this.distributeOrganizations(response);
                        this.removeLoader();
                        this.processInAction = false;
                    });
                }
            }, 1000);
        });
    }

    // добавлять организации к input организации
    distributeOrganizations(response) {
        BX.remove(document.querySelector(".access__input_organizations"));
        let organizationsWrapper =
            BX.create(
                "div",
                {
                    props: {
                        className: "access__input_organizations"
                    }
                }
            );

        if(response.data.length > 0) {
            response.data.forEach((org) => {
                let currentOrg =
                    BX.create(
                        "div",
                        {
                            props: {
                                className: "access__input_organization"
                            },
                            html: "<span style='position: fixed; top: -100000%;'>" + org.ID + ") </span>" +  org.UF_NAME
                        }
                    );
                BX.append(currentOrg,organizationsWrapper);
            });
        } else {
            let currentOrg =
                BX.create(
                    "div",
                    {
                        props: {
                            className: "access__input_organization"
                        },
                        text: "Совпадений не найдено"
                    }
                );
            BX.append(currentOrg,organizationsWrapper);
        }

        // добавим сформированные организации к inputу
        let orgInput = document.querySelector("[data-organization]");
        BX.append(organizationsWrapper, orgInput.parentNode);
    }

    // добавляет организацию в строку организации
    handleOrganizations() {
        $(document).on("click",".access__input_organization",function() {
            if(event.target.classList.contains("access__input_organization")) {
                if( event.target.innerText !== "Совпадений не найдено" && event.target.innerText !== "" ) {
                    // удалим сообщение об ошибке, если оно есть
                    let dataOrganization = document.querySelector("[data-organization]");
                    if(dataOrganization.parentNode.querySelector(".access__input_error-message")) {
                        BX.remove(dataOrganization.parentNode.querySelector(".access__input_error-message"));
                    }

                    dataOrganization.value = event.target.innerText;
                    BX.remove(document.querySelector(".access__input_organizations"));
                }
            }
        });
    }

    // добавить loader к любому объекту
    addLoader(currentInput) {
        if( !currentInput.parentNode.querySelector(".access__input-loader") ) {
            let loader = BX.create(
                "div",
                {
                    props: {
                        className: "access__input-loader",
                    },
                    html: '<img src="/local/components/gazprom/providing.access/templates/.default/images/loader.svg" />'
                }
            );
            BX.append(loader, currentInput.parentNode);
        }
    }

    // удалить loader
    removeLoader() {
        let accessInputLoader = document.querySelector(".access__input-loader");
        BX.remove(accessInputLoader);
    }

    // проверка поля ответственного с правой формы
    checkFieldResponsible() {
        let maySend = false;
        let responsiblesField = document.querySelector("select[name='sub-extended-form-responsible']");
        if( responsiblesField.selectedOptions[0].innerText === "--Выберите ответственного--" ) {
            // отобразим попап с сообщением об ошибке
            let popupWindowIdentifier = "popup-message_" + (Math.random() + 1).toString(36).substring(7);
            let popup = BX.PopupWindowManager.create(popupWindowIdentifier, null, {
                autoHide: true,
                offsetTop: 0,
                padding: 60,
                overlay : true,
                draggable: {restrict:true},
                closeByEsc: true,
                content: "<div style='font-size: 18px'>Необходимо выбрать ответственного от подразделения</div>",
                offsetLeft: 0,
                closeIcon: { right : "0", top : "0", width: "64px", height: "64px", opacity: 1},
                events: {
                    onPopupShow: function() {

                    },
                    onPopupClose: function() {
                        BX.PopupWindowManager.getCurrentPopup().destroy();
                    }
                }
            });
            popup.show()
        } else {
            // все поля заполнены, мы можем сделать ajax запрос
            // this.ajaxRequestCreateDraftSmartProcess();
            maySend = true;
        }
        return maySend;
    }

    /** --------------------------------------------------------------------------------------------------------------- **/

    // ajax запрос для создания смарт-процесса с типом черновик
    ajaxRequestCreateDraftSmartProcess() {
        this.addLoader(this.form);
        let formData = new FormData(this.form);
        let formDataFormated = {};

        // соберем данные в переменную formDataFormated
        let rights = [];
        for( let[key, value] of formData.entries() ) {
            if(key.length) {
                if(key === "RIGHTS[]") {
                    rights.push(value);
                } else {
                    formDataFormated[key] = value;
                }
            }
        }
        formDataFormated['RIGHTS'] = rights;

        if( !this.processInAction ) {
            this.processInAction = true;
            this.addLoader(this.form);

            let userId = ((document.querySelector("select[name='sub-extended-form-user']")).selectedOptions)[0].value;
            let userChief = ((document.querySelector("select[name='sub-extended-form-approver']")).selectedOptions)[0].value;
            let userChief0 = ((document.querySelector("select[name='sub-extended-form-responsible']")).selectedOptions)[0].value;

            BX.ajax.runComponentAction("gazprom:providing.access", "save", {
                mode: "class",
                signedParameters: this.signedParameters,
                data: {
                    formDataFormated: formDataFormated,
                    userId: userId,
                    userChief: userChief,
                    userChief0: userChief0,
                }
            }).then(response => {
                this.removeLoader();
                if( response.data.smart_item_id>0 ) {
                    location.href = response.data.service_view_url + '?WFID=' + response.data.smart_item_id;
                }
            });
        }
    }

    // ajax запрос, который удаляет строчку с правами
    deleteRow(tableRow) {
        // теперь сделаем ajax запрос с удалением текущей записи в инфоблоке
        if( !this.processInAction ) {
            this.processInAction = true;
            this.addLoader(tableRow.querySelector("td"));
            let rowId = tableRow.dataset.userdoid;
            BX.ajax.runComponentAction("gazprom:providing.access", "delete", {
                mode: "class",
                data: {
                    rowId: rowId
                }
            }).then(response => {
                // перезагрузим страницу после удаления строки (
                window.location.reload();
            });
        }
    }

    // ajax запрос для перевода смарт-процесса из черновика и запуск бизнес-процессов
    ajaxRequestStartBusinessProcces() {
        if( this.checkFieldResponsible() ) {
            if( !this.processInAction ) {
                this.processInAction = true;
                this.addLoader(this.form);

                let userId = ((document.querySelector("select[name='sub-extended-form-user']")).selectedOptions)[0].value;
                let userChief = ((document.querySelector("select[name='sub-extended-form-approver']")).selectedOptions)[0].value;
                let userChief0 = ((document.querySelector("select[name='sub-extended-form-responsible']")).selectedOptions)[0].value;

                BX.ajax.runComponentAction("gazprom:providing.access", "send", {
                    mode: "class",
                    signedParameters: this.signedParameters,
                    data: {
                        userId: userId,
                        userChief: userChief,
                        userChief0: userChief0,
                    }
                }).then(response => {
                    this.processInAction = false;
                    window.location.reload();
                });
            }
        }
    }


    /** Установка заявителя, согласующего и ответственного в правой форме */
    static addApplicantApproverResponsible() {
        const rightForm = document.getElementById("sub-extended-form");
        const selections = rightForm.querySelectorAll("select");

        if(BX.message('applicant')>0 && BX.message('approver')>0 && BX.message('responsible')>0) {
            selections.forEach((selection) => {
                switch ( selection.attributes.name.value ) {
                    case "sub-extended-form-user":
                        ProvidingAccessUI.addSelectedField( selection, BX.message("applicant") );
                        break;
                    case "sub-extended-form-approver":
                        ProvidingAccessUI.addSelectedField( selection, BX.message("approver") );
                        break;
                    case "sub-extended-form-responsible":
                        ProvidingAccessUI.addSelectedField( selection, BX.message("responsible") );
                        break;
                }
            });

            // обнулим значения, чтобы они не устанавливались повторно
            BX.message({
                applicant: "",
                approver: "",
                responsible: "",
            });
        }

    }

    static addSelectedField( selection, selectedValue ) {
        let options = selection.querySelectorAll('option');
        for(let i=0; i<options.length; i++) {
            if( parseInt(options[i].value) === selectedValue ) {
                options[i].selected = true;
            }
        }
    }
}

// класс формы таблицы прав пользователей
class ProvidingAccessTableUI {
    constructor( formId ) {
        this.form = document.getElementById(formId);
        this.tableRows = this.form.querySelectorAll('tr');
        this.submitButton = this.form.querySelector("input[type='submit']");

        // проинициализруем события
        this.mouseOverTableRow(this.tableRows);
        this.mouseOutTableRow(this.tableRows);
        this.removeRow(this.tableRows);
        this.sendRequest(this.submitButton);
    }

    // наведение на строку таблицы, появляется значок для удаления таблицы
    mouseOverTableRow(tableRows) {
        tableRows.forEach((tableRow) => {
            tableRow.addEventListener("mouseenter",function() {
                if( !(tableRow.querySelector(".tr-remover")) ) {
                    if(this.parentNode.localName !== "thead") {
                        let rowRemoveButton = BX.create(
                            "span",
                            {
                                props: {
                                    className: "tr-remover",
                                    title: "Удалить строку",
                                },
                                html: "<img src='/local/components/gazprom/providing.access/templates/.default/images/remove-row.svg' />"
                            }
                        );
                        let lastTd = this.querySelectorAll("td")[(this.querySelectorAll("td").length)-1];
                        BX.append(rowRemoveButton, lastTd);
                    }
                }
            });
        });
    }

    // уход со строки таблицы
    mouseOutTableRow(tableRows) {
        tableRows.forEach((tableRow) => {
            tableRow.addEventListener("mouseleave",function() {
                let trRemover = this.querySelector(".tr-remover");
                if(typeof(trRemover) != 'undefined') {
                    BX.remove(trRemover);
                }
            });
        });
    }

    // удаление строки
    removeRow(tableRows) {
        tableRows.forEach((tableRow) => {
            tableRow.addEventListener("click",function(event) {
                if(event.target.parentNode.classList.contains("tr-remover")) {
                    // сначала сделаем ajax запрос, которые удалит элементы smart-процесса
                   new ProvidingAccessUI("provide-access").deleteRow(tableRow);
                }
            });
        });
    }

    // перевод смарт-процесса из состояние черновика, запуск бизнес-процессов
    sendRequest(submitButton) {
        submitButton.addEventListener("click",function(event) {
            event.preventDefault();
            new ProvidingAccessUI("provide-access").ajaxRequestStartBusinessProcces();
        });
    }

}

BX.ready(function() {
    const providingAccess  = new ProvidingAccessUI( "provide-access" );
    if(document.getElementById("provide-access-table")) {
        new ProvidingAccessTableUI( "provide-access-table" );
    }
});
