import confetti from 'canvas-confetti';

/**
 * Tembakan confetti perayaan (modul selesai / lulus kuis).
 * Bisa dipanggil dari mana saja: window.fireConfetti()
 */
window.fireConfetti = function () {
    const duration = 1200;
    const end = Date.now() + duration;
    // Palet Nagari Creative Hub: deep blue, Minang gold, hijau SDG-3, biru SDG-14.
    const colors = ['#003857', '#fed33e', '#4c9f38', '#0a97d9'];

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
