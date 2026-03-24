document.addEventListener('DOMContentLoaded', function() {
    // Плавное появление формы
    const formContainer = document.querySelector('.form-container');
    if (formContainer) {
        formContainer.style.opacity = '0';
        formContainer.style.transform = 'translateY(20px)';
        formContainer.style.transition = 'opacity 0.3s ease, transform 0.3s ease';
        setTimeout(() => {
            formContainer.style.opacity = '1';
            formContainer.style.transform = 'translateY(0)';
        }, 100);
    }
    
    const passwordField = document.getElementById('password');
    if (passwordField) {
        const toggleBtn = document.createElement('button');
        toggleBtn.type = 'button';
        toggleBtn.className = 'btn btn-outline-secondary btn-sm position-absolute end-0 top-50 translate-middle-y me-2';
        toggleBtn.style.zIndex = '10';
        toggleBtn.innerHTML = '👁';
        toggleBtn.style.background = 'transparent';
        toggleBtn.style.border = 'none';
        
        const wrapper = document.createElement('div');
        wrapper.style.position = 'relative';
        passwordField.parentNode.insertBefore(wrapper, passwordField);
        wrapper.appendChild(passwordField);
        wrapper.appendChild(toggleBtn);
        
        toggleBtn.addEventListener('click', function() {
            const type = passwordField.getAttribute('type') === 'password' ? 'text' : 'password';
            passwordField.setAttribute('type', type);
            toggleBtn.innerHTML = type === 'password' ? '👁' : '🙈';
        });
    }
    
    const form = document.querySelector('form');
    if (form) {
        form.addEventListener('submit', function(e) {
            const login = document.getElementById('login').value.trim();
            const password = document.getElementById('password').value.trim();
            
            if (!login || !password) {
                e.preventDefault();
                alert('Пожалуйста, заполните логин и пароль.');
                return false;
            }
        });
    }
});