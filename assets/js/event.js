// assets/js/event.js
document.addEventListener('DOMContentLoaded', function() {
    const urlParams = new URLSearchParams(window.location.search);
    const success = urlParams.get('success');
    const error = urlParams.get('error');

    if (success === 'registered') {
        showAlert('success', 'Вы успешно записаны на мероприятие. Статус: на рассмотрении.');
        const newUrl = window.location.pathname + '?id=' + urlParams.get('id');
        window.history.replaceState({}, document.title, newUrl);
    } else if (error) {
        let message = '';
        switch (error) {
            case 'invalid_event':
                message = 'Неверный идентификатор мероприятия.';
                break;
            case 'not_found':
                message = 'Мероприятие не найдено.';
                break;
            case 'registration_closed':
                message = 'Регистрация на это мероприятие закрыта.';
                break;
            case 'already_registered':
                message = 'Вы уже зарегистрированы на это мероприятие.';
                break;
            case 'db_failed':
                message = 'Ошибка базы данных. Попробуйте позже.';
                break;
            default:
                message = 'Произошла ошибка.';
        }
        showAlert('danger', message);
        // Убираем параметры ошибки
        const newUrl = window.location.pathname + '?id=' + urlParams.get('id');
        window.history.replaceState({}, document.title, newUrl);
    }
});

function showAlert(type, message) {
    const alertDiv = document.createElement('div');
    alertDiv.className = `alert alert-${type} alert-dismissible fade show mt-3`;
    alertDiv.role = 'alert';
    alertDiv.innerHTML = `
        ${message}
        <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
    `;
    // Вставляем после заголовка или в нужное место
    const main = document.querySelector('main .row .col-lg-8');
    if (main) {
        const firstCard = main.querySelector('.card:first-child');
        if (firstCard) {
            firstCard.parentNode.insertBefore(alertDiv, firstCard.nextSibling);
        } else {
            main.prepend(alertDiv);
        }
    }
}