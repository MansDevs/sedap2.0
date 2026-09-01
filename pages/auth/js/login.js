document.addEventListener('DOMContentLoaded', () => {
    const btns = document.querySelectorAll('.role-btn');
    const input = document.getElementById('role-input');
    btns.forEach(btn => {
        btn.addEventListener('click', () => {
            btns.forEach(b => b.classList.remove('active'));
            btn.classList.add('active');
            input.value = btn.dataset.role;
        });
    });
});
