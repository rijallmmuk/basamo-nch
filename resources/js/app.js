import confetti from 'canvas-confetti';

/**
 * Tembakan confetti perayaan (modul selesai / lulus kuis).
 * Bisa dipanggil dari mana saja: window.fireConfetti()
 */
window.fireConfetti = function () {
    const duration = 1200;
    const end = Date.now() + duration;
    const colors = ['#6366f1', '#8b5cf6', '#22c55e', '#f59e0b'];

    (function frame() {
        confetti({ particleCount: 4, angle: 60, spread: 55, origin: { x: 0 }, colors });
        confetti({ particleCount: 4, angle: 120, spread: 55, origin: { x: 1 }, colors });
        if (Date.now() < end) {
            requestAnimationFrame(frame);
        }
    })();

    confetti({ particleCount: 80, spread: 70, origin: { y: 0.6 }, colors });
};

// Dengarkan event dari Livewire (mis. lulus kuis).
document.addEventListener('livewire:init', () => {
    window.Livewire.on('confetti', () => window.fireConfetti());
});
