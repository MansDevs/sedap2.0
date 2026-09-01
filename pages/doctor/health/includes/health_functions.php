<?php
/**
 * Health module helper functions.
 * Every function expects a live PDO instance (see config/db.php).
 */

/**
 * All registered patients, for the patient picker.
 */
function getAllPatients(PDO $pdo): array
{
    $stmt = $pdo->query("
        SELECT id, registration_number, full_name
        FROM patients
        ORDER BY full_name ASC
    ");
    return $stmt->fetchAll();
}

function getPatientById(PDO $pdo, int $patientId): ?array
{
    $stmt = $pdo->prepare("SELECT id, registration_number, full_name FROM patients WHERE id = ?");
    $stmt->execute([$patientId]);
    $patient = $stmt->fetch();
    return $patient ?: null;
}

// ---------------------------------------------------------
// Bristol stool scale
// ---------------------------------------------------------

function addBristolLog(PDO $pdo, int $patientId, int $scaleType, ?string $notes): int
{
    $stmt = $pdo->prepare("INSERT INTO bristol_scale_logs (patient_id, scale_type, notes) VALUES (?, ?, ?)");
    $stmt->execute([$patientId, $scaleType, $notes]);
    return (int) $pdo->lastInsertId();
}

function getBristolLogs(PDO $pdo, int $patientId, int $limit = 20): array
{
    $stmt = $pdo->prepare("
        SELECT id, scale_type, notes, logged_at
        FROM bristol_scale_logs
        WHERE patient_id = ?
        ORDER BY logged_at DESC
        LIMIT ?
    ");
    $stmt->bindValue(1, $patientId, PDO::PARAM_INT);
    $stmt->bindValue(2, $limit, PDO::PARAM_INT);
    $stmt->execute();
    return $stmt->fetchAll();
}

// ---------------------------------------------------------
// Water intake
// ---------------------------------------------------------

function addWaterLog(PDO $pdo, int $patientId, int $amountMl): int
{
    $stmt = $pdo->prepare("INSERT INTO water_intake_logs (patient_id, amount_ml) VALUES (?, ?)");
    $stmt->execute([$patientId, $amountMl]);
    return (int) $pdo->lastInsertId();
}

function getWaterLogs(PDO $pdo, int $patientId, int $limit = 20): array
{
    $stmt = $pdo->prepare("
        SELECT id, amount_ml, logged_at
        FROM water_intake_logs
        WHERE patient_id = ?
        ORDER BY logged_at DESC
        LIMIT ?
    ");
    $stmt->bindValue(1, $patientId, PDO::PARAM_INT);
    $stmt->bindValue(2, $limit, PDO::PARAM_INT);
    $stmt->execute();
    return $stmt->fetchAll();
}

function getWaterTotalToday(PDO $pdo, int $patientId): int
{
    $stmt = $pdo->prepare("
        SELECT COALESCE(SUM(amount_ml), 0)
        FROM water_intake_logs
        WHERE patient_id = ? AND DATE(logged_at) = CURDATE()
    ");
    $stmt->execute([$patientId]);
    return (int) $stmt->fetchColumn();
}

// ---------------------------------------------------------
// Emotion & mood journal
// ---------------------------------------------------------

function addMoodEntry(PDO $pdo, int $patientId, string $mood, ?string $note): int
{
    $allowed = ['very_sad', 'sad', 'neutral', 'happy', 'very_happy'];
    if (!in_array($mood, $allowed, true)) {
        throw new InvalidArgumentException('Invalid mood value.');
    }
    $stmt = $pdo->prepare("INSERT INTO mood_journal_entries (patient_id, mood, note) VALUES (?, ?, ?)");
    $stmt->execute([$patientId, $mood, $note]);
    return (int) $pdo->lastInsertId();
}

function getMoodEntries(PDO $pdo, int $patientId, int $limit = 20): array
{
    $stmt = $pdo->prepare("
        SELECT id, mood, note, logged_at
        FROM mood_journal_entries
        WHERE patient_id = ?
        ORDER BY logged_at DESC
        LIMIT ?
    ");
    $stmt->bindValue(1, $patientId, PDO::PARAM_INT);
    $stmt->bindValue(2, $limit, PDO::PARAM_INT);
    $stmt->execute();
    return $stmt->fetchAll();
}

// ---------------------------------------------------------
// Medicines & reminders
// ---------------------------------------------------------

function addMedicine(PDO $pdo, int $patientId, string $name, ?string $dosage, ?string $frequency, ?string $startDate, ?string $endDate, ?string $notes): int
{
    $stmt = $pdo->prepare("
        INSERT INTO medicines (patient_id, medicine_name, dosage, frequency, start_date, end_date, notes)
        VALUES (?, ?, ?, ?, ?, ?, ?)
    ");
    $stmt->execute([
        $patientId,
        $name,
        $dosage ?: null,
        $frequency ?: null,
        $startDate ?: null,
        $endDate ?: null,
        $notes ?: null,
    ]);
    return (int) $pdo->lastInsertId();
}

function getMedicines(PDO $pdo, int $patientId): array
{
    $stmt = $pdo->prepare("
        SELECT id, medicine_name, dosage, frequency, start_date, end_date, notes, created_at
        FROM medicines
        WHERE patient_id = ?
        ORDER BY created_at DESC
    ");
    $stmt->execute([$patientId]);
    $medicines = $stmt->fetchAll();

    foreach ($medicines as &$medicine) {
        $medicine['reminders'] = getReminders($pdo, (int) $medicine['id']);
    }
    unset($medicine);

    return $medicines;
}

function medicineBelongsToPatient(PDO $pdo, int $medicineId, int $patientId): bool
{
    $stmt = $pdo->prepare("SELECT 1 FROM medicines WHERE id = ? AND patient_id = ?");
    $stmt->execute([$medicineId, $patientId]);
    return (bool) $stmt->fetchColumn();
}

function addReminder(PDO $pdo, int $medicineId, string $reminderTime, string $daysOfWeek): int
{
    $stmt = $pdo->prepare("
        INSERT INTO medicine_reminders (medicine_id, reminder_time, days_of_week)
        VALUES (?, ?, ?)
    ");
    $stmt->execute([$medicineId, $reminderTime, $daysOfWeek]);
    return (int) $pdo->lastInsertId();
}

function getReminders(PDO $pdo, int $medicineId): array
{
    $stmt = $pdo->prepare("
        SELECT id, reminder_time, days_of_week, is_active
        FROM medicine_reminders
        WHERE medicine_id = ?
        ORDER BY reminder_time ASC
    ");
    $stmt->execute([$medicineId]);
    return $stmt->fetchAll();
}
