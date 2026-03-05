<?php
/**
 * 팀 목록 API
 * GET /api/teams/list.php
 */

require_once __DIR__ . '/../../includes/config.php';
require_once __DIR__ . '/../../includes/Database.php';
require_once __DIR__ . '/../../includes/Auth.php';

setCorsHeaders();
header('Content-Type: application/json; charset=utf-8');

if ($_SERVER['REQUEST_METHOD'] !== 'GET') {
    errorResponse('Method not allowed', 405);
}

$db = new Database();

$teams = $db->fetchAll("SELECT * FROM teams ORDER BY sort_order ASC, name ASC");

successResponse([
    'teams' => $teams,
    'total' => count($teams)
]);
