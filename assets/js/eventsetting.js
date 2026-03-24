document.addEventListener('DOMContentLoaded', function() {
    const bonusSelect = document.getElementById('bonus_select');
    const newBonusField = document.getElementById('new_bonus_field');
    const newBonusName = document.getElementById('new_bonus_name');
    const createBtn = document.getElementById('create_bonus_btn');
    const errorDiv = document.getElementById('new_bonus_error');

    if (!bonusSelect) return;

    bonusSelect.addEventListener('change', function() {
        if (this.value === 'new') {
            newBonusField.style.display = 'block';
        } else {
            newBonusField.style.display = 'none';
            if (errorDiv) errorDiv.innerHTML = '';
            if (newBonusName) newBonusName.value = '';
        }
    });

    if (createBtn) {
        createBtn.addEventListener('click', function() {
            const name = newBonusName.value.trim();
            if (!name) {
                if (errorDiv) errorDiv.innerHTML = 'Название бонуса не может быть пустым';
                return;
            }
            if (errorDiv) errorDiv.innerHTML = '';

            fetch('/create-bonus', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/x-www-form-urlencoded',
                },
                body: 'name=' + encodeURIComponent(name)
            })
            .then(response => response.json())
            .then(data => {
                if (data.error) {
                    if (errorDiv) errorDiv.innerHTML = data.error;
                } else if (data.success) {
                    const newOption = document.createElement('option');
                    newOption.value = data.id;
                    newOption.textContent = data.name;
                    const options = bonusSelect.options;
                    bonusSelect.insertBefore(newOption, options[options.length - 1]);
                    bonusSelect.value = data.id;
                    newBonusField.style.display = 'none';
                    newBonusName.value = '';
                    if (errorDiv) errorDiv.innerHTML = '';

                    const container = document.querySelector('.mb-3:has(select[name="bonus_id"])');
                    if (container) {
                        const successMsg = document.createElement('div');
                        successMsg.className = 'alert alert-success alert-dismissible fade show mt-2';
                        successMsg.innerHTML = 'Бонус добавлен и выбран' +
                            '<button type="button" class="btn-close" data-bs-dismiss="alert"></button>';
                        container.appendChild(successMsg);
                        setTimeout(() => successMsg.remove(), 3000);
                    }
                }
            })
            .catch(err => {
                if (errorDiv) errorDiv.innerHTML = 'Ошибка связи с сервером';
                console.error(err);
            });
        });
    }
});