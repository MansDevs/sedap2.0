<?php
/**
 * Dashboard overview stats. Every query is wrapped in try/catch so a
 * fresh install (some tables not yet imported) still renders the
 * dashboard instead of fatal-erroring — missing data just shows as 0.
 */
function getDashboardStats(PDO $pdo, int $userId): array
{
    $stats = [
        'total_patients' => 0,
        'patients_change_pct' => null,
        'active_staff' => 0,
        'pending_staff' => 0,
        'today_triage' => 0,
        'waiting_triage' => 0,
        'total_conversations' => 0,
        'conversations_needing_attention' => 0,
        'triage_breakdown' => ['red' => 0, 'yellow' => 0, 'green' => 0, 'black' => 0],
    ];

    try {
        $stats['total_patients'] = (int) $pdo->query("SELECT COUNT(*) FROM patients")->fetchColumn();

        $thisWeek = (int) $pdo->query("SELECT COUNT(*) FROM patients WHERE created_at >= DATE_SUB(NOW(), INTERVAL 7 DAY)")->fetchColumn();
        $lastWeek = (int) $pdo->query("SELECT COUNT(*) FROM patients WHERE created_at >= DATE_SUB(NOW(), INTERVAL 14 DAY) AND created_at < DATE_SUB(NOW(), INTERVAL 7 DAY)")->fetchColumn();

        if ($lastWeek > 0) {
            $stats['patients_change_pct'] = (int) round((($thisWeek - $lastWeek) / $lastWeek) * 100);
        } elseif ($thisWeek > 0) {
            $stats['patients_change_pct'] = 100;
        }
    } catch (\PDOException $e) {
        // patients table not present yet
    }

    try {
        $stats['active_staff'] = (int) $pdo->query("SELECT COUNT(*) FROM personnel WHERE status = 'active'")->fetchColumn();
        $stats['pending_staff'] = (int) $pdo->query("SELECT COUNT(*) FROM personnel WHERE status = 'pending'")->fetchColumn();
    } catch (\PDOException $e) {
        // personnel table not present yet
    }

    try {
        $stats['today_triage'] = (int) $pdo->query("SELECT COUNT(*) FROM triage_records WHERE DATE(triaged_at) = CURDATE()")->fetchColumn();
        $stats['waiting_triage'] = (int) $pdo->query("SELECT COUNT(*) FROM triage_records WHERE status = 'waiting'")->fetchColumn();

        $stmt = $pdo->query("
            SELECT triage_level, COUNT(*) AS c
            FROM triage_records
            WHERE status != 'discharged'
            GROUP BY triage_level
        ");
        foreach ($stmt->fetchAll() as $row) {
            if (isset($stats['triage_breakdown'][$row['triage_level']])) {
                $stats['triage_breakdown'][$row['triage_level']] = (int) $row['c'];
            }
        }
    } catch (\PDOException $e) {
        // triage_records table not present yet
    }

    try {
        $stmt = $pdo->prepare("SELECT COUNT(*) FROM conversation_participants WHERE user_id = ? AND left_at IS NULL");
        $stmt->execute([$userId]);
        $stats['total_conversations'] = (int) $stmt->fetchColumn();

        $stmt = $pdo->prepare("
            SELECT COUNT(DISTINCT m.conversation_id) FROM messages m
            INNER JOIN conversation_participants cp
                ON cp.conversation_id = m.conversation_id AND cp.user_id = ?
            WHERE m.sender_id != ?
              AND m.deleted_at IS NULL
              AND m.id > COALESCE(cp.last_read_message_id, 0)
              AND cp.left_at IS NULL
        ");
        $stmt->execute([$userId, $userId]);
        $stats['conversations_needing_attention'] = (int) $stmt->fetchColumn();
    } catch (\PDOException $e) {
        // chat tables not present yet
    }

    return $stats;
}

/**
 * A real activity feed, merged from the most recent rows across a few
 * tables — not scripted/fake entries. Returns [] gracefully if nothing
 * exists yet or the relevant tables aren't imported.
 */
function getRecentActivity(PDO $pdo, int $limit = 4): array
{
    $items = [];

    try {
        $stmt = $pdo->query("SELECT full_name, created_at FROM patients ORDER BY created_at DESC LIMIT 5");
        foreach ($stmt->fetchAll() as $row) {
            $items[] = [
                'icon' => 'person_add',
                'tone' => 'error',
                'title' => 'Patient registered: ' . $row['full_name'],
                'subtitle' => 'New patient added to the system.',
                'time' => $row['created_at'],
            ];
        }
    } catch (\PDOException $e) {
    }

    try {
        $stmt = $pdo->query("SELECT full_name, type, created_at FROM personnel ORDER BY created_at DESC LIMIT 5");
        foreach ($stmt->fetchAll() as $row) {
            $items[] = [
                'icon' => 'how_to_reg',
                'tone' => 'primary',
                'title' => ucfirst($row['type']) . ' registered: ' . $row['full_name'],
                'subtitle' => 'Added to the roster.',
                'time' => $row['created_at'],
            ];
        }
    } catch (\PDOException $e) {
    }

    try {
        $stmt = $pdo->query("
            SELECT tr.triage_level, tr.triaged_at, p.full_name
            FROM triage_records tr
            INNER JOIN patients p ON p.id = tr.patient_id
            ORDER BY tr.triaged_at DESC LIMIT 5
        ");
        foreach ($stmt->fetchAll() as $row) {
            $items[] = [
                'icon' => 'monitor_heart',
                'tone' => 'tertiary',
                'title' => 'Triage logged: ' . $row['full_name'],
                'subtitle' => ucfirst($row['triage_level']) . ' priority.',
                'time' => $row['triaged_at'],
            ];
        }
    } catch (\PDOException $e) {
    }

    usort($items, function ($a, $b) {
        return strtotime($b['time']) - strtotime($a['time']);
    });

    return array_slice($items, 0, $limit);
}

function dashboardTimeAgo(string $datetime): string
{
    $diff = time() - strtotime($datetime);
    if ($diff < 60) return 'just now';
    if ($diff < 3600) return floor($diff / 60) . 'm ago';
    if ($diff < 86400) return floor($diff / 3600) . 'h ago';
    if ($diff < 604800) return floor($diff / 86400) . 'd ago';
    return date('d M', strtotime($datetime));
}

function dashboardGreeting(): string
{
    $hour = (int) date('G');
    if ($hour < 12) return 'Morning';
    if ($hour < 18) return 'Afternoon';
    return 'Evening';
}
