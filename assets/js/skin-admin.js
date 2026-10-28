"use strict";
document.addEventListener("DOMContentLoaded", () => {
    const c = window.PUREVIA_SKIN_ADMIN;
    if (!c) return;
    const el = id => document.getElementById(id), modal = el("saModal"), form = el("saForm"), fields = el("saFields");
    const node = (tag, text, cls) => { const n = document.createElement(tag); if (text !== undefined) n.textContent = text; if (cls) n.className = cls; return n; };
    let opener = null, current = null, pending = false, loadController = null, priorInert = [], priorOverflow = "";
    function error(message) { el("saFormError").textContent = message; el("saFormError").hidden = false; el("saFormError").focus(); }
    function open(title, subtitle = "") {
        loadController?.abort(); loadController = null; current = null; fields.replaceChildren(); el("saFormError").hidden = true;
        el("saModalTitle").textContent = title; el("saModalSubtitle").textContent = subtitle; el("saSubmit").hidden = false; el("saSubmit").disabled = false; el("saSubmit").textContent = "Save Changes";
        if (!modal.classList.contains("is-open")) { opener = document.activeElement; priorOverflow = document.body.style.overflow; document.body.style.overflow = "hidden"; priorInert = [...document.body.children].filter(n => n !== modal && !["SCRIPT", "STYLE", "LINK"].includes(n.tagName)).map(n => [n, n.inert]); priorInert.forEach(([n]) => { n.inert = true; }); }
        modal.classList.add("is-open"); modal.setAttribute("aria-hidden", "false"); modal.querySelector(".form-modal-dialog").focus();
    }
    function close() { if (pending) return; loadController?.abort(); modal.classList.remove("is-open"); modal.setAttribute("aria-hidden", "true"); priorInert.forEach(([n, value]) => { n.inert = value; }); priorInert = []; document.body.style.overflow = priorOverflow; opener?.focus(); }
    function control(label, key, value = "", kind = "input", options = []) {
        const group = node("div", undefined, "form-group"), text = node("label", label), input = node(kind); input.name = key; input.id = `sa-${key}`; text.htmlFor = input.id;
        input.className = "form-control"; if (kind === "select") options.forEach(([v, t]) => input.append(new Option(t, v))); else if (kind === "textarea") { input.rows = 3; input.classList.add("form-textarea"); }
        input.value = value; group.append(text, input); fields.append(group); return input;
    }
    function checkbox(label, key, checked = false) { const row = node("label", undefined, "sa-check"), input = node("input"); input.type = "checkbox"; input.name = key; input.checked = checked; row.append(input, node("span", label)); fields.append(row); return input; }
    async function get(record, id) {
        loadController = new AbortController(); const controller = loadController;
        const response = await fetch(`${c.baseUrl}/actions/admin/skin.php?record=${record}&id=${id}`, { credentials: "same-origin", signal: controller.signal, headers: { "Accept": "application/json" } });
        const data = await response.json(); if (controller.signal.aborted) return null; if (!response.ok || !data.success) throw new Error(data.message || "Unable to load this record."); return data.record;
    }
    function typeEditor(id, deleting = false) {
        const record = id ? c.types.find(r => Number(r.id) === id) : null; if (id && !record) return;
        open(deleting ? "Delete Skin Type" : id ? "Edit Skin Type" : "Add Skin Type", record?.assessment_key ? "Primary quiz type — labels and descriptions can be updated." : "Catalog tags do not add a new primary quiz result.");
        current = { action: deleting ? "type.delete" : "type.save", id };
        if (deleting) { fields.append(node("p", `Delete ${record.skin_type_name}? Types used by products or profiles cannot be deleted.`)); el("saSubmit").textContent = "Delete Skin Type"; }
        else { const name = control("Skin Type Name", "name", record?.skin_type_name || ""); name.maxLength = 100; name.required = true;
            control("Description", "description", record?.description || "", "textarea").maxLength = 5000;
            control("Status", "status", record?.status || "active", "select", record?.assessment_key ? [["active", "Active"]] : [["active", "Active"], ["inactive", "Inactive"]]); name.focus(); }
    }
    async function suitabilityEditor(id) {
        open("Product Suitability", "Loading product settings…"); el("saSubmit").disabled = true;
        try {
            const r = await get("suitability", id); if (!r) return;
            el("saModalSubtitle").textContent = r.product_name; current = { action: "suitability.save", id, revision: Number(r.revision) };
            for (const [key, title] of [["product_skin_types", "Suitable Skin Types"], ["product_concerns", "Skin Concerns"], ["product_ingredients", "Ingredients"]]) {
                const group = node("fieldset", undefined, "sa-associations"); group.append(node("legend", title));
                const search = node("input"); search.type = "search"; search.className = "form-control"; search.placeholder = `Search ${title.toLowerCase()}`; search.setAttribute("aria-label", search.placeholder); group.append(search);
                const list = node("div", undefined, "sa-check-list");
                for (const option of c.options[key]) { const row = node("label", undefined, "sa-check"), input = node("input"); input.type = "checkbox"; input.name = key; input.value = option.id; input.checked = r[key].includes(Number(option.id)); input.disabled = option.status !== "active" && !input.checked; row.append(input, node("span", option.label + (option.status === "active" ? "" : " (inactive)"))); list.append(row); }
                const empty = node("p", "No options found.", "sa-help"); empty.hidden = !!list.children.length;
                search.addEventListener("input", () => { [...list.children].forEach(row => { row.hidden = !row.textContent.toLocaleLowerCase().includes(search.value.toLocaleLowerCase().trim()); }); empty.hidden = [...list.children].some(row => !row.hidden); }); group.append(list, empty); fields.append(group);
            }
            checkbox("All ingredients on the product label are recorded above", "ingredients_complete", !!Number(r.ingredients_complete));
            fields.append(node("p", "Incomplete ingredient lists remain unverified and are excluded when customers select ingredients to avoid.", "sa-help"));
            const role = control("Routine Role", "routine_role", r.routine_role, "select", [["none", "Not assigned"], ["cleanser", "Cleanser"], ["moisturizer", "Moisturizer"], ["sunscreen", "Sunscreen"], ["treatment", "Targeted treatment"]]);
            control("Usage Period", "usage_period", r.usage_period, "select", [["both", "Morning and evening"], ["am", "Morning"], ["pm", "Evening"]]);
            control("Product Label Directions", "routine_instructions", r.routine_instructions, "textarea").maxLength = 2000;
            checkbox("Catalog guidance supports a gentle routine", "gentle", !!Number(r.gentle));
            const targeted = checkbox("Usage guidance reviewed for an optional targeted routine product", "targeted_approved", !!Number(r.targeted_approved));
            const sunscreen = checkbox("Label confirms broad-spectrum SPF 30+ protection", "sunscreen_verified", !!Number(r.sunscreen_verified));
            function updateRole() { targeted.parentElement.hidden = role.value !== "treatment"; sunscreen.parentElement.hidden = role.value !== "sunscreen"; if (role.value !== "treatment") targeted.checked = false; if (role.value !== "sunscreen") sunscreen.checked = false; }
            role.addEventListener("change", updateRole); updateRole(); el("saSubmit").disabled = false;
        } catch (e) { if (e.name !== "AbortError") error(e.message || "Unable to load this product."); }
    }
    function tagEditor(id, deleting = false) {
        const record = id ? c.tags.find(r => Number(r.id) === id) : null; if (id && !record) return;
        open(deleting ? "Delete Suitability" : id ? "Edit Suitability" : "Add Suitability");
        current = { action: deleting ? "tag.delete" : "tag.save", id };
        if (deleting) { fields.append(node("p", `Delete ${record.name}? Suitability assigned to products cannot be deleted until those assignments are removed.`)); el("saSubmit").textContent = "Delete Suitability"; }
        else {
            const name = control("Name", "name", record?.name || ""); name.maxLength = 100; name.required = true;
            control("Description", "description", record?.description || "", "textarea").maxLength = 5000;
            control("Status", "status", record?.status || "active", "select", [["active", "Active"], ["inactive", "Inactive"]]); name.focus();
        }
    }
    function questionEditor(id, deleting = false) {
        const r = id ? c.questionBank.find(q => Number(q.id) === id) : null;
        if (id && !r) return;
        open(deleting ? "Delete observation question" : id ? "Edit assessment question" : "Add observation question", "Custom questions are not clinically validated. Changes affect new assessments; saved answers keep their original wording.");
        current = { action: deleting ? "question.delete" : "question.save", id, revision: Number(r?.revision || 0) };
        if (deleting) { fields.append(node("p", `Delete ${r.prompt}? Existing assessment snapshots are retained.`)); el("saSubmit").textContent = "Delete question"; return; }
        control("Question", "prompt", r?.prompt || "", "textarea").maxLength = 1000;
        const options = r ? JSON.parse(r.options_json) : ["Yes", "No", "Not sure"];
        fields.append(node("p", r?.is_core ? "Preserve each answer's meaning and order. Scoring rules remain fixed." : "Keep answer meanings: A = symptom present, B = absent, C = unsure. Concern suggestions require customer confirmation.", "sa-help"));
        options.forEach((o, i) => { control(`Answer ${String.fromCharCode(65 + i)}`, `option_${i}`, o).maxLength = 300; });
        control("Supplied source", "source_key", r?.source_key || "baumann", "select", Object.entries(c.sources).map(([k, v]) => [k, v.title]));
        control("Evidence note / adaptation limits", "rationale", r?.rationale || "Original symptom observation informed by the supplied reference; not a validated diagnostic question.", "textarea").maxLength = 2000;
        if (!r?.is_core) control("Concern to suggest when Yes (optional)", "concern_id", r?.concern_id || "", "select", [["", "No concern mapping"], ...c.concerns.map(v => [String(v.id), v.label])]);
        control("Status", "status", r?.status || "active", "select", r?.is_core ? [["active", "Active"]] : [["active", "Active"], ["inactive", "Inactive"]]);
        current.optionCount = options.length;
    }
    async function assessmentViewer(id) {
        open("Assessment Details", "Loading assessment…"); el("saSubmit").hidden = true;
        try {
            const r = await get("assessment", id); if (!r) return;
            const p = r.profile, detail = node("dl", undefined, "sa-detail-grid");
            for (const [title, value] of [["Date", r.created_at], ["Method", r.assessment_method], ["Result", r.skin_type_label || "Undetermined"], ["Clarity", r.clarity], ["Sensitivity", p.sensitivity.level === "unknown" ? "Insufficient information" : p.sensitivity.level], ["Usefulness", r.usefulness ? `${r.usefulness} / 5` : "Not rated"], ["Main concern", c.concerns.find(n => Number(n.id) === p.main_concern_id)?.label || "No specific concern"], ["Concerns", p.concerns.map(id => c.concerns.find(n => Number(n.id) === id)?.label || "Removed concern").join(", ") || "None"], ["Ingredients to avoid", p.avoided_ingredients.map(id => c.ingredients.find(n => Number(n.id) === id)?.label || "Removed ingredient").join(", ") || "None"], ["Routine", p.routine_preference]]) { const item = node("div"); item.append(node("dt", title), node("dd", value)); detail.append(item); }
            fields.append(detail);
            if (p.assessment) { fields.append(node("h3", "Skin-type scores", "sa-detail-title")); const scores = node("p", Object.entries(p.assessment.scores).map(([type, score]) => `${type}: ${score}`).join(" · "), "sa-help"); fields.append(scores); }
            fields.append(node("h3", "Questionnaire Answers", "sa-detail-title"));
            fields.append(node("p", `Questionnaire: ${p.questionnaire_version || "legacy (original wording unavailable)"}. Rules: ${p.rule_version || "legacy"}. Scores are not accuracy percentages.`, "sa-help"));
            const definitions = p.questionnaire_snapshot || c.questions;
            for (const [key, answer] of Object.entries(p.answers)) if (answer && definitions[key]) { const row = node("div", undefined, "sa-answer"); row.append(node("strong", definitions[key][0]), node("p", definitions[key][1][answer.charCodeAt(0) - 65] || "Not answered")); fields.append(row); }
        } catch (e) { if (e.name !== "AbortError") error(e.message || "Unable to load assessment."); }
    }
    document.addEventListener("click", event => { const target = event.target.closest("[data-sa-question],[data-sa-question-delete],[data-sa-type],[data-sa-delete],[data-sa-tag],[data-sa-tag-delete],[data-sa-suitability],[data-sa-assessment],[data-sa-close]"); if (!target || pending) return;
        if (target.hasAttribute("data-sa-close")) close();
        else if (target.hasAttribute("data-sa-question")) questionEditor(Number(target.dataset.saQuestion));
        else if (target.hasAttribute("data-sa-question-delete")) questionEditor(Number(target.dataset.saQuestionDelete), true);
        else if (target.hasAttribute("data-sa-type")) typeEditor(Number(target.dataset.saType));
        else if (target.hasAttribute("data-sa-delete")) typeEditor(Number(target.dataset.saDelete), true);
        else if (target.hasAttribute("data-sa-tag")) tagEditor(Number(target.dataset.saTag));
        else if (target.hasAttribute("data-sa-tag-delete")) tagEditor(Number(target.dataset.saTagDelete), true);
        else if (target.hasAttribute("data-sa-suitability")) suitabilityEditor(Number(target.dataset.saSuitability));
        else assessmentViewer(Number(target.dataset.saAssessment));
    });
    document.addEventListener("keydown", event => { if (!modal.classList.contains("is-open")) return; if (event.key === "Escape") { event.preventDefault(); close(); } if (event.key === "Tab") { const controls = [...modal.querySelectorAll('button:not(:disabled),input:not(:disabled),textarea,select,[tabindex="0"]')].filter(n => n.getClientRects().length); const first = controls[0], last = controls.at(-1); if (event.shiftKey && (document.activeElement === first || document.activeElement === modal.querySelector('.form-modal-dialog'))) { event.preventDefault(); last?.focus(); } else if (!event.shiftKey && document.activeElement === last) { event.preventDefault(); first?.focus(); } } });
    form.addEventListener("submit", async event => {
        event.preventDefault(); if (pending || !current || !form.reportValidity()) return; pending = true; el("saSubmit").disabled = true; el("saFormError").hidden = true;
        const data = new FormData(form), payload = { ...current, csrf_token: c.csrfToken };
        if (["type.save", "tag.save"].includes(current.action)) for (const key of ["name", "description", "status"]) payload[key] = data.get(key);
        if (current.action === "question.save") { for (const key of ["prompt", "source_key", "rationale", "status"]) payload[key] = data.get(key); payload.concern_id = data.get("concern_id") || ""; payload.options = Array.from({ length: current.optionCount }, (_, i) => data.get(`option_${i}`)); }
        if (current.action === "suitability.save") { for (const key of ["product_skin_types", "product_concerns", "product_ingredients"]) payload[key] = data.getAll(key); for (const key of ["ingredients_complete", "gentle", "targeted_approved", "sunscreen_verified"]) payload[key] = data.has(key); for (const key of ["routine_role", "usage_period", "routine_instructions"]) payload[key] = data.get(key); }
        try { const response = await fetch(`${c.baseUrl}/actions/admin/skin.php`, { method: "POST", credentials: "same-origin", headers: { "Content-Type": "application/json", "Accept": "application/json" }, body: JSON.stringify(payload) }); const result = await response.json(); if (!response.ok || !result.success) throw new Error(result.message || "Unable to save this record."); window.location.reload(); }
        catch (e) { error(e.message || "Unable to save this record."); pending = false; el("saSubmit").disabled = false; }
    });
    function filterTypes() { const query = el("saTypeSearch")?.value.trim().toLocaleLowerCase(), status = el("saTypeStatus")?.value; if (query === undefined) return; const rows = [...el("saTypeRows").querySelectorAll("tr[data-name]")]; rows.forEach(row => { row.hidden = !row.dataset.name.includes(query) || (!!status && row.dataset.status !== status); }); el("saTypeEmpty").hidden = rows.some(row => !row.hidden); }
    el("saTypeSearch")?.addEventListener("input", filterTypes); el("saTypeStatus")?.addEventListener("change", filterTypes);
    ["suTagStatus", "suCategory"].forEach(id => el(id)?.addEventListener("change", () => el("suTagFilters").requestSubmit()));
    el("suTagSearch")?.addEventListener("search", event => { if (!event.target.value) el("suTagFilters").requestSubmit(); });
});
