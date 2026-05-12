<?php require_once '../includes/auth.php'; if (!current_user()) { header('Location: /login.php'); exit; } ?>
<!doctype html>
<html lang="he" dir="rtl">
<head>
  <meta charset="utf-8">
  <meta name="viewport" content="width=device-width,initial-scale=1">
  <title>דשבורד ולוח פעילות</title>
  <link rel="stylesheet" href="/assets/style.css">
</head>
<body>
<main class="container">
  <section class="card">
    <h2>שלום <?= htmlspecialchars(current_user()['full_name']) ?></h2>
    <p>דשבורד, פילטרים ולוח פעילות לפי מבנה Taf-Div.</p>
  </section>

  <section class="filters card">
    <h3>סינון</h3>
    <select id="filter_project_id"><option value="">כל התוכניות</option></select>
    <select id="filter_group_id"><option value="">כל הקבוצות</option></select>
    <select id="filter_activity_type_id"><option value="">כל סוגי הפעילות</option></select>
    <input id="filter_national_id" placeholder="תעודת זהות תלמיד">
    <input type="date" id="filter_from">
    <input type="date" id="filter_to">
    <select id="filter_status">
      <option value="">כל הסטטוסים</option>
      <option value="present">נוכח</option>
      <option value="absent">נעדר</option>
    </select>
    <button onclick="loadDashboard()">הפעל סינון</button>
    <a id="exportCsvBtn" class="button" href="/api/export_attendance_csv.php">יצוא CSV</a>
  </section>

  <section class="dashboard-cards">
    <div class="card"><strong id="studentsCount">0</strong><span>תלמידים</span></div>
    <div class="card"><strong id="activitiesCount">0</strong><span>פעילויות</span></div>
    <div class="card"><strong id="presentCount">0</strong><span>נוכחים</span></div>
    <div class="card"><strong id="absentCount">0</strong><span>נעדרים</span></div>
  </section>

  <section class="card">
    <h3>פירוט נוכחות</h3>
    <div id="dashboardRows"></div>
  </section>

  <section class="card">
    <div class="calendar-header">
      <button onclick="moveCalendar(-1)">‹</button>
      <h2 id="calendarTitle">לוח פעילות</h2>
      <button onclick="moveCalendar(1)">›</button>
    </div>
    <div class="view-switcher">
      <button onclick="setView('day')">יום</button>
      <button onclick="setView('week')">שבוע</button>
      <button onclick="setView('month')">חודש</button>
    </div>
    <button onclick="openActivityModal()" class="primary">הוסף פעילות עתידית</button>
    <section id="calendar"></section>
  </section>
</main>

<dialog id="activityModal">
  <form method="dialog" class="modal-card" onsubmit="createActivity(event)">
    <h3>הוספת פעילות</h3>
    <label>קבוצה</label><select id="modal_group_id" required onchange="onGroupChanged()"></select>
    <label>סוג פעילות</label><select id="modal_activity_type_id" required></select>
    <label>תאריך</label><input type="date" id="modal_activity_date" required>
    <label>שעת התחלה</label><input type="time" id="modal_start_time">
    <label>שעת סיום</label><input type="time" id="modal_end_time">
    <label><input type="checkbox" id="modal_is_personal" onchange="togglePersonalActivity()"> פעילות אישית לתלמיד</label>
    <div id="personalStudentBox" style="display:none"><label>בחר תלמיד</label><select id="modal_personal_student_id"></select></div>
    <label>הערות</label><textarea id="modal_notes"></textarea>
    <button type="submit">שמור פעילות</button>
    <button type="button" onclick="activityModal.close()">ביטול</button>
  </form>
</dialog>

<script src="/assets/app.js"></script>
<script src="/assets/calendar.js"></script>
</body>
</html>
