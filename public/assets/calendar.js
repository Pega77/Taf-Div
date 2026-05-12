let calendarView = 'week';
let currentDate = new Date();
let activities = [];
const hebrewDays = ['א׳','ב׳','ג׳','ד׳','ה׳','ו׳','ש׳'];

async function onGroupChanged() {
  const groupId = modal_group_id.value;
  if (!groupId) return;
  const types = await api(`/api/activity_types.php?group_id=${groupId}`);
  modal_activity_type_id.innerHTML = types.map(t => `<option value="${t.id}">${t.name}</option>`).join('');
  await loadGroupStudents();
}

async function loadGroupStudents() {
  const groupId = modal_group_id.value;
  if (!groupId) return;
  const students = await api(`/api/group_students.php?group_id=${groupId}`);
  modal_personal_student_id.innerHTML = students.map(s => `<option value="${s.id}">${s.full_name} - ${s.national_id}</option>`).join('');
}

function togglePersonalActivity() { personalStudentBox.style.display = modal_is_personal.checked ? 'block' : 'none'; }

function openActivityModal(date = null) {
  modal_activity_date.value = date || toDateInputValue(new Date());
  modal_start_time.value = '';
  modal_end_time.value = '';
  modal_notes.value = '';
  modal_is_personal.checked = false;
  togglePersonalActivity();
  activityModal.showModal();
}

async function createActivity(event) {
  event.preventDefault();
  const isPersonal = modal_is_personal.checked;
  await api('/api/activities.php', {
    method: 'POST',
    body: JSON.stringify({
      group_id: modal_group_id.value,
      activity_type_id: modal_activity_type_id.value,
      activity_date: modal_activity_date.value,
      start_time: modal_start_time.value || null,
      end_time: modal_end_time.value || null,
      personal_student_id: isPersonal ? modal_personal_student_id.value : null,
      notes: modal_notes.value
    })
  });
  activityModal.close();
  await loadCalendar();
  await loadDashboard();
}

async function loadCalendar() {
  const { from, to } = getRange();
  activities = await api(`/api/activities.php?from=${from}&to=${to}`);
  renderCalendar();
}

function renderCalendar() { if (calendarView === 'day') renderDay(); if (calendarView === 'week') renderWeek(); if (calendarView === 'month') renderMonth(); }

function renderDay() {
  const dateStr = toDateInputValue(currentDate);
  calendarTitle.textContent = formatHebrewDate(currentDate);
  calendar.innerHTML = `<div class="day-view">${renderActivityList(activities.filter(a => a.activity_date === dateStr))}</div>`;
}

function renderWeek() {
  const start = startOfWeek(currentDate);
  calendarTitle.textContent = 'תצוגה שבועית';
  let html = '<div class="week-grid">';
  for (let i = 0; i < 7; i++) {
    const date = addDays(start, i);
    const dateStr = toDateInputValue(date);
    html += `<div class="calendar-day" onclick="openActivityModal('${dateStr}')"><strong>${hebrewDays[i]} ${date.getDate()}</strong>${renderActivityList(activities.filter(a => a.activity_date === dateStr))}</div>`;
  }
  calendar.innerHTML = html + '</div>';
}

function renderMonth() {
  const year = currentDate.getFullYear(), month = currentDate.getMonth();
  calendarTitle.textContent = currentDate.toLocaleDateString('he-IL', { month: 'long', year: 'numeric' });
  const start = startOfWeek(new Date(year, month, 1));
  let html = '<div class="month-grid">';
  for (let i = 0; i < 42; i++) {
    const date = addDays(start, i), dateStr = toDateInputValue(date);
    html += `<div class="calendar-day ${date.getMonth() !== month ? 'faded' : ''}" onclick="openActivityModal('${dateStr}')"><strong>${date.getDate()}</strong>${renderActivityList(activities.filter(a => a.activity_date === dateStr))}</div>`;
  }
  calendar.innerHTML = html + '</div>';
}

function renderActivityList(list) {
  return list.map(a => `<div class="activity-pill"><div>${a.start_time ? a.start_time.slice(0,5) + ' ' : ''}${a.activity_type}</div><small>${a.group_name}</small>${a.personal_student_id ? `<small>אישי: ${a.personal_student_name}</small>` : ''}</div>`).join('');
}

function setView(view) { calendarView = view; loadCalendar(); }
function moveCalendar(direction) { if (calendarView === 'day') currentDate = addDays(currentDate, direction); if (calendarView === 'week') currentDate = addDays(currentDate, direction * 7); if (calendarView === 'month') currentDate = new Date(currentDate.getFullYear(), currentDate.getMonth() + direction, 1); loadCalendar(); }
function getRange() { if (calendarView === 'day') { const d = toDateInputValue(currentDate); return { from: d, to: d }; } if (calendarView === 'week') { const s = startOfWeek(currentDate); return { from: toDateInputValue(s), to: toDateInputValue(addDays(s, 6)) }; } const first = new Date(currentDate.getFullYear(), currentDate.getMonth(), 1); const last = new Date(currentDate.getFullYear(), currentDate.getMonth()+1, 0); return { from: toDateInputValue(startOfWeek(first)), to: toDateInputValue(addDays(startOfWeek(last), 6)) }; }
function startOfWeek(date) { const d = new Date(date); d.setDate(d.getDate() - d.getDay()); return d; }
function addDays(date, days) { const d = new Date(date); d.setDate(d.getDate() + days); return d; }
function toDateInputValue(date) { const tz = new Date(date.getTime() - date.getTimezoneOffset() * 60000); return tz.toISOString().slice(0, 10); }
function formatHebrewDate(date) { return date.toLocaleDateString('he-IL', { weekday: 'long', day: 'numeric', month: 'long', year: 'numeric' }); }

document.addEventListener('DOMContentLoaded', () => { if (document.querySelector('#calendar')) loadCalendar(); });
