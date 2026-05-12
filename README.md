# Taf-Div PHP Attendance MVP

מותאם למבנה הטבלאי הקיים ב־`database/schema.sql` ו־`database/seed.sql` של הריפו Taf-Div.

## התקנה

1. הרץ את הקבצים הקיימים מהריפו:
   - `database/schema.sql`
   - `database/seed.sql`
2. הרץ בנוסף:
   - `sql/taf_div_extensions.sql`
3. עדכן פרטי DB ב־`includes/db.php` אם צריך.
4. הפעל שרת PHP מתוך תיקיית `public`:

```bash
php -S localhost:8000 -t public
```

## התאמות שבוצעו

- `projects` הותאם ל־`programs`.
- `students` משתמש ב־`id` פנימי וב־`national_id` כת.ז.
- `student_groups` הותאם ל־`group_student`.
- `attendance` הותאם ל־`activity_students`.
- `attendance.status` הותאם ל־`activity_students.participation_status`.
- `created_by` הותאם ל־`created_by_user_id`.
- metadata נשמר בטבלאות `metadata_fields` ו־`metadata_values`, לא JSON מרכזי.
- נוספו הרחבות מינימליות ללוח פעילות: שעות, פעילות אישית לתלמיד, שיוך רכז לקבוצות, וסוגי פעילות לפי תוכנית.

## הערה על רכזים

ב־schema המקורי אין טבלה לשיוך רכז לקבוצות. לכן נוסף `coordinator_groups` בקובץ ההרחבות.

## הערה על סוגי פעילות לפי תוכנית

ב־schema המקורי `activity_types` היא טבלה גלובלית. לכן נוסף `activity_type_program` כדי לאפשר סוגי פעילות לפי תוכנית בלי לשבור את הנתונים הקיימים.
