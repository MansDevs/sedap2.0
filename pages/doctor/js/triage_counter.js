// Custom JS for Triage Counter
document.addEventListener('DOMContentLoaded', () => {

    const tempIn = document.getElementById('val_temp');
    const glucIn = document.getElementById('val_gluc');
    const cbs = document.querySelectorAll('.symptom-cb');
    const badge = document.getElementById('auto-badge');

    function calculateTriage() {
        let score = 0;
        let temp = parseFloat(tempIn.value) || 37;
        let gluc = parseFloat(glucIn.value) || 5;
        let symptoms = Array.from(cbs).filter(cb => cb.checked).length;
        
        if (temp > 39 || temp < 35) score += 3;
        else if (temp > 38) score += 1;
        
        if (gluc > 15 || gluc < 3) score += 3;
        else if (gluc > 10) score += 1;
        
        score += symptoms;
        
        if (score >= 4) { badge.className = 'w-48 h-48 rounded-full bg-triage-red text-white flex items-center justify-center text-3xl font-bold shadow-lg border-4 border-white mx-auto transition-colors duration-500'; badge.innerText = 'RED'; }
        else if (score >= 2) { badge.className = 'w-48 h-48 rounded-full bg-triage-yellow text-white flex items-center justify-center text-3xl font-bold shadow-lg border-4 border-white mx-auto transition-colors duration-500'; badge.innerText = 'YELLOW'; }
        else { badge.className = 'w-48 h-48 rounded-full bg-triage-green text-white flex items-center justify-center text-3xl font-bold shadow-lg border-4 border-white mx-auto transition-colors duration-500'; badge.innerText = 'GREEN'; }
    }
    
    if (tempIn && glucIn) {
        [tempIn, glucIn, ...cbs].forEach(el => el.addEventListener('input', calculateTriage));
        calculateTriage();
    }

});