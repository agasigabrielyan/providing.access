// класс формы согласующих
class UserSubExtendedForm {
    // конструктор формы согласующих
    constructor(formId) {
        this.form =                 document.getElementById(formId);
        this.currentuid =           this.form.dataset.currentuid;
        this.selects =              this.form.querySelectorAll("select");
        this.applicant =            this.form.querySelector("select[name='sub-extended-form-user']");
        this.approver =             this.form.querySelector("select[name='sub-extended-form-approver']");
        this.responsible =          this.form.querySelector("select[name='sub-extended-form-responsible']");
        this.userdepartment =       this.form.querySelector(".user-department");
        this.ajaxProccessInAction = false;
        this.users =                BX.message("users");

        this.getApproversAndResponsibles();
        this.applicationSelectionHandler();
        this.changeUserdepartmentData();
    }

    // обработчик изменения поля Заявитель
    applicationSelectionHandler() {
        this.applicant.addEventListener("change", (e) => {
            let selectedApplicantUid = e.target.value;
            this.getApproversAndResponsibles(selectedApplicantUid);
            this.changeUserdepartmentData(selectedApplicantUid);
        });
    }

    // обработчик изменения данных заявителя при изменении Заявителя
    changeUserdepartmentData(uid = null) {
        if(!uid) {
            uid = this.currentuid;
        }

        this.userdepartment.innerHTML = "";
        let userDepartmentHtml = "";
        debugger;
        this.users.forEach( (user) => {
            if( parseInt(user.ID) === parseInt(uid) ) {
                if(user.POSITION.length > 0) {
                    userDepartmentHtml += '<a href="" class="access__department">';
                        userDepartmentHtml += user.POSITION;
                    userDepartmentHtml += '</a>';
                }
                if(user.DIVISION.length > 0) {
                    for(let j=0; j<user.DIVISION.length; j++) {
                        userDepartmentHtml += '<a href="" class="access__department">';
                            userDepartmentHtml += user.DIVISION[j];
                        userDepartmentHtml += '</a>';
                    }
                }
            }
        } );
        this.userdepartment.innerHTML = userDepartmentHtml;
    }

    // ajax запрос для получения руководителей согласующих и списка ответственных
    getApproversAndResponsibles( uid = null ) {
        this.addLoader();
        let data = {};
        if(!uid) {
            uid = this.currentuid;
        }
        data['uid'] = uid;
        if(!this.ajaxProccessInAction) {
            this.ajaxProccessInAction = true;
            BX.ajax.get(
                "/bitrix/services/main/ajax.php?mode=class&c=gpi:request.smart.ajax&action=getUserChief",
                data,
                (answer) => {
                    let answerData = JSON.parse(answer);
                    this.distributeData(answerData);

                    this.ajaxProccessInAction = false;
                }
            );
        }
    }

    // распределение полученных данных по верстке формы согласующих
    distributeData(answer) {
        this.distributeApprovers(answer);
        this.distributeResponsibles(answer);


        if(BX.message("applicant")>0) {
            this.changeUserdepartmentData(BX.message("applicant"));
        }
        // class находится /local/components/gazprom/providing.access/templates/.default/script.js
        ProvidingAccessUI.addApplicantApproverResponsible();


        this.removeLoader();
    }

    // заполнение согласующих
    distributeApprovers(answer) {
        let approversHTML = "";
        answer.data.user_chief0.forEach((chief)=>{
            if(chief['FIRST'] && chief['FIRST'] === true) {
                approversHTML += "<option selected value='" + chief['ID'] + "'>" + chief['LAST_NAME'] + " " + chief['NAME'] + "</option>";
            } else {
                approversHTML += "<option value='" + chief['ID'] + "'>" + chief['LAST_NAME'] + " " + chief['NAME'] + "</option>";
            }
        });
        this.approver.innerHTML = approversHTML;
    }

    // заполнение ответственных
    distributeResponsibles(answer) {
        let repsponsiblesHTML = "<option>--Выберите ответственного--</option>";
        answer.data.user_chief.forEach((chief)=>{
            repsponsiblesHTML += "<option value='" + chief['ID'] + "'>" + chief['LAST_NAME'] + " " + chief['NAME'] + "</option>"
        });
        this.responsible.innerHTML = repsponsiblesHTML;
    }

    // включить loader формы
    addLoader() {
        this.form.classList.add("access__form_loading");
    }

    // отключить loader формы
    removeLoader() {
        this.form.classList.remove("access__form_loading");
    }
}

BX.ready(function() {
    const userSubExtendedForm = new UserSubExtendedForm( "sub-extended-form" );
});