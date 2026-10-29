"use strict";
document.addEventListener("DOMContentLoaded",()=>{
    const password=document.getElementById("resetPassword");
    const confirmation=document.getElementById("resetConfirmPassword");
    const feedback=document.getElementById("resetPasswordFeedback");
    const strength=document.getElementById("resetPasswordStrength");
    if(password&&confirmation&&feedback&&strength){
        const rules=[...feedback.querySelectorAll("[data-password-rule]")];
        function updateConfirmation(){
            const mismatch=confirmation.value!==""&&confirmation.value!==password.value;
            const error=document.getElementById("resetConfirmPasswordError");
            confirmation.setCustomValidity(mismatch?"The passwords do not match.":"");
            confirmation.setAttribute("aria-invalid",String(mismatch));
            error.hidden=!mismatch;
            error.textContent=mismatch?"The passwords do not match.":"";
        }
        function updateFeedback(){
            const value=password.value;
            let matched=0;
            for(const item of rules){
                const key=item.dataset.passwordRule;
                const minimum=Number(item.dataset.minCount||1);
                const pattern={uppercase:/[A-Z]/g,lowercase:/[a-z]/g,number:/[0-9]/g,symbol:/[^A-Za-z0-9]/gu}[key];
                const met=key==="length"?[...value].length>=minimum:(value.match(pattern)||[]).length>=minimum;
                if(met)matched++;
                item.dataset.met=String(met);
                item.setAttribute("aria-label",`${met?"Met":"Not met"}: ${item.lastElementChild.textContent}`);
            }
            const predictable=/^(?:password|qwerty|letmein|welcome|admin|123456|abcdef)/i.test(value)||/^(.{1,4})\1+$/.test(value);
            const level=!value?"empty":matched===rules.length&&!predictable?"strong":value.length>=8&&matched>=3&&!predictable?"medium":"weak";
            strength.dataset.level=level;
            strength.textContent={empty:"Not set",weak:"Weak",medium:"Medium",strong:"Strong"}[level];
            const policyError=value&&matched!==rules.length?"Meet all password requirements.":value&&(new TextEncoder().encode(value).length>72||value.includes("\u0000"))?"Password must not exceed 72 bytes or contain null characters.":"";
            password.setCustomValidity(policyError);
            updateConfirmation();
        }
        const wrapper=password.closest(".auth-password-wrapper");
        wrapper.addEventListener("focusin",()=>{feedback.hidden=false;});
        wrapper.addEventListener("focusout",event=>{if(!wrapper.contains(event.relatedTarget))feedback.hidden=true;});
        password.addEventListener("input",updateFeedback);
        confirmation.addEventListener("input",updateConfirmation);
        updateFeedback();
    }
    document.querySelectorAll("[data-reset-toggle]").forEach(button=>button.addEventListener("click",()=>{
        const input=document.getElementById(button.dataset.resetToggle);
        const visible=input.type==="password";
        input.type=visible?"text":"password";
        button.textContent=visible?"Hide":"Show";
        button.setAttribute("aria-pressed",String(visible));
        button.setAttribute("aria-label",`${visible?"Hide":"Show"} ${input.id==="resetPassword"?"new password":"password confirmation"}`);
    }));
    document.querySelectorAll("[data-reset-form]").forEach(form=>form.addEventListener("submit",()=>{
        const button=form.querySelector('button[type="submit"]');
        button.disabled=true;button.textContent=button.dataset.busy||"Please wait...";
    }));
    document.querySelectorAll("[data-wait]").forEach(button=>{
        let seconds=Number(button.dataset.wait);
        const update=()=>{button.disabled=seconds>0;button.textContent=seconds>0?`Resend OTP in ${seconds}s`:"Resend OTP";};
        update();if(seconds>0){const timer=setInterval(()=>{seconds--;update();if(seconds<=0)clearInterval(timer);},1000);}
    });
});
