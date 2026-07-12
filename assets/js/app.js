// ======================================================================
// KILIMO-HIFADHI — Frontend JS
// ======================================================================

'use strict';

// --- Password visibility toggle ---
function togglePass(fieldId) {
    const el = document.getElementById(fieldId);
    if (!el) return;
    el.type = el.type === 'password' ? 'text' : 'password';
}

// --- Live table search ---
function liveSearch(inputId, tableId) {
    const input = document.getElementById(inputId);
    if (!input) return;
    input.addEventListener('keyup', function () {
        const q = this.value.toLowerCase();
        const rows = document.querySelectorAll('#' + tableId + ' tbody tr');
        rows.forEach(row => {
            row.style.display = row.textContent.toLowerCase().includes(q) ? '' : 'none';
        });
    });
}

// --- Toast notification ---
function showToast(message, type = 'success') {
    const container = document.getElementById('toastContainer') || createToastContainer();
    const id = 'toast_' + Date.now();
    const icon = type === 'success' ? '✅' : type === 'warning' ? '⚠️' : '❌';
    const bg   = type === 'success' ? 'bg-success' : type === 'warning' ? 'bg-warning text-dark' : 'bg-danger';
    const html = `
        <div id="${id}" class="toast align-items-center text-white ${bg} border-0" role="alert">
          <div class="d-flex">
            <div class="toast-body">${icon} ${message}</div>
            <button type="button" class="btn-close btn-close-white me-2 m-auto" data-bs-dismiss="toast"></button>
          </div>
        </div>`;
    container.insertAdjacentHTML('beforeend', html);
    const el = document.getElementById(id);
    const toast = new bootstrap.Toast(el, { delay: 4000 });
    toast.show();
    el.addEventListener('hidden.bs.toast', () => el.remove());
}

function createToastContainer() {
    const div = document.createElement('div');
    div.id = 'toastContainer';
    div.className = 'toast-container position-fixed bottom-0 end-0 p-3';
    div.style.zIndex = '9999';
    document.body.appendChild(div);
    return div;
}

// --- Animate stat counters ---
function animateCounters() {
    document.querySelectorAll('.stat-value[data-target]').forEach(el => {
        const target = parseFloat(el.dataset.target) || 0;
        const duration = 1200;
        const step = target / (duration / 16);
        let current = 0;
        const timer = setInterval(() => {
            current = Math.min(current + step, target);
            el.textContent = Number.isInteger(target) ? Math.floor(current) : current.toFixed(2);
            if (current >= target) clearInterval(timer);
        }, 16);
    });
}

// --- Confirm delete ---
function confirmDelete(form) {
    if (confirm('Je, una uhakika wa kufuta? Hatua hii haiwezi kutenduliwa.')) {
        form.submit();
    }
    return false;
}

// --- Cost calculator (storage) ---
function calcStorageCost() {
    const qty   = parseFloat(document.getElementById('qty_kg')?.value || 0);
    const price = parseFloat(document.getElementById('price_per_kg')?.value || 0);
    const days  = parseFloat(document.getElementById('duration_months')?.value || 0);
    const total = qty * price * days;
    const el    = document.getElementById('calculated_cost');
    if (el) el.textContent = 'Tshs ' + total.toLocaleString('en', { minimumFractionDigits: 2 });
}

// --- Cost calculator (transport) ---
function calcTransportCost() {
    const dist  = parseFloat(document.getElementById('distance_km')?.value || 0);
    const price = parseFloat(document.getElementById('price_per_km')?.value || 0);
    const total = dist * price;
    const el    = document.getElementById('calculated_cost');
    if (el) el.textContent = 'Tshs ' + total.toLocaleString('en', { minimumFractionDigits: 2 });
}

// --- Init on DOM ready ---
document.addEventListener('DOMContentLoaded', function () {
    // Animate stat counters
    animateCounters();

    // Activate any search boxes bound by data attribute
    document.querySelectorAll('[data-search-table]').forEach(input => {
        const tableId = input.dataset.searchTable;
        input.addEventListener('keyup', function () {
            const q = this.value.toLowerCase();
            document.querySelectorAll('#' + tableId + ' tbody tr').forEach(row => {
                row.style.display = row.textContent.toLowerCase().includes(q) ? '' : 'none';
            });
        });
    });

    // Auto-dismiss alerts after 5s
    document.querySelectorAll('.alert.alert-success, .alert.alert-info').forEach(el => {
        setTimeout(() => { el.classList.add('fade'); setTimeout(() => el.remove(), 400); }, 5000);
    });
});
