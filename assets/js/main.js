// Auto-dismiss alerts after 5s
document.querySelectorAll('.alert').forEach(function(el) {
    setTimeout(function() {
        el.style.transition = 'opacity .5s';
        el.style.opacity = '0';
        setTimeout(function() { el.remove(); }, 500);
    }, 5000);
});

// Confirm dialogs
document.querySelectorAll('[data-confirm]').forEach(function(el) {
    el.addEventListener('click', function(e) {
        if (!confirm(this.dataset.confirm || 'Are you sure?')) e.preventDefault();
    });
});

// Print button
var printBtn = document.getElementById('printBtn');
if (printBtn) printBtn.addEventListener('click', function() { window.print(); });

// Grade auto-calculate
function calcTotal() {
    var mid  = parseFloat(document.getElementById('midterm_score')?.value) || 0;
    var fin  = parseFloat(document.getElementById('final_score')?.value)   || 0;
    var asgn = parseFloat(document.getElementById('assignment_score')?.value) || 0;
    var total = (mid * 0.3) + (fin * 0.5) + (asgn * 0.2);
    var tEl = document.getElementById('total_score');
    if (tEl) tEl.value = total.toFixed(2);
    var gEl = document.getElementById('grade_letter');
    if (gEl) gEl.value = total >= 75 ? 'A' : total >= 65 ? 'B' : total >= 55 ? 'C' : total >= 45 ? 'D' : 'F';
}
['midterm_score','final_score','assignment_score'].forEach(function(id) {
    var el = document.getElementById(id);
    if (el) el.addEventListener('input', calcTotal);
});
