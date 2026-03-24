document.addEventListener('DOMContentLoaded', function() {
    const cards = document.querySelectorAll('.event-card');
    if (cards.length) {
        cards.forEach((card, index) => {
            setTimeout(() => {
                card.classList.add('visible');
            }, index * 50);
        });
    }
});