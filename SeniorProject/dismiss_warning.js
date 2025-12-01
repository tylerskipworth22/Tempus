document.addEventListener('DOMContentLoaded', () => {
    document.querySelectorAll('.dismiss-btn').forEach(btn => {
        btn.addEventListener('click', async () => {
            const warningId = btn.dataset.id;
            if (!warningId) return;

            try {
                const formData = new FormData();
                formData.append('id', warningId);

                const response = await fetch('dismiss_warning.php', {
                    method: 'POST',
                    body: formData
                });

                const data = await response.json();

                if (data.success) {
                    const card = document.getElementById('warning-' + warningId);
                    if (card) card.remove();
                } else {
                    alert('Failed to dismiss warning: ' + data.error);
                }
            } catch (err) {
                console.error(err);
                alert('Error dismissing warning.');
            }
        });
    });
});