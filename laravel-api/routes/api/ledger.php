<?php

declare(strict_types=1);

use App\Http\Controllers\Ledger\EvidenceController;
use App\Http\Controllers\Ledger\LearningGoalController;
use App\Http\Controllers\Ledger\SearchController;
use App\Http\Controllers\Ledger\SkillController;
use App\Http\Controllers\Ledger\SkillLevelController;
use App\Http\Controllers\Ledger\TagController;
use Illuminate\Support\Facades\Route;

// Skill Ledger (RFC-002 §4.3). Loaded inside the authenticated admin group.
foreach ([
    'skill' => SkillController::class,
    'tag' => TagController::class,
    'evidence' => EvidenceController::class,
    'learning-goal' => LearningGoalController::class,
] as $resource => $controller) {
    Route::get("{$resource}/list", [$controller, 'list']);
    Route::post("{$resource}/store", [$controller, 'store']);
    Route::put("{$resource}/update/{id}", [$controller, 'update']);
    Route::post("{$resource}/delete", [$controller, 'delete']);
}

// Level history is append-only (REQ-002 US-1)
Route::get('skill-level/list', [SkillLevelController::class, 'list']);
Route::post('skill-level/store', [SkillLevelController::class, 'store']);

// One search box over skills, goals and evidence (REQ-002 US-5, ADR-0009)
Route::get('search', [SearchController::class, 'search']);
