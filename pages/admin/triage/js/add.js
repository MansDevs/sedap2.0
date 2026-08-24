document.addEventListener('input', () => {
    const temp = parseFloat(document.getElementById('t_temp').value) || 0;
    const symptoms = document.querySelectorAll('.symptom-cb:checked');
    const badge = document.getElementById('triage-badge');
    
    let isRed = false, isYellow = false;
    
    let hasFever = temp > 38 || document.querySelector('.symptom-cb[value="fever"]').checked;
    let hasVomit = document.querySelector('.symptom-cb[value="vomit"]').checked;
    let hasDiarrhea = document.querySelector('.symptom-cb[value="diarrhea"]').checked;
    
    if (hasFever && hasVomit && hasDiarrhea) isRed = true;
    else if (hasFever || symptoms.length >= 2) isYellow = true;
    
    if (isRed) { badge.textContent = 'RED'; badge.className = 'text-3xl font-bold px-6 py-2 rounded-full inline-block bg-[#C0392B] text-white'; }
    else if (isYellow) { badge.textContent = 'YELLOW'; badge.className = 'text-3xl font-bold px-6 py-2 rounded-full inline-block bg-[#D4A017] text-white'; }
    else { badge.textContent = 'GREEN'; badge.className = 'text-3xl font-bold px-6 py-2 rounded-full inline-block bg-[#1E8449] text-white'; }
});
