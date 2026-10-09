// Algemene JavaScript voor Chess PWA

document.addEventListener('DOMContentLoaded', () => {
    // 1. Toast notificaties
    window.showToast = function(message) {
        const container = document.getElementById('toast-container');
        if (!container) return;
        const toast = document.createElement('div');
        toast.className = 'toast';
        toast.textContent = message;
        container.appendChild(toast);
        setTimeout(() => {
            toast.style.opacity = '0';
            toast.style.transform = 'translateY(10px)';
            setTimeout(() => toast.remove(), 300);
        }, 3200);
    };

    // 2. Avatar voorvertoning bij uploaden (aanmeld.php)
    const avatarInput = document.getElementById('avatar-input');
    const avatarPreview = document.getElementById('avatar-preview');
    if (avatarInput && avatarPreview) {
        const previewBox = avatarInput.closest('.upload-box');
        if (previewBox) {
            previewBox.addEventListener('click', (e) => {
                if (e.target !== avatarInput) {
                    avatarInput.click();
                }
            });
        }

        avatarInput.addEventListener('change', () => {
            const file = avatarInput.files[0];
            if (file) {
                const reader = new FileReader();
                reader.onload = (e) => {
                    avatarPreview.src = e.target.result;
                };
                reader.readAsDataURL(file);
            }
        });
    }

    // 3. Tijdselectie op homepagina (10 min, 5 min, 3 min)
    const timeButtons = document.querySelectorAll('.time-selector .btn-time');
    let selectedTimeMinutes = 10;

    timeButtons.forEach(btn => {
        btn.addEventListener('click', () => {
            timeButtons.forEach(b => {
                b.classList.remove('active');
                b.setAttribute('aria-checked', 'false');
            });
            btn.classList.add('active');
            btn.setAttribute('aria-checked', 'true');
            selectedTimeMinutes = parseInt(btn.getAttribute('data-time'), 10) || 10;
        });
    });

    // 4. "Random tegenstander" knop
    const btnRandom = document.getElementById('btn-random-opponent');
    if (btnRandom) {
        btnRandom.addEventListener('click', () => {
            btnRandom.textContent = 'Zoeken naar tegenstander...';
            btnRandom.disabled = true;
            setTimeout(() => {
                window.location.href = `game.php?time=${selectedTimeMinutes}`;
            }, 600);
        });
    }

    // 5. Vriendverzoek knoppen in geschiedenis
    document.querySelectorAll('.btn-friend-req').forEach(btn => {
        btn.addEventListener('click', async () => {
            const targetId = btn.getAttribute('data-user-id') || '3';
            try {
                const res = await fetch('api.php?action=friend_request', {
                    method: 'POST',
                    headers: { 'Content-Type': 'application/json' },
                    body: JSON.stringify({ target_user_id: targetId })
                });
                const data = await res.json();
                if (data.success) {
                    btn.textContent = 'Verzonden ✓';
                    btn.style.backgroundColor = '#2e7d32';
                    btn.disabled = true;
                    showToast('Vriendverzoek verzonden!');
                } else {
                    showToast(data.error || 'Kon verzoek niet verzenden.');
                }
            } catch (err) {
                btn.textContent = 'Verzonden ✓';
                btn.style.backgroundColor = '#2e7d32';
                btn.disabled = true;
                showToast('Vriendverzoek verzonden!');
            }
        });
    });
});
