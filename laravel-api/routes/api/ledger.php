<?php

declare(strict_types=1);

use App\Http\Controllers\Ledger\DashboardController;
use App\Http\Controllers\Ledger\EvidenceController;
use App\Http\Controllers\Ledger\LearningGoalController;
use App\Http\Controllers\Ledger\SearchController;
use App\Http\Controllers\Ledger\SkillController;
use App\Http\Controllers\Ledger\SkillLevelController;
use App\Http\Controllers\Ledger\TagController;
use Illuminate\Support\Facades\Route;

// Skill Ledger (RFC-002 §4.3). Loaded inside the authenticated admin group.
// Every route needs a name: it is the permission AdminMiddleware checks (ADR-0013).
foreach ([
    'skill' => SkillController::class,
    'tag' => TagController::class,
    'evidence' => EvidenceController::class,
    'learning-goal' => LearningGoalController::class,
] as $resource => $controller) {
    Route::get("{$resource}/list", [$controller, 'list'])->name("{$resource}.list");
    Route::post("{$resource}/store", [$controller, 'store'])->name("{$resource}.store");
    Route::put("{$resource}/update/{id}", [$controller, 'update'])->name("{$resource}.update");
    Route::post("{$resource}/delete", [$controller, 'delete'])->name("{$resource}.delete");
}

// Level history is append-only (REQ-002 US-1)
Route::get('skill-level/list', [SkillLevelController::class, 'list'])->name('skill-level.list');
Route::post('skill-level/store', [SkillLevelController::class, 'store'])->name('skill-level.store');

// One search box over skills, goals and evidence (REQ-002 US-5, ADR-0009)
Route::get('search', [SearchController::class, 'search'])->name('search.query');

// Real dashboard numbers (REQ-002 US-7)
Route::get('dashboard/summary', [DashboardController::class, 'summary'])->name('dashboard.summary');
