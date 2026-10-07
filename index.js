import { createClient } from 'https://esm.sh/@supabase/supabase-js@2';

const config = window.APP_CONFIG || {};
const configured = Boolean(config.supabaseUrl && config.supabaseAnonKey);
const supabase = configured ? createClient(config.supabaseUrl, config.supabaseAnonKey) : null;
const $ = id => document.getElementById(id);
let currentUser, currentProfile, modules = [], activeModule, questions = [], answers = [], attemptId, currentQuestion = 0;
let activityMode = 'evaluation', evaluationResults = null, practiceResults = new Map(), scores = [], users = [], courses = [], managedModules = [];

function escapeHtml(value) { return String(value ?? '').replace(/[&<>"']/g, char => ({ '&': '&amp;', '<': '&lt;', '>': '&gt;', '"': '&quot;', "'": '&#39;' })[char]); }
function showAuthMessage(message, isError = true) { const el = $('auth-message'); el.textContent = message; el.classList.toggle('error', isError); el.classList.toggle('success', !isError); }
function showToast(message) { const el = $('toast'); el.textContent = message; el.classList.add('visible'); clearTimeout(showToast.timer); showToast.timer = setTimeout(() => el.classList.remove('visible'), 4000); }
function roleLabel(role) { return ({ student: 'Estudiante', teacher: 'Profesor', content_manager: 'Gestor de contenidos', admin: 'Administrador' })[role] || role; }
function setSignedIn(value) { document.body.classList.toggle('signed-in', value); }

function setView(view) {
  document.querySelectorAll('.view').forEach(section => section.classList.toggle('active', section.id === view));
  document.querySelectorAll('.nav-link').forEach(button => button.classList.toggle('active', button.dataset.view === view));
  window.scrollTo({ top: 0, behavior: 'smooth' });
  if (view === 'contenido-lista') renderModuleCards('content-modules-grid');
  if (view === 'progreso') void loadProgress();
  if (view === 'recursos') void loadResources();
  if (view === 'profesor') void loadScores();
  if (view === 'gestion') void loadContentManagement();
  if (view === 'administracion') void loadAdministration();
}

async function signUp(event) {
  event.preventDefault();
  if (!configured) return showAuthMessage('Configura el proyecto Supabase en supabase-config.js.');
  const form = event.currentTarget, fullName = form.elements.fullName.value.trim();
  if (fullName.length < 3) return showAuthMessage('Escribe tu nombre completo (mínimo 3 caracteres).');
  if (!form.elements.privacyConsent.checked) return showAuthMessage('Debes aceptar la política de tratamiento de datos.');
  const { data, error } = await supabase.auth.signUp({
    email: form.elements.email.value.trim(), password: form.elements.password.value,
    options: { data: { full_name: fullName, accepted_privacy_policy: true, privacy_policy_version: '2026-10-05' } },
  });
  if (error) return showAuthMessage(error.message);
  form.reset();
  if (!data.session) return showAuthMessage('Cuenta creada. Confirma tu correo y luego inicia sesión.', false);
  await handleSession(data.session);
}

async function signIn(event) {
  event.preventDefault();
  if (!configured) return showAuthMessage('Configura el proyecto Supabase en supabase-config.js.');
  const form = event.currentTarget;
  const { data, error } = await supabase.auth.signInWithPassword({ email: form.elements.email.value.trim(), password: form.elements.password.value });
  if (error) return showAuthMessage(error.message);
  form.reset(); await handleSession(data.session);
}

async function handleSession(session) {
  currentUser = session?.user || null;
  if (!currentUser) { currentProfile = null; setSignedIn(false); return; }
  const { data: profile, error } = await supabase.from('profiles').select('id, full_name, email, role, active').eq('id', currentUser.id).single();
  if (error) { setSignedIn(false); return showAuthMessage(`No se pudo cargar el perfil: ${error.message}`); }
  if (!profile.active) { await supabase.auth.signOut(); return showAuthMessage('Esta cuenta está desactivada. Contacta al administrador.'); }
  currentProfile = profile;
  $('profile-name').textContent = profile.full_name; $('profile-role').textContent = roleLabel(profile.role);
  $('profile-avatar').textContent = profile.full_name.split(/\s+/).slice(0, 2).map(part => part[0]).join('').toUpperCase();
  $('progress-nav').hidden = profile.role !== 'student';
  $('teacher-nav').hidden = !['teacher', 'admin'].includes(profile.role);
  $('content-nav').hidden = !['content_manager', 'admin'].includes(profile.role);
  $('admin-nav').hidden = profile.role !== 'admin'; setSignedIn(true); await loadModules();
}

async function loadModules() {
  const { data, error } = await supabase.from('modules').select('id, title, description, theory, correct_example, incorrect_example, example_explanation, position, published').eq('published', true).order('position');
  if (error) return showToast(`No se pudieron cargar los módulos: ${error.message}`);
  modules = data || []; renderModuleCards('modules-grid');
  if (!modules.length) showToast('Aún no hay módulos publicados o asignados a tu curso.');
}

function renderModuleCards(containerId) {
  const container = $(containerId); if (!container) return;
  if (!modules.length) { container.innerHTML = '<p class="empty-state">No hay módulos disponibles todavía.</p>'; return; }
  container.innerHTML = modules.map((module, index) => `<article class="module-card"><div class="icon ${['blue','orange','purple','green','blue'][index % 5]}">${['✦','❝','¶','☷','↗'][index % 5]}</div><span class="tag">MÓDULO ${index + 1}</span><h3>${escapeHtml(module.title)}</h3><p>${escapeHtml(module.description || 'Estudia el contenido, practica y presenta la evaluación.')}</p><div class="module-actions"><button class="text-button" data-theory="${module.id}">Estudiar contenido</button><button class="secondary" data-practice="${module.id}">Practicar</button><button class="primary" data-evaluate="${module.id}">Evaluación</button></div></article>`).join('');
  container.querySelectorAll('[data-theory]').forEach(button => button.addEventListener('click', () => openTheory(button.dataset.theory)));
  container.querySelectorAll('[data-practice]').forEach(button => button.addEventListener('click', () => void startActivity(button.dataset.practice, 'practice')));
  container.querySelectorAll('[data-evaluate]').forEach(button => button.addEventListener('click', () => void startActivity(button.dataset.evaluate, 'evaluation')));
}

function openTheory(moduleId) {
  activeModule = modules.find(module => module.id === moduleId); if (!activeModule) return;
  $('theory-title').textContent = activeModule.title; $('theory-description').textContent = activeModule.description || '';
  $('theory-body').innerHTML = (activeModule.theory || 'El contenido de este módulo se está preparando.').split(/\n+/).filter(Boolean).map(paragraph => `<p>${escapeHtml(paragraph)}</p>`).join('');
  const examples = Boolean(activeModule.correct_example || activeModule.incorrect_example || activeModule.example_explanation);
  $('theory-examples').hidden = !examples; $('correct-example').textContent = activeModule.correct_example || 'Ejemplo pendiente.'; $('incorrect-example').textContent = activeModule.incorrect_example || 'Ejemplo pendiente.'; $('example-explanation').textContent = activeModule.example_explanation || '';
  setView('contenido');
}

async function startActivity(moduleId, mode) {
  activeModule = modules.find(module => module.id === moduleId); if (!activeModule) return showToast('No se encontró el módulo.');
  const allowed = mode === 'practice' ? ['practice', 'both'] : ['evaluation', 'both'];
  const { data: rows, error } = await supabase.from('questions').select('id, prompt, error_type, difficulty, activity, position').eq('module_id', moduleId).in('activity', allowed).order('position');
  if (error) return showToast(error.message); if (!rows?.length) return showToast('Este módulo todavía no tiene preguntas para esta actividad.');
  const { data: optionRows, error: optionError } = await supabase.from('question_options').select('id, question_id, option_text, position').in('question_id', rows.map(question => question.id)).order('position');
  if (optionError) return showToast(optionError.message);
  const map = new Map(rows.map(question => [question.id, []])); for (const option of optionRows || []) map.get(option.question_id)?.push(option);
  questions = rows.map(question => ({ ...question, options: map.get(question.id) || [] }));
  if (questions.some(question => question.options.length < 2)) return showToast('Hay ejercicios sin suficientes alternativas.');
  answers = Array(questions.length).fill(null); evaluationResults = null; practiceResults = new Map(); attemptId = null; currentQuestion = 0; activityMode = mode; $('evaluation-result').hidden = true;
  if (mode === 'evaluation') {
    const { data, error: attemptError } = await supabase.rpc('start_attempt', { target_module: moduleId });
    if (attemptError) return showToast(attemptError.message); attemptId = Array.isArray(data) ? data[0]?.id : data?.id;
    if (!attemptId) return showToast('No se pudo iniciar el intento de evaluación.');
  }
  $('activity-title').textContent = activeModule.title; $('activity-mode').textContent = mode === 'practice' ? 'PRÁCTICA GUIADA' : 'EVALUACIÓN'; renderQuestion(); setView('evaluacion');
}

function renderQuestion() {
  const question = questions[currentQuestion]; if (!question) return;
  const selectedId = answers[currentQuestion], result = activityMode === 'practice' ? practiceResults.get(question.id) : evaluationResults?.find(row => row.question_id === question.id), reviewed = Boolean(result);
  $('question-counter').textContent = `Pregunta ${currentQuestion + 1} de ${questions.length}`; $('assessment-bar').style.width = `${((currentQuestion + 1) / questions.length) * 100}%`;
  $('question-card').innerHTML = `<p class="tag">${escapeHtml(question.error_type)} · Nivel ${question.difficulty}</p><h3>${escapeHtml(question.prompt)}</h3><div class="answers">${question.options.map((option, index) => `<button class="answer ${option.id === selectedId ? 'selected' : ''} ${reviewed && option.id === selectedId ? (result.is_correct ? 'correct' : 'incorrect') : ''}" data-option="${option.id}" ${reviewed ? 'disabled' : ''}><b>${String.fromCharCode(65 + index)}.</b> ${escapeHtml(option.option_text)}</button>`).join('')}</div>${reviewed ? `<p class="feedback">${result.is_correct ? '✓ Respuesta correcta. ' : '↳ Revisa este concepto. '}${escapeHtml(result.explanation || question.explanation)}</p>` : ''}`;
  if (!reviewed) $('question-card').querySelectorAll('[data-option]').forEach(button => button.addEventListener('click', () => void selectAnswer(button.dataset.option)));
  $('previous-question').disabled = currentQuestion === 0; $('next-question').textContent = reviewed || evaluationResults ? (currentQuestion === questions.length - 1 ? 'Terminar revisión' : 'Siguiente →') : (currentQuestion === questions.length - 1 ? 'Finalizar evaluación' : 'Siguiente →');
}

async function selectAnswer(optionId) {
  answers[currentQuestion] = optionId; if (activityMode !== 'practice') return renderQuestion();
  const question = questions[currentQuestion], { data, error } = await supabase.rpc('check_practice_answer', { target_question: question.id, selected_option: optionId });
  if (error) { answers[currentQuestion] = null; return showToast(error.message); }
  practiceResults.set(question.id, Array.isArray(data) ? data[0] : data); renderQuestion();
}

async function submitEvaluation() {
  if (answers.some(answer => !answer)) return showToast('Selecciona una respuesta para cada pregunta.');
  const sent = questions.map((question, index) => ({ question_id: question.id, option_id: answers[index] })), { data: score, error } = await supabase.rpc('submit_attempt', { target_attempt: attemptId, submitted_answers: sent });
  if (error) return showToast(error.message);
  const { data: rows, error: reviewError } = await supabase.rpc('review_attempt', { target_attempt: attemptId });
  if (reviewError) return showToast(`Puntaje guardado (${score}%), pero no se pudo mostrar la revisión: ${reviewError.message}`);
  evaluationResults = rows; $('evaluation-result').textContent = `Evaluación finalizada: ${score}% de aciertos. Revisa tus respuestas y la retroalimentación.`; $('evaluation-result').hidden = false; renderQuestion();
}

async function loadProgress() {
  if (currentProfile?.role !== 'student') return;
  const [summaryResult, historyResult] = await Promise.all([supabase.rpc('student_progress'), supabase.from('attempts').select('score, submitted_at, modules(title)').eq('student_id', currentUser.id).eq('status', 'submitted').order('submitted_at', { ascending: false }).limit(50)]);
  if (summaryResult.error || historyResult.error) return showToast(summaryResult.error?.message || historyResult.error?.message);
  const row = (Array.isArray(summaryResult.data) ? summaryResult.data[0] : summaryResult.data) || {};
  $('progress-evaluations').textContent = row.evaluations_completed || 0; $('progress-average').textContent = row.average_score == null ? '—' : `${Math.round(row.average_score)}%`; $('progress-practice').textContent = `${row.practice_correct || 0} / ${row.practice_answered || 0}`;
  $('progress-history').innerHTML = (historyResult.data || []).map(item => `<tr><td>${escapeHtml(item.modules?.title || 'Evaluación')}</td><td class="score">${item.score}%</td><td>${new Date(item.submitted_at).toLocaleDateString('es-CO')}</td></tr>`).join('') || '<tr><td colspan="3">Aún no has finalizado evaluaciones.</td></tr>';
}

async function loadResources() {
  const { data, error } = await supabase.from('learning_resources').select('title, description, url, category').eq('published', true).order('position');
  if (error) return showToast(error.message);
  $('resources-grid').innerHTML = (data || []).map(resource => `<article class="module-card"><span class="tag">${escapeHtml(resource.category)}</span><h3>${escapeHtml(resource.title)}</h3><p>${escapeHtml(resource.description)}</p><a class="primary" href="${escapeHtml(resource.url)}" target="_blank" rel="noopener noreferrer">Abrir recurso ↗</a></article>`).join('') || '<p class="empty-state">No hay recursos publicados todavía.</p>';
}

async function loadScores(filter = '') {
  if (!['teacher', 'admin'].includes(currentProfile?.role)) return;
  const [results, errors] = await Promise.all([supabase.rpc('teacher_results'), supabase.rpc('teacher_error_summary')]);
  if (results.error || errors.error) return showToast(results.error?.message || errors.error?.message);
  scores = (results.data || []).map(row => ({ name: row.student_name, email: row.student_email, course: row.course_name, activity: row.module_title, score: row.score, submittedAt: row.submitted_at, date: new Date(row.submitted_at).toLocaleDateString('es-CO') })); renderScores(filter);
  $('error-summary-body').innerHTML = (errors.data || []).map(row => `<tr><td>${escapeHtml(row.module_title)}</td><td>${escapeHtml(row.error_type)}</td><td>${row.errors_total} de ${row.answers_total}</td><td class="score">${row.error_rate}%</td></tr>`).join('') || '<tr><td colspan="4">Aún no hay respuestas evaluadas.</td></tr>';
}
function renderScores(filter = '') { const query = filter.toLocaleLowerCase('es'), visible = scores.filter(row => `${row.name} ${row.email} ${row.course}`.toLocaleLowerCase('es').includes(query)); $('scores-body').innerHTML = visible.map(row => `<tr><td><b>${escapeHtml(row.name)}</b></td><td>${escapeHtml(row.email)}</td><td>${escapeHtml(row.course)}</td><td>${escapeHtml(row.activity)}</td><td class="score">${row.score}%</td><td>${row.date}</td></tr>`).join('') || '<tr><td colspan="6">No hay resultados para mostrar.</td></tr>'; $('student-count').textContent = new Set(scores.map(row => row.email)).size; $('completed-count').textContent = scores.length; $('average-score').textContent = scores.length ? `${Math.round(scores.reduce((sum, row) => sum + row.score, 0) / scores.length)}%` : '—'; }
async function downloadScores() {
  if (!scores.length) return showToast('No hay resultados para exportar todavía.');
  const button = $('download-scores');
  button.disabled = true;
  button.textContent = 'Generando Excel…';
  try {
    // Se carga solo al exportar para no ralentizar la aplicación durante el uso normal.
    const { default: ExcelJS } = await import('https://esm.sh/exceljs@4.4.0?bundle');
    const workbook = new ExcelJS.Workbook();
    workbook.creator = 'APA Interactivo';
    workbook.created = new Date();
    const sheet = workbook.addWorksheet('Resultados', { views: [{ state: 'frozen', ySplit: 3 }] });
    const blue = 'FF1769AA', navy = 'FF102A43', paleBlue = 'FFEAF4FB', border = { style: 'thin', color: { argb: 'FFD9E2EC' } };
    sheet.mergeCells('A1:F1');
    sheet.getCell('A1').value = 'Reporte de resultados — APA Interactivo';
    sheet.getCell('A1').font = { name: 'Aptos Display', size: 16, bold: true, color: { argb: 'FFFFFFFF' } };
    sheet.getCell('A1').fill = { type: 'pattern', pattern: 'solid', fgColor: { argb: navy } };
    sheet.getCell('A1').alignment = { horizontal: 'left', vertical: 'middle' };
    sheet.getRow(1).height = 28;
    sheet.mergeCells('A2:F2');
    sheet.getCell('A2').value = `Generado el ${new Date().toLocaleDateString('es-CO')}`;
    sheet.getCell('A2').font = { italic: true, color: { argb: 'FF52677C' } };
    sheet.getRow(2).height = 20;
    sheet.columns = [
      { key: 'name', width: 28 }, { key: 'email', width: 32 }, { key: 'course', width: 24 },
      { key: 'activity', width: 28 }, { key: 'score', width: 14 }, { key: 'date', width: 16 },
    ];
    const header = sheet.getRow(3);
    header.values = ['Estudiante', 'Correo', 'Curso', 'Actividad', 'Puntaje', 'Fecha'];
    header.eachCell(cell => {
      cell.font = { bold: true, color: { argb: 'FFFFFFFF' } };
      cell.fill = { type: 'pattern', pattern: 'solid', fgColor: { argb: blue } };
      cell.alignment = { horizontal: 'center', vertical: 'middle' };
      cell.border = { top: border, left: border, bottom: border, right: border };
    });
    header.height = 23;
    for (const result of scores) {
      const row = sheet.addRow({ name: result.name, email: result.email, course: result.course, activity: result.activity, score: result.score / 100, date: new Date(result.submittedAt) });
      row.eachCell(cell => { cell.border = { top: border, left: border, bottom: border, right: border }; cell.alignment = { vertical: 'middle' }; });
      if (row.number % 2 === 0) row.eachCell(cell => { cell.fill = { type: 'pattern', pattern: 'solid', fgColor: { argb: paleBlue } }; });
      row.getCell('E').numFmt = '0%'; row.getCell('E').alignment = { horizontal: 'center', vertical: 'middle' };
      row.getCell('F').numFmt = 'dd/mm/yyyy'; row.height = 20;
    }
    sheet.autoFilter = { from: 'A3', to: `F${scores.length + 3}` };
    const buffer = await workbook.xlsx.writeBuffer();
    const url = URL.createObjectURL(new Blob([buffer], { type: 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet' }));
    const link = document.createElement('a');
    link.href = url;
    link.download = `resultados-apa-interactivo-${new Date().toISOString().slice(0, 10)}.xlsx`;
    link.click();
    setTimeout(() => URL.revokeObjectURL(url), 1000);
  } catch (error) {
    showToast(`No se pudo generar el Excel: ${error.message}`);
  } finally {
    button.disabled = false;
    button.innerHTML = 'Descargar Excel (.xlsx) <span>↓</span>';
  }
}

function fillSelect(select, items, placeholder) { if (select) select.innerHTML = `<option value="">${escapeHtml(placeholder)}</option>` + items.map(item => `<option value="${item.id}">${escapeHtml(item.title || item.name || item.email)}</option>`).join(''); }
async function loadContentManagement() {
  if (!['content_manager', 'admin'].includes(currentProfile?.role)) return;
  const { data, error } = await supabase.from('modules').select('id, title, description, theory, correct_example, incorrect_example, example_explanation, position, published').order('position'); if (error) return showToast(error.message);
  managedModules = data || []; fillSelect($('manage-module'), managedModules, 'Elige un módulo'); fillSelect($('question-module'), managedModules, 'Elige un módulo');
  $('manage-module').onchange = () => { const module = managedModules.find(item => item.id === $('manage-module').value); if (!module) return; $('manage-title').value = module.title; $('manage-description').value = module.description || ''; $('manage-theory').value = module.theory || ''; $('manage-correct-example').value = module.correct_example || ''; $('manage-incorrect-example').value = module.incorrect_example || ''; $('manage-example-explanation').value = module.example_explanation || ''; $('manage-published').checked = module.published; $('question-module').value = module.id; void loadQuestionOptions(module.id); };
  $('question-module').onchange = () => void loadQuestionOptions($('question-module').value); if (managedModules.length) $('manage-module').dispatchEvent(new Event('change'));
}
function clearQuestionForm() { ['manage-question-id', 'question-prompt', 'question-explanation', 'option-a', 'option-b', 'option-c', 'question-error-type'].forEach(id => { $(id).value = ''; }); $('correct-option').value = '0'; $('question-difficulty').value = '1'; $('question-activity').value = 'both'; }
async function loadQuestionOptions(moduleId) { const dropdown = $('manage-question'); clearQuestionForm(); const { data, error } = await supabase.from('questions').select('id, prompt, position').eq('module_id', moduleId).order('position'); if (error) return showToast(error.message); dropdown.innerHTML = '<option value="">Nuevo ejercicio</option>' + (data || []).map(question => `<option value="${question.id}">${question.position}. ${escapeHtml(question.prompt)}</option>`).join(''); dropdown.onchange = () => void selectManagedQuestion(dropdown.value, moduleId); }
async function selectManagedQuestion(questionId, moduleId) { clearQuestionForm(); $('manage-question-id').value = questionId; if (!questionId) return; const [questionResult, optionsResult] = await Promise.all([supabase.from('questions').select('id, prompt, explanation, error_type, difficulty, activity').eq('id', questionId).single(), supabase.rpc('get_question_answer_key', { target_question: questionId })]); if (questionResult.error || optionsResult.error) return showToast(questionResult.error?.message || optionsResult.error?.message); const question = questionResult.data, options = optionsResult.data || []; $('question-module').value = moduleId; $('question-prompt').value = question.prompt; $('question-explanation').value = question.explanation; $('question-error-type').value = question.error_type; $('question-difficulty').value = question.difficulty; $('question-activity').value = question.activity; ['option-a', 'option-b', 'option-c'].forEach((id, index) => { $(id).value = options[index]?.option_text || ''; }); $('correct-option').value = String(Math.max(0, options.findIndex(option => option.is_correct))); }
async function saveManagedModule() { const moduleId = $('manage-module').value; if (!moduleId) return showToast('Selecciona un módulo.'); const { error } = await supabase.from('modules').update({ title: $('manage-title').value.trim(), description: $('manage-description').value.trim(), theory: $('manage-theory').value.trim(), correct_example: $('manage-correct-example').value.trim(), incorrect_example: $('manage-incorrect-example').value.trim(), example_explanation: $('manage-example-explanation').value.trim(), published: $('manage-published').checked }).eq('id', moduleId); if (error) return showToast(error.message); showToast('Módulo actualizado.'); await loadModules(); await loadContentManagement(); }
async function createManagedModule(event) { event.preventDefault(); const form = event.currentTarget, position = managedModules.reduce((max, module) => Math.max(max, module.position || 0), 0) + 1, { error } = await supabase.from('modules').insert({ title: form.elements.title.value.trim(), description: form.elements.description.value.trim(), theory: '', position, published: false, created_by: currentUser.id }); if (error) return showToast(error.message); form.reset(); showToast('Módulo creado como borrador.'); await loadModules(); await loadContentManagement(); }
async function deleteManagedModule() { const moduleId = $('manage-module').value; if (!moduleId) return showToast('Selecciona un módulo para eliminarlo.'); if (!window.confirm('Se eliminarán el módulo y sus ejercicios. ¿Deseas continuar?')) return; const { error } = await supabase.from('modules').delete().eq('id', moduleId); if (error) return showToast(error.message); showToast('Módulo eliminado.'); await loadModules(); await loadContentManagement(); }
async function saveManagedQuestion() { const moduleId = $('question-module').value, prompt = $('question-prompt').value.trim(), explanation = $('question-explanation').value.trim(), errorType = $('question-error-type').value.trim(), options = [$('option-a').value.trim(), $('option-b').value.trim(), $('option-c').value.trim()]; if (!moduleId || !prompt || !explanation || !errorType || options.some(text => !text)) return showToast('Completa todos los campos del ejercicio.'); const choices = options.map((option_text, index) => ({ option_text, position: index + 1, is_correct: index === Number($('correct-option').value) })); const { error } = await supabase.rpc('save_question_with_options', { target_question: $('manage-question-id').value || null, target_module: moduleId, question_prompt: prompt, question_explanation: explanation, target_error_type: errorType, target_difficulty: Number($('question-difficulty').value), target_activity: $('question-activity').value, choices }); if (error) return showToast(error.message); $('question-module').value = moduleId; await loadQuestionOptions(moduleId); showToast('Ejercicio guardado.'); }
async function deleteManagedQuestion() { const id = $('manage-question-id').value; if (!id) return showToast('Selecciona un ejercicio existente.'); const { error } = await supabase.from('questions').delete().eq('id', id); if (error) return showToast(error.message); await loadQuestionOptions($('question-module').value); showToast('Ejercicio eliminado.'); }

async function loadAdministration() {
  if (currentProfile?.role !== 'admin') return;
  const [usersResult, coursesResult, modulesResult, statsResult] = await Promise.all([
    supabase.from('profiles').select('id, full_name, email, role, active').order('full_name'),
    supabase.from('courses').select('id, name, code, term').order('name'),
    supabase.from('modules').select('id, title, position').order('position'),
    supabase.rpc('admin_overview_stats'),
  ]);
  const error = usersResult.error || coursesResult.error || modulesResult.error || statsResult.error;
  if (error) return showToast(`Error al cargar la administración: ${error.message}`);
  users = usersResult.data || []; courses = coursesResult.data || []; renderUsers();
  fillSelect($('assign-course'), courses, 'Elige un curso'); fillSelect($('assign-module'), modulesResult.data || [], 'Elige un módulo');
  fillSelect($('assign-teacher'), users.filter(user => user.role === 'teacher'), 'Elige un profesor'); fillSelect($('assign-student'), users.filter(user => user.role === 'student'), 'Elige un estudiante');
  const stats = (Array.isArray(statsResult.data) ? statsResult.data[0] : statsResult.data) || {};
  $('admin-active-users').textContent = stats.active_users || 0;
  $('admin-evaluations').textContent = stats.evaluations_completed || 0;
  $('admin-success-rate').textContent = `${stats.success_rate ?? 0}%`;
}
function renderUsers() { $('users-body').innerHTML = users.map(user => `<tr><td>${escapeHtml(user.full_name)}</td><td>${escapeHtml(user.email)}</td><td><select data-user-role="${user.id}"><option value="student" ${user.role === 'student' ? 'selected' : ''}>Estudiante</option><option value="teacher" ${user.role === 'teacher' ? 'selected' : ''}>Profesor</option><option value="content_manager" ${user.role === 'content_manager' ? 'selected' : ''}>Gestor de contenidos</option><option value="admin" ${user.role === 'admin' ? 'selected' : ''}>Administrador</option></select></td><td><input type="checkbox" data-user-active="${user.id}" ${user.active ? 'checked' : ''} aria-label="Usuario activo"></td><td><button class="text-button" data-save-user="${user.id}">Guardar</button></td></tr>`).join('') || '<tr><td colspan="5">No hay usuarios.</td></tr>'; $('users-body').querySelectorAll('[data-save-user]').forEach(button => button.addEventListener('click', () => void saveUser(button.dataset.saveUser))); }
async function saveUser(id) { const { error } = await supabase.from('profiles').update({ role: document.querySelector(`[data-user-role="${id}"]`).value, active: document.querySelector(`[data-user-active="${id}"]`).checked }).eq('id', id); if (error) return showToast(error.message); showToast('Usuario actualizado.'); await loadAdministration(); if (id === currentUser.id) await handleSession({ user: currentUser }); }
async function createCourse(event) { event.preventDefault(); const form = event.currentTarget, { error } = await supabase.from('courses').insert({ name: form.elements.name.value.trim(), code: form.elements.code.value.trim(), term: form.elements.term.value.trim(), created_by: currentUser.id }); if (error) return showToast(error.message); form.reset(); showToast('Curso creado.'); await loadAdministration(); }
async function saveAssignment() { const course_id = $('assign-course').value, teacher_id = $('assign-teacher').value, student_id = $('assign-student').value, module_id = $('assign-module').value; if (!course_id || !teacher_id || !student_id || !module_id) return showToast('Selecciona curso, profesor, estudiante y módulo.'); const results = await Promise.all([supabase.from('course_teachers').upsert({ course_id, teacher_id }), supabase.from('enrollments').upsert({ course_id, student_id }), supabase.from('course_modules').upsert({ course_id, module_id })]); const error = results.find(result => result.error)?.error; if (error) return showToast(error.message); showToast('Asignación guardada.'); }

document.querySelectorAll('[data-view]').forEach(button => button.addEventListener('click', () => setView(button.dataset.view)));
$('signup-form').addEventListener('submit', signUp); $('signin-form').addEventListener('submit', signIn);
$('show-signup').addEventListener('click', () => { $('signin-panel').hidden = true; $('signup-panel').hidden = false; showAuthMessage(''); }); $('show-signin').addEventListener('click', () => { $('signup-panel').hidden = true; $('signin-panel').hidden = false; showAuthMessage(''); });
$('signout-button').addEventListener('click', async () => { const { error } = await supabase.auth.signOut(); if (error) showToast(error.message); });
$('theory-practice').addEventListener('click', () => void startActivity(activeModule?.id, 'practice')); $('theory-evaluation').addEventListener('click', () => void startActivity(activeModule?.id, 'evaluation'));
$('previous-question').addEventListener('click', () => { if (currentQuestion > 0) { currentQuestion--; renderQuestion(); } });
$('next-question').addEventListener('click', async () => { const reviewed = activityMode === 'practice' ? practiceResults.has(questions[currentQuestion]?.id) : Boolean(evaluationResults); if (reviewed) { if (currentQuestion < questions.length - 1) { currentQuestion++; renderQuestion(); } else setView(activityMode === 'practice' ? 'progreso' : 'inicio'); return; } if (!answers[currentQuestion]) return showToast('Selecciona una respuesta para continuar.'); if (currentQuestion < questions.length - 1) { currentQuestion++; renderQuestion(); return; } await submitEvaluation(); });
$('score-search').addEventListener('input', event => renderScores(event.target.value)); $('download-scores').addEventListener('click', downloadScores); $('save-module').addEventListener('click', saveManagedModule); $('delete-module').addEventListener('click', deleteManagedModule); $('create-module-form').addEventListener('submit', createManagedModule); $('save-question').addEventListener('click', saveManagedQuestion); $('delete-question').addEventListener('click', deleteManagedQuestion); $('create-course-form').addEventListener('submit', createCourse); $('save-assignment').addEventListener('click', saveAssignment); $('refresh-users').addEventListener('click', () => void loadAdministration());

if (!configured) showAuthMessage('Configura la URL y la clave pública de Supabase en supabase-config.js para activar el acceso.');
else { supabase.auth.onAuthStateChange((_event, session) => { void handleSession(session); }); supabase.auth.getSession().then(({ data }) => void handleSession(data.session)); }
