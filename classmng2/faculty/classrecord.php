<?php
// classmng2/faculty/classrecord_inspect.php
// Run this in your dev environment to inspect the DB and print table/column/sample data.
// After opening it in the browser, copy-paste the full output here.

ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

require_once __DIR__ . '/../config.php';
require_login();

echo "<!doctype html><html><head><meta charset='utf-8'><title>DB Inspect</title>";
echo "<style>body{font-family:Arial,Helvetica,sans-serif;font-size:14px} pre{background:#f8f8f8;padding:8px;border:1px solid #ddd;}</style>";
echo "</head><body>";
echo "<h1>Database Inspection</h1>";

try {
    $db = pdo();

    // 1) List tables
    $tables = $db->query("SELECT table_name FROM information_schema.tables WHERE table_schema = DATABASE() ORDER BY table_name")
                 ->fetchAll(PDO::FETCH_COLUMN);

    echo "<h2>Tables in database '".htmlspecialchars($db->query('SELECT DATABASE()')->fetchColumn())."'</h2>";
    if (empty($tables)) {
        echo "<div style='color:red'>No tables found in the current database.</div></body></html>";
        exit;
    }

    echo "<ul>";
    foreach ($tables as $t) {
        echo "<li>" . htmlspecialchars($t) . "</li>";
    }
    echo "</ul>";

    // Candidate name hints
    echo "<h3>Candidate table name groups (for your reference)</h3>";
    echo "<pre>Subjects candidates: subjects, subject, classes, class_subjects, tbl_subjects
Students candidates: students, student, users, tbl_students
Enrollments candidates: enrollments, enrollment, student_subjects, student_enrollments, class_students, student_class, classlist
Grades candidates: grades, class_grades, student_grades</pre>";

    // 2) For each table show columns and up to 5 sample rows
    foreach ($tables as $table) {
        echo "<hr><h3>Table: " . htmlspecialchars($table) . "</h3>";

        // Columns
        try {
            $cols = $db->query("SHOW COLUMNS FROM `{$table}`")->fetchAll(PDO::FETCH_ASSOC);
            if ($cols) {
                echo "<strong>Columns:</strong><br><pre>";
                foreach ($cols as $c) {
                    echo htmlspecialchars($c['Field'] . " \t " . $c['Type'] . " \t " . ($c['Null'] === 'YES' ? 'NULL' : 'NOT NULL') . " \t Key:" . $c['Key'] . " \n");
                }
                echo "</pre>";
            } else {
                echo "<div style='color:orange'>Could not read columns for table.</div>";
            }
        } catch (Throwable $e) {
            echo "<div style='color:red'>Error reading columns: " . htmlspecialchars($e->getMessage()) . "</div>";
        }

        // Sample rows
        try {
            $stmt = $db->query("SELECT * FROM `{$table}` LIMIT 5");
            $rows = $stmt->fetchAll(PDO::FETCH_ASSOC);
            echo "<strong>Sample rows (up to 5):</strong>";
            if (empty($rows)) {
                echo "<div><em>— no rows —</em></div>";
            } else {
                echo "<table border='1' cellpadding='6' cellspacing='0' style='border-collapse:collapse;margin-top:8px;'>";
                // header
                echo "<tr style='background:#eee'>";
                foreach (array_keys($rows[0]) as $h) {
                    echo "<th>" . htmlspecialchars($h) . "</th>";
                }
                echo "</tr>";
                // rows
                foreach ($rows as $r) {
                    echo "<tr>";
                    foreach ($r as $v) {
                        $s = is_null($v) ? '<em>NULL</em>' : htmlspecialchars((string)$v);
                        echo "<td style='vertical-align:top'>" . $s . "</td>";
                    }
                    echo "</tr>";
                }
                echo "</table>";
            }
        } catch (Throwable $e) {
            echo "<div style='color:red'>Error fetching sample rows: " . htmlspecialchars($e->getMessage()) . "</div>";
        }
    }

    // 3) Quick heuristics: try to suggest which table looks like subjects/students/enrollments/grades
    echo "<hr><h3>Auto-detection hints</h3>";
    $pick = function(array $candidates) use ($tables) {
        foreach ($candidates as $c) {
            if (in_array($c, $tables, true)) return $c;
        }
        return null;
    };
    $suggest_subjects = $pick(['subjects','subject','classes','class_subjects','tbl_subjects']);
    $suggest_students = $pick(['students','student','users','tbl_students']);
    $suggest_enroll = $pick(['enrollments','enrollment','student_subjects','student_enrollments','class_students','classlist','students_subjects']);
    $suggest_grades = $pick(['grades','grade','student_grades','class_grades']);

    echo "<ul>";
    echo "<li>Suggested subjects table: <strong>" . htmlspecialchars($suggest_subjects ?? 'none detected') . "</strong></li>";
    echo "<li>Suggested students table: <strong>" . htmlspecialchars($suggest_students ?? 'none detected') . "</strong></li>";
    echo "<li>Suggested enrollments table: <strong>" . htmlspecialchars($suggest_enroll ?? 'none detected') . "</strong></li>";
    echo "<li>Suggested grades table: <strong>" . htmlspecialchars($suggest_grades ?? 'none detected') . "</strong></li>";
    echo "</ul>";

    echo "<p><strong>Next step:</strong> Copy-paste the full HTML output you see here (or the CREATE TABLE outputs) and I will generate a corrected classrecord.php that uses the exact table & column names.</p>";

} catch (Throwable $ex) {
    echo "<div style='color:red'><strong>Fatal error:</strong> " . htmlspecialchars($ex->getMessage()) . "</div>";
}

echo "</body></html>";