CREATE TABLE attendance(
    attendanceID INTEGER PRIMARY KEY AUTOINCREMENT,
    studentID INTEGER NOT NULL,
    total_class_days INTEGER,
    days_present INTEGER,
    total_absences INTEGER,
);