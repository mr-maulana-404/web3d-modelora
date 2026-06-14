<?php

use App\Http\Controllers\Admin\AdminController;
use App\Http\Controllers\Admin\ModelController as AdminModelController;
use App\Http\Controllers\Admin\TextureController as AdminTextureController;
use App\Http\Controllers\Admin\UserCustomController;
use App\Http\Controllers\Admin\UserEnhanceController;
use App\Http\Controllers\Admin\UserManagementController;
use App\Http\Controllers\Auth\AuthController; 
use App\Http\Controllers\System\CreditController;
use App\Http\Controllers\System\DownloadCustomizedController;
use App\Http\Controllers\System\TopUpController;
use App\Http\Controllers\System\GlbTextureEnhancementController;
use App\Http\Controllers\User\DashboardController as UserDashboardController;
use App\Http\Controllers\User\CustomizationController;
use App\Http\Controllers\User\GalleryController;
use App\Http\Controllers\User\ModelController as UserModelController;
use App\Http\Controllers\User\SavedModelController;
use App\Http\Controllers\User\TextureSuggestionController;
use App\Http\Controllers\User\TextureController as UserTextureController;
use Illuminate\Support\Facades\Route;

/* Buat Public dan halaman statis */
Route::get('/', [UserDashboardController::class, 'index'])->name('home');

Route::get('/about', function () {
    return view('app.about');
});
Route::get('/help', function () {
    return view('app.help');
});

/* Bagian login atau register */
Route::get('/login', [AuthController::class, 'showLoginForm'])->name('login');
Route::get('/register', [AuthController::class, 'showRegistrationForm'])->name('register');

// auth submit actions (di luar middleware('auth'))
Route::post('/login', [AuthController::class, 'loginCheck'])->name('logincheck');
Route::post('/register', [AuthController::class, 'registerCheck'])->name('registercheck');

/* Routes buat user yang sudah login */
Route::middleware(['auth', 'prevent-back'])->group(function () {

    Route::get('/dashboard', [AuthController::class, 'goDashboard'])->name('dashboard');
    Route::post('/logout', [AuthController::class, 'logout'])->name('logout');

    // gallery
    Route::get('/gallery', [GalleryController::class, 'index'])
        ->name('gallery');
    Route::get('/gallery/upload', [UserModelController::class,'create'])
        ->name('gallery.create');
    Route::post('/gallery/upload', [UserModelController::class,'store'])
        ->name('gallery.store');
    Route::get('/gallery/{model}/edit', [UserModelController::class,'edit'])
        ->name('gallery.edit');
    Route::put('/gallery/{model}', [UserModelController::class,'update'])
        ->name('gallery.update');
    Route::delete('/gallery/{model}', [UserModelController::class,'destroy'])
        ->name('gallery.delete');
    Route::post('/gallery/{model}/publish', [UserModelController::class,'toggle'])
        ->name('gallery.publish');
        
    // download original model
    Route::get('/gallery/download/{model3d:slug}', [GalleryController::class, 'download'])
        ->name('gallery.download');

    // customize page
    Route::get('/gallery/{model3d:slug}/customize', [CustomizationController::class, 'edit'])
        ->name('gallery.customize');

    // save customization
    Route::post('/customizations/save', [CustomizationController::class, 'store'])
        ->name('customizations.store');
    Route::get('/customizations/{customization}/download/obj', [DownloadCustomizedController::class, 'obj'])
        ->name('customizations.download.obj');
    
    // saved model
    Route::get('/gallery/saved-models', [SavedModelController::class, 'index'])
    ->name('gallery.saved');
    Route::get('/gallery/saved-model/{customization}', [SavedModelController::class, 'edit'])
    ->name('gallery.saved.edit');
    Route::delete('/gallery/saved-model/{customization}', [SavedModelController::class, 'destroy'])
    ->name('gallery.saved.delete');

    // user textures CRUD
    Route::get('/user-textures', [UserTextureController::class, 'index']);
    Route::post('/user-textures', [UserTextureController::class, 'store']);
    Route::delete('/user-textures/{texture}', [UserTextureController::class, 'destroy']);
    Route::post('/texture-suggestions', [TextureSuggestionController::class, 'suggest'])
        ->name('texture-suggestions.suggest');
    Route::get('/credits', [CreditController::class, 'show'])
        ->name('credits.show');
    
    // top up
    Route::get('/gallery/top-up', [TopUpController::class, 'index'])
        ->name('gallery.topup');
    Route::post('/topup/transaction', [TopUpController::class, 'createTransaction'])
        ->name('topup.transaction');
    Route::post('/topup/success', [TopUpController::class, 'handleSuccess']);

    // model enhancement
    Route::redirect('/gallery/enchancement', '/gallery/enhancement');
    Route::get('/gallery/enhancement', [GlbTextureEnhancementController::class, 'index'])
        ->name('gallery.enhancement.index');
    Route::post('/gallery/enhancement', [GlbTextureEnhancementController::class, 'store'])
        ->name('gallery.enhancement.store');
    Route::delete('/gallery/enhancement/{project}', [GlbTextureEnhancementController::class, 'destroy'])
        ->name('gallery.enhancement.delete');
    Route::get('/gallery/enhancement/{project}/poll', [GlbTextureEnhancementController::class, 'poll'])
        ->name('gallery.enhancement.poll');
});


/* Rute Admin - Admin sudah datang */
Route::middleware(['auth', 'admin', 'prevent-back'])
    ->prefix('admin')
    ->name('admin.')
    ->group(function () {
        Route::get('/dashboard', [AdminController::class, 'index'])->name('dashboard');
        /*Model 3d*/
        Route::resource('models', AdminModelController::class);
        Route::post('/models/{model}/toggle', [AdminModelController::class, 'toggle'])
            ->name('models.toggle');
        /*Texture*/
        Route::resource('textures', AdminTextureController::class);
        Route::get('/texture/user', [AdminTextureController::class, 'usertextureindex'])->name('user.textures');
        Route::delete('/texture/user/{texture}', [AdminTextureController::class, 'deleteusertexture'])->name('user.textures.delete');
        /*UserCustom*/
        Route::get('/user-customs', [UserCustomController::class, 'index'])->name('usercustom.index');
        Route::delete('/user-customs/{id}', [UserCustomController::class, 'destroy'])->name('usercustom.destroy');
        /*UserEnhance*/
        Route::get('/user-enhancements', [UserEnhanceController::class, 'index'])->name('userenhance.index');
        Route::delete('/user-enhancements/{id}', [UserEnhanceController::class, 'destroy'])->name('userenhance.destroy');
        /*User Management*/
        Route::get('/users', [UserManagementController::class, 'index'])->name('users.index');
        Route::get('/users/create', [UserManagementController::class, 'create'])->name('users.create');
        Route::post('/users', [UserManagementController::class, 'store'])->name('users.store');
        Route::delete('/users/{id}', [UserManagementController::class, 'destroy'])->name('users.destroy');
});

/* Rute global User-Admin urusan model*/
Route::middleware(['auth'])->group(function () {

    Route::post('/models/{model}/generate-parts', [AdminModelController::class, 'generateParts']);
    Route::post('/models/{model}/save-thumbnail', [AdminModelController::class, 'saveThumbnail']);
    Route::post('/models/{model}/mark-ready', [AdminModelController::class, 'markReady']);

});
