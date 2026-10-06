<?php

use App\Http\Controllers\Custom\CredentialController;
use App\Http\Controllers\DocsController;
use App\Http\Controllers\History\Management\BannerMgmtHistController;
use App\Http\Controllers\History\Management\CategoryMgmtHistController;
use App\Http\Controllers\History\Management\EntryDescriptionMgmtHistController;
use App\Http\Controllers\History\Management\EntryMgmtHistController;
use App\Http\Controllers\History\Management\SettingLinkMgmtHistController;
use App\Http\Controllers\History\Management\SliderMgmtHistController;
use App\Http\Controllers\History\Management\SocialMgmtHistController;
use App\Http\Controllers\History\Management\UserMgmtHistController;
use App\Http\Controllers\History\Master\AdminMstHistController;
use App\Http\Controllers\History\Master\ApiMstHistController;
use App\Http\Controllers\History\Master\DepartmentMstHistController;
use App\Http\Controllers\History\Master\FeatureMstHistController;
use App\Http\Controllers\History\Master\PolicyDepartmentMstHistController;
use App\Http\Controllers\History\Master\RoleMstHistController;
use App\Http\Controllers\Management\BannerMgmtController;
use App\Http\Controllers\Management\CategoryMgmtController;
use App\Http\Controllers\Management\EntryDescriptionMgmtController;
use App\Http\Controllers\Management\EntryMgmtController;
use App\Http\Controllers\Management\MediaMgmtController;
use App\Http\Controllers\Management\SettingLinkMgmtController;
use App\Http\Controllers\Management\SliderMgmtController;
use App\Http\Controllers\Management\SocialMgmtController;
use App\Http\Controllers\Management\UserMgmtController;
use App\Http\Controllers\Master\AdminDepartmentMstController;
use App\Http\Controllers\Master\AdminMstController;
use App\Http\Controllers\Master\AdminRoleMstController;
use App\Http\Controllers\Master\ApiMstController;
use App\Http\Controllers\Master\ApiRoleMstController;
use App\Http\Controllers\Master\DepartmentManagementMstController;
use App\Http\Controllers\Master\DepartmentMstController;
use App\Http\Controllers\Master\FeatureMstController;
use App\Http\Controllers\Master\PolicyDepartmentMstController;
use App\Http\Controllers\Master\RoleMstController;
use App\Http\Controllers\Master\TokenMstController;
use App\Http\Middleware\AdminMiddleware;
use App\Http\Middleware\BroadcastingAuthMiddleware;
use App\Http\Middleware\GenerateResponseMiddleware;
use App\Http\Middleware\TransactionMiddleware;
use Illuminate\Support\Facades\Broadcast;
use Illuminate\Support\Facades\Route;

Broadcast::routes(['middleware' => ['api', BroadcastingAuthMiddleware::class], 'prefix' => 'admin']);

// ============================================================
// PUBLIC DOCS API (No authentication required)
// ============================================================
Route::prefix('docs')
    ->middleware([GenerateResponseMiddleware::class])
    ->group(function () {
        Route::get('categories', [DocsController::class, 'getCategories']);
        Route::get('categories/{slug}/entries', [DocsController::class, 'getEntriesByCategory']);
        Route::get('entries/{slug}', [DocsController::class, 'getEntryDetail']);
        Route::get('search', [DocsController::class, 'search']);
    });

