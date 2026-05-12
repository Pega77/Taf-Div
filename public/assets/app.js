async function api(url, options = {}) {
  const res = await fetch(url, { headers: { 'Content-Type': 'application/json' }, ...options });
  if (res.status === 401) { location.href = '/login.php'; return; }
  if (!res.ok) throw new Error(await res.text());
  return res.json();
}

let groups = [];
let programs = [];

async function loadFilters() {
  programs = await api('/api/projects.php');
  groups = await api('/api/groups.php');
  filter_project_id.innerHTML = '<option value="">כל התוכניות</option>' + programs.map(p => `<option value="${p.id}">${p.name}</option>`).join('');
  filter_group_id.innerHTML = '<option value="">כל הקבוצות</option>' + groups.map(g => `<option value="${g.id}">${g.project_name} - ${g.name}</option>`).join('');
  modal_group_id.innerHTML = groups.map(g => `<option value="${g.id}">${g.project_name} - ${g.name}</option>`).join('');
  await onGroupChanged();
  await loadActivityTypeFilter();
}

async function loadActivityTypeFilter() {
  const groupId = filter_group_id.value || (groups[0] && groups[0].id);
  if (!groupId) return;
  const types = await api(`/api/activity_types.php?group_id=${groupId}`);
  filter_activity_type_id.innerHTML = '<option value="">כל סוגי הפעילות</option>' + types.map(t => `<option value="${t.id}">${t.name}</option>`).join('');
}

async function loadDashboard() {
  const params = new URLSearchParams();
  const fields = {
    project_id: '#filter_project_id', group_id: '#filter_group_id', activity_type_id: '#filter_activity_type_id',
    national_id: '#filter_national_id', from: '#filter_from', to: '#filter_to', status: '#filter_status'
  };
  Object.entries(fields).forEach(([key, selector]) => {
    const value = document.querySelector(selector)?.value;
    if (value) params.set(key, value);
  });
  exportCsvBtn.href = '/api/export_attendance_csv.php?' + params.toString();
  const data = await api('/api/dashboard.php?' + params.toString());
  studentsCount.textContent = data.summary.students_count;
  activitiesCount.textContent = data.summary.activities_count;
  presentCount.textContent = data.summary.present_count;
  absentCount.textContent = data.summary.absent_count;
  dashboardRows.innerHTML = data.rows.map(row => `
    <div class="attendance-row">
      <strong>${row.full_name}</strong>
      <span>${row.national_id}</span>
      <span>${row.project_name} / ${row.group_name}</span>
      <span>${row.activity_date}${row.start_time ? ' ' + row.start_time.slice(0,5) : ''} - ${row.activity_type}</span>
      <span>${translateStatus(row.status)}</span>
    </div>`).join('') || '<p>אין נתונים להצגה</p>';
}

function translateStatus(status) { return { present: 'נוכח', absent: 'נעדר' }[status] || status; }

document.addEventListener('DOMContentLoaded', async () => {
  if (document.querySelector('#filter_group_id')) {
    await loadFilters();
    await loadDashboard();
  }
});
