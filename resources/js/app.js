import './bootstrap';

import Alpine from 'alpinejs';

window.Alpine = Alpine;

Alpine.start();

// ==============================================================================
// GLOBAL ÇİFT TIKLAMA VE ÇOKLU GÖNDERİM ÖNLEYİCİ (DOUBLE-CLICK PREVENTION)
// ==============================================================================
document.addEventListener('DOMContentLoaded', () => {
    // 1. Form gönderimlerinde (Taslak Kaydet, Onaya Gönder, Sil, Excel'den Yükle vb.) çift tıklama engelleme
    document.addEventListener('submit', (e) => {
        const form = e.target;
        if (!form || form.tagName !== 'FORM') return;

        if (form.dataset.submitting === 'true') {
            e.preventDefault();
            e.stopImmediatePropagation();
            return false;
        }

        form.dataset.submitting = 'true';
        const submitButtons = form.querySelectorAll('button[type="submit"], input[type="submit"]');
        submitButtons.forEach(btn => {
            btn.classList.add('pointer-events-none', 'opacity-70', 'cursor-not-allowed');
        });

        // 6 saniye sonra fallback olarak kilidi aç
        setTimeout(() => {
            delete form.dataset.submitting;
            submitButtons.forEach(btn => {
                btn.classList.remove('pointer-events-none', 'opacity-70', 'cursor-not-allowed');
            });
        }, 6000);
    }, true);

    // 2. Kritik aksiyon link ve butonlarında (Yeni Veri Girişi, Forma Veri Gir, İncele, Düzenle, Şablon İndir vb.) çift tıklama engelleme
    document.addEventListener('click', (e) => {
        const target = e.target.closest('a[href], button:not([type="submit"])');
        if (!target) return;

        // Sayfa içi anchor (#) ve javascript: linklerini hariç tut
        const href = target.getAttribute('href');
        if (href && (href.startsWith('#') || href.startsWith('javascript:'))) return;

        // Modal tetikleyicilerini veya dropdown butonlarını hariç tut
        const clickAttr = target.getAttribute('@click') || '';
        if (clickAttr.includes('open') || clickAttr.includes('modal') || clickAttr.includes('show')) return;

        const isDownload = target.getAttribute('download') || (href && (href.includes('download') || href.includes('export')));
        const lockDuration = isDownload ? 1500 : 3000;

        if (target.dataset.clickLocked === 'true') {
            e.preventDefault();
            e.stopImmediatePropagation();
            return false;
        }

        target.dataset.clickLocked = 'true';
        target.classList.add('pointer-events-none');

        setTimeout(() => {
            delete target.dataset.clickLocked;
            target.classList.remove('pointer-events-none');
        }, lockDuration);
    }, true);
});

