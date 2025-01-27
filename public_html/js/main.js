document.addEventListener('DOMContentLoaded', () => {

    const welcomeMessage = document.getElementById('welcome-message');
    if (welcomeMessage) {
        alert('Bem-vindo ao Sistema de Registro!');
    }

    const redirectToHome = () => {
        window.location.href = 'index.php';
    };

    const sessionMessage = document.getElementById('session-message');
    if (sessionMessage && sessionMessage.dataset.icon === 'success') {
        setTimeout(redirectToHome, 3000); 
    }
});
