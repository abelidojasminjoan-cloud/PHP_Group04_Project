'use strict';
(() => {
    const el=id=>document.getElementById(id);
    const money=value=>'₱'+Number(value).toFixed(2);
    function illustration(kind) {
        const drawing=kind==='package'?'<rect x="20" y="28" width="80" height="65" rx="5" fill="#ccb796"/><path d="M20 45h80M60 28v65" stroke="#937f61" stroke-width="3"/><path d="m80 70 10 8-8 9" fill="none" stroke="#815d48" stroke-width="3"/>':'<rect x="35" y="25" width="50" height="14" rx="4" fill="#415b45"/><rect x="28" y="39" width="64" height="61" rx="13" fill="#a7bf8f"/><rect x="33" y="55" width="54" height="29" rx="3" fill="#f7f4e9"/><text x="60" y="73" text-anchor="middle" font-size="10" fill="#3f5138">PureVia</text>';
        return 'data:image/svg+xml,'+encodeURIComponent('<svg xmlns="http://www.w3.org/2000/svg" width="120" height="120" viewBox="0 0 120 120"><rect width="120" height="120" fill="#f0ece3"/>'+drawing+'</svg>');
    }
    const productPhoto=illustration('product'), packagePhoto=illustration('package');
    const customerItems=[{id:101,name:'PureVia Aloe Vera Soothe Moisturizer',size:'50g',quantity:1,price:79,variants:[{id:1,size:'50g'},{id:2,size:'100g'}]},{id:102,name:'Retinol Renewal Night Serum',size:'30ml',quantity:1,price:75,variants:[{id:3,size:'30ml'}]}];
    const initial=[
        {id:410278,order:'ORD-2026-0878',customer:'Sofia Dela Rosa',items:[{name:'Retinol Renewal Night Serum',size:'30ml',quantity:1,price:75,variants:[{id:3,size:'30ml'}]}],reason:'Skin reaction or sensitivity',resolution:'refund',method:'courier',status:'pending',date:'2026-10-30 10:18',details:'Experienced redness after the first use. The product and original packaging are available.',evidence:[productPhoto,packagePhoto],note:'',reference:''},
        {id:409614,order:'ORD-2026-0882',customer:'Carlo Bautista',items:[{name:'Cica Calming Sheet Mask',size:'25ml',quantity:3,price:18,variants:[{id:4,size:'25ml'}]}],reason:'Product arrived opened or leaking',resolution:'replacement',method:'dropoff',status:'approved',date:'2026-10-29 14:35',details:'One pouch leaked inside the delivery box. I would prefer a replacement.',evidence:[packagePhoto],note:'Return approved. Awaiting receipt and inspection.',reference:''},
        {id:408901,order:'ORD-2026-0841',customer:'Maya Perez',items:[{name:'PureVia Aloe Vera Soothe Moisturizer',size:'50g',quantity:1,price:79,variants:[{id:1,size:'50g'}]}],reason:'Received the wrong product',resolution:'refund',method:'courier',status:'completed',date:'2026-10-26 09:42',details:'Received the wrong moisturizer size. The product is unopened.',evidence:[productPhoto],note:'Unopened item received and inspected. Refund fulfilled.',reference:'DEMO-REF-0841'}
    ];
    let requests=structuredClone(initial),selected=null,photos=[],previousFocus=null,customerSubmitted=false;
    function node(tag,text,className){const item=document.createElement(tag);if(text!==undefined)item.textContent=text;if(className)item.className=className;return item;}
    function setMessage(id,text,success=false){const item=el(id);item.textContent=text;item.hidden=!text;item.classList.toggle('demo-success',success);}
    function openDialog(dialog){previousFocus=document.activeElement;dialog.showModal();document.body.classList.add('return-dialog-open');dialog.querySelector('.form-modal-body').scrollTop=0;}
    function closeDialog(dialog){dialog.close();document.body.classList.remove('return-dialog-open');if(previousFocus&&!previousFocus.disabled)previousFocus.focus();}
    function clearPhotos(){photos.forEach(photo=>URL.revokeObjectURL(photo.url));photos=[];el('orPhotos').value='';el('orPhotoList').replaceChildren();}
    function showView(view){el('demoAdminView').hidden=view!=='admin';el('demoCustomerView').hidden=view!=='customer';document.querySelectorAll('[data-demo-view]').forEach(button=>{const active=button.dataset.demoView===view;button.classList.toggle('is-active',active);button.classList.toggle('active',active);button.setAttribute('aria-pressed',String(active));});el('demoPageTitle').textContent=view==='admin'?'Admin Returns Preview':'Customer Return Preview';}
    document.querySelectorAll('[data-demo-view]').forEach(button=>button.addEventListener('click',()=>showView(button.dataset.demoView)));
    function renderRows(){
        const query=el('returnsSearch').value.trim().toLowerCase(),status=el('returnsFilter').value;
        const rows=requests.filter(request=>(!status||request.status===status)&&('RET-'+request.id+' '+request.order+' '+request.customer+' '+request.items.map(item=>item.name).join(' ')).toLowerCase().includes(query));
        el('demoReturnCount').textContent=requests.length+' sample records';
        el('demoReturnRows').replaceChildren(...rows.map(request=>{
            const tr=node('tr',undefined,'inventory-row');tr.append(node('td','RET-'+request.id),node('td',request.order),node('td',request.customer));
            const items=node('td');items.append(node('strong',request.items.map(item=>item.name).join(', ')),node('small',request.items.reduce((sum,item)=>sum+item.quantity,0)+' units'));tr.append(items,node('td',request.reason));
            const resolution=node('td');resolution.append(node('span',request.resolution==='refund'?'Refund':'Replacement','return-tag'));tr.append(resolution,node('td',money(request.items.reduce((sum,item)=>sum+item.price*item.quantity,0))));
            const state=node('td');state.append(node('span',request.status[0].toUpperCase()+request.status.slice(1),'return-tag return-tag-'+request.status));tr.append(state);
            const action=node('td'),button=node('button','Manage','return-manage');button.type='button';button.dataset.demoManage=request.id;button.addEventListener('click',()=>openManage(request));action.append(button);tr.append(action);return tr;
        }));
        if(!rows.length){const tr=node('tr'),td=node('td');tr.id='returnsEmpty';td.colSpan=9;const message=node('div',undefined,'returns-empty-message'),icon=node('i',undefined,'fa-regular fa-folder-open');icon.setAttribute('aria-hidden','true');message.append(icon,node('p','No return requests found.'));td.append(message);tr.append(td);el('demoReturnRows').append(tr);}
    }
    function completion(){const visible=el('manageReturnStatus').value==='completed'&&!el('manageReturnSave').hidden;el('manageReturnCompletion').hidden=!visible;el('manageReturnReference').required=visible;el('manageReturnCompletion').querySelectorAll('input,select').forEach(control=>control.disabled=!visible);}
    function openManage(request){
        selected=request;el('manageReturnForm').reset();el('manageReturnFields').hidden=false;setMessage('manageReturnMessage','');
        el('manageReturnNumber').textContent='RET-'+request.id+' · '+request.order+' · Demo';
        el('manageReturnReason').textContent=request.reason;el('manageReturnDate').textContent=request.date;el('manageReturnResolution').textContent=request.resolution==='refund'?'Refund':'Replacement';el('manageReturnMethod').textContent=request.method==='dropoff'?'Drop-off':'Courier Pickup';el('manageReturnDetails').textContent=request.details||'No additional details provided.';
        el('manageReturnNote').value=request.note;el('manageReturnReference').value=request.reference;el('manageReturnStatus').value=request.status;
        el('manageReturnEvidence').replaceChildren(...request.evidence.map((src,index)=>{const link=node('a'),img=node('img');link.href=src;link.target='_blank';link.rel='noopener';img.src=src;img.alt='Sample evidence '+(index+1);link.append(img);return link;}));if(!request.evidence.length)el('manageReturnEvidence').textContent='No evidence photos submitted.';
        el('manageReturnStock').replaceChildren(...request.items.map((item,index)=>{const row=node('div',undefined,'form-group'),label=node('label',item.name+' · '+item.quantity+' units'),select=node('select',undefined,'form-control');select.id='demoRestock'+index;label.htmlFor=select.id;select.append(new Option('Do not restock (opened, damaged, or not received)','0'));item.variants.forEach(variant=>select.append(new Option(variant.size,variant.id)));row.append(label,select);return row;}));
        const closed=['completed','rejected'].includes(request.status);el('manageReturnFields').querySelectorAll('input,select,textarea').forEach(control=>control.disabled=closed);
        const transitions={pending:['pending','approved','rejected'],approved:['approved','rejected','completed']};[...el('manageReturnStatus').options].forEach(option=>option.disabled=!closed&&!transitions[request.status].includes(option.value));el('manageReturnSave').hidden=closed;el('manageReturnReadOnly').hidden=!closed;completion();openDialog(el('manageReturnDialog'));
    }
    el('manageReturnStatus').addEventListener('change',completion);
    el('manageReturnForm').addEventListener('submit',event=>{
        event.preventDefault();if(!selected||el('manageReturnSave').hidden||!el('manageReturnForm').reportValidity())return;
        const form=el('manageReturnForm'),status=el('manageReturnStatus').value;
        if(status==='completed'&&!form.elements.confirm_resolution.checked){setMessage('manageReturnMessage','For this demo, confirm that the resolution has been fulfilled.');return;}
        const restock=[...el('manageReturnStock').querySelectorAll('select')].some(select=>select.value!=='0');if(status==='completed'&&restock&&!form.elements.confirm_inspected.checked){setMessage('manageReturnMessage','Confirm inspection before simulating a restock.');return;}
        selected.status=status;selected.note=el('manageReturnNote').value;selected.reference=el('manageReturnReference').value;renderRows();
        setMessage('manageReturnMessage','Demo updated on this page only. No refund, stock change, email, or database update was made.',true);
        if(['completed','rejected'].includes(status)){el('manageReturnSave').hidden=true;el('manageReturnReadOnly').hidden=false;el('manageReturnFields').querySelectorAll('input,select,textarea').forEach(control=>control.disabled=true);}
    });
    function showPhotos(){el('orPhotoList').replaceChildren(...photos.map((photo,index)=>{const li=node('li'),img=node('img'),remove=node('button','×');img.src=photo.url;img.alt='Selected sample evidence '+(index+1);remove.type='button';remove.setAttribute('aria-label','Remove photo '+(index+1));remove.addEventListener('click',()=>{URL.revokeObjectURL(photo.url);photos.splice(index,1);showPhotos();});li.append(img,remove);return li;}));}
    el('demoCustomerItems').replaceChildren(...customerItems.map(item=>{const row=node('div',undefined,'demo-order-item'),img=node('img'),info=node('div');img.src=productPhoto;img.alt='Sample product illustration';info.append(node('strong',item.name),node('small',item.size+' · Qty: '+item.quantity+' · '+money(item.price)));row.append(img,info);return row;}));
    el('demoStartReturn').addEventListener('click',()=>{
        if(customerSubmitted)return;const form=el('orReturnForm');form.reset();clearPhotos();form.querySelectorAll('.rt-error').forEach(error=>{error.hidden=true;error.textContent='';});el('orReturnFields').hidden=false;el('orSuccess').hidden=true;el('orSubmitReturn').hidden=false;el('orKeepOrder').textContent='Cancel';el('orOrderNumber').textContent='ORD-2026-0901 · Demo';
        el('orProducts').replaceChildren(...customerItems.map(item=>{const label=node('label',undefined,'return-product'),input=node('input'),img=node('img'),info=node('span');input.type='checkbox';input.name='items[]';input.value=item.id;img.src=productPhoto;img.alt='';img.width=58;img.height=58;info.append(node('strong',item.name),node('small',item.size+' · Qty: '+item.quantity+' · '+money(item.price)));label.append(input,img,info);return label;}));openDialog(el('orReturnModal'));
    });
    el('orAddPhotos').addEventListener('click',()=>el('orPhotos').click());
    el('orPhotos').addEventListener('change',()=>{const files=[...el('orPhotos').files];if(files.length+photos.length>5||files.some(file=>!['image/jpeg','image/png','image/webp'].includes(file.type)||file.size>5*1024*1024)){setMessage('orPhotosError','Choose up to 5 JPG, PNG, or WebP images, up to 5 MB each.');}else{files.forEach(file=>photos.push({file,url:URL.createObjectURL(file)}));setMessage('orPhotosError','');showPhotos();}el('orPhotos').value='';});
    el('orReturnForm').addEventListener('submit',event=>{
        event.preventDefault();if(customerSubmitted)return;const form=el('orReturnForm'),checked=[...el('orProducts').querySelectorAll('input:checked')];setMessage('orProductsError','');setMessage('orReasonError','');
        if(!checked.length){setMessage('orProductsError','Please select at least one product.');return;}if(!el('orReason').value){setMessage('orReasonError','Please select a reason for return.');return;}
        const chosen=customerItems.filter(item=>checked.some(input=>Number(input.value)===item.id));
        requests.unshift({id:410901,order:'ORD-2026-0901',customer:'Alex Demo',items:structuredClone(chosen),reason:el('orReason').value,resolution:form.elements.resolution.value,method:form.elements.return_method.value,status:'pending',date:'2026-10-31 10:30',details:el('orDetails').value,evidence:photos.map(photo=>photo.url),note:'',reference:''});
        // Keep selected photo URLs alive while the sample request is shown in Admin.
        photos=[];customerSubmitted=true;el('orReturnFields').hidden=true;el('orSubmitReturn').hidden=true;el('orKeepOrder').textContent='Close';setMessage('orSuccess','Demo request submitted! Switch to Admin to preview its review screen. Nothing was sent to the server.',true);el('demoStartReturn').textContent='Return requested';el('demoStartReturn').disabled=true;renderRows();
    });
    document.querySelectorAll('[data-or-close]').forEach(button=>button.addEventListener('click',()=>closeDialog(el('orReturnModal'))));document.querySelectorAll('[data-return-close]').forEach(button=>button.addEventListener('click',()=>closeDialog(el('manageReturnDialog'))));
    document.querySelectorAll('.return-dialog').forEach(dialog=>dialog.addEventListener('cancel',event=>{event.preventDefault();closeDialog(dialog);}));
    el('returnsSearch').addEventListener('input',renderRows);el('returnsFilter').addEventListener('change',renderRows);
    el('demoReset').addEventListener('click',()=>{requests.forEach(request=>request.evidence.filter(src=>src.startsWith('blob:')).forEach(src=>URL.revokeObjectURL(src)));clearPhotos();requests=structuredClone(initial);customerSubmitted=false;el('demoStartReturn').disabled=false;el('demoStartReturn').textContent='Return items';el('returnsSearch').value='';el('returnsFilter').value='';renderRows();});
    showView('admin');renderRows();
})();