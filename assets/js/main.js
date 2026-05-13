// ============================================
// TIMEZONE DETECTION
// Šalje vremensku zonu browsera serveru
// ============================================
const timezone = Intl.DateTimeFormat().resolvedOptions().timeZone;
fetch('?action=set_timezone', {
    method: 'POST',
    headers: {
        'Content-Type': 'application/json',
        'X-Requested-With': 'XMLHttpRequest'
    },
    body: JSON.stringify({timezone: timezone})
});

// ============================================
// MOBILNI MENI
// ============================================
const hamburgerBtn = document.getElementById('hamburger-btn');
const closeMenuBtn = document.getElementById('close-menu-btn');
const mobileMenu = document.getElementById('mobile-menu');

if (hamburgerBtn && mobileMenu) {
    hamburgerBtn.addEventListener('click', () => {
        mobileMenu.classList.add('active');
        document.body.style.overflow = 'hidden'; // Sprečava skrolovanje stranice
    });
}

if (closeMenuBtn && mobileMenu) {
    closeMenuBtn.addEventListener('click', () => {
        mobileMenu.classList.remove('active');
        document.body.style.overflow = ''; // Vraća skrolovanje
    });
}

// ============================================
// LOGIN FORMA
// AJAX login bez reload-a cele stranice
// ============================================
const loginForm = document.getElementById('login-form');
if (loginForm) {
    loginForm.addEventListener('submit', async (e) => {
        e.preventDefault();
        
        const errorDiv = document.getElementById('error-message');
        errorDiv.style.display = 'none';
        
        const formData = new FormData(loginForm);
        const data = {
            username: formData.get('username'),
            password: formData.get('password')
        };
        
        try {
            const response = await fetch('?action=login_api', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'X-Requested-With': 'XMLHttpRequest'
                },
                body: JSON.stringify(data)
            });
            
            const result = await response.json();
            
            if (result.success) {
                window.location.reload();
            } else {
                errorDiv.textContent = result.message;
                errorDiv.style.display = 'block';
            }
        } catch (err) {
            errorDiv.textContent = 'Network error. Please try again.';
            errorDiv.style.display = 'block';
        }
    });
}