Route::prefix('admin')
    ->middleware([GenerateResponseMiddleware::class, TransactionMiddleware::class])
    ->group(function () {
        Route::prefix('credential')->group(function () {
            Route::post('login', [CredentialController::class, 'login']);
            Route::prefix('trust')->group(function () {
                Route::post('refresh-token', [CredentialController::class, 'refreshToken']);
            });
        });

        Route::middleware(AdminMiddleware::class)
            ->group(function () {
                Route::prefix('credential')->group(function () {
                    Route::prefix('trust')->group(function () {
                        Route::post('logout', [CredentialController::class, 'logout']);
                    });
                    Route::get('me', [CredentialController::class, 'me']);
                });

                // ============================================================
                // MASTER DATA ROUTES
                // ============================================================

                // Admin Master
                Route::get('admin-mst/list', [AdminMstController::class, 'list']);
                Route::post('admin-mst/store', [AdminMstController::class, 'store']);
                Route::put('admin-mst/update/{id}', [AdminMstController::class, 'update']);
                Route::post('admin-mst/delete', [AdminMstController::class, 'delete']);

                // Role Master
                Route::get('role-mst/list', [RoleMstController::class, 'list']);
                Route::post('role-mst/store', [RoleMstController::class, 'store']);
                Route::put('role-mst/update/{id}', [RoleMstController::class, 'update']);
                Route::post('role-mst/delete', [RoleMstController::class, 'delete']);

                // Department Master
                Route::get('department-mst/list', [DepartmentMstController::class, 'list']);
                Route::post('department-mst/store', [DepartmentMstController::class, 'store']);
                Route::put('department-mst/update/{id}', [DepartmentMstController::class, 'update']);
                Route::post('department-mst/delete', [DepartmentMstController::class, 'delete']);

                // Feature Master
                Route::get('feature-mst/list', [FeatureMstController::class, 'list']);
                Route::post('feature-mst/store', [FeatureMstController::class, 'store']);
                Route::put('feature-mst/update/{id}', [FeatureMstController::class, 'update']);
                Route::post('feature-mst/delete', [FeatureMstController::class, 'delete']);

                // API Master
                Route::get('api-mst/list', [ApiMstController::class, 'list']);
                Route::post('api-mst/store', [ApiMstController::class, 'store']);
                Route::put('api-mst/update/{id}', [ApiMstController::class, 'update']);
                Route::post('api-mst/delete', [ApiMstController::class, 'delete']);

                // Token Master
                Route::get('token-mst/list', [TokenMstController::class, 'list']);
                Route::post('token-mst/store', [TokenMstController::class, 'store']);
                Route::put('token-mst/update/{id}', [TokenMstController::class, 'update']);
                Route::post('token-mst/delete', [TokenMstController::class, 'delete']);

                // Policy Department Master
                Route::get('policy-department-mst/list', [PolicyDepartmentMstController::class, 'list']);
                Route::post('policy-department-mst/store', [PolicyDepartmentMstController::class, 'store']);
                Route::put('policy-department-mst/update/{id}', [PolicyDepartmentMstController::class, 'update']);
                Route::post('policy-department-mst/delete', [PolicyDepartmentMstController::class, 'delete']);

                // ============================================================
                // JUNCTION TABLES (Many-to-Many Relationships)
                // ============================================================

                // Admin-Department Junction
                Route::get('admin-department-mst/list', [AdminDepartmentMstController::class, 'list']);
                Route::put('admin-department-mst/update', [AdminDepartmentMstController::class, 'update']);

                // Admin-Role Junction
                Route::get('admin-role-mst/list', [AdminRoleMstController::class, 'list']);
                Route::put('admin-role-mst/update', [AdminRoleMstController::class, 'update']);

                // API-Role Junction
                Route::get('api-role-mst/list', [ApiRoleMstController::class, 'list']);
                Route::put('api-role-mst/update', [ApiRoleMstController::class, 'update']);

                // Department-Management Junction
                Route::get('department-management-mst/list', [DepartmentManagementMstController::class, 'list']);
                Route::put('department-management-mst/update', [DepartmentManagementMstController::class, 'update']);

                // ============================================================
                // MANAGEMENT DATA ROUTES
                // ============================================================

                // Banner Management
                Route::get('banner-mgmt/list', [BannerMgmtController::class, 'list']);
                Route::post('banner-mgmt/store', [BannerMgmtController::class, 'store']);
                Route::put('banner-mgmt/update/{id}', [BannerMgmtController::class, 'update']);
                Route::post('banner-mgmt/delete', [BannerMgmtController::class, 'delete']);

                // Category Management
                Route::get('category-mgmt/list', [CategoryMgmtController::class, 'list']);
                Route::post('category-mgmt/store', [CategoryMgmtController::class, 'store']);
                Route::put('category-mgmt/update/{id}', [CategoryMgmtController::class, 'update']);
                Route::post('category-mgmt/delete', [CategoryMgmtController::class, 'delete']);

                // Entry Management
                Route::get('entry-mgmt/list', [EntryMgmtController::class, 'list']);
                Route::post('entry-mgmt/store', [EntryMgmtController::class, 'store']);
                Route::put('entry-mgmt/update/{id}', [EntryMgmtController::class, 'update']);
                Route::post('entry-mgmt/delete', [EntryMgmtController::class, 'delete']);

                // Entry Description Management
                Route::get('entry-description-mgmt/list', [EntryDescriptionMgmtController::class, 'list']);
                Route::post('entry-description-mgmt/store', [EntryDescriptionMgmtController::class, 'store']);
                Route::put('entry-description-mgmt/update/{id}', [EntryDescriptionMgmtController::class, 'update']);
                Route::post('entry-description-mgmt/delete', [EntryDescriptionMgmtController::class, 'delete']);

                // Slider Management
                Route::get('slider-mgmt/list', [SliderMgmtController::class, 'list']);
                Route::post('slider-mgmt/store', [SliderMgmtController::class, 'store']);
                Route::put('slider-mgmt/update/{id}', [SliderMgmtController::class, 'update']);
                Route::post('slider-mgmt/delete', [SliderMgmtController::class, 'delete']);

                // Social Management
                Route::get('social-mgmt/list', [SocialMgmtController::class, 'list']);
                Route::post('social-mgmt/store', [SocialMgmtController::class, 'store']);
                Route::put('social-mgmt/update/{id}', [SocialMgmtController::class, 'update']);
                Route::post('social-mgmt/delete', [SocialMgmtController::class, 'delete']);

                // User Management
                Route::get('user-mgmt/list', [UserMgmtController::class, 'list']);
                Route::post('user-mgmt/store', [UserMgmtController::class, 'store']);
                Route::put('user-mgmt/update/{id}', [UserMgmtController::class, 'update']);
                Route::post('user-mgmt/delete', [UserMgmtController::class, 'delete']);

                // Setting Link Management
                Route::get('setting-link-mgmt/list', [SettingLinkMgmtController::class, 'list']);
                Route::post('setting-link-mgmt/store', [SettingLinkMgmtController::class, 'store']);
                Route::put('setting-link-mgmt/update/{id}', [SettingLinkMgmtController::class, 'update']);
                Route::post('setting-link-mgmt/delete', [SettingLinkMgmtController::class, 'delete']);

                // Media Management (MinIO-based File Manager)
                Route::get('media-mgmt/list', [MediaMgmtController::class, 'list']);
                Route::post('media-mgmt/prepare-upload', [MediaMgmtController::class, 'prepareUpload']);

                // Multipart Upload Routes
                Route::post('media-mgmt/init-multipart-upload', [MediaMgmtController::class, 'initMultipartUpload']);
                Route::post('media-mgmt/get-multipart-url', [MediaMgmtController::class, 'getMultipartUrl']);
                Route::post('media-mgmt/complete-multipart-upload', [MediaMgmtController::class, 'completeMultipartUpload']);

                Route::post('media-mgmt/store', [MediaMgmtController::class, 'store']);
                Route::put('media-mgmt/update/{id}', [MediaMgmtController::class, 'update']);
                Route::delete('media-mgmt/delete/{id}', [MediaMgmtController::class, 'delete']);

                // ============================================================
                // HISTORY ROUTES (Audit Trail)
                // ============================================================

                // Admin Master History
                Route::get('admin-mst-hist/list', [AdminMstHistController::class, 'list']);
                Route::post('admin-mst-hist/store', [AdminMstHistController::class, 'store']);
                Route::put('admin-mst-hist/update/{id}', [AdminMstHistController::class, 'update']);
                Route::post('admin-mst-hist/delete', [AdminMstHistController::class, 'delete']);

                // API Master History
                Route::get('api-mst-hist/list', [ApiMstHistController::class, 'list']);
                Route::post('api-mst-hist/store', [ApiMstHistController::class, 'store']);
                Route::put('api-mst-hist/update/{id}', [ApiMstHistController::class, 'update']);
                Route::post('api-mst-hist/delete', [ApiMstHistController::class, 'delete']);

                // Department Master History
                Route::get('department-mst-hist/list', [DepartmentMstHistController::class, 'list']);
                Route::post('department-mst-hist/store', [DepartmentMstHistController::class, 'store']);
                Route::put('department-mst-hist/update/{id}', [DepartmentMstHistController::class, 'update']);
                Route::post('department-mst-hist/delete', [DepartmentMstHistController::class, 'delete']);

                // Feature Master History
                Route::get('feature-mst-hist/list', [FeatureMstHistController::class, 'list']);
                Route::post('feature-mst-hist/store', [FeatureMstHistController::class, 'store']);
                Route::put('feature-mst-hist/update/{id}', [FeatureMstHistController::class, 'update']);
                Route::post('feature-mst-hist/delete', [FeatureMstHistController::class, 'delete']);

                // Policy Department Master History
                Route::get('policy-department-mst-hist/list', [PolicyDepartmentMstHistController::class, 'list']);
                Route::post('policy-department-mst-hist/store', [PolicyDepartmentMstHistController::class, 'store']);
                Route::put('policy-department-mst-hist/update/{id}', [PolicyDepartmentMstHistController::class, 'update']);
                Route::post('policy-department-mst-hist/delete', [PolicyDepartmentMstHistController::class, 'delete']);

                // Role Master History
                Route::get('role-mst-hist/list', [RoleMstHistController::class, 'list']);
                Route::post('role-mst-hist/store', [RoleMstHistController::class, 'store']);
                Route::put('role-mst-hist/update/{id}', [RoleMstHistController::class, 'update']);
                Route::post('role-mst-hist/delete', [RoleMstHistController::class, 'delete']);

                // Banner Management History
                Route::get('banner-mgmt-hist/list', [BannerMgmtHistController::class, 'list']);
                Route::post('banner-mgmt-hist/store', [BannerMgmtHistController::class, 'store']);
                Route::put('banner-mgmt-hist/update/{id}', [BannerMgmtHistController::class, 'update']);
                Route::post('banner-mgmt-hist/delete', [BannerMgmtHistController::class, 'delete']);

                // Category Management History
                Route::get('category-mgmt-hist/list', [CategoryMgmtHistController::class, 'list']);
                Route::post('category-mgmt-hist/store', [CategoryMgmtHistController::class, 'store']);
                Route::put('category-mgmt-hist/update/{id}', [CategoryMgmtHistController::class, 'update']);
                Route::post('category-mgmt-hist/delete', [CategoryMgmtHistController::class, 'delete']);

                // Setting Link Management History
                Route::get('setting-link-mgmt-hist/list', [SettingLinkMgmtHistController::class, 'list']);
                Route::post('setting-link-mgmt-hist/store', [SettingLinkMgmtHistController::class, 'store']);
                Route::put('setting-link-mgmt-hist/update/{id}', [SettingLinkMgmtHistController::class, 'update']);
                Route::post('setting-link-mgmt-hist/delete', [SettingLinkMgmtHistController::class, 'delete']);

                // Entry Description Management History
                Route::get('entry-description-mgmt-hist/list', [EntryDescriptionMgmtHistController::class, 'list']);
                Route::post('entry-description-mgmt-hist/store', [EntryDescriptionMgmtHistController::class, 'store']);
                Route::put('entry-description-mgmt-hist/update/{id}', [EntryDescriptionMgmtHistController::class, 'update']);
                Route::post('entry-description-mgmt-hist/delete', [EntryDescriptionMgmtHistController::class, 'delete']);

                // Entry Management History
                Route::get('entry-mgmt-hist/list', [EntryMgmtHistController::class, 'list']);
                Route::post('entry-mgmt-hist/store', [EntryMgmtHistController::class, 'store']);
                Route::put('entry-mgmt-hist/update/{id}', [EntryMgmtHistController::class, 'update']);
                Route::post('entry-mgmt-hist/delete', [EntryMgmtHistController::class, 'delete']);

                // Slider Management History
                Route::get('slider-mgmt-hist/list', [SliderMgmtHistController::class, 'list']);
                Route::post('slider-mgmt-hist/store', [SliderMgmtHistController::class, 'store']);
                Route::put('slider-mgmt-hist/update/{id}', [SliderMgmtHistController::class, 'update']);
                Route::post('slider-mgmt-hist/delete', [SliderMgmtHistController::class, 'delete']);

                // Social Management History
                Route::get('social-mgmt-hist/list', [SocialMgmtHistController::class, 'list']);
                Route::post('social-mgmt-hist/store', [SocialMgmtHistController::class, 'store']);
                Route::put('social-mgmt-hist/update/{id}', [SocialMgmtHistController::class, 'update']);
                Route::post('social-mgmt-hist/delete', [SocialMgmtHistController::class, 'delete']);

                // User Management History
                Route::get('user-mgmt-hist/list', [UserMgmtHistController::class, 'list']);
                Route::post('user-mgmt-hist/store', [UserMgmtHistController::class, 'store']);
                Route::put('user-mgmt-hist/update/{id}', [UserMgmtHistController::class, 'update']);
                Route::post('user-mgmt-hist/delete', [UserMgmtHistController::class, 'delete']);
            });
    });
