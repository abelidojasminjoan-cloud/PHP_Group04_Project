"use strict";
document.addEventListener("DOMContentLoaded", () => {
    const c = window.PUREVIA_SKIN_QUIZ;
    if (!c) return;
    const el = id => document.getElementById(id);
    const selected = name => document.querySelector(`input[name="${name}"]:checked`)?.value || "";
    const checked = name => [...document.querySelectorAll(`input[name="${name}"]:checked`)].map(i => i.value);
    const node = (tag, text, cls) => { const n = document.createElement(tag); if (text !== undefined) n.textContent = text; if (cls) n.className = cls; return n; };
    const label = (list, id) => list.find(r => String(r.id) === String(id))?.label || "Unavailable selection";
    const setRadio = (name, value) => document.querySelectorAll(`input[name="${name}"]`).forEach(i => { i.checked = i.value === String(value); });
    let screen = "awareness", busy = false, lastProfile = null, saved = false, assessmentId = null;
    const path = () => ["awareness", ...(selected("awareness") === "yes" ? ["manual"] : ["q2", "q3", "q4", "q5", "q6", "q7", "q8"]), "sensitivity", ...(c.observations?.length ? ["observations"] : []), "concerns", "ingredients", "routine"];
    function error(message) { el("sqError").textContent = message; el("sqError").hidden = false; el("sqError").scrollIntoView({ block: "center" }); }
    function clearError() { el("sqError").hidden = true; }
    function phase(value) { return value === "review" ? 5 : ({ sensitivity: 1, observations: 2, concerns: 2, ingredients: 3, routine: 4 }[value] || 0); }
    function progress(value) {
        el("sqProgress").hidden = value === "awareness";
        document.querySelectorAll("[data-phase]").forEach(n => {
            const p = Number(n.dataset.phase); n.classList.toggle("current", p === phase(value)); n.classList.toggle("complete", p < phase(value));
            if (p === phase(value)) n.setAttribute("aria-current", "step"); else n.removeAttribute("aria-current");
        });
    }
    function show(value, focus = true) {
        screen = value; clearError(); el("sqForm").hidden = false; el("sqReview").hidden = true; el("sqForm").dataset.screen = value;
        document.querySelectorAll("[data-screen]").forEach(n => { if (n.tagName === "SECTION") n.hidden = n.dataset.screen !== value; });
        el("sqNext").textContent = value === "routine" ? "Preview My Skin Profile" : "Continue  →"; progress(value);
        if (focus) { const heading = document.querySelector(`section[data-screen="${value}"] h2, section[data-screen="${value}"] legend`); heading.tabIndex = -1; heading.focus({ preventScroll: true }); el("sqProgress").hidden ? el("sqForm").scrollIntoView({ block: "start" }) : el("sqProgress").scrollIntoView({ block: "start" }); }
    }
    function concerns() {
        const ids = checked("concerns"), old = el("sqMainConcern").value;
        el("sqMainConcern").replaceChildren(new Option("Select your main concern", ""), ...ids.map(id => new Option(label(c.choices.concerns, id), id)));
        if (ids.includes(old)) el("sqMainConcern").value = old; else if (ids.length === 1) el("sqMainConcern").value = ids[0];
        el("sqMainConcernField").hidden = !ids.length; el("sqConcernCount").textContent = `${ids.length} ${ids.length === 1 ? "concern" : "concerns"} selected`;
        const suggestions = [...new Set((c.observations || []).filter(q => q.concern_id && selected(q.question_key) === "A").map(q => label(c.choices.concerns, q.concern_id)))];
        el("sqConcernSuggestions").hidden = !suggestions.length;
        el("sqConcernSuggestions").textContent = `Your observations may relate to: ${suggestions.join(", ")}. Select only concerns that fit your experience. This is not a diagnosis.`;
    }
    function validate() {
        let message = "";
        if (screen === "awareness" && !selected("awareness")) message = "Choose whether you already know your skin type.";
        if (screen === "manual" && !selected("skin_type")) message = "Select your skin type, or use the guided finder.";
        if (/^q[2-8]$/.test(screen) && screen !== "q8" && !selected(screen)) message = "Choose an answer. You may select Not sure.";
        if (screen === "q8" && Array.from({ length: 7 }, (_, i) => selected(`q${i + 2}`)).filter(v => v && v !== "E").length < 5) message = "Answer at least five skin-type questions with an observation. Go back to update your answers, or choose your skin type manually.";
        if (screen === "sensitivity" && [9, 10, 11].some(q => !selected(`q${q}`))) message = "Answer all three sensitivity questions. You can select Not sure.";
        if (screen === "concerns" && !checked("concerns").length && !el("sqNoConcerns").checked) message = "Select your concerns or No specific concern.";
        if (screen === "concerns" && checked("concerns").length && !el("sqMainConcern").value) message = "Choose your main concern from the selected concerns.";
        if (screen === "ingredients" && (!selected("ingredient_preference") || (selected("ingredient_preference") === "selected" && !checked("avoided_ingredients").length))) message = "Choose your ingredient preference and any ingredients to avoid.";
        if (screen === "routine" && !selected("routine_preference")) message = "Choose a routine preference.";
        if (message) { error(message); return false; } return true;
    }
    function payload() {
        const answers = {}; for (const key of [...Array.from({ length: 10 }, (_, i) => `q${i + 2}`), "f1", "f2"]) answers[key] = selected(key);
        for (const q of c.observations || []) answers[q.question_key] = selected(q.question_key) || "C";
        return { assessment_method: selected("awareness") === "yes" ? "manual" : "guided", skin_type: selected("skin_type"), answers,
            concerns: checked("concerns"), main_concern_id: el("sqMainConcern").value, ingredient_preference: selected("ingredient_preference"), avoided_ingredients: checked("avoided_ingredients"), avoidance_reason: el("sqAvoidanceReason").value, routine_preference: selected("routine_preference") };
    }
    function hydrate(p, editing = false) {
        setRadio("awareness", editing && p.skin_type ? "yes" : p.assessment_method === "manual" ? "yes" : "no"); setRadio("skin_type", p.skin_type || "");
        for (const key of [...Array.from({ length: 10 }, (_, i) => `q${i + 2}`), "f1", "f2"]) setRadio(key, p.answers[key] || "");
        for (const q of c.observations || []) setRadio(q.question_key, p.answers[q.question_key] || "C");
        for (const key of ["concerns", "avoided_ingredients"]) document.querySelectorAll(`input[name="${key}"]`).forEach(i => { i.checked = p[key].map(String).includes(i.value); });
        el("sqNoConcerns").checked = !p.concerns.length; concerns(); el("sqMainConcern").value = p.main_concern_id || "";
        setRadio("ingredient_preference", p.ingredient_preference); el("sqAvoided").hidden = p.ingredient_preference !== "selected"; el("sqAvoidanceReason").value = p.avoidance_reason; setRadio("routine_preference", p.routine_preference);
    }
    function review(result, focus = true) {
        const p = result.profile; lastProfile = p; saved = !!result.saved; assessmentId = result.assessment_id || null;
        el("sqForm").hidden = true; el("sqReview").hidden = false; progress("review");
        el("sqReviewLabel").textContent = saved ? "Your saved skin profile" : "Review your skin profile";
        el("sqResultTitle").textContent = p.skin_type_label ? `${p.skin_type_label} Skin` : "Skin type not determined";
        const skinTypeDescriptions = {
            normal: "Normal skin generally feels balanced and comfortable, without excessive oiliness or dryness.",
            dry: "Dry skin may feel tight, rough, or flaky and can lack moisture, especially after cleansing.",
            oily: "Oily skin produces more oil and may look shiny across much of the face, especially as the day goes on.",
            combination: "Combination skin has both oily and dry or normal areas. The forehead, nose, and chin may feel oilier, while the cheeks may feel normal or dry."
        };
        const skinTypeDescription = skinTypeDescriptions[p.skin_type] || "";
        el("sqSkinTypeDescription").textContent = skinTypeDescription;
        el("sqSkinTypeDescription").hidden = !skinTypeDescription;
        el("sqResultDescription").textContent = p.assessment_method === "manual" ? "Self-reported skin type. Your selection skips the guided skin-type questions." : p.skin_type ? "Suggested from your questionnaire answers. You can confirm or manually change this result." : p.assessment?.needs_followup ? "Your answers overlap. Please try the clarification questions below." : "We couldn't confidently suggest a single skin type. You can update your answers, choose manually, or continue without skin-type filtering.";
        const summaries = [["Sensitivity", p.sensitivity.level === "unknown" ? "Insufficient information" : `${p.sensitivity.level[0].toUpperCase()}${p.sensitivity.level.slice(1)} reported sensitivity`], ["Main concern", p.main_concern_id ? label(c.choices.concerns, p.main_concern_id) : "No specific concern"], ["Other concerns", p.concerns.filter(id => id !== p.main_concern_id).map(id => label(c.choices.concerns, id)).join(", ") || "None selected"], ["Ingredients to avoid", p.avoided_ingredients.map(id => label(c.choices.ingredients, id)).join(", ") || (p.ingredient_preference === "unsure" ? "Not sure" : "None selected")], ["Routine preference", { basic: "Basic", targeted: "Targeted", unsure: "Simple routine" }[p.routine_preference]]];
        el("sqSummary").replaceChildren(...summaries.map(([key, value]) => { const d = node("div"); d.append(node("dt", key), node("dd", value)); return d; }));
        el("sqSensitivityNotice").hidden = !p.sensitivity.frequent_reaction && p.sensitivity.level !== "high";
        el("sqClarification").hidden = saved || !p.assessment?.needs_followup; el("sqManualOverride").hidden = saved || p.assessment_method !== "guided";
        el("sqSave").hidden = !c.canSave || !!p.assessment?.needs_followup || p.assessment?.reason === "insufficient"; el("sqSave").disabled = saved;
        el("sqSave").textContent = saved ? "Profile saved" : p.skin_type ? "Save Skin Profile" : "Save without skin-type filtering";
        el("sqSaveStatus").textContent = saved ? "Saved. You can update your preferences anytime in My Account → Skin Profile." : "";
        el("sqRating").hidden = !assessmentId;
        const matches = result.matches;
        el("sqResultEvidence").textContent = p.assessment?.explanation || (p.questionnaire_version === "legacy" ? "Saved using an earlier questionnaire. Retake the assessment for the current questions." : "Skin type is self-reported. Sensitivity and symptom suggestions are observations, not diagnoses.");
        const guidance = (matches.guidance || []).map(g => { const card = node("article"); card.append(node("h3", g.title), node("p", g.text), node("small", `Source: ${g.sources.map(key => c.sources[key]?.title || key).join("; ")} (see sources below)`)); return card; });
        for (const observation of p.observed_concerns || []) { const card = node("article"); card.append(node("h3", `Possible concern: ${label(c.choices.concerns, observation.concern_id)}`), node("p", `Based on your Yes answer to: ${observation.question}. This is a symptom-based suggestion; only your confirmed concerns affect product matching.`)); guidance.push(card); }
        el("sqGuidance").replaceChildren(...guidance);
        el("sqMatchNotice").textContent = p.avoided_ingredients.length ? "Ranked using catalog skin-type and concern information. Products with selected ingredients or unverified ingredient lists are excluded. Compatibility is not guaranteed." : "Ranked using catalog skin-type and concern information. Check full product labels; ingredient information may be incomplete. Compatibility is not guaranteed.";
        el("sqProducts").replaceChildren(...matches.products.map(product => {
            const article = node("article", undefined, "sq-product"), imageLink = node("a"); imageLink.href = `${c.baseUrl}/public/product.php?id=${product.id}`;
            const image = node("img"); image.src = product.image; image.alt = product.name; image.loading = "lazy"; image.addEventListener("error", () => { image.src = `${c.baseUrl}/assets/images/products/default-product.svg`; }, { once: true }); imageLink.append(image);
            const body = node("div"), h = node("h3"), title = node("a", product.name); title.href = imageLink.href; h.append(title);
            body.append(node("p", product.category, "sq-kicker"), h, node("p", product.reasons.join(" · ") || "Available catalog product", "sq-help"), node("p", product.ingredient_status === "unverified" ? "Ingredient information unverified" : "Catalog ingredients screened — check the full label", "sq-help"), node("strong", new Intl.NumberFormat("en-PH", { style: "currency", currency: "PHP" }).format(product.price), "sq-price")); article.append(imageLink, body); return article;
        }));
        if (!matches.products.length) el("sqProducts").append(node("p", "No matching product available. You can edit your preferences or browse the catalog.", "sq-empty"));
        const item = entry => { const li = node("li"); li.append(node("strong", entry.step)); if (entry.product) { const link = node("a", entry.product.name); link.href = `${c.baseUrl}/public/product.php?id=${entry.product.id}`; li.append(link); } else li.append(node("span", "No matching product available.")); return li; };
        el("sqRoutine").replaceChildren(...["am", "pm"].map(period => { const card = node("div", undefined, "sq-routine-card"); card.append(node("h3", period === "am" ? "Morning routine" : "Evening routine")); const list = node("ol"); list.append(...matches.routine[period].map(item)); card.append(list); return card; }));
        el("sqTargeted").replaceChildren();
        if (matches.routine.targeted) { const card = node("div", undefined, "sq-routine-card"); card.append(node("h3", "Optional targeted product")); const link = node("a", matches.routine.targeted.name); link.href = `${c.baseUrl}/public/product.php?id=${matches.routine.targeted.id}`; card.append(link, node("p", matches.routine.targeted.instructions, "sq-help")); el("sqTargeted").append(card); }
        if (focus) { el("sqResultTitle").focus({ preventScroll: true }); el("sqProgress").scrollIntoView({ block: "start" }); }
    }
    async function api(action, data) {
        if (["preview", "save"].includes(action)) data = Object.fromEntries(["assessment_method", "skin_type", "answers", "concerns", "main_concern_id", "ingredient_preference", "avoided_ingredients", "avoidance_reason", "routine_preference"].map(key => [key, data[key]]));
        const response = await fetch(`${c.baseUrl}/actions/skin/profile.php`, { method: "POST", credentials: "same-origin", headers: { "Content-Type": "application/json", "Accept": "application/json" }, body: JSON.stringify({ ...data, action, csrf_token: c.csrfToken, assessment_token: c.assessmentToken }) });
        const result = await response.json(); if (!response.ok || !result.success) throw new Error(result.message || "Unable to process your profile."); return result;
    }
    async function submit(action, data) {
        if (busy) return; busy = true; clearError();
        const buttons = [...document.querySelectorAll("#sqForm button, .sq-review-actions button, #sqRecalculate")]; buttons.forEach(b => { b.disabled = true; });
        el("sqReview").setAttribute("aria-busy", "true");
        try { const result = await api(action, data); hydrate(result.profile); review(result, action !== "save"); }
        catch (e) { error(e.message || "Unable to process your profile. Please try again."); }
        finally { busy = false; buttons.forEach(b => { b.disabled = false; }); el("sqSave").disabled = saved; el("sqReview").removeAttribute("aria-busy"); }
    }
    el("sqForm").addEventListener("submit", event => { event.preventDefault(); if (busy || !validate()) return; if (screen === "routine") submit("preview", payload()); else show(path()[path().indexOf(screen) + 1]); });
    el("sqBack").addEventListener("click", () => { if (busy) return; if (screen === "awareness") window.location.assign(`${c.baseUrl}/public/shop.php`); else show(path()[path().indexOf(screen) - 1]); });
    el("sqSensitiveOnly").addEventListener("click", () => { setRadio("awareness", "no"); show("q2"); });
    el("sqForm").addEventListener("change", event => {
        clearError(); if (/^q[2-8]$/.test(event.target.name)) ["f1", "f2"].forEach(k => setRadio(k, ""));
        if (event.target.name === "concerns") { el("sqNoConcerns").checked = false; concerns(); }
        if (event.target.id === "sqNoConcerns") { if (event.target.checked) document.querySelectorAll('[name="concerns"]').forEach(i => { i.checked = false; }); concerns(); }
        if (event.target.name === "ingredient_preference") el("sqAvoided").hidden = selected("ingredient_preference") !== "selected";
        if (event.target.name?.startsWith("obs_")) concerns();
    });
    el("sqIngredientSearch").addEventListener("input", event => { const query = event.target.value.trim().toLocaleLowerCase(), rows = [...el("sqIngredientList").children]; rows.forEach(r => { r.hidden = !r.textContent.toLocaleLowerCase().includes(query); }); el("sqIngredientEmpty").hidden = rows.some(r => !r.hidden); });
    el("sqRecalculate").addEventListener("click", () => { if (!selected("f1") || !selected("f2")) return error("Answer both clarification questions, or choose your skin type manually."); submit("preview", { ...lastProfile, answers: { ...lastProfile.answers, f1: selected("f1"), f2: selected("f2") } }); });
    el("sqSave").addEventListener("click", () => submit("save", lastProfile));
    el("sqManualOverride").addEventListener("click", () => { if (!busy) { setRadio("awareness", "yes"); setRadio("skin_type", ""); show("manual"); } });
    el("sqEdit").addEventListener("click", () => { if (busy) return; if (saved) window.location.assign(`${c.baseUrl}/account/skin-profile.php?edit=1`); else { hydrate(lastProfile); show(selected("awareness") === "yes" ? "manual" : "q2"); } });
    el("sqRating").addEventListener("click", async event => { const button = event.target.closest("[data-rating]"); if (!button || busy) return; busy = true; try { await api("rate", { assessment_id: assessmentId, rating: Number(button.dataset.rating) }); el("sqRating").querySelectorAll("button").forEach(b => b.setAttribute("aria-pressed", String(b === button))); el("sqRatingStatus").textContent = "Thank you for your feedback."; } catch (e) { error(e.message); } finally { busy = false; } });
    if (c.saved) { hydrate(c.saved.profile, c.edit); if (c.edit) show(c.saved.profile.skin_type ? "manual" : "awareness", false); else review(c.saved, false); }
    else if (c.guided) { setRadio("awareness", "no"); show("q2", false); } else show("awareness", false);
});
