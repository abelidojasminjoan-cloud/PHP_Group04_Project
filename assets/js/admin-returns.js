'use strict';
(() => {
    const dialog=document.getElementById('manageReturnDialog');
    const form=document.getElementById('manageReturnForm');
    if(!dialog||!form)return;
    const el=id=>document.getElementById(id);
    const base=form.dataset.baseUrl;
    let previousFocus=null, saving=false, controller=null, currentId=0;
    function message(text){el('manageReturnMessage').textContent=text;el('manageReturnMessage').hidden=!text;}
    function close(){if(saving)return;controller?.abort();dialog.close();document.body.classList.remove('return-dialog-open');previousFocus?.focus();}
    function completion(){
        const visible=el('manageReturnStatus').value==='completed'&&!el('manageReturnSave').hidden;
        el('manageReturnCompletion').hidden=!visible;
        el('manageReturnReference').required=visible;
        el('manageReturnCompletion').querySelectorAll('input,select').forEach(input=>input.disabled=!visible);
    }
    function populate(request){
        el('manageReturnId').value=request.id;el('manageReturnRevision').value=request.revision;
        el('manageReturnNumber').textContent='RET-'+request.id+' · '+request.order_number;
        el('manageReturnReason').textContent=request.reason;el('manageReturnDate').textContent=request.created_at;
        el('manageReturnResolution').textContent=request.resolution==='refund'?'Refund':'Replacement';
        el('manageReturnMethod').textContent=request.return_method==='dropoff'?'Drop-off':'Courier Pickup';
        el('manageReturnDetails').textContent=request.details||'No additional details provided.';
        el('manageReturnStatus').value=request.status;el('manageReturnNote').value=request.internal_note||'';el('manageReturnReference').value=request.resolution_reference||'';
        const evidence=el('manageReturnEvidence');evidence.replaceChildren();
        (request.evidence||[]).forEach((_,index)=>{
            const link=document.createElement('a');link.href=base+'/actions/admin/returns/evidence.php?id='+request.id+'&photo='+index;link.target='_blank';link.rel='noopener';
            const image=document.createElement('img');image.src=link.href;image.alt='Return evidence '+(index+1);link.append(image);evidence.append(link);
        });
        if(!evidence.children.length)evidence.textContent='No evidence photos submitted.';
        const stock=el('manageReturnStock');stock.replaceChildren();
        request.items.forEach(item=>{
            const row=document.createElement('div');row.className='form-group';
            const label=document.createElement('label');label.htmlFor='restockItem'+item.id;label.textContent=item.product_name+' · '+item.quantity+' units';
            const select=document.createElement('select');select.className='form-control';select.id=label.htmlFor;select.name='restock['+item.id+']';select.append(new Option('Do not restock (opened, damaged, or not received)','0'));
            item.variants.forEach(variant=>select.append(new Option(variant.size_capacity+(variant.variant_label?' · '+variant.variant_label:''),variant.id)));
            row.append(label,select);stock.append(row);
        });
        const closed=['completed','rejected'].includes(request.status);
        form.querySelectorAll('#manageReturnFields input, #manageReturnFields select, #manageReturnFields textarea').forEach(input=>input.disabled=closed);
        const transitions={pending:['pending','approved','rejected'],approved:['approved','rejected','completed']};
        [...el('manageReturnStatus').options].forEach(option=>option.disabled=!closed&&!(transitions[request.status]||[]).includes(option.value));
        el('manageReturnSave').hidden=closed;el('manageReturnReadOnly').hidden=!closed;
        completion();el('manageReturnFields').hidden=false;
    }
    document.querySelectorAll('[data-manage-return]').forEach(button=>button.addEventListener('click',async()=>{
        controller?.abort();controller=new AbortController();const activeController=controller;
        currentId=Number(button.dataset.manageReturn);previousFocus=button;form.reset();message('Loading return request…');el('manageReturnFields').hidden=true;el('manageReturnSave').disabled=true;el('manageReturnSave').hidden=false;
        dialog.showModal();document.body.classList.add('return-dialog-open');
        try{
            const response=await fetch(form.action+'?id='+currentId,{credentials:'same-origin',headers:{Accept:'application/json'},signal:activeController.signal});
            const result=await response.json();if(!response.ok||!result.success)throw Error(result.message||'Unable to load this return.');
            if(controller!==activeController||!dialog.open)return;
            populate(result.request);message('');el('manageReturnSave').disabled=false;
        }catch(error){if(error.name!=='AbortError'&&controller===activeController)message(error.message||'Unable to load this return.');}
    }));
    dialog.querySelectorAll('[data-return-close]').forEach(button=>button.addEventListener('click',close));
    dialog.addEventListener('cancel',event=>{event.preventDefault();close();});
    dialog.addEventListener('click',event=>{if(event.target===dialog){const bounds=dialog.getBoundingClientRect();if(event.clientX<bounds.left||event.clientX>bounds.right||event.clientY<bounds.top||event.clientY>bounds.bottom)close();}});
    el('manageReturnStatus').addEventListener('change',completion);
    form.addEventListener('submit',async event=>{
        event.preventDefault();if(saving||el('manageReturnFields').hidden||el('manageReturnSave').hidden||!form.reportValidity())return;
        saving=true;el('manageReturnSave').disabled=true;form.setAttribute('aria-busy','true');message('Saving…');
        try{
            const response=await fetch(form.action,{method:'POST',body:new FormData(form),credentials:'same-origin',headers:{Accept:'application/json'}});
            const result=await response.json();if(!response.ok||!result.success)throw Error(result.message||'Unable to update this return.');
            window.location.reload();
        }catch(error){message(error.message||'Unable to update this return.');el('manageReturnMessage').focus();}
        finally{saving=false;el('manageReturnSave').disabled=false;form.removeAttribute('aria-busy');}
    });
    function filter(){const query=el('returnsSearch').value.trim().toLowerCase();const status=el('returnsFilter').value;let count=0;document.querySelectorAll('[data-return-row]').forEach(row=>{row.hidden=!(row.dataset.search.includes(query)&&(!status||row.dataset.status===status));if(!row.hidden)count++;});el('returnsEmpty').hidden=count>0;}
    el('returnsSearch').addEventListener('input',filter);el('returnsFilter').addEventListener('change',filter);
})();