/* Search and status filtering only; the existing assessment editor owns actions. */
document.addEventListener('DOMContentLoaded', () => {
    const search=document.getElementById('assessmentQuestionSearch');
    const status=document.getElementById('assessmentQuestionStatus');
    const empty=document.getElementById('assessmentQuestionsEmpty');
    if(!search||!status||!empty)return;
    const rows=[...document.querySelectorAll('[data-assessment-question]')];
    function filter(){
        const query=search.value.trim().toLowerCase();let count=0;
        rows.forEach(row=>{
            row.hidden=!(row.textContent.toLowerCase().includes(query)&&(status.value==='all'||row.dataset.status===status.value));
            if(!row.hidden)count++;
        });
        empty.hidden=count>0;
    }
    search.addEventListener('input',filter);status.addEventListener('change',filter);filter();
});