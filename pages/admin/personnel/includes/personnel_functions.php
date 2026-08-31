<?php
/**
 * Personnel (staff & volunteer) helper functions.
 * Every function expects a live PDO instance (see config/db.php).
 */

const PERSONNEL_TYPES = ['staff', 'volunteer'];
const PERSONNEL_STATUSES = ['pending', 'active', 'inactive'];

/**
 * All personnel, for the initial page render (client-side search/filter
 * works off this full list — no round trip needed for small rosters).
 */
function getAllPersonnel(PDO $pdo): array
{
    $stmt = $pdo->query("
        SELECT id, type, full_name, ic_number, gender, date_of_birth, phone, email, address,
               department, skills, availability_date, emergency_contact_name, emergency_contact_phone,
               status, created_at
        FROM personnel
        ORDER BY full_name ASC
    ");
    return $stmt->fetchAll();
}

/**
 * Same data, filtered server-side — used by the CSV export so the file
 * matches whatever the admin currently has filtered/searched for.
 */
function getFilteredPersonnel(PDO $pdo, string $q = '', string $type = '', string $status = ''): array
{
    $sql = "
        SELECT id, type, full_name, ic_number, gender, date_of_birth, phone, email, address,
               department, skills, availability_date, emergency_contact_name, emergency_contact_phone,
               status, created_at
        FROM personnel
        WHERE 1=1
    ";
    $params = [];

    if ($q !== '') {
        $sql .= " AND (full_name LIKE ? OR ic_number LIKE ? OR phone LIKE ? OR email LIKE ?)";
        $like = '%' . $q . '%';
        $params = array_merge($params, [$like, $like, $like, $like]);
    }
    if ($type !== '' && in_array($type, PERSONNEL_TYPES, true)) {
        $sql .= " AND type = ?";
        $params[] = $type;
    }
    if ($status !== '' && in_array($status, PERSONNEL_STATUSES, true)) {
        $sql .= " AND status = ?";
        $params[] = $status;
    }

    $sql .= " ORDER BY full_name ASC";

    $stmt = $pdo->prepare($sql);
    $stmt->execute($params);
    return $stmt->fetchAll();
}

/**
 * Insert a new staff/volunteer record. Returns the new row's id.
 * Throws InvalidArgumentException on bad input.
 */
function addPersonnel(PDO $pdo, array $data, int $registeredBy): int
{
    $requiredFields = [
        'type', 'full_name', 'ic_number', 'gender', 'date_of_birth', 'phone', 'email',
        'address', 'department', 'availability_date', 'skills',
        'emergency_contact_name', 'emergency_contact_phone', 'status',
    ];

    foreach ($requiredFields as $field) {
        if (trim($data[$field] ?? '') === '') {
            throw new InvalidArgumentException('Please fill in every field before submitting.');
        }
    }

    $type = $data['type'];
    $status = $data['status'];

    if (!in_array($type, PERSONNEL_TYPES, true)) {
        throw new InvalidArgumentException('Select whether this person is staff or a volunteer.');
    }
    if (!in_array($data['gender'], ['male', 'female'], true)) {
        throw new InvalidArgumentException('Select a valid gender.');
    }
    if (!in_array($status, PERSONNEL_STATUSES, true)) {
        throw new InvalidArgumentException('Select a valid status.');
    }

    $stmt = $pdo->prepare("
        INSERT INTO personnel (
            type, full_name, ic_number, gender, date_of_birth, phone, email, address,
            department, skills, availability_date, emergency_contact_name, emergency_contact_phone,
            status, registered_by
        ) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)
    ");
    $stmt->execute([
        $type,
        trim($data['full_name']),
        trim($data['ic_number']),
        $data['gender'],
        trim($data['date_of_birth']),
        trim($data['phone']),
        trim($data['email']),
        trim($data['address']),
        trim($data['department']),
        trim($data['skills']),
        trim($data['availability_date']),
        trim($data['emergency_contact_name']),
        trim($data['emergency_contact_phone']),
        $status,
        $registeredBy,
    ]);

    return (int) $pdo->lastInsertId();
}
