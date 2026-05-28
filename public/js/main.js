/* Pixel Pod — Public JS */
document.addEventListener('DOMContentLoaded', () => {
    // Sticky nav
    const nav = document.getElementById('siteNav');
    if (nav) window.addEventListener('scroll', () => nav.classList.toggle('scrolled', scrollY > 30), {passive:true});

    // Mobile nav toggle
    const toggle = document.getElementById('navToggle');
    const links  = document.getElementById('navLinks');
    const actions= document.querySelector('.nav-actions');
    if (toggle) toggle.addEventListener('click', () => {
        links?.classList.toggle('open');
        actions?.classList.toggle('open');
    });

    // Auto-dismiss alerts
    document.querySelectorAll('.alert[data-dismiss]').forEach(el => {
        setTimeout(() => { el.style.opacity='0'; setTimeout(()=>el.remove(),400); }, 4000);
    });

    // Booking: auto-fill end time
    const svcSel    = document.getElementById('service_id');
    const startTime = document.getElementById('start_time');
    const endTime   = document.getElementById('end_time');
    function fillEndTime() {
        if (!svcSel || !startTime || !endTime || !startTime.value) return;
        const dur = parseInt(svcSel.options[svcSel.selectedIndex]?.dataset.duration || 2);
        const [h,m] = startTime.value.split(':').map(Number);
        const end = new Date(0,0,0,h+dur,m);
        endTime.value = end.toTimeString().slice(0,5);
    }
    svcSel?.addEventListener('change', fillEndTime);
    startTime?.addEventListener('change', fillEndTime);

    // Price preview
    svcSel?.addEventListener('change', function() {
        const el = document.getElementById('price_preview');
        if (!el) return;
        const price = this.options[this.selectedIndex]?.dataset.price || '0';
        el.textContent = '₱' + parseFloat(price).toLocaleString('en-PH', {minimumFractionDigits:2});
    });

    // Prevent past dates
    const dateEl = document.getElementById('event_date');
    if (dateEl) dateEl.setAttribute('min', new Date().toISOString().split('T')[0]);

    // Char counter
    const msg = document.getElementById('message');
    const cnt = document.getElementById('char_count');
    if (msg && cnt) msg.addEventListener('input', () => cnt.textContent = msg.value.length);

    // Animated counters
    document.querySelectorAll('[data-count]').forEach(el => {
        const t = parseInt(el.dataset.count), suf = el.dataset.suffix||'';
        let c = 0;
        const step = Math.ceil(t/60);
        const timer = setInterval(() => { c=Math.min(c+step,t); el.textContent=c+suf; if(c>=t) clearInterval(timer); }, 24);
    });
});

function confirmAction(msg, formId) {
    if (confirm(msg)) document.getElementById(formId)?.submit();
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

//Disable Ctrl+Shift+C
document.addEventListener("keydown", e=>{
    if(e.ctrlkey && e.shiftKey && e.key === "C"){
        e.preventDefault();
        alert("Ctrl+Shift+C Disabled")
    }
})

//Disable Ctrl+Shift+J
document.addEventListener("keydown", e=>{
    if(e.ctrlKey && e.shiftKey && e.key === "J"){
        e.preventDefault();
        alert("Ctrl+Shift+J Disabled")
    }
})

//Disable Ctrl+U
document.addEventListener("keydown", e=>{
    if(e.ctrlKey && e.key === "U"){
        e.preventDefault();
        alert("Ctrl+U Disabled")
    }
})

