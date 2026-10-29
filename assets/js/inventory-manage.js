"use strict";
document.addEventListener("DOMContentLoaded",()=>{
    const dialog=document.getElementById("inventoryManageDialog");
    if(!dialog)return;
    const form=document.getElementById("inventoryManageForm"),get=id=>document.getElementById(id);
    const fields=get("inventoryManageFields"),save=get("inventoryManageSave"),type=get("inventoryAdjustmentType"),reason=get("inventoryAdjustmentReason"),quantity=get("inventoryAdjustmentQuantity"),threshold=get("inventoryLowThreshold"),notes=get("inventoryAdjustmentNotes"),error=get("inventoryManageError");
    let current=null,saving=false,generation=0;
    function showError(message){error.textContent=message;error.hidden=false;}
    function preview(){
        const q=quantity.value===""?NaN:Number(quantity.value),t=threshold.value===""?NaN:Number(threshold.value);
        const total=current?Number(current.stock_quantity)+(type.value==="add"?q:-q):NaN;
        const valid=Number.isSafeInteger(q)&&q>=0&&q<=2147483647&&Number.isSafeInteger(t)&&t>=0&&t<=2147483647&&Number.isSafeInteger(total)&&total>=0&&total<=2147483647;
        get("inventoryNewStock").textContent=Number.isSafeInteger(total)?`${total.toLocaleString()} units`:"—";
        get("inventoryNewStock").closest(".inventory-stock-preview").hidden=!Number.isSafeInteger(q);
        get("inventoryNewStock").style.color=total<0?"#b42318":"";
        save.disabled=!current||saving||!valid||(q===0&&t===Number(current.low_stock_threshold));
    }
    function updateReason(){
        const requiredType={restock:"add",cancelled_order:"add",customer_return:"add",damaged_expired:"remove"}[reason.value]||null;
        if(requiredType)type.value=requiredType;
        for(const option of type.options)option.disabled=!!requiredType&&option.value!==requiredType;
        notes.required=reason.value!=="restock";
        const help=get("inventoryAdjustmentHelp");
        help.textContent=reason.value==="customer_return"?"Add only returned units that have been received, inspected and marked suitable for resale. Record the order number and condition in Notes.":reason.value==="cancelled_order"?"Record the order number in Notes. Do not add stock again if it was already restored automatically.":notes.required?"Include the reason and relevant details in Notes.":"";
        help.hidden=!help.textContent;
        preview();
    }
    async function responseJson(response){if(!response.headers.get("content-type")?.includes("application/json"))throw new Error("Your session may have expired. Sign in again and reopen inventory.");const result=await response.json();if(!response.ok||!result.success)throw new Error(result.message||"Unable to load inventory.");return result;}
    document.querySelectorAll("[data-manage-inventory]").forEach(button=>button.addEventListener("click",async()=>{
        const request=++generation;current=null;saving=false;form.reset();error.hidden=true;fields.disabled=true;save.disabled=true;
        get("inventoryManageProduct").textContent="Loading product…";
        for(const id of ["inventoryCurrentStock","inventoryCurrentThreshold","inventoryLastUpdated","inventoryNewStock"])get(id).textContent="—";
        if(!dialog.open)dialog.showModal();
        try{
            const url=new URL(form.action,location.href);url.searchParams.set("id",button.dataset.manageInventory);
            const result=await responseJson(await fetch(url,{credentials:"same-origin",headers:{Accept:"application/json"}}));
            if(request!==generation||!dialog.open)return;
            current=result.variant;
            get("inventoryManageProduct").textContent=current.title;
            get("inventoryCurrentStock").textContent=`${Number(current.stock_quantity).toLocaleString()} units`;
            get("inventoryCurrentThreshold").textContent=`${Number(current.low_stock_threshold).toLocaleString()} units`;
            get("inventoryLastUpdated").textContent=current.updated_date;
            get("inventoryVariantId").value=current.id;get("inventoryRevision").value=current.revision;
            get("inventoryRequestId").value=[...crypto.getRandomValues(new Uint8Array(16))].map(b=>b.toString(16).padStart(2,"0")).join("");
            threshold.value=current.low_stock_threshold;quantity.value="";fields.disabled=false;updateReason();type.focus();
        }catch(e){if(request===generation)showError(e.message||"Unable to load inventory.");}
    }));
    document.querySelectorAll("[data-inventory-close]").forEach(button=>button.addEventListener("click",()=>{if(!saving){generation++;dialog.close();}}));
    dialog.addEventListener("cancel",event=>{if(saving)event.preventDefault();else generation++;});
    type.addEventListener("change",updateReason);reason.addEventListener("change",updateReason);
    quantity.addEventListener("input",preview);threshold.addEventListener("input",preview);
    form.addEventListener("submit",async event=>{
        event.preventDefault();if(saving||!current||save.disabled||!form.reportValidity())return;
        const data=new FormData(form);saving=true;fields.disabled=true;save.disabled=true;save.textContent="Saving…";error.hidden=true;
        try{await responseJson(await fetch(form.action,{method:"POST",credentials:"same-origin",headers:{Accept:"application/json"},body:data}));location.reload();}
        catch(e){showError(e.message||"Unable to save inventory.");saving=false;fields.disabled=false;save.textContent="Save Changes";updateReason();}
    });
});
