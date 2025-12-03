document.addEventListener('DOMContentLoaded', () => {
    const dismissedKey = 'dismissedCapsules';
    const dismissed = JSON.parse(localStorage.getItem(dismissedKey)) || [];

    //remove any rejected capsules that were dismissed before
    dismissed.forEach(id => {
        const card = document.getElementById('rejected-' + id);
        if (card) card.remove();
    });

    document.querySelectorAll('.dismiss-btn').forEach(btn => {
        btn.addEventListener('click', async () => {
            const id = btn.dataset.id;
            if (!id) return;

            //check if is warning card
            const warningCard = document.getElementById('warning-' + id);
            if (warningCard) {
                //delete from DB
                try {
                    const formData = new FormData();
                    formData.append('id', id);

                    const response = await fetch('dismiss_warning.php', {
                        method: 'POST',
                        body: formData
                    });
                    const data = await response.json();

                    if (!data.success) {
                        alert('Failed to dismiss warning: ' + data.error);
                        return;
                    }
                } catch (err) {
                    console.error(err);
                    alert('Error dismissing warning.');
                    return;
                }
                warningCard.remove();
            }

            //otherwise, is rejected capsule
            const rejectedCard = document.getElementById('rejected-' + id);
            if (rejectedCard) {
                rejectedCard.remove();
                if (!dismissed.includes(id)) {
                    dismissed.push(id);
                    localStorage.setItem(dismissedKey, JSON.stringify(dismissed));
                }
            }
        });
    });
});
