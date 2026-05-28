document.addEventListener('DOMContentLoaded', () => {
    // Sidebar toggle (mobile)
    document.getElementById('sidebarToggle')?.addEventListener('click', () =>
        document.querySelector('.admin-sidebar')?.classList.toggle('open'));

    // Auto-dismiss alerts
    document.querySelectorAll('.alert[data-dismiss]').forEach(el => {
        setTimeout(() => { el.style.opacity='0'; setTimeout(()=>el.remove(),400); }, 3500);
    });

    // Modal open/close
    document.querySelectorAll('[data-modal-open]').forEach(btn =>
        btn.addEventListener('click', () => document.getElementById(btn.dataset.modalOpen)?.classList.add('active')));
    document.querySelectorAll('[data-modal-close]').forEach(btn =>
        btn.addEventListener('click', () => btn.closest('.modal-overlay')?.classList.remove('active')));
    document.querySelectorAll('.modal-overlay').forEach(o =>
        o.addEventListener('click', e => { if(e.target===o) o.classList.remove('active'); }));

    // Table search
    const s = document.getElementById('tableSearch');
    if (s) s.addEventListener('input', function() {
        const v = this.value.toLowerCase();
        document.querySelectorAll('.searchable-row').forEach(r =>
            r.style.display = r.textContent.toLowerCase().includes(v) ? '' : 'none');
    });

    // Charts
    initBookingsChart();
    initServiceDonut();
});

function initBookingsChart() {
    const canvas = document.getElementById('bookingsChart');
    if (!canvas || typeof Chart === 'undefined') return;
    const months = ['Jan','Feb','Mar','Apr','May','Jun','Jul','Aug','Sep','Oct','Nov','Dec'];
    const cur = new Date().getMonth();
    const labels = months.slice(Math.max(0,cur-5), cur+1);
    new Chart(canvas, {
        type: 'line',
        data: {
            labels,
            datasets: [
                { label:'Bookings', data:labels.map(()=>Math.floor(Math.random()*16)+4),
                  borderColor:'#6b0f0f',backgroundColor:'rgba(107,15,15,.08)',borderWidth:2.5,
                  pointRadius:4,pointBackgroundColor:'#6b0f0f',tension:.4,fill:true },
                { label:'Revenue (×₱1k)', data:labels.map(()=>Math.floor(Math.random()*100)+20),
                  borderColor:'#c9a84c',backgroundColor:'rgba(201,168,76,.06)',borderWidth:2,
                  pointRadius:3,pointBackgroundColor:'#c9a84c',tension:.4,fill:true,yAxisID:'y1' }
            ]
        },
        options: { responsive:true, interaction:{mode:'index',intersect:false},
            plugins:{legend:{labels:{font:{family:'DM Sans',size:12},color:'#5a3a3a'}}},
            scales:{
                x:{grid:{color:'rgba(107,15,15,.05)'},ticks:{font:{family:'DM Sans',size:11},color:'#9a7575'}},
                y:{grid:{color:'rgba(107,15,15,.05)'},ticks:{font:{family:'DM Sans',size:11},color:'#9a7575'}},
                y1:{position:'right',grid:{display:false},ticks:{font:{family:'DM Sans',size:11},color:'#9a7575'}}
            }
        }
    });
}

function initServiceDonut() {
    const canvas = document.getElementById('serviceDonut');
    if (!canvas || typeof Chart === 'undefined') return;
    const d = window.serviceChartData || {labels:['Classic','Premium','Roving','360','Mirror','Mini'],values:[8,14,6,5,9,3]};
    new Chart(canvas, {
        type:'doughnut',
        data:{ labels:d.labels, datasets:[{ data:d.values,
            backgroundColor:['#6b0f0f','#8b1a1a','#c9a84c','#e8c97a','#3b82f6','#22c55e'],
            borderWidth:2,borderColor:'#fff' }] },
        options:{responsive:true,cutout:'68%',plugins:{legend:{display:false}}}
    });
}

function updateBookingStatus(id, status) {
    if (!confirm(`${status.charAt(0).toUpperCase()+status.slice(1)} this booking?`)) return;
    fetch('../api/booking_status.php', {
        method:'POST', headers:{'Content-Type':'application/x-www-form-urlencoded'},
        body:`booking_id=${id}&status=${status}`
    }).then(r=>r.json()).then(d => {
        showToast(d.message, d.success ? 'success' : 'error');
        if (d.success) setTimeout(()=>location.reload(),900);
    }).catch(()=>showToast('Network error.','error'));
}
function showToast(msg, type='success') {
    document.getElementById('toast')?.remove();
    const t = document.createElement('div');
    t.id='toast';
    t.style.cssText=`position:fixed;bottom:28px;right:28px;z-index:9999;padding:14px 22px;border-radius:12px;
        font-family:'DM Sans',sans-serif;font-size:.9rem;font-weight:500;
        box-shadow:0 8px 32px rgba(0,0,0,.18);color:#fff;
        background:${type==='success'?'#166534':'#991b1b'}`;
    t.textContent=msg;
    document.body.appendChild(t);
    setTimeout(()=>{t.style.opacity='0';setTimeout(()=>t.remove(),400);},3000);
}
function confirmDelete(formId) {
    if (confirm('Delete permanently? This cannot be undone.')) document.getElementById(formId)?.submit();
}

// Disable Right Click
document.addEventListener("contextmenu", e=>{
    e.preventDefault();
    alert("Right Click Disabled");
})

//Disable F12
document.addEventListener("keydown", e=>{
    if(e.key === "F12"){
        e.preventDefault();
        alert("F12 Disabled");
    }
})

//Disable Ctrl+Shift+I
document.addEventListener("keydown", e=>{
    if(e.ctrlKey && e.shiftKey && e.key === "I"){
        e.preventDefault();
        alert("Ctrl+Shift+I Disabled");
    }
})
